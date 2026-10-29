<?php
namespace App\Controllers;
class Pages extends BaseController
{
    public function about(){ return view('frontend/simple',['title'=>'About HiNoida — Noida\'s Local Business Directory','metaDescription'=>'About HiNoida — Noida\'s most complete local business directory covering restaurants, hospitals, shopping, salons, real estate and more.','canonical'=>hi_page('about'),'heading'=>'About HiNoida','body'=>hi_fe('fe_about', 'about_us', true) ?: 'HiNoida is Noida\'s most complete local business directory — restaurants, hospitals, shopping, salons, real estate and more, curated in one place.']); }
    /** Contact page — the original stored submissions in `discover_contact`;
     *  the rewrite only printed an email address, so messages were lost. */
    public function contact()
    {
        helper('setting');
        return view('frontend/contact', [
            'title'           => 'Contact HiNoida',
            'metaDescription' => 'Contact the HiNoida team to list or update your Noida business.',
            'canonical'       => base_url('contact'),
            'email'           => hi_setting('system_email', 'hello@new.hinoida.com'),
            'phone'           => hi_setting('phone'),
            'address'         => hi_setting('address'),
            'old'             => session()->getFlashdata('old') ?? [],
            'flash'           => session()->getFlashdata('flash'),
            'err'             => session()->getFlashdata('err'),
        ]);
    }

    /** Store the message and notify the site owner. */
    public function contactSend()
    {
        helper('setting');
        $req  = $this->request;
        $name = trim((string) $req->getPost('name'));
        $mail = trim((string) $req->getPost('email'));
        $tel  = trim((string) $req->getPost('phone'));
        $msg  = trim((string) $req->getPost('message'));

        $keep = ['name' => $name, 'email' => $mail, 'message' => $msg];
        $fail = static function (string $why) use ($keep) {
            session()->setFlashdata('err', $why);
            session()->setFlashdata('old', $keep);
            return redirect()->to('/contact');
        };

        if ($name === '')                                    { return $fail('Please enter your name.'); }
        if ($mail === '' || ! filter_var($mail, FILTER_VALIDATE_EMAIL)) { return $fail('Please enter a valid email address.'); }
        if (mb_strlen($msg) < 10)                            { return $fail('Please write a slightly longer message (at least 10 characters).'); }
        // Phone is required, and the number itself has to be a real one. The
        // field submits "+91 9876543210", so the dial code is stripped before
        // counting — an Indian mobile is ten digits starting 6-9.
        if ($tel === '')                                     { return $fail('Please enter your phone number.'); }
        $natl = preg_replace('/\D/', '', preg_replace('~^\s*\+\d{1,4}~', '', $tel));
        if (str_starts_with(preg_replace('/\s/', '', $tel), '+91') || ! str_starts_with(trim($tel), '+')) {
            if (! preg_match('/^[6-9][0-9]{9}$/', $natl)) { return $fail('Please enter a valid 10-digit mobile number.'); }
        } elseif (strlen($natl) < 7 || strlen($natl) > 12) {
            return $fail('That phone number does not look right.');
        }
        // simple honeypot — bots fill hidden fields
        if (trim((string) $req->getPost('website')) !== '')  { return redirect()->to('/contact'); }

        $db = \Config\Database::connect();
        if (! $db->tableExists('discover_contact')) {
            $db->query('CREATE TABLE discover_contact (id INTEGER PRIMARY KEY '
                . ($db->DBDriver === 'MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT')
                . ', name VARCHAR(120), email VARCHAR(160), phone VARCHAR(40), message TEXT, created_at DATETIME)');
        }
        $db->table('discover_contact')->insert([
            'name' => $name, 'email' => $mail, 'phone' => $tel,
            'message' => $msg, 'created_at' => date('Y-m-d H:i:s'),
        ]);

        $to = hi_setting('system_email', hi_setting('mail_from', ''));
        if ($to !== '') {
            (new \App\Libraries\Mailer())->queue($to, 'HiNoida', 'New contact message from ' . $name,
                '<div style="font-family:Inter,Arial,sans-serif;color:#33405a">'
                . '<h3 style="color:#101a2c">New message via the contact form</h3>'
                . '<p><b>Name:</b> ' . esc($name) . '<br><b>Email:</b> ' . esc($mail)
                . ($tel !== '' ? '<br><b>Phone:</b> ' . esc($tel) : '') . '</p>'
                . '<p style="padding:12px 15px;background:#f4f7fc;border-radius:10px">' . nl2br(esc($msg)) . '</p></div>');
        }

        session()->setFlashdata('flash', 'Thanks — your message has been sent. We\'ll reply by email.');
        return redirect()->to('/contact');
    }
    public function pricing(){ return view('frontend/simple',['title'=>'Pricing — List Your Business | HiNoida','metaDescription'=>'List your business on HiNoida free. Upgrade for a verified badge, featured placement and more.','canonical'=>hi_page('pricing'),'heading'=>'Simple, honest pricing','body'=>'List your business free. Upgrade for a verified badge, featured placement and more.']); }

