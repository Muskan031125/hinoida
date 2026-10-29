<?php
namespace App\Controllers;

use App\Libraries\Mailer;

class Auth extends BaseController
{
    /** validate a candidate redirect (absolute same-site or relative) into a safe relative path */
    private function clean(string $r): string {
        if ($r === '') { return ''; }
        $base = rtrim(base_url(), '/');
        if (str_starts_with($r, $base)) { $r = substr($r, strlen($base)) ?: '/'; }
        if ($r !== '' && $r[0] === '/' && ! str_starts_with($r, '//')
            && ! preg_match('~^/(login|register|logout|signup)~', $r)) { return $r; }
        return '';
    }
    /** capture ?redirect= from the auth-page load and remember it for after login */
    private function captureRedirect(): string {
        $r = $this->clean((string) $this->request->getGet('redirect'));
        if ($r !== '') { session()->set('auth_redirect', $r); }
        return $r ?: (string) session()->get('auth_redirect');
    }
    /** consume the remembered redirect at login/signup completion */
    private function safeRedirect(): string {
        $r = $this->clean((string) ($this->request->getPost('redirect') ?? ''));
        if ($r === '') { $r = (string) session()->get('auth_redirect'); }
        session()->remove('auth_redirect');
        return $r;
    }
    public function login(){ return view('frontend/login',['title'=>'Login | HiNoida','mode'=>'login','biz'=>false,'err'=>session()->getFlashdata('err'),'ok'=>session()->getFlashdata('ok'),'redirect'=>$this->captureRedirect()]); }
    public function register(){ return view('frontend/login',['title'=>'Sign up | HiNoida','mode'=>'register','biz'=>false,'err'=>session()->getFlashdata('err'),'ok'=>session()->getFlashdata('ok'),'redirect'=>$this->captureRedirect()]); }
    public function registerBusiness(){ return view('frontend/login',['title'=>'Create your account | HiNoida','mode'=>'register','biz'=>true,'err'=>session()->getFlashdata('err'),'ok'=>session()->getFlashdata('ok'),'redirect'=>$this->captureRedirect()]); }

    public function attempt(){
        $db=\Config\Database::connect();
        $email=strtolower(trim((string)$this->request->getPost('email')));
        $pass=trim((string)$this->request->getPost('password'));
        $u=$db->table('user')->where('email',$email)->where('password',sha1($pass))->get()->getRowArray();
        if(!$u){ session()->setFlashdata('err','Invalid email or password.'); return redirect()->to('/login'); }
        session()->set(['uid'=>(int)$u['id'],'uname'=>$u['name'],'uemail'=>$u['email']??'','role'=>(int)($u['role_id']??3)]);
        // Email must be verified. New signups verify via OTP before the account exists, so this
        // only catches legacy accounts — send them to verify (with resend), not a dead-end.
        if((int)($u['is_verified']??1)===0){ return redirect()->to('/verify-email'); }
        $to=$this->safeRedirect(); if($to!==''){ return redirect()->to($to); }
        return redirect()->to((int)($u['role_id']??3)===1?'/admin':'/dashboard');
    }