    public function faq(){
        helper('seo');
        $faqs=[
            ['How do I list my business on HiNoida?','Click "List your business", create a free account, verify your email and complete the short listing wizard — name, category, photos, contact and hours. Your listing goes live after a quick review.'],
            ['Is listing on HiNoida free?','Yes. A standard listing is completely free. Paid plans add a verified badge, featured placement and more photos.'],
            ['How do I get the verified badge?','Verified badges are given to businesses that confirm ownership and complete their profile. Upgrade from your dashboard or contact our team.'],
            ['How do customers contact my business?','Every listing shows your phone, WhatsApp, website and an enquiry form. Enquiries and bookings come straight to you.'],
            ['Can I edit my listing later?','Yes — log in to your dashboard any time to update details, photos, hours and offers.'],
            ['How are reviews and ratings handled?','Logged-in users can leave a star rating and review. Ratings are shown on your listing to build trust.'],
            ['Which areas of Noida does HiNoida cover?','All sectors and localities of Noida and Greater Noida. Browse by area from the Areas page.'],
        ];
        $faqLd=['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>array_map(static fn($f)=>['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]],$faqs)];
        return view('frontend/faq',['title'=>'Frequently Asked Questions | HiNoida','metaDescription'=>'Answers to common questions about listing your business, pricing, reviews and contacting businesses on HiNoida.','canonical'=>hi_page('faq'),'faqs'=>$faqs,'jsonld'=>[$faqLd]]);
    }

    public function advertise(){ return view('frontend/simple',['title'=>'Advertise With Us — Reach Noida | HiNoida','metaDescription'=>'Advertise your business on HiNoida and reach thousands of local customers across Noida with featured placement and banners.','canonical'=>base_url('advertise'),'heading'=>'Advertise with us','body'=>'Reach thousands of local customers across Noida. HiNoida offers featured listings, homepage placement, category sponsorships and banner ads. Email hello@new.hinoida.com to get a media kit and pricing tailored to your business.']); }
    public function privacy(){ return view('frontend/simple',['title'=>'Privacy Policy | HiNoida','metaDescription'=>'How HiNoida collects, uses and protects your data.','canonical'=>hi_page('privacy'),'heading'=>'Privacy Policy','body'=>hi_fe('fe_privacy', 'privacy_policy', true) ?: 'HiNoida respects your privacy. We collect only the information needed to provide our directory service — such as your name, email and business details — and never sell your personal data. You can request access to or deletion of your data at any time by emailing hello@new.hinoida.com. We use cookies to improve your experience; you can control these in your browser settings.']); }
    public function terms(){ return view('frontend/simple',['title'=>'Terms & Conditions | HiNoida','metaDescription'=>'The terms governing use of the HiNoida business directory.','canonical'=>hi_page('terms'),'heading'=>'Terms & Conditions','body'=>hi_fe('fe_terms', 'terms_and_condition', true) ?: 'By using HiNoida you agree to list accurate business information, not to post unlawful or misleading content, and to respect other users. HiNoida is a directory and does not guarantee the services of listed businesses. Listings may be moderated or removed at our discretion. For the full terms, contact hello@new.hinoida.com.']); }
    public function cookie(){ return view('frontend/simple',['title'=>'Cookie Policy | HiNoida','metaDescription'=>'How HiNoida uses cookies.','canonical'=>base_url('cookie'),'heading'=>'Cookie Policy','body'=>'HiNoida uses essential cookies to keep you logged in and to remember your preferences, plus analytics cookies to understand how the site is used. You can disable non-essential cookies in your browser at any time without affecting core functionality.']); }
    public function news(){
        $articles = [
            ['best-restaurants-noida','Top 20 Restaurants in Noida (2026 Guide)','food','Fine dining to street food — the most-loved places to eat across every sector of Noida.','Food & Drink'],
            ['top-hospitals-noida','Best Hospitals & Clinics in Noida','medical','Multispeciality hospitals, 24x7 clinics and diagnostic centres you can rely on.','Health'],
            ['shopping-malls-noida','Noida Shopping Guide: Malls & Markets','shopping','From premium malls to weekly bazaars — where to shop for everything.','Shopping'],
            ['real-estate-noida','Buying or Renting in Noida: Area-by-Area Guide','realestate','Sector-wise price trends, best localities and what to check before you sign.','Real Estate'],
            ['best-salons-spas-noida','Best Salons & Spas in Noida','beauty','Unisex salons, bridal makeup and luxury spas rated by real customers.','Beauty'],
            ['coaching-schools-noida','Schools & Coaching in Noida: Parent\'s Guide','education','CBSE/ICSE schools, play schools and top coaching institutes compared.','Education'],
        ];
        return view('frontend/news',[
            'title'=>'News & Local Guides for Noida | HiNoida',
            'metaDescription'=>'Local guides and news for Noida — best restaurants, hospitals, shopping, schools, salons and real estate, curated by HiNoida.',
            'canonical'=>hi_page('news'),
            'articles'=>$articles,
        ]);
    }

    public function credits(){
        $f = FCPATH . 'assets/img/cat/credits.json';
        $c = is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
        return view('frontend/credits', [
            'title' => 'Photo Credits | HiNoida',
            'metaDescription' => 'Attribution for the category photographs used on HiNoida.',
            'canonical' => base_url('credits'),
            'credits' => $c,
        ]);
    }
}