    /** STEP 1 — validate the form, email an OTP, park the data in session. */
    public function create(){
        $db=\Config\Database::connect();
        $email=strtolower(trim((string)$this->request->getPost('email')));
        $name=trim((string)$this->request->getPost('name'));
        $pass=(string)$this->request->getPost('password');
        $biz=$this->request->getPost('type')==='business';
        $back=$biz?'/register/business':'/register';
        // keep what was typed so the form never silently resets
        $old=['name'=>$name,'email'=>$email,'phone'=>trim((string)$this->request->getPost('phone'))];
        $fail=function(string $msg) use ($back,$old){ session()->setFlashdata('err',$msg); session()->setFlashdata('old',$old); return redirect()->to($back); };
        if($name===''){ return $fail('Please enter your name.'); }
        if($email===''){ return $fail('Please enter your email address.'); }
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)){ return $fail('That email address doesn\'t look valid — please check it.'); }
        if(strlen($pass)<6){ return $fail('Your password must be at least 6 characters.'); }
        if($db->table('user')->where('email',$email)->countAllResults()>0){ return $fail('That email is already registered — try logging in instead.'); }

        $otp=(string)random_int(100000,999999);
        session()->set('signup',[
            'name'=>$name,'email'=>$email,'phone'=>trim((string)$this->request->getPost('phone')),
            'pass'=>sha1($pass),'biz'=>$biz,'redirect'=>$this->safeRedirect(),
            'otp'=>$otp,'exp'=>time()+7200,'tries'=>0,'sent_at'=>time(),
        ]);
        if(!$this->sendOtp($email,$name,$otp)){
            // nothing was delivered — do not park them on a screen waiting for
            // a code that is not coming
            session()->remove('signup');
            return $fail('We could not send your verification code just now. '
                .'Please try again in a few minutes, or contact us if it keeps happening.');
        }
        return redirect()->to('/signup/verify');
    }

    /**
     * Send the signup code, and say whether it actually went.
     *
     * processQueue() already reports how many failed; this used to throw that
     * away and send people to the "check your email" screen regardless. When
     * SMTP is misconfigured — an expired relay key returns 535 — they sit
     * waiting for a code that was never delivered, with nothing on screen to
     * suggest anything went wrong.
     */
    private function sendOtp(string $email,string $name,string $otp): bool {
        $m=new Mailer();
        $m->queue($email,$name,'Your HiNoida verification code: '.$otp,$this->otpEmailBody($name,$otp));
        [$sent,$logged,$failed]=$m->processQueue(1);
        if($failed>0){
            log_message('error','signup OTP to '.$email.' failed to send — check SMTP settings');
            return false;
        }

        return ($sent+$logged)>0;
    }

    /** STEP 2 — OTP entry screen. */
    public function otpForm(){
        $s=session()->get('signup');
        if(!$s){ return redirect()->to(hi_page('register')); }
        return view('frontend/signup_otp',['title'=>'Verify your email | HiNoida','email'=>$s['email'],'err'=>session()->getFlashdata('err'),'ok'=>session()->getFlashdata('ok')]);
    }

    /** STEP 2 submit — check OTP, create the account, log in, send welcome email. */
    public function otpConfirm(){
        $s=session()->get('signup');
        if(!$s){ return redirect()->to(hi_page('register')); }
        $entered=preg_replace('/\D/','',(string)$this->request->getPost('otp'));
        if(time()>$s['exp']){ session()->remove('signup'); session()->setFlashdata('err','Your code expired. Please sign up again.'); return redirect()->to(hi_page('register')); }
        if($s['tries']>=5){ session()->remove('signup'); session()->setFlashdata('err','Too many attempts. Please sign up again.'); return redirect()->to(hi_page('register')); }
        if($entered!==$s['otp']){ $s['tries']++; session()->set('signup',$s); session()->setFlashdata('err','Incorrect code. '.(5-$s['tries']).' attempts left.'); return redirect()->to('/signup/verify'); }

        // OTP correct → create the verified account
        $db=\Config\Database::connect();
        if($db->table('user')->where('email',$s['email'])->countAllResults()>0){ session()->remove('signup'); session()->setFlashdata('err','That email is already registered.'); return redirect()->to('/login'); }
        // the original schema never recorded when an account was created, so
        // signup trends could not be measured — add it and fill it from now on
        if(!$db->fieldExists('created_date','user')){
            try { $db->query('ALTER TABLE user ADD COLUMN created_date BIGINT DEFAULT NULL'); } catch(\Throwable $e){}
        }
        // hi_user_defaults() fills otp / updated_date, which the live schema
        // declares NOT NULL with no default — without them every signup failed
        // against the real database.
        $db->table('user')->insert(hi_row_for('user', hi_user_defaults($db) + [
            'name'=>$s['name'],'email'=>$s['email'],'password'=>$s['pass'],
            'role_id'=>3,'is_verified'=>1,'approved'=>1,
            'verification_code'=>'','wishlists'=>'[]','social'=>'{}','phone'=>$s['phone'],
            'created_date'=>time(),
        ], $db));
        $uid=(int)$db->insertID();

        // welcome email + notify admins for business
        $m=new Mailer();
        $m->queue($s['email'],$s['name'],'Welcome to HiNoida 🎉',$this->welcomeEmailBody($s['name'],$s['biz']));
        // One account type now, so there is no person left to approve — only
        // the listings they post, which admin still moderates.
        $m->processQueue(8);

        session()->set(['uid'=>$uid,'uname'=>$s['name'],'uemail'=>$s['email']??'','role'=>3,'signup_done'=>['biz'=>$s['biz'],'name'=>$s['name']]]);
        $redir=$s['redirect']??'';
        session()->remove('signup');
        session()->set('signup_redirect',$redir);
        return redirect()->to('/signup/done');
    }

    /** Resend OTP (rate-limited to once / 30s). */
    public function otpResend(){
        $s=session()->get('signup');
        if(!$s){ return redirect()->to(hi_page('register')); }
        if(time()-($s['sent_at']??0)<30){ session()->setFlashdata('err','Please wait a few seconds before resending.'); return redirect()->to('/signup/verify'); }
        $s['otp']=(string)random_int(100000,999999); $s['exp']=time()+7200; $s['sent_at']=time(); $s['tries']=0;
        session()->set('signup',$s);
        if($this->sendOtp($s['email'],$s['name'],$s['otp'])){
            session()->setFlashdata('ok','A new code is on its way to '.$s['email'].'.');
        }else{
            // do not claim it was sent when it was not
            session()->setFlashdata('err','We could not send the code just now. Please try again shortly.');
        }
        return redirect()->to('/signup/verify');
    }

    /** Confirmation screen with links. */
    public function done(){
        $d=session()->get('signup_done');
        if(!$d){ return redirect()->to('/dashboard'); }
        session()->remove('signup_done');
        return view('frontend/signup_done',['title'=>'You\'re all set | HiNoida','biz'=>!empty($d['biz']),'name'=>$d['name'],'redirect'=>session()->get('signup_redirect')]);
    }

    public function verify(){
        $db=\Config\Database::connect();
        $code=(string)$this->request->getGet('code');
        $u=$code?$db->table('user')->where('verification_code',$code)->get()->getRowArray():null;
        if(!$u){ session()->setFlashdata('err','That confirmation link is invalid or already used.'); return redirect()->to('/login'); }
        $db->table('user')->where('id',$u['id'])->update(['is_verified'=>1,'verification_code'=>'']);

        // normal user: log in
        session()->set(['uid'=>(int)$u['id'],'uname'=>$u['name'],'uemail'=>$u['email']??'','role'=>(int)($u['role_id']??3)]);
        session()->setFlashdata('ok','Email confirmed — welcome to HiNoida!');
        return redirect()->to('/dashboard');
    }

    /** Verify email for an already-logged-in user (auto-sends an OTP on first view). */
    public function emailVerifyForm(){
        $uid=(int)session()->get('uid'); if(!$uid){ return redirect()->to('/login'); }
        $db=\Config\Database::connect(); $u=$db->table('user')->where('id',$uid)->get()->getRowArray();
        if(!$u){ session()->destroy(); return redirect()->to('/login'); }
        if((int)($u['is_verified']??1)===1){ return redirect()->to('/dashboard'); }
        $ev=session()->get('email_verify');
        if(!$ev || ($ev['email']??'')!==$u['email'] || time()>($ev['exp']??0)){
            $otp=(string)random_int(100000,999999);
            session()->set('email_verify',['email'=>$u['email'],'otp'=>$otp,'exp'=>time()+7200,'tries'=>0,'sent_at'=>time()]);
            $this->sendOtp($u['email'],$u['name'],$otp);
        }
        return view('frontend/verify_email',['title'=>'Verify your email | HiNoida','email'=>$u['email'],'err'=>session()->getFlashdata('err'),'ok'=>session()->getFlashdata('ok')]);
    }
    public function emailVerifyConfirm(){
        $uid=(int)session()->get('uid'); if(!$uid){ return redirect()->to('/login'); }
        $ev=session()->get('email_verify'); if(!$ev){ return redirect()->to('/verify-email'); }
        $entered=preg_replace('/\D/','',(string)$this->request->getPost('otp'));
        if(time()>$ev['exp']){ session()->remove('email_verify'); session()->setFlashdata('err','Code expired — we sent a fresh one.'); return redirect()->to('/verify-email'); }
        if(($ev['tries']??0)>=5){ session()->remove('email_verify'); session()->setFlashdata('err','Too many attempts — we sent a fresh one.'); return redirect()->to('/verify-email'); }
        if($entered!==$ev['otp']){ $ev['tries']=($ev['tries']??0)+1; session()->set('email_verify',$ev); session()->setFlashdata('err','Incorrect code. '.(5-$ev['tries']).' attempts left.'); return redirect()->to('/verify-email'); }
        \Config\Database::connect()->table('user')->where('id',$uid)->update(['is_verified'=>1,'verification_code'=>'']);
        session()->remove('email_verify');
        session()->setFlashdata('flash','Your email is verified — thank you! 🎉');
        return redirect()->to('/dashboard');
    }
    public function emailVerifyResend(){
        $uid=(int)session()->get('uid'); if(!$uid){ return redirect()->to('/login'); }
        $ev=session()->get('email_verify');
        if($ev && time()-($ev['sent_at']??0)<30){ session()->setFlashdata('err','Please wait a few seconds before resending.'); return redirect()->to('/verify-email'); }
        $db=\Config\Database::connect(); $u=$db->table('user')->where('id',$uid)->get()->getRowArray();
        $otp=(string)random_int(100000,999999);
        session()->set('email_verify',['email'=>$u['email'],'otp'=>$otp,'exp'=>time()+7200,'tries'=>0,'sent_at'=>time()]);
        $this->sendOtp($u["email"],$u["name"],$otp);
        session()->setFlashdata('ok','A new code is on its way to '.$u['email'].'.');
        return redirect()->to('/verify-email');
    }

    public function logout(){ session()->destroy(); return redirect()->to('/'); }

    private function wrap(string $inner): string {
        return '<div style="font-family:Inter,Arial,sans-serif;max-width:540px;margin:auto">
            <div style="background:linear-gradient(120deg,#0a2a6b,#123a8f);color:#fff;padding:22px 26px;border-radius:14px 14px 0 0"><h2 style="margin:0;color:#fff">HiNoida</h2></div>
            <div style="border:1px solid #e7ebf2;border-top:0;padding:26px;border-radius:0 0 14px 14px;color:#33405a;line-height:1.6">'.$inner.'</div></div>';
    }

    protected function otpEmailBody(string $name,string $otp): string {
        return $this->wrap('<p>Hi '.esc($name).' 👋</p><p>Use this code to verify your email and complete your HiNoida signup:</p>
            <p style="text-align:center;margin:22px 0"><span style="font-size:34px;font-weight:800;letter-spacing:10px;color:#014dda;background:#eef3fc;padding:14px 22px;border-radius:12px;display:inline-block">'.esc($otp).'</span></p>
            <p style="color:#7c8aa3;font-size:13px">This code stays valid for 2 hours. If you didn\'t request it, you can ignore this email.</p>');
    }

    protected function welcomeEmailBody(string $name,bool $biz): string {
        $feat='<ul style="padding-left:18px;margin:10px 0">
            <li><b>Discover Noida</b> — browse 700+ categories: restaurants, hospitals, salons, real estate & more.</li>
            <li><b>Book & enquire</b> — contact any business or request a booking in one tap.</li>
            <li><b>Reviews & ratings</b> — read genuine reviews and leave your own.</li>
            <li><b>Save places</b> — tap the heart to build your wishlist.</li>
            <li><b>Your dashboard</b> — track bookings, enquiries, reviews and saved places.</li></ul>';
        if($biz){ $feat='<ul style="padding-left:18px;margin:10px 0">
            <li><b>List your business</b> — add photos, hours, amenities and contact details.</li>
            <li><b>Get leads</b> — receive enquiries and booking requests from customers.</li>
            <li><b>Manage everything</b> — your business dashboard shows listings, leads and reviews.</li>
            <li><b>Grow</b> — collect reviews and get featured across Noida.</li></ul>
            <p style="background:#fff8e6;border:1px solid #f0dca3;border-radius:10px;padding:12px 14px;color:#8a6d1a"><b>Next step:</b> our team will review and approve your business — you\'ll get an email once it\'s live.</p>'; }
        return $this->wrap('<p>Welcome aboard, '.esc($name).'! 🎉 Your HiNoida account is confirmed and ready.</p>
            <p>Here\'s what you can do:</p>'.$feat.'
            <p style="text-align:center;margin:24px 0"><a href="'.base_url('dashboard').'" style="background:#014dda;color:#fff;text-decoration:none;padding:13px 26px;border-radius:10px;font-weight:700;display:inline-block">Go to my dashboard</a></p>
            <p style="color:#7c8aa3;font-size:13px">Need help? Just reply to this email. Happy exploring Noida!</p>');
    }

    protected function verifyEmailBody(string $name,string $link,bool $biz): string {
        $intro=$biz?'Thanks for listing your business on HiNoida.':'Thanks for signing up to HiNoida.';
        return '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;margin:auto">
            <div style="background:linear-gradient(120deg,#0a2a6b,#123a8f);color:#fff;padding:22px 26px;border-radius:14px 14px 0 0">
              <h2 style="margin:0;color:#fff">Hi '.esc($name).' 👋</h2></div>
            <div style="border:1px solid #e7ebf2;border-top:0;padding:26px;border-radius:0 0 14px 14px">
              <p>'.$intro.' Please confirm your email address to activate your account.</p>
              <p style="text-align:center;margin:26px 0">
                <a href="'.$link.'" style="background:#014dda;color:#fff;text-decoration:none;padding:13px 26px;border-radius:10px;font-weight:700;display:inline-block">Confirm my email</a></p>
              <p style="color:#7c8aa3;font-size:13px">Or paste this link into your browser:<br><a href="'.$link.'">'.$link.'</a></p>
              '.($biz?'<p style="color:#7c8aa3;font-size:13px">After confirming, our team will review and approve your business listing.</p>':'').'
            </div></div>';
    }

    /* ===================================================================
       Password reset — the original site had this and the rewrite did not,
       so a locked-out user had no way back in.
       Flow: request -> emailed 6-digit code -> set a new password.
       =================================================================== */

    /** Step 1 — the "forgot password" form. */
    public function forgotForm()
    {
        return view('frontend/forgot', [
            'title'  => 'Reset your password | HiNoida',
            'robots' => 'noindex,nofollow',
            'err'    => session()->getFlashdata('err'),
            'flash'  => session()->getFlashdata('flash'),
        ]);
    }

    /** Step 2 — generate a code and email it. */
    public function forgotSend()
    {
        $db    = \Config\Database::connect();
        $email = strtolower(trim((string) $this->request->getPost('email')));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            session()->setFlashdata('err', 'Please enter a valid email address.');
            return redirect()->to(hi_page('forgot'));
        }

        // Throttle: one code per minute per address.
        $last = (int) session()->get('reset_sent_at');
        if ($last && (time() - $last) < 60) {
            session()->setFlashdata('err', 'Please wait a minute before requesting another code.');
            return redirect()->to(hi_page('forgot'));
        }

        $u = $db->table('user')->where('email', $email)->get()->getRowArray();

        // Always report success — never reveal whether an address is registered.
        if ($u) {
            $code = (string) random_int(100000, 999999);
            $db->table('user')->where('id', (int) $u['id'])->update(['verification_code' => $code]);
            session()->set([
                'reset_email'   => $email,
                'reset_exp'     => time() + 3600,
                'reset_tries'   => 0,
                'reset_sent_at' => time(),
            ]);

            $name = $u['name'] ?: 'there';
            (new \App\Libraries\Mailer())->queue($email, $name, 'Your HiNoida password reset code',
                '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;margin:0 auto;color:#33405a">'
                . '<h2 style="color:#101a2c">Reset your password</h2>'
                . '<p>Hi ' . esc($name) . ', use this code to set a new password:</p>'
                . '<p style="font-size:30px;font-weight:800;letter-spacing:7px;color:#014dda;'
                . 'background:#eef3fc;padding:16px 20px;border-radius:12px;text-align:center">' . $code . '</p>'
                . '<p style="font-size:14px">The code expires in <b>1 hour</b>.</p>'
                . '<p style="font-size:13px;color:#7c8aa3">If you did not ask for this, ignore this email — '
                . 'your password has not changed.</p></div>');
        } else {
            session()->set(['reset_email' => $email, 'reset_exp' => time() + 3600, 'reset_tries' => 0, 'reset_sent_at' => time()]);
        }

        session()->setFlashdata('flash', 'If that address has an account, a reset code is on its way.');
        return redirect()->to('/reset');
    }

    /** Step 3 — enter the code and the new password. */
    public function resetForm()
    {
        if (! session()->get('reset_email')) { return redirect()->to(hi_page('forgot')); }
        return view('frontend/reset', [
            'title'  => 'Set a new password | HiNoida',
            'robots' => 'noindex,nofollow',
            'email'  => (string) session()->get('reset_email'),
            'err'    => session()->getFlashdata('err'),
            'flash'  => session()->getFlashdata('flash'),
        ]);
    }

    /** Step 4 — verify the code and store the new password. */
    public function resetConfirm()
    {
        $db    = \Config\Database::connect();
        $email = (string) session()->get('reset_email');
        $exp   = (int) session()->get('reset_exp');

        if ($email === '') { return redirect()->to(hi_page('forgot')); }
        if ($exp < time()) {
            session()->remove(['reset_email', 'reset_exp', 'reset_tries']);
            session()->setFlashdata('err', 'That code has expired — please request a new one.');
            return redirect()->to(hi_page('forgot'));
        }

        $tries = (int) session()->get('reset_tries');
        if ($tries >= 5) {
            session()->remove(['reset_email', 'reset_exp', 'reset_tries']);
            session()->setFlashdata('err', 'Too many incorrect attempts. Please start again.');
            return redirect()->to(hi_page('forgot'));
        }

        $code = preg_replace('/\D/', '', (string) $this->request->getPost('code'));
        $pass = (string) $this->request->getPost('password');
        $conf = (string) $this->request->getPost('password_confirm');

        if (strlen($pass) < 6) {
            session()->setFlashdata('err', 'Your new password must be at least 6 characters.');
            return redirect()->to('/reset');
        }
        if ($pass !== $conf) {
            session()->setFlashdata('err', 'The two passwords do not match.');
            return redirect()->to('/reset');
        }

        $u = $db->table('user')->where('email', $email)->get()->getRowArray();
        if (! $u || $code === '' || (string) ($u['verification_code'] ?? '') !== $code) {
            session()->set('reset_tries', $tries + 1);
            session()->setFlashdata('err', 'Incorrect code. ' . (4 - $tries) . ' attempts left.');
            return redirect()->to('/reset');
        }

        $db->table('user')->where('id', (int) $u['id'])->update([
            'password'          => sha1($pass),
            'verification_code' => '',
            'is_verified'       => 1,      // proving inbox access also verifies the address
            'updated_date'      => time(),   // bigint(20) column in production
        ]);
        session()->remove(['reset_email', 'reset_exp', 'reset_tries', 'reset_sent_at']);
        session()->setFlashdata('flash', 'Password changed — you can sign in now.');
        return redirect()->to('/login');
    }
}
