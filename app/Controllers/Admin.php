<?php
namespace App\Controllers;
class Admin extends BaseController
{
    private function guard(){ return (int)session()->get('role')===1; }

    public function index(){ return $this->section('overview'); }

    public function section(string $sec='overview'){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $stats=[
            'listings'=>$db->table('listing')->where('status','active')->countAllResults(),
            'categories'=>$db->table('category')->countAllResults(),
            'users'=>$db->table('user')->countAllResults(),
            'pending'=>$db->table('listing')->whereNotIn('status',['active','rejected'])->countAllResults(),
            'approvals'=>$db->table('user')->where('role_id',2)->where('approved',0)->countAllResults(),
        ];
        $rows=[]; $edit=null; $extra=[];
        switch($sec){
            case 'listings': $rows=$db->table('listing')->orderBy('id','DESC')->limit(60)->get()->getResultArray(); break;
            case 'pending':  $rows=$db->table('listing')->whereNotIn('status',['active','rejected'])->limit(60)->get()->getResultArray(); break;
            // Was every user mixed together, capped at 80 with no paging — unusable
            // once the live database lands. Filter by role, search by name/email,
            // and page through the rest.
            case 'users':
                $uq   = trim((string)$this->request->getGet('q'));
                $urole= (int)$this->request->getGet('role');
                $ub   = $db->table('user');
                if($urole){ $ub->where('role_id',$urole); }
                if($uq!==''){ $ub->groupStart()->like('name',$uq)->orLike('email',$uq)->orLike('phone',$uq)->groupEnd(); }
                $utotal=$ub->countAllResults(false);
                $upage =max(1,(int)$this->request->getGet('page'));
                $rows  =$ub->orderBy('id','DESC')->limit(50,($upage-1)*50)->get()->getResultArray();
                $extra =['q'=>$uq,'role'=>$urole,'page'=>$upage,'total'=>$utotal,'pages'=>(int)ceil($utotal/50),
                         'counts'=>[
                            'all'=>$db->table('user')->countAllResults(),
                            1=>$db->table('user')->where('role_id',1)->countAllResults(),
                            2=>$db->table('user')->where('role_id',2)->countAllResults(),
                            3=>$db->table('user')->where('role_id',3)->countAllResults(),
                         ]];
                break;
            case 'approvals':$rows=$db->table('user')->where('role_id',2)->where('approved',0)->orderBy('id','DESC')->limit(80)->get()->getResultArray(); break;
            case 'categories': $rows=$db->table('category')->where('parent',0)->orderBy('name','ASC')->limit(200)->get()->getResultArray();
                               $eid=(int)$this->request->getGet('edit'); if($eid){ $edit=$db->table('category')->where('id',$eid)->get()->getRowArray(); } break;
            case 'areas':    $rows=$db->table('locality')->orderBy('name','ASC')->limit(400)->get()->getResultArray();
                             $eid=(int)$this->request->getGet('edit'); if($eid){ $edit=$db->table('locality')->where('id',$eid)->get()->getRowArray(); } break;
            case 'amenities':$rows=$db->tableExists('amenities')?$db->table('amenities')->orderBy('name','ASC')->get()->getResultArray():[];
                             $eid=(int)$this->request->getGet('edit'); if($eid){ $edit=$db->table('amenities')->where('id',$eid)->get()->getRowArray(); } break;
            case 'bookings': if($db->tableExists('booking')){
                                 $rows=$db->table('booking b')->select('b.*, li.name AS listing_name')
                                     ->join('listing li','li.id = b.listing_id','left')
                                     ->orderBy('b.id','DESC')->limit(200)->get()->getResultArray();
                             } break;
            case 'reported': if($db->tableExists('reported_listing')){
                                 $rows=$db->table('reported_listing r')
                                     ->select('r.*, li.name AS listing_name, li.status AS listing_status, u.name AS reporter_name, u.email AS reporter_email')
                                     ->join('listing li','li.id = r.listing_id','left')
                                     ->join('user u','u.id = r.reporter_id','left')
                                     ->orderBy('r.status','ASC')->orderBy('r.id','DESC')->limit(200)->get()->getResultArray();
                             } break;
            case 'comments': if($db->tableExists('blog_comment')){
                                 // posts live in `blogs` on the real database, `blog` here
                                 $_bt = ($_s = hi_content_src('blog', $db)) ? $_s['table'] : 'blog';
                                 $rows=$db->table('blog_comment c')->select('c.*, b.title AS post_title, b.slug')
                                     ->join($_bt.' b','b.id = c.blog_id','left')
                                     // pending first — that is what needs a decision
                                     ->orderBy("CASE WHEN c.status='pending' THEN 0 ELSE 1 END",'ASC',false)
                                     ->orderBy('c.id','DESC')->limit(200)->get()->getResultArray();
                             } break;
            case 'reviews':  $rows=$db->table('review r')->select('r.*, r.'.hi_review_pk($db).' AS id, li.name AS listing_name, u.name AS reviewer_name')
                                 ->join('listing li','li.id = r.listing_id','left')
                                 ->join('user u','u.id = r.reviewer_id','left')
                                 ->orderBy('r.'.hi_review_pk($db),'DESC')->limit(200)->get()->getResultArray();
                             break;
            case 'packages': $rows=$db->tableExists('package')?$db->table('package')->orderBy('price','ASC')->get()->getResultArray():[];
                             $eid=(int)$this->request->getGet('edit'); if($eid){ $edit=$db->table('package')->where('id',$eid)->get()->getRowArray(); } break;
            case 'revenue':  if($db->tableExists('package_purchased_history')){
                                 $rows=$db->table('package_purchased_history h')
                                     ->select('h.*, p.name AS package_name, u.name AS user_name, u.email')
                                     ->join('package p','p.id = h.package_id','left')
                                     ->join('user u','u.id = h.user_id','left')
                                     ->orderBy('h.purchase_date','DESC')->limit(200)->get()->getResultArray();
                             }
                             $edit=[
                                 'packages'=>$db->tableExists('package')?$db->table('package')->orderBy('price','ASC')->get()->getResultArray():[],
                                 'members' =>$db->table('user')->whereIn('role_id',[1,2])->orderBy('name','ASC')->limit(400)->get()->getResultArray(),
                             ];
                             break;
            case 'claims':   if($db->tableExists('claimed_listing')){
                                 $rows=$db->table('claimed_listing c')->select('c.*, li.name AS listing_name')
                                     ->join('listing li','li.id = c.listing_id','left')
                                     ->orderBy('c.status','ASC')->orderBy('c.id','DESC')->limit(200)->get()->getResultArray();
                             } break;
            case 'messages': $rows=$db->tableExists('discover_contact')?$db->table('discover_contact')->orderBy('id','DESC')->limit(100)->get()->getResultArray():[]; break;
            case 'settings': $edit=[]; if($db->tableExists('settings')){ foreach($db->table('settings')->get()->getResultArray() as $s){ $edit[$s['type']]=$s['description']; } } break;
            case 'analytics':
                $rows=[
                    'reviews'=>$db->tableExists('review')?$db->table('review')->countAllResults():0,
                    'bookings'=>$db->tableExists('booking')?$db->table('booking')->countAllResults():0,
                    'enquiries'=>$db->tableExists('discover_enquiry')?$db->table('discover_enquiry')->countAllResults():0,
                    'members'=>$db->table('user')->countAllResults(),
                    'top'=>[],
                ];
                $cm=new \App\Models\CategoryModel();
                $tops=$cm->where('parent',0)->findAll(60);
                foreach($tops as $t){ $rows['top'][]=['name'=>$t['name'],'cnt'=>$cm->countForCategory((int)$t['id'])]; }
                usort($rows['top'],static fn($a,$b)=>$b['cnt']-$a['cnt']);
                $rows['top']=array_slice($rows['top'],0,8);

                // ---- this week against last week ----
                // Each source stores its date differently: some as a DATETIME
                // string, some as a unix int. countBetween handles both.
                $wkStart = strtotime('today -6 days');
                $prStart = strtotime('today -13 days');
                $prEnd   = strtotime('today -7 days');

                $countBetween = static function(string $table, string $col, int $from, int $to) use ($db): int {
                    if(!$db->tableExists($table) || !$db->fieldExists($col,$table)){ return 0; }
                    $q=$db->table($table);
                    // a numeric column holds a unix timestamp; anything else is a date string
                    $probe=$db->table($table)->select($col)->where($col.' IS NOT NULL',null,false)->limit(1)->get()->getRowArray();
                    $val=$probe[$col]??null;
                    if($val!==null && is_numeric($val) && (int)$val > 100000000){
                        $q->where($col.' >=',$from)->where($col.' <=',$to);
                    } else {
                        $q->where($col.' >=',date('Y-m-d 00:00:00',$from))->where($col.' <=',date('Y-m-d 23:59:59',$to));
                    }
                    return $q->countAllResults();
                };

                $today=strtotime('today 23:59:59');
                $series=[
                    ['Page views','profile_views','created_at','eye'],
                    ['Enquiries','discover_enquiry','created_at','paper-plane'],
                    ['Bookings','booking','created_at','calendar-check'],
                    ['Reviews','review','timestamp','star'],
                    ['New members','user','created_date','user-plus'],
                    ['New listings','listing','date_added','store'],
                ];
                $rows['week']=[];
                foreach($series as [$label,$tbl,$col,$icon]){
                    $now=$countBetween($tbl,$col,$wkStart,$today);
                    $prev=$countBetween($tbl,$col,$prStart,$prEnd);
                    $rows['week'][]=['label'=>$label,'icon'=>$icon,'now'=>$now,'prev'=>$prev,
                        'delta'=>$prev>0 ? (int)round((($now-$prev)/$prev)*100) : ($now>0?100:0)];
                }

                // daily page views for the last 14 days, for the sparkline
                $rows['daily']=[];
                if($db->tableExists('profile_views')){
                    for($i=13;$i>=0;$i--){
                        $d=date('Y-m-d',strtotime("today -{$i} days"));
                        $rows['daily'][]=['d'=>$d,
                            'n'=>$db->table('profile_views')->like('created_at',$d,'after')->countAllResults()];
                    }
                }

                // busiest listings by page views this week
                $rows['busiest']=[];
                if($db->tableExists('profile_views')){
                    $rows['busiest']=$db->table('profile_views v')
                        ->select('v.listing_id, li.name, COUNT(*) AS n')
                        ->join('listing li','li.id = v.listing_id','left')
                        ->where('v.created_at >=',date('Y-m-d 00:00:00',$wkStart))
                        ->groupBy('v.listing_id')->orderBy('n','DESC')->limit(8)->get()->getResultArray();
                }
                break;
            case 'seo': $edit=[]; if($db->tableExists('settings')){ foreach($db->table('settings')->like('type','seo_')->get()->getResultArray() as $s){ $edit[$s['type']]=$s['description']; } }
                        $rows=$this->seoGaps($db); break;
            // Branding, social links and page copy — the parts of the original's
            // Admin::frontend_settings that `settings` did not already cover.
            case 'sitetext':
            case 'frontend': $edit=[]; if($db->tableExists('settings')){
                    foreach($db->table('settings')->get()->getResultArray() as $s){ $edit[$s['type']]=$s['description']; } }
                break;
            // Owner edits to live listings, held until approved.
            case 'changes':
                hi_ensure_tables($db);
                // Two shapes: this rewrite stores the change as a payload of
                // old/new pairs; the live table has an old_<col>/new_<col>
                // column for each field and no user_id or status at all.
                $lcq=$db->table('listing_changes lc')
                    ->select('lc.*, li.name AS listing_name')
                    ->join('listing li','li.id = lc.listing_id','left');
                if($db->fieldExists('user_id','listing_changes')){
                    $lcq->select('u.name AS owner_name')->join('user u','u.id = lc.user_id','left');
                }
                if($db->fieldExists('status','listing_changes')){
                    $lcq->orderBy("CASE WHEN lc.status='pending' THEN 0 ELSE 1 END",'ASC',false);
                }
                $rows=$lcq->orderBy('lc.id','DESC')->limit(300)->get()->getResultArray();

                $labels=array_flip(hi_change_fields());   // column => label
                foreach($rows as &$_r){
                    $_r['status'] = $_r['status'] ?? 'pending';
                    if(!empty($_r['payload'])){
                        $_r['_diff']=json_decode((string)$_r['payload'],true) ?: [];
                        continue;
                    }
                    // rebuild the diff from the live old_/new_ column pairs
                    $d=[];
                    foreach($_r as $k=>$v){
                        if(strpos($k,'new_')!==0){ continue; }
                        $col=substr($k,4);
                        $old=(string)($_r['old_'.$col] ?? '');
                        $new=(string)$v;
                        if($new==='' || $old===$new){ continue; }
                        $d[$col]=['label'=>$labels[$col] ?? ucfirst(str_replace('_',' ',$col)),'old'=>$old,'new'=>$new];
                    }
                    $_r['_diff']=$d;
                } unset($_r);
                break;
            // Ported from Admin::map_settings — zoom levels and the starting
            // point were hard-coded in the map view.
            case 'map': $edit=[]; if($db->tableExists('settings')){ foreach($db->table('settings')->like('type','map_')->get()->getResultArray() as $s){ $edit[$s['type']]=$s['description']; } }
                        break;
            case 'reports':
                // Ported from Reports.php: a category tree report and a
                // per-member listing report, both date-filterable.
                $from=trim((string)$this->request->getGet('from'));
                $to=trim((string)$this->request->getGet('to'));
                $which=$this->request->getGet('r')==='users'?'users':'categories';

                $rows=['which'=>$which,'from'=>$from,'to'=>$to,'cats'=>[],'users'=>[]];

                if($which==='categories'){
                    $cm=new \App\Models\CategoryModel();
                    foreach($cm->where('parent',0)->orderBy('name','ASC')->findAll(400) as $p){
                        $kids=[];
                        foreach($cm->where('parent',(int)$p['id'])->orderBy('name','ASC')->findAll(400) as $k){
                            $kids[]=['name'=>$k['name'],'n'=>$cm->countForCategory((int)$k['id'])];
                        }
                        $rows['cats'][]=['name'=>$p['name'],'n'=>$cm->countForCategory((int)$p['id']),'kids'=>$kids];
                    }
                } else {
                    $rows['users']=$this->userListingStats($db,$from,$to);
                }
                break;
            case 'requests': \App\Controllers\Dashboard::ensureRequests($db);
                             $rows=$db->table('request r')->select('r.*, li.name AS listing_name, u.name AS owner_name, u.email AS owner_email')
                                 ->join('listing li','li.id = r.listing_id','left')
                                 ->join('user u','u.id = r.created_by','left')
                                 ->orderBy("CASE WHEN r.status='open' THEN 0 ELSE 1 END",'ASC',false)
                                 ->orderBy('r.id','DESC')->limit(200)->get()->getResultArray();
                             // the live site threads replies in `reply`; without
                             // this the admin saw the question but none of the answers
                             if($db->tableExists('reply') && $rows){
                                 $ids=array_map(static fn($x)=>(int)$x['id'],$rows);
                                 $byReq=[];
                                 foreach($db->table('reply p')->select('p.*, u.name AS by_name')
                                     ->join('user u','u.id = p.created_by','left')
                                     ->whereIn('p.request_id',$ids)->orderBy('p.id','ASC')
                                     ->get()->getResultArray() as $p){ $byReq[(int)$p['request_id']][]=$p; }
                                 foreach($rows as &$_r){ $_r['_replies']=$byReq[(int)$_r['id']] ?? []; } unset($_r);
                             }
                             break;
            case 'team':     $rows=$db->table('user')->select('id, name, email, phone, role_id, is_verified')
                                 ->where('role_id',1)->orderBy('name','ASC')->get()->getResultArray();
                             $eid=(int)$this->request->getGet('edit');
                             $edit=$eid?$db->table('user')->where('id',$eid)->where('role_id',1)->get()->getRowArray():null;
                             break;
            case 'places':   $this->ensurePlaces($db);
                             $rows=[
                                 'states'=>$db->table('state')->orderBy('name','ASC')->get()->getResultArray(),
                                 'cities'=>$db->table('city c')->select('c.*, s.name AS state_name, (SELECT COUNT(*) FROM locality l WHERE l.city_id = c.id) AS areas')
                                     ->join('state s','s.id = c.state_id','left')->orderBy('c.name','ASC')->get()->getResultArray(),
                             ];
                             $eid=(int)$this->request->getGet('edit');
                             $what=$this->request->getGet('t')==='state'?'state':'city';
                             $edit=['what'=>$what,'row'=>$eid?$db->table($what)->where('id',$eid)->get()->getRowArray():null];
                             break;
            case 'quality':  $this->ensureQuality($db);
                             $rows=$db->table('review_wise_quality')->orderBy('rating_from','ASC')->get()->getResultArray();
                             $eid=(int)$this->request->getGet('edit'); if($eid){ $edit=$db->table('review_wise_quality')->where('id',$eid)->get()->getRowArray(); }
                             break;
            case 'profile': $edit=$db->table('user')->where('id',(int)session()->get('uid'))->get()->getRowArray()?:[]; break;
            case 'language':
                $codes=hi_languages();
                $editing=strtolower(preg_replace('/[^a-z0-9_-]/','',(string)$this->request->getGet('lang')));
                if($editing===''||!in_array($editing,$codes,true)){ $editing=hi_lang_code(); }
                if(!in_array($editing,$codes,true)){ $editing=$codes[0]; }
                $q=trim((string)$this->request->getGet('q'));
                $phrases=hi_phrases($editing,true);
                if($q!==''){
                    $phrases=array_filter($phrases,static fn($v,$k)=>
                        stripos($k,$q)!==false||stripos((string)$v,$q)!==false, ARRAY_FILTER_USE_BOTH);
                }
                ksort($phrases);
                $rows=['codes'=>$codes,'editing'=>$editing,'phrases'=>$phrases,
                       'active'=>hi_lang_code(),'q'=>$q,'total'=>count(hi_phrases($editing)),
                       'writable'=>is_writable(hi_lang_dir())];
                break;
            case 'addons':
                $rows=[];
                foreach(self::addons() as $k=>$a){
                    $rows[]=$a+['key'=>$k,'on'=>hi_addon($k)];
                }
                break;
            case 'email':
                $rows=$db->tableExists('email_queue')?$db->table('email_queue')->orderBy('id','DESC')->limit(50)->get()->getResultArray():[];
                $edit=['pending'=>0,'sent'=>0,'logged'=>0,'failed'=>0];
                if($db->tableExists('email_queue')){ foreach($db->table('email_queue')->select('status, COUNT(*) c')->groupBy('status')->get()->getResultArray() as $s){ $edit[$s['status']]=(int)$s['c']; } }
                break;
        }
        return view('admin/console',['title'=>'Admin Console | HiNoida','sec'=>$sec,'stats'=>$stats,'rows'=>$rows,'edit'=>$edit,'extra'=>$extra,'name'=>session()->get('uname'),'saved'=>session()->getFlashdata('saved')]);
    }

    public function exportListings(){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $rows=$db->table('listing li')->select('li.id, li.name, li.phone, li.email, li.address, li.status, l.name AS locality')->join('locality l','l.id=li.locality_id','left')->orderBy('li.id','DESC')->get()->getResultArray();
        $out=fopen('php://temp','r+'); fputcsv($out,['ID','Name','Phone','Email','Address','Status','Locality']);
        foreach($rows as $r){ fputcsv($out,[$r['id'],$r['name'],$r['phone'],$r['email'],$r['address'],$r['status'],$r['locality']]); }
        rewind($out); $csv=stream_get_contents($out); fclose($out);
        return $this->response->setHeader('Content-Type','text/csv')->setHeader('Content-Disposition','attachment; filename="hinoida-listings.csv"')->setBody($csv);
    }

    private function settingsTable($db){ if(!$db->tableExists('settings')){ $db->query('CREATE TABLE settings (id INTEGER PRIMARY KEY ' . ($db->DBDriver==='MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ', type VARCHAR(120), description TEXT)'); } }
    public function saveSettings(){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect(); $this->settingsTable($db);
        foreach(['website_title','system_email','phone','address','footer_text','free_listing_limit',
                 'mail_from','mail_from_name',
                 'smtp_host','smtp_port','smtp_secure','smtp_user','smtp_pass',
                 'hero_title','hero_subtitle','hero_placeholder','hero_boxes_title',
                 'hero_topic_one','hero_icon_one','hero_subtopic_one',
                 'hero_topic_two','hero_icon_two','hero_subtopic_two',
                 'hero_topic_three','hero_icon_three','hero_subtopic_three',
                 'pixel_id','map_access_token','map_default_location','map_max_zoom','map_min_zoom',
                 'cookie_notice','cookie_text','popup_enabled','popup_title','popup_text','popup_link','popup_link_text',
                 'pay_gateway','pay_currency','pay_key_id','pay_key_secret','pay_upi','pay_bank_details','pay_note'] as $k){
            // a field the form did not post must not be blanked out
            if($this->request->getPost($k)===null){ continue; }
            $v=(string)$this->request->getPost($k);
            $ex=$db->table('settings')->where('type',$k)->get()->getRowArray();
            if($ex){ $db->table('settings')->where('type',$k)->update(['description'=>$v]); } else { $db->table('settings')->insert(['type'=>$k,'description'=>$v]); }
        }
        session()->setFlashdata('saved','Settings saved.');
        // the free-listing box is posted from the Packages screen
        return redirect()->to($this->request->getPost('free_listing_limit')!==null ? '/admin/packages' : '/admin/settings');
    }

    /**
     * Approve or reject a queued owner edit. Approving writes the stored "new"
     * values onto the listing; rejecting leaves the listing untouched. Either
     * way the row is kept so there is a record of what was asked for.
     */
    public function changeAction(int $cid, string $decision)
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect(); hi_ensure_tables($db);
        $row=$db->table('listing_changes')->where('id',$cid)->get()->getRowArray();
        if(!$row || $row['status']!=='pending'){
            session()->setFlashdata('saved','That request has already been dealt with.');
            return redirect()->to('/admin/changes');
        }
        if($decision==='approve'){
            $diff=json_decode((string)$row['payload'],true) ?: [];
            $set=[];
            // only columns this feature is allowed to touch — the payload is
            // written by us, but it is still data going straight into an update
            $allowed=array_values(hi_change_fields());
            foreach($diff as $col=>$d){
                if(in_array($col,$allowed,true)){ $set[$col]=$d['new'] ?? ''; }
            }
            if($set){ $db->table('listing')->where('id',(int)$row['listing_id'])->update($set); }
            $db->table('listing_changes')->where('id',$cid)->update(['status'=>'approved','reviewed_at'=>date('Y-m-d H:i:s')]);
            session()->setFlashdata('saved',count($set).' change'.(count($set)===1?'':'s').' applied.');
        }else{
            $db->table('listing_changes')->where('id',$cid)->update(['status'=>'rejected','reviewed_at'=>date('Y-m-d H:i:s')]);
            session()->setFlashdata('saved','Change request rejected — the listing is unchanged.');
        }

        return redirect()->to('/admin/changes');
    }

    /** Branding, social links and page copy (the original's frontend_settings). */
    /**
     * Site copy and imagery, driven entirely by hi_content_fields(): a new
     * editable string needs a line in the catalogue and nothing here.
     *
     * A blank text box stores a blank, and hi_c() then falls back to the
     * built-in wording — so clearing a field restores the original rather
     * than emptying the page.
     */
    public function saveSiteText()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect(); $this->settingsTable($db);
        helper('content');

        foreach(hi_content_fields() as $fields){
            foreach($fields as $k => $fld){
                if(($fld[1] ?? 'text') === 'image'){
                    $file=$this->request->getFile($k);
                    if(!$file || !$file->isValid() || $file->hasMoved()){ continue; }
                    $ext=strtolower($file->getClientExtension());
                    if(!in_array($ext,['png','jpg','jpeg','svg','webp','gif'],true)){ continue; }
                    $dir=FCPATH.'uploads'.DIRECTORY_SEPARATOR.'brand';
                    if(!is_dir($dir)){ @mkdir($dir,0775,true); }
                    $name=$k.'-'.time().'.'.$ext;
                    try { $file->move($dir,$name,true); $this->putSetting($db,'c_'.$k,'uploads/brand/'.$name); }
                    catch(\Throwable $e){ log_message('error','site text upload failed: '.$e->getMessage()); }
                    continue;
                }
                $this->putSetting($db,'c_'.$k,trim((string)$this->request->getPost($k)));
            }
        }
        // the auto-registered strings, keyed by the views that use them
        foreach((array)$this->request->getPost('reg') as $k => $v){
            $k=preg_replace('/[^a-zA-Z0-9._-]/','',(string)$k);
            if($k===''){ continue; }
            $this->putSetting($db,'c_'.$k,trim((string)$v));
        }
        hi_settings_flush();
        session()->setFlashdata('saved','Site text saved.');

        return redirect()->to('admin/sitetext');
    }

    public function saveFrontend()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect(); $this->settingsTable($db);

        // logo / favicon / hero image are uploads; everything else is text
        foreach(['fe_logo','fe_favicon','fe_hero_image'] as $k){
            $f=$this->request->getFile($k);
            if($f && $f->isValid() && !$f->hasMoved()){
                $ext=strtolower($f->getClientExtension());
                if(!in_array($ext,['png','jpg','jpeg','svg','webp','ico'],true)){ continue; }
                $dir=FCPATH.'uploads'.DIRECTORY_SEPARATOR.'brand';
                if(!is_dir($dir)){ @mkdir($dir,0775,true); }
                $name=$k.'-'.time().'.'.$ext;
                try { $f->move($dir,$name,true); $this->putSetting($db,$k,'uploads/brand/'.$name); }
                catch(\Throwable $e){ log_message('error','brand upload failed: '.$e->getMessage()); }
            }
        }
        $igWas = trim((string)($db->table('settings')->where('type','fe_ig_token')->get()->getRowArray()['description'] ?? ''));
        foreach(['fe_facebook','fe_instagram','fe_ig_reels','fe_ig_token','fe_linkedin','fe_youtube','fe_twitter',
                 'fe_about','fe_faq','fe_privacy','fe_terms'] as $k){
            $this->putSetting($db,$k,trim((string)$this->request->getPost($k)));
        }
        // a new token starts its own 60-day clock, and the cached reels from
        // the old one have to go or the change looks like it did nothing
        if (trim((string)$this->request->getPost('fe_ig_token')) !== $igWas) {
            $this->putSetting($db,'fe_ig_token_at','');
            (new \App\Libraries\Instagram())->forget();
        }
        session()->setFlashdata('saved','Frontend settings saved.');

        return redirect()->to('/admin/frontend');
    }

    private function putSetting($db, string $k, string $v): void
    {
        $ex=$db->table('settings')->where('type',$k)->get()->getRowArray();
        if($ex){ $db->table('settings')->where('type',$k)->update(['description'=>$v]); }
        else   { $db->table('settings')->insert(['type'=>$k,'description'=>$v]); }
    }

    /** Map zoom + starting point (the original's Admin::map_settings). */
    public function saveMap(){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect(); $this->settingsTable($db);
        foreach(['map_default_location','map_min_zoom_listings','map_min_zoom_directory','map_max_zoom'] as $k){
            $v=trim((string)$this->request->getPost($k));
            $ex=$db->table('settings')->where('type',$k)->get()->getRowArray();
            if($ex){ $db->table('settings')->where('type',$k)->update(['description'=>$v]); }
            else { $db->table('settings')->insert(['type'=>$k,'description'=>$v]); }
        }
        session()->setFlashdata('saved','Map settings saved.');
        return redirect()->to('/admin/map');
    }

    public function saveSeo(){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect(); $this->settingsTable($db);
        foreach(['seo_home_title','seo_home_desc','seo_default_title','seo_default_desc',
                 'seo_ga4_id','seo_gsc_token','seo_head_code','seo_body_code'] as $k){
            $v=(string)$this->request->getPost($k);
            $ex=$db->table('settings')->where('type',$k)->get()->getRowArray();
            if($ex){ $db->table('settings')->where('type',$k)->update(['description'=>$v]); } else { $db->table('settings')->insert(['type'=>$k,'description'=>$v]); }
        }
        session()->setFlashdata('saved','SEO saved.');
        return redirect()->to('/admin/seo');
    }

    public function moderate(){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $id=(int)$this->request->getPost('id');
        $decision=$this->request->getPost('decision');
        $approved=$decision==='approve';
        $db->table('listing')->where('id',$id)->update(['status'=>$approved?'active':'rejected']);

        // Tell the owner. Approving a business account sent mail, but the
        // decision on their actual listing did not, so they were left watching
        // a pending badge with no idea it had gone live or been turned down.
        $note='Decision saved.';
        $l=$db->table('listing')->where('id',$id)->get()->getRowArray();
        if($l && !empty($l['user_id'])){
            $u=$db->table('user')->select('name, email')->where('id',(int)$l['user_id'])->get()->getRowArray();
            if($u && !empty($u['email'])){
                $name=esc($u['name'] ?: 'there');
                $biz=esc($l['name'] ?? 'your listing');
                $body=$approved
                    ? '<p>Hello '.$name.',</p><p><b>'.$biz.'</b> has been approved and is now live on HiNoida.</p>'
                      .'<p><a href="'.hi_listing_url($id).'">View your listing</a></p>'
                      .'<p>You can update photos, hours and details any time from your dashboard.</p>'
                    : '<p>Hello '.$name.',</p><p>We could not publish <b>'.$biz.'</b> as it is.</p>'
                      .'<p>This usually means something is incomplete or does not meet our listing guidelines. '
                      .'Edit it from your <a href="'.base_url('dashboard/listings').'">dashboard</a> and it will be reviewed again.</p>';
                try{
                    $mailer=new \App\Libraries\Mailer();
                    $mailer->queue($u['email'],(string)$u['name'],
                        $approved?'Your listing is live on HiNoida':'Your HiNoida listing needs a change',$body);
                    $mailer->processQueue(5);
                    $note=$approved?'Approved — the owner has been emailed.':'Rejected — the owner has been emailed.';
                }catch(\Throwable $e){
                    // the decision still stands even if the mail could not go
                    log_message('error','moderate: could not notify owner of listing '.$id.' — '.$e->getMessage());
                    $note=($approved?'Approved':'Rejected').', but the owner could not be emailed — check Admin > Email queue.';
                }
            }
        }
        session()->setFlashdata('saved',$note);
        return redirect()->to('/admin/pending');
    }

    /** Admin override on any booking, whoever owns the listing. */
    public function bookingAction(int $id, string $action){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        if(!$db->tableExists('booking')){ return redirect()->to('/admin/bookings'); }

        if($action==='delete'){
            $db->table('booking')->where('id',$id)->delete();
            session()->setFlashdata('saved','Booking deleted.');
            return redirect()->to('/admin/bookings');
        }

        $map=['approve'=>'approved','reject'=>'rejected','contacted'=>'contacted','close'=>'closed'];
        if(!isset($map[$action])){ return redirect()->to('/admin/bookings'); }
        $db->table('booking')->where('id',$id)->update(['status'=>$map[$action]]);
        session()->setFlashdata('saved','Booking marked '.$map[$action].'.');
        return redirect()->to('/admin/bookings');
    }

    /**
     * What is holding the directory's search visibility back.
     *
     * Each row is a real, countable gap — no scores or grades. Counted over
     * active listings only, since the others are not indexed anyway.
     */
    private function seoGaps($db): array
    {
        $total = $db->table('listing')->where('status','active')->countAllResults();
        if(!$total){ return ['total'=>0,'gaps'=>[]]; }

        $missing = static function(array $where) use ($db) {
            $q=$db->table('listing')->where('status','active');
            $q->groupStart();
            foreach($where as $i=>$w){ $i===0 ? $q->where($w,null,false) : $q->orWhere($w,null,false); }
            $q->groupEnd();
            return $q->countAllResults();
        };

        $gaps=[];
        $add=static function(&$gaps,$n,$total,$label,$why,$fix,$icon) {
            if($n<=0){ return; }
            $gaps[]=['n'=>$n,'pct'=>(int)round($n/$total*100),'label'=>$label,'why'=>$why,'fix'=>$fix,'icon'=>$icon];
        };

        $add($gaps,$missing(["description IS NULL","description = ''"]),$total,
            'listings have no description',
            'The page falls back to generated text, so it reads like every other listing.',
            'Edit the listing and write two or three real sentences.','align-left');

        $add($gaps,$missing(["phone IS NULL","phone = ''"]),$total,
            'listings have no phone number',
            'Local results lean on contact details; a listing nobody can ring converts badly.',
            'Add a phone number, or ask the owner to claim the listing.','phone');

        $add($gaps,$missing(["address IS NULL","address = ''"]),$total,
            'listings have no street address',
            'Without an address the LocalBusiness markup is incomplete and the map pin is missing.',
            'Fill in the address field.','location-dot');

        $add($gaps,$missing(["latitude IS NULL","latitude = ''","latitude = 0"]),$total,
            'listings have no map coordinates',
            'They cannot appear on the results map or give directions.',
            'Add latitude and longitude when editing the listing.','map-pin');

        $add($gaps,$missing(["photos IS NULL","photos = ''","photos = '[]'"]),$total,
            'listings have no photos',
            'A generated tile is shown instead — fine, but a real photo earns far more clicks.',
            'Upload photos from the listing\'s Photos screen.','image');

        $add($gaps,$missing(["locality_id IS NULL","locality_id = 0"]),$total,
            'listings are not assigned to an area',
            'They are missing from every area page and from the area filter.',
            'Set the area when editing the listing.','map');

        $add($gaps,$missing(["categories IS NULL","categories = ''","categories = '[]'"]),$total,
            'listings have no category',
            'They cannot be browsed or filtered — only found by direct search.',
            'Assign at least one category.','layer-group');

        // categories with nothing in them are thin pages that dilute the site
        $empty=0;
        $cm=new \App\Models\CategoryModel();
        foreach($cm->findAll(1000) as $c){ if($cm->countForCategory((int)$c['id'])===0){ $empty++; } }
        if($empty){
            $gaps[]=['n'=>$empty,'pct'=>null,'label'=>'categories have no listings',
                'why'=>'Empty category pages are thin content and can drag the whole site down.',
                'fix'=>'Add listings to them, or delete the categories you do not need.','icon'=>'folder-open'];
        }

        // reviews are the strongest differentiator a directory has
        $noRev=0;
        if($db->tableExists('review')){
            $noRev=$total-(int)($db->table('review r')->join('listing li','li.id = r.listing_id')
                ->where('li.status','active')->select('COUNT(DISTINCT r.listing_id) c')
                ->get()->getRowArray()['c'] ?? 0);
        }
        $add($gaps,$noRev,$total,'listings have no reviews',
            'Star ratings are what make a directory listing stand out in results.',
            'Invite customers to review, and reply to the reviews you get.','star');

        usort($gaps,static fn($a,$b)=>$b['n']<=>$a['n']);

        return ['total'=>$total,'gaps'=>$gaps];
    }

    /**
     * The optional features an admin can switch off, and what each one hides.
     * Anything not listed here is core and always on.
     */
    public static function addons(): array
    {
        return [
            'bookings' => ['name'=>'Bookings &amp; enquiries','icon'=>'calendar-check','default'=>1,
                'note'=>'The booking and enquiry form on every listing page.'],
            'reviews'  => ['name'=>'Reviews &amp; ratings','icon'=>'star','default'=>1,
                'note'=>'Star ratings, the review form and the rating breakdown.'],
            'qa'       => ['name'=>'Questions &amp; answers','icon'=>'circle-question','default'=>1,
                'note'=>'The community Q&amp;A section on listing pages.'],
            'vcard'    => ['name'=>'Digital card &amp; QR','icon'=>'qrcode','default'=>1,
                'note'=>'The shareable business card at /card/{id}.'],
            'packages' => ['name'=>'Packages &amp; pricing','icon'=>'box-open','default'=>1,
                'note'=>'The public pricing page and paid plans.'],
            'claims'   => ['name'=>'Ownership claims','icon'=>'hand-holding-heart','default'=>1,
                'note'=>'“Is this your business?” on unclaimed listings.'],
            'comments' => ['name'=>'Blog comments','icon'=>'message','default'=>1,
                'note'=>'Comments underneath news and guide posts.'],
            'compare'  => ['name'=>'Compare places','icon'=>'scale-balanced','default'=>1,
                'note'=>'The side-by-side compare tool.'],
        ];
    }

    /** Turn an add-on on or off. */
    public function toggleAddon(string $key)
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        if(!isset(self::addons()[$key])){ return redirect()->to('/admin/addons'); }

        $db=\Config\Database::connect(); $this->settingsTable($db);
        $now=hi_addon($key) ? '0' : '1';
        $type='addon_'.$key;
        $ex=$db->table('settings')->where('type',$type)->get()->getRowArray();
        if($ex){ $db->table('settings')->where('type',$type)->update(['description'=>$now]); }
        else { $db->table('settings')->insert(['type'=>$type,'description'=>$now]); }

        hi_settings_flush();
        session()->setFlashdata('saved',strip_tags(self::addons()[$key]['name']).($now==='1'?' switched on.':' switched off.'));
        return redirect()->to('/admin/addons');
    }

    /**
     * The admin's own account. Changing the password needs the current one —
     * an admin session left open on a shared machine should not be enough to
     * take the account over.
     */
    public function saveOwnProfile()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $uid=(int)session()->get('uid');
        $me=$db->table('user')->where('id',$uid)->get()->getRowArray();
        if(!$me){ return redirect()->to('/login'); }

        $name=trim((string)$this->request->getPost('name'));
        $mail=trim((string)$this->request->getPost('email'));
        $phone=trim((string)$this->request->getPost('phone'));

        if($name===''||!filter_var($mail,FILTER_VALIDATE_EMAIL)){
            session()->setFlashdata('saved','A name and a valid email address are required.');
            return redirect()->to('/admin/profile');
        }
        // the email is the login, so it cannot collide with another account
        if($db->table('user')->where('email',$mail)->where('id !=',$uid)->countAllResults()){
            session()->setFlashdata('saved','Another account already uses that email address.');
            return redirect()->to('/admin/profile');
        }

        $upd=['name'=>$name,'email'=>$mail,'phone'=>$phone];

        $new=(string)$this->request->getPost('new_password');
        if($new!==''){
            $cur=(string)$this->request->getPost('current_password');
            if(!hi_pass_check((string)$cur,(string)$me['password'])){
                session()->setFlashdata('saved','That is not your current password — nothing was changed.');
                return redirect()->to('/admin/profile');
            }
            if(strlen($new)<8){
                session()->setFlashdata('saved','Use at least 8 characters for the new password.');
                return redirect()->to('/admin/profile');
            }
            if($new!==(string)$this->request->getPost('confirm_password')){
                session()->setFlashdata('saved','The two new passwords do not match.');
                return redirect()->to('/admin/profile');
            }
            $upd['password']=hi_pass_hash((string)$new);
        }

        $db->table('user')->where('id',$uid)->update($upd);
        session()->set('name',$name);
        session()->setFlashdata('saved',isset($upd['password'])?'Profile and password updated.':'Profile updated.');
        return redirect()->to('/admin/profile');
    }

    /**
     * Act on a reported listing.
     *
     * Ported from Listing::reported_listings — the original offered
     * mark_as_active, mark_as_pending and delete.
     */
    public function reportAction(int $id, string $action)
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        if(!$db->tableExists('reported_listing')){ return redirect()->to('/admin/reported'); }

        $r=$db->table('reported_listing')->where('id',$id)->get()->getRowArray();
        if(!$r){ return redirect()->to('/admin/reported'); }

        if($action==='delete'){
            $db->table('reported_listing')->where('id',$id)->delete();
            session()->setFlashdata('saved','Report dismissed.');
        } elseif($action==='pending'){
            $db->table('listing')->where('id',(int)$r['listing_id'])->update(['status'=>'pending']);
            $db->table('reported_listing')->where('id',$id)->update(['status'=>1]);
            session()->setFlashdata('saved','Listing taken down pending review.');
        } elseif($action==='active'){
            $db->table('listing')->where('id',(int)$r['listing_id'])->update(['status'=>'active']);
            $db->table('reported_listing')->where('id',$id)->update(['status'=>1]);
            session()->setFlashdata('saved','Listing kept live and the report closed.');
        }
        return redirect()->to('/admin/reported');
    }

    /** Reply to a change request — Admin::request_action + User::reply. */
    public function requestReply(int $id)
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        \App\Controllers\Dashboard::ensureRequests($db);
        $r=$db->table('request')->where('id',$id)->get()->getRowArray();
        if(!$r){ return redirect()->to('/admin/requests'); }

        $reply=trim((string)$this->request->getPost('admin_reply'));
        if($reply===''){ session()->setFlashdata('saved','Write a reply first.'); return redirect()->to('/admin/requests'); }

        // Live hinoida threads replies in a `reply` table rather than keeping a
        // single admin_reply column, so an owner can be answered more than once.
        // Write there when it exists, and keep the column in step where it does.
        if($db->tableExists('reply')){
            $db->table('reply')->insert([
                'request_id'    => $id,
                'reply_message' => $reply,
                'created_by'    => (int) session()->get('uid'),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }
        $set=['status'=>$this->request->getPost('close') ? 'closed' : 'open'];
        if($db->fieldExists('admin_reply','request')){ $set['admin_reply']=$reply; }
        if($db->fieldExists('replied_at','request')){ $set['replied_at']=date('Y-m-d H:i:s'); }
        $db->table('request')->where('id',$id)->update($set);

        $owner=$db->table('user')->select('name, email')->where('id',(int)$r['created_by'])->get()->getRowArray();
        if(!empty($owner['email'])){
            (new \App\Libraries\Mailer())->queue($owner['email'], $owner['name'] ?? 'there',
                'Re: your change request — HiNoida',
                '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;color:#33405a">'
                . '<h2 style="color:#101a2c">We have replied</h2>'
                . '<p style="padding:12px 15px;background:#f5f7fb;border-radius:10px">' . esc($r['req_message']) . '</p>'
                . '<p><b>Our reply:</b><br>' . nl2br(esc($reply)) . '</p>'
                . '<p><a href="' . base_url('dashboard/requests') . '">See it in your dashboard</a></p></div>');
        }

        session()->setFlashdata('saved','Reply sent to '.esc($owner['name'] ?? 'the owner').'.');
        return redirect()->to('/admin/requests');
    }

    /** Close or reopen a change request. */
    public function requestAction(int $id, string $action)
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        \App\Controllers\Dashboard::ensureRequests($db);
        if($action==='delete'){ $db->table('request')->where('id',$id)->delete(); session()->setFlashdata('saved','Request deleted.'); }
        elseif(in_array($action,['close','open'],true)){
            $db->table('request')->where('id',$id)->update(['status'=>$action==='close'?'closed':'open']);
            session()->setFlashdata('saved','Request '.($action==='close'?'closed':'reopened').'.');
        }
        return redirect()->to('/admin/requests');
    }

    /**
     * Listings per member, optionally within a date range.
     * Ported from Reports::user_listing_stats.
     */
    private function userListingStats($db, string $from='', string $to=''): array
    {
        $b=$db->table('user u')
            ->select('u.id, u.name, u.email, u.role_id, COUNT(l.id) AS listing_count, MAX(l.date_added) AS last_listing')
            ->join('listing l','l.user_id = u.id','left');

        // date_added is a plain date string in this schema
        if($from!==''){ $b->where('l.date_added >=',$from); }
        if($to!==''){ $b->where('l.date_added <=',$to.' 23:59:59'); }

        return $b->groupBy('u.id')->orderBy('listing_count','DESC')->limit(500)->get()->getResultArray();
    }

    /**
     * Add, edit or remove someone who can run the admin console.
     * Admin::users add/edit/delete, which the rewrite never had.
     */
    public function saveTeam()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $id=(int)$this->request->getPost('id');
        $me=(int)session()->get('uid');
        $act=(string)$this->request->getPost('act');

        $admins=$db->table('user')->where('role_id',1)->countAllResults();

        if($act==='remove'){
            if($id===$me){ session()->setFlashdata('saved','You cannot remove your own access.'); return redirect()->to('/admin/team'); }
            if($admins<=1){ session()->setFlashdata('saved','There has to be at least one admin.'); return redirect()->to('/admin/team'); }
            // step them down rather than deleting — their listings and history stay put
            $db->table('user')->where('id',$id)->where('role_id',1)->update(['role_id'=>3]);
            session()->setFlashdata('saved','Admin access removed. The account itself is untouched.');
            return redirect()->to('/admin/team');
        }

        $name=trim((string)$this->request->getPost('name'));
        $mail=trim((string)$this->request->getPost('email'));
        $phone=trim((string)$this->request->getPost('phone'));
        $pass=(string)$this->request->getPost('password');

        if($name===''||!filter_var($mail,FILTER_VALIDATE_EMAIL)){
            session()->setFlashdata('saved','A name and a valid email address are required.');
            return redirect()->to('/admin/team');
        }
        if($db->table('user')->where('email',$mail)->where('id !=',$id)->countAllResults()){
            session()->setFlashdata('saved','Another account already uses that email address.');
            return redirect()->to('/admin/team');
        }

        $d=['name'=>$name,'email'=>$mail,'phone'=>$phone];

        if($id>0){
            if($pass!==''){
                if(strlen($pass)<8){ session()->setFlashdata('saved','Use at least 8 characters for the password.'); return redirect()->to('/admin/team'); }
                $d['password']=hi_pass_hash((string)$pass);
            }
            $db->table('user')->where('id',$id)->update($d);
            session()->setFlashdata('saved','Saved.');
            return redirect()->to('/admin/team');
        }

        if(strlen($pass)<8){
            session()->setFlashdata('saved','Give the new admin a password of at least 8 characters.');
            return redirect()->to('/admin/team');
        }
        $d+=['password'=>hi_pass_hash((string)$pass),'role_id'=>1,'is_verified'=>1,'approved'=>1,
             'verification_code'=>'','wishlists'=>'[]','social'=>'{}'];
        if($db->fieldExists('created_date','user')){ $d['created_date']=time(); }
        $d += hi_user_defaults($db);   // otp / updated_date are NOT NULL on the live schema
        $db->table('user')->insert($d);

        (new \App\Libraries\Mailer())->queue($mail,$name,'You can now manage HiNoida',
            '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;color:#33405a">'
            . '<h2 style="color:#101a2c">Admin access</h2>'
            . '<p>Hi '.esc($name).', you have been given admin access to HiNoida.</p>'
            . '<p>Sign in with <b>'.esc($mail).'</b> and the password you were given, then change it under My account.</p>'
            . '<p><a href="'.hi_page('login').'" style="background:#014dda;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">Sign in</a></p></div>');

        session()->setFlashdata('saved',esc($name).' can now manage the admin console, and has been emailed.');
        return redirect()->to('/admin/team');
    }

    /** state / city, and the columns locality gained to hang off them. */
    private function ensurePlaces($db): void
    {
        $inc=$db->DBDriver==='MySQLi'?'AUTO_INCREMENT':'AUTOINCREMENT';
        if(!$db->tableExists('state')){
            $db->query('CREATE TABLE state (id INTEGER PRIMARY KEY '.$inc.', name VARCHAR(120), country_id INT DEFAULT 101)');
        }
        if(!$db->tableExists('city')){
            $db->query('CREATE TABLE city (id INTEGER PRIMARY KEY '.$inc.', name VARCHAR(120), state_id INT, country_id INT DEFAULT 101)');
        }
        foreach(['city_id'=>'INT','seo_title'=>'TEXT','meta_description'=>'TEXT'] as $col=>$type){
            if(!$db->fieldExists($col,'locality')){
                try { $db->query('ALTER TABLE locality ADD COLUMN '.$col.' '.$type); } catch(\Throwable $e){}
            }
        }
    }

    /** Add, rename or remove a state or city — Admin::cities / states. */
    public function savePlace(string $what)
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        if(!in_array($what,['state','city'],true)){ return redirect()->to('/admin/places'); }
        $db=\Config\Database::connect(); $this->ensurePlaces($db);
        $id=(int)$this->request->getPost('id');

        if($this->request->getPost('act')==='delete'){
            // a state with cities, or a city with areas, would orphan them
            $kids=$what==='state'
                ? $db->table('city')->where('state_id',$id)->countAllResults()
                : $db->table('locality')->where('city_id',$id)->countAllResults();
            if($kids){
                session()->setFlashdata('saved','Move or remove its '.($what==='state'?'cities':'areas').' first — '.$kids.' still attached.');
                return redirect()->to('/admin/places');
            }
            $db->table($what)->where('id',$id)->delete();
            session()->setFlashdata('saved',ucfirst($what).' deleted.');
            return redirect()->to('/admin/places');
        }

        $name=trim((string)$this->request->getPost('name'));
        if($name===''){ session()->setFlashdata('saved','Give it a name.'); return redirect()->to('/admin/places'); }
        $d=['name'=>$name,'country_id'=>(int)($this->request->getPost('country_id') ?: 101)];
        if($what==='city'){ $d['state_id']=(int)$this->request->getPost('state_id'); }

        if($id>0){ $db->table($what)->where('id',$id)->update($d); }
        else { $db->table($what)->insert($d); }
        session()->setFlashdata('saved',ucfirst($what).' saved.');
        return redirect()->to('/admin/places');
    }

    /** The rating band table the original kept in review_wise_quality. */
    private function ensureQuality($db): void
    {
        if($db->tableExists('review_wise_quality')){ return; }
        $inc=$db->DBDriver==='MySQLi'?'AUTO_INCREMENT':'AUTOINCREMENT';
        $db->query('CREATE TABLE review_wise_quality (id INTEGER PRIMARY KEY '.$inc.',
            rating_from DECIMAL(3,1), rating_to DECIMAL(3,1), quality VARCHAR(60))');
        $db->table('review_wise_quality')->insertBatch([
            ['rating_from'=>4.5,'rating_to'=>5.0,'quality'=>'Excellent'],
            ['rating_from'=>4.0,'rating_to'=>4.4,'quality'=>'Very good'],
            ['rating_from'=>3.0,'rating_to'=>3.9,'quality'=>'Good'],
            ['rating_from'=>2.0,'rating_to'=>2.9,'quality'=>'Average'],
            ['rating_from'=>0.1,'rating_to'=>1.9,'quality'=>'Poor'],
        ]);
    }

    /** Add, edit or delete a rating band — Admin::rating_wise_quality. */
    public function saveQuality()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect(); $this->ensureQuality($db);
        $id=(int)$this->request->getPost('id');

        if($this->request->getPost('act')==='delete'){
            $db->table('review_wise_quality')->where('id',$id)->delete();
            session()->setFlashdata('saved','Band deleted.');
            return redirect()->to('/admin/quality');
        }

        $from=(float)$this->request->getPost('rating_from');
        $to=(float)$this->request->getPost('rating_to');
        $word=trim((string)$this->request->getPost('quality'));
        if($word===''){ session()->setFlashdata('saved','Give the band a word.'); return redirect()->to('/admin/quality'); }
        if($from>$to){ [$from,$to]=[$to,$from]; }
        $from=max(0,min(5,$from)); $to=max(0,min(5,$to));

        $d=['rating_from'=>$from,'rating_to'=>$to,'quality'=>$word];
        if($id>0){ $db->table('review_wise_quality')->where('id',$id)->update($d); }
        else { $db->table('review_wise_quality')->insert($d); }
        session()->setFlashdata('saved','Band saved.');
        return redirect()->to('/admin/quality');
    }

    /** Category tree with listing counts — Reports::export_hierarchical_category_csv. */
    public function exportCategoryTree()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $cm=new \App\Models\CategoryModel();
        $out=[];
        foreach($cm->where('parent',0)->orderBy('name','ASC')->findAll(400) as $p){
            $out[]=[$p['name'],'',$cm->countForCategory((int)$p['id'])];
            foreach($cm->where('parent',(int)$p['id'])->orderBy('name','ASC')->findAll(400) as $k){
                $out[]=[$p['name'],$k['name'],$cm->countForCategory((int)$k['id'])];
            }
        }
        return $this->csv('category-tree.csv',['Parent Category','Subcategory','Listing Count'],$out);
    }

    /** Listings per member — Reports::download_user_listing_stats_csv. */
    public function exportUserStats()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $from=trim((string)$this->request->getGet('from'));
        $to=trim((string)$this->request->getGet('to'));
        $rows=$this->userListingStats($db,$from,$to);

        $roles=[1=>'Admin',2=>'Business',3=>'Member'];
        return $this->csv('member-listings.csv',
            ['Member','Email','Role','Listings','Last listing added'],
            array_map(static fn($r)=>[
                $r['name'] ?? '', $r['email'] ?? '', $roles[(int)($r['role_id']??3)] ?? '',
                (int)($r['listing_count']??0), $r['last_listing'] ?? '',
            ],$rows));
    }

    /** Every listing belonging to one member — Reports::download_user_individual_listings_csv. */
    public function exportUserListings(int $uid)
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $u=$db->table('user')->select('name')->where('id',$uid)->get()->getRowArray();
        $rows=$db->table('listing li')->select('li.id, li.name, li.phone, li.email, li.address, li.status, li.date_added, l.name AS locality')
            ->join('locality l','l.id = li.locality_id','left')
            ->where('li.user_id',$uid)->orderBy('li.id','DESC')->get()->getResultArray();

        $slug=preg_replace('/[^a-z0-9]+/i','-',strtolower((string)($u['name']??'member')));
        return $this->csv(trim($slug,'-').'-listings.csv',
            ['ID','Name','Phone','Email','Address','Area','Status','Added'],
            array_map(static fn($r)=>[$r['id'],$r['name'],$r['phone'],$r['email'],$r['address'],$r['locality'],$r['status'],$r['date_added']],$rows));
    }

    /**
     * Phrase editor — ported from Admin::manage_language,
     * add_language / delete_language / add_phrase / update_phrase_with_ajax.
     */
    public function language(string $action = '')
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $back=static fn($c='')=>'/admin/language'.($c?'?lang='.$c:'');

        $clean=static fn($v)=>preg_replace('/[^a-z0-9_-]/','',strtolower(trim((string)$v)));

        if($action==='add'){
            $code=$clean($this->request->getPost('code'));
            if($code===''){ session()->setFlashdata('saved','Give the language a code, like hindi.'); return redirect()->to($back()); }
            $file=hi_lang_dir().$code.'.json';
            if(is_file($file)){ session()->setFlashdata('saved','That language already exists.'); return redirect()->to($back($code)); }
            // start from English so every key is present and ready to translate
            $seed=hi_phrases('english');
            if(!$seed){ $seed=hi_phrases(hi_lang_code()); }
            if(@file_put_contents($file,json_encode($seed,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))===false){
                session()->setFlashdata('saved','Could not write to writable/language — check the folder permissions.');
                return redirect()->to($back());
            }
            session()->setFlashdata('saved',ucfirst($code).' added, seeded with the English phrases.');
            return redirect()->to($back($code));
        }

        if($action==='delete'){
            $code=$clean($this->request->getPost('code'));
            if($code===''||$code==='english'){ session()->setFlashdata('saved','English cannot be deleted.'); return redirect()->to($back()); }
            if($code===hi_lang_code()){ session()->setFlashdata('saved','That language is currently in use — switch to another one first.'); return redirect()->to($back()); }
            @unlink(hi_lang_dir().$code.'.json');
            session()->setFlashdata('saved',ucfirst($code).' deleted.');
            return redirect()->to($back());
        }

        if($action==='activate'){
            $code=$clean($this->request->getPost('code'));
            if($code===''||!in_array($code,hi_languages(),true)){ return redirect()->to($back()); }
            $this->settingsTable($db);
            $ex=$db->table('settings')->where('type','language')->get()->getRowArray();
            if($ex){ $db->table('settings')->where('type','language')->update(['description'=>$code]); }
            else { $db->table('settings')->insert(['type'=>'language','description'=>$code]); }
            hi_settings_flush();
            session()->setFlashdata('saved','The site now uses '.ucfirst($code).'.');
            return redirect()->to($back($code));
        }

        if($action==='save'){
            $code=$clean($this->request->getPost('code'));
            $keys=(array)$this->request->getPost('phrase');
            if($code===''||!in_array($code,hi_languages(),true)){ return redirect()->to($back()); }
            $n=0;
            foreach($keys as $k=>$v){
                $k=hi_phrase_key((string)$k);
                if($k===''){ continue; }
                if(hi_phrase_save($code,$k,(string)$v)){ $n++; }
            }
            session()->setFlashdata('saved',$n.' phrase'.($n===1?'':'s').' saved.');
            return redirect()->to($back($code).($this->request->getPost('q')?'&q='.urlencode((string)$this->request->getPost('q')):''));
        }

        if($action==='addphrase'){
            $code=$clean($this->request->getPost('code'));
            $key=hi_phrase_key((string)$this->request->getPost('key'));
            if($code===''||$key===''){ return redirect()->to($back($code)); }
            hi_phrase_save($code,$key,(string)($this->request->getPost('value') ?: ucfirst(str_replace('_',' ',$key))));
            session()->setFlashdata('saved','Phrase “'.$key.'” added.');
            return redirect()->to($back($code).'&q='.urlencode($key));
        }

        return redirect()->to($back());
    }

    /** Approve, reject or delete a blog comment. */
    public function commentAction(int $id, string $action){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        if(!$db->tableExists('blog_comment')){ return redirect()->to('/admin/comments'); }

        if($action==='delete'){
            $db->table('blog_comment')->where('id',$id)->delete();
            session()->setFlashdata('saved','Comment deleted.');
        } elseif(in_array($action,['approve','reject'],true)){
            $db->table('blog_comment')->where('id',$id)->update(['status'=>$action==='approve'?'approved':'rejected']);
            session()->setFlashdata('saved',$action==='approve'?'Comment published.':'Comment rejected.');
        }
        return redirect()->to('/admin/comments');
    }

    /** Hide or restore a review, or remove it entirely. */
    public function reviewAction(int $id, string $action){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();

        if($action==='delete'){
            $db->table('review')->where(hi_review_pk($db),$id)->delete();
            session()->setFlashdata('saved','Review deleted.');
            return redirect()->to('/admin/reviews');
        }

        // hidden reviews stay in the table so a mistake can be undone
        if(!$db->fieldExists('is_hidden','review')){
            try { $db->query('ALTER TABLE review ADD COLUMN is_hidden INT DEFAULT 0'); } catch(\Throwable $e){}
        }
        $db->table('review')->where(hi_review_pk($db),$id)->update(['is_hidden'=>$action==='hide'?1:0]);
        session()->setFlashdata('saved',$action==='hide'?'Review hidden from the site.':'Review restored.');
        return redirect()->to('/admin/reviews');
    }

    public function approveUser(int $id){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $u=$db->table('user')->where('id',$id)->get()->getRowArray();
        if($u){
            $db->table('user')->where('id',$id)->update(['approved'=>1]);
            $mailer=new \App\Libraries\Mailer();
            $mailer->queue($u['email'],$u['name'],'Your HiNoida business is approved',
                '<p>Good news '.esc($u['name']).'! Your business account has been approved. You can now <a href="'.hi_page('login').'">log in</a> and publish your listing.</p>');
            $mailer->processQueue(5);
        }
        session()->setFlashdata('saved','Business approved & notified.');
        return redirect()->to('/admin/approvals');
    }

    public function userAction(string $act, int $id){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $u=$db->table('user')->where('id',$id)->get()->getRowArray();
        if(!$u){ return redirect()->to('/admin/users'); }
        switch($act){
            case 'verify':   $db->table('user')->where('id',$id)->update(['is_verified'=>1,'verification_code'=>'']); $msg='Email marked verified.'; break;
            case 'approve':  $db->table('user')->where('id',$id)->update(['approved'=>1]);
                             $m=new \App\Libraries\Mailer(); $m->queue($u['email'],$u['name'],'Your HiNoida business is approved','<p>Good news '.esc($u['name']).'! Your business has been approved. You can now publish your listings.</p>'); $m->processQueue(5);
                             $msg='Business approved & notified.'; break;
            case 'makeadmin':$db->table('user')->where('id',$id)->update(['role_id'=>1]); $msg='Promoted to admin.'; break;
            case 'makeuser': $db->table('user')->where('id',$id)->update(['role_id'=>3]); $msg='Set as member.'; break;
            case 'makebiz':  $db->table('user')->where('id',$id)->update(['role_id'=>2,'approved'=>0]); $msg='Set as business (pending approval).'; break;
            case 'delete':   if((int)$u['id']!==(int)session()->get('uid')){ $db->table('user')->where('id',$id)->delete(); } $msg='User deleted.'; break;
            default: $msg='';
        }
        session()->setFlashdata('saved',$msg);
        return redirect()->to('/admin/user/'.$id);
    }

    public function userDetail(int $id){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $u=$db->table('user')->where('id',$id)->get()->getRowArray();
        if(!$u){ return redirect()->to('/admin/users'); }
        $listings=$db->table('listing')->where('user_id',$id)->orderBy('id','DESC')->get()->getResultArray();
        $lids=array_map(fn($r)=>(int)$r['id'],$listings);
        $enq=$bok=[];
        if($lids){
            if($db->tableExists('discover_enquiry')) $enq=$db->table('discover_enquiry')->whereIn('listing_id',$lids)->orderBy('id','DESC')->limit(30)->get()->getResultArray();
            if($db->tableExists('booking')) $bok=$db->table('booking')->whereIn('listing_id',$lids)->orderBy('id','DESC')->limit(30)->get()->getResultArray();
        }
        return view('admin/user_detail',['title'=>'Manage '.$u['name'].' | HiNoida Admin','u'=>$u,'listings'=>$listings,'enq'=>$enq,'bok'=>$bok,'saved'=>session()->getFlashdata('saved')]);
    }

    public function editListing(int $id = 0){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $row=$id?$db->table('listing')->where('id',$id)->get()->getRowArray():[];
        helper('property');
        return view('admin/listing_edit',[
            'title'=>($id?'Edit':'Add').' Listing | HiNoida Admin',
            'row'=>$row,
            'cats'=>$db->table('category')->where('parent',0)->orderBy('name','ASC')->get()->getResultArray(),
            'locs'=>$db->table('locality')->orderBy('name','ASC')->get()->getResultArray(),
            'amen'=>hi_amenity_choices($row?:['listing_type'=>''],$db),
            // named places inside a locality — malls, blocks, markets
            'areas'=>$db->tableExists('area')
                ? $db->table('area a')->select('a.id, a.name, l.name AS locality_name')
                    ->join('locality l','l.id = a.locality_id','left')
                    ->orderBy('a.name','ASC')->get()->getResultArray()
                : [],
            'saved'=>session()->getFlashdata('saved'),
            'errors'=>session()->getFlashdata('errors'),
            // the property side of a listing, when it has one
            'prop'=>$id ? (static function() use ($db,$id){
                helper('property'); hi_ensure_property_table($db); return hi_property_of($id);
            })() : [],
        ]);
    }

    public function saveListing(){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect(); $p=$this->request; $id=(int)$p->getPost('id');
        $tomin=static fn($t)=>preg_match('/^(\d{1,2}):(\d{2})/',(string)$t,$m)?((int)$m[1]*60+(int)$m[2]):0;
        $cat=(int)$p->getPost('category');
        $amen=$p->getPost('amenities'); $amen=is_array($amen)?array_map('strval',array_map('intval',$amen)):[];

        // ---- validation ------------------------------------------------
        // Until now this form took anything: "99581" as a phone, an address
        // with no @ as an email. Both went straight onto the public page.
        $errors = [];
        $name   = trim((string)$p->getPost('name'));
        $phone  = trim((string)$p->getPost('phone'));
        $email  = trim((string)$p->getPost('email'));
        $site   = trim((string)$p->getPost('website'));
        if ($p->getPost('name') !== null && mb_strlen($name) < 2) { $errors[]='Business name is required.'; }
        if ($phone !== '' && ! hi_valid_phone($phone))  { $errors[]='Phone must be a 10-digit mobile or a landline with STD code. Separate two numbers with a comma.'; }
        if ($email !== '' && ! hi_valid_email($email))  { $errors[]='“'.$email.'” is not a valid email address.'; }
        if ($site  !== '' && ! hi_valid_website($site)) { $errors[]='“'.$site.'” is not a valid website address.'; }
        if ($errors) {
            session()->setFlashdata('errors', $errors);
            return redirect()->to('/admin/listing/'.($id ?: 'new'))->withInput();
        }
        if ($site !== '') { $site = hi_normalise_url($site); }

        // social profiles — stored as the JSON object live already uses
        $social = [];
        foreach (['facebook','instagram','twitter','linkedin','youtube','whatsapp'] as $k) {
            $v = trim((string)$p->getPost('social_'.$k));
            if ($v === '') { continue; }
            $social[$k] = $k === 'whatsapp' ? preg_replace('/[^0-9+]/','',$v) : hi_normalise_url($v);
        }

        $data=[
            'name'=>trim((string)$p->getPost('name')),
            'listing_type'=>trim((string)$p->getPost('listing_type'))?:'listing',
            'categories'=>json_encode($cat?[(string)$cat]:[]),
            'locality_id'=>(int)$p->getPost('locality_id'),
            'address'=>trim((string)$p->getPost('address')),
            'phone'=>$phone,
            'email'=>$email,
            'website'=>$site,
            'description'=>trim((string)$p->getPost('description')),
            // everything the public page shows but the form never offered
            'area_id'=>(int)$p->getPost('area_id') ?: null,
            'head_point'=>trim((string)$p->getPost('head_point')),
            'social'=>json_encode($social),
            'video_url'=>trim((string)$p->getPost('video_url')),
            'video_provider'=>hi_video_provider((string)$p->getPost('video_url')),
            'price_range'=>trim((string)$p->getPost('price_range')) ?: 'no',
            'tags'=>trim((string)$p->getPost('tags')),
            'seo_title'=>trim((string)$p->getPost('seo_title')),
            'meta_description'=>trim((string)$p->getPost('meta_description')),
            'seo_meta_tags'=>trim((string)$p->getPost('seo_meta_tags')),
            // stored as numbers — these end up inside a maps URL, and a free
            // text value would flow straight into the markup
            'latitude'=>is_numeric(trim((string)$p->getPost('latitude'))) ? (float)$p->getPost('latitude') : null,
            'longitude'=>is_numeric(trim((string)$p->getPost('longitude'))) ? (float)$p->getPost('longitude') : null,
            'amenities'=>json_encode($amen),
            'is_featured'=>$p->getPost('is_featured')?1:0,
            'is_verified'=>$p->getPost('is_verified')?1:0,
            'status'=>trim((string)$p->getPost('status'))?:'active',
        ];
        helper('property');
        if (! hi_ensure_verified_column($db ?? null)) { unset($data['is_verified']); }
        // Only touch fields the form actually submitted. Without this a partial
        // POST silently blanks phone/address/email and the rest.
        if ($id) {
            foreach (['name','listing_type','locality_id','address','phone','email','website',
                      'description','latitude','longitude','status','area_id','head_point',
                      'video_url','price_range','tags','seo_title','meta_description','seo_meta_tags'] as $fld) {
                if ($p->getPost($fld) === null) { unset($data[$fld]); }
            }
            if ($p->getPost('video_url') === null)       { unset($data['video_provider']); }
            if ($p->getPost('category') === null)        { unset($data['categories']); }
            if ($p->getPost('amenities') === null)       { unset($data['amenities']); }
            // social inputs all share one column, so only rewrite it when the
            // form that carries them was actually submitted
            if ($p->getPost('social_facebook') === null) { unset($data['social']); }
            // a checkbox is absent when unticked, so only honour it if the form was really shown
            if ($p->getPost('status') === null)          { unset($data['is_featured']); }
            if ($p->getPost('status') === null)          { unset($data['is_verified']); }
        }

        // ---- opening hours, one row per day (same shape the wizard writes) ---
        if ($p->getPost('mon_open') !== null || $p->getPost('mon_closed') !== null) {
            $same = (bool) $p->getPost('same_all_week');
            $week = [];
            foreach (array_keys(hi_days()) as $d) {
                $src  = $same ? 'mon' : $d;
                if (! $same && $p->getPost($d.'_closed')) { $week[$d] = null; continue; }
                $ranges = [];
                foreach (['','2_'] as $slot) {
                    $o = $tomin($p->getPost($src.'_'.$slot.'open'));
                    $c = $tomin($p->getPost($src.'_'.$slot.'close'));
                    if ($o && $c && $o !== $c) { $ranges[] = [$o,$c]; }
                }
                $week[$d] = $ranges ?: null;
            }
            $first = null; foreach ($week as $wk) { if ($wk) { $first = $wk[0]; break; } }
            // only write the column when it is really there — including it when
            // it is not makes the whole UPDATE fail, and with DBDebug off that
            // failure is silent, so nothing on the form saves at all
            helper('hours');
            if (hi_ensure_hours_column($db)) { $data['hours_json'] = json_encode($week); }
            $data['opened_minutes'] = $first[0] ?? 0;
            $data['closed_minutes'] = $first[1] ?? 0;
        }

        // photo uploads (append to existing)
        $existing=[]; if($id){ $cur=$db->table('listing')->where('id',$id)->get()->getRowArray(); $ph=json_decode($cur['photos']??'[]',true); $existing=is_array($ph)?$ph:[]; }
        $keep=$p->getPost('keep'); if(is_array($keep)){ $existing=array_values(array_intersect($existing,$keep)); }
        foreach(($p->getFileMultiple('photos')??[]) as $file){
            if($file && $file->isValid() && !$file->hasMoved() && in_array($file->getExtension(),['jpg','jpeg','png','webp','gif'],true)){
                $dir=FCPATH.'uploads'.DIRECTORY_SEPARATOR.'listings'.DIRECTORY_SEPARATOR.($id?:'new');
                if(!is_dir($dir)) @mkdir($dir,0777,true);
                $nm=$file->getRandomName(); $file->move($dir,$nm);
                $existing[]='uploads/listings/'.($id?:'new').'/'.$nm;
            }
        }
        // manually entered paths / URLs (one per line)
        $paths=(string)$p->getPost('photo_paths');
        if(trim($paths)!==''){
            foreach(preg_split('/[\r\n,]+/',$paths) as $ph){
                $ph=trim($ph);
                if($ph===''){ continue; }
                if(preg_match('~^https?://~i',$ph)){ $existing[]=$ph; continue; }
                $ph=ltrim(str_replace('\\','/',$ph),'/');
                if(str_contains($ph,'..')){ continue; }                 // no traversal
                if(!preg_match('~\.(jpe?g|png|webp|gif|svg)$~i',$ph)){ continue; }
                $existing[]=$ph;
            }
        }
        $existing=array_values(array_unique($existing));
        $existing=array_slice($existing,0,10);
        $data['photos']=json_encode($existing);
        // the admin picks the cover; falling back to the first photo only when
        // they have not, or when the one they picked has just been removed
        $pick=(string)$p->getPost('cover');
        if($existing){ $data['listing_cover']=in_array($pick,$existing,true)?$pick:$existing[0]; }

        if($id>0){ $db->table('listing')->where('id',$id)->update($data); }
        else { $data['user_id']=(int)session()->get('uid'); $data['date_added']=date('Y-m-d'); $db->table('listing')->insert($data); $id=(int)$db->insertID(); }

        // the property side, when this listing is one
        if(($data['listing_type'] ?? $p->getPost('listing_type')) === 'property'){
            helper('property');
            $in=[];
            foreach(hi_property_columns() as $col){
                if($p->getPost($col) !== null || in_array($col,['meals','boundary'],true)){ $in[$col]=$p->getPost($col); }
            }
            hi_property_save($id, $in);
        }
        session()->setFlashdata('saved','Listing saved.');
        return redirect()->to('/admin/listing/'.$id);
    }

    public function deleteListing(int $id){
        if(!$this->guard()){ return redirect()->to('/login'); }
        \Config\Database::connect()->table('listing')->where('id',$id)->delete();
        session()->setFlashdata('saved','Listing deleted.');
        return redirect()->to('/admin/listings');
    }

    public function crud(string $entity){
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $act=$this->request->getPost('act'); $id=(int)$this->request->getPost('id');
        $map=['categories'=>'category','areas'=>'locality','amenities'=>'amenities'];
        $table=$map[$entity]??null; if(!$table){ return redirect()->to('/admin'); }
        if($act==='delete'){ $db->table($table)->where('id',$id)->delete(); if($entity==='categories'){ $db->table('category')->where('parent',$id)->delete(); } }
        else {
            $slug=strtolower(preg_replace('/[^a-z0-9]+/i','-',trim((string)$this->request->getPost('name'))));
            $data=['name'=>trim((string)$this->request->getPost('name'))];
            if($db->fieldExists('slug',$table)){ $data['slug']=trim((string)$this->request->getPost('slug'))?:$slug; }
            if($entity==='categories'&&$db->fieldExists('parent',$table)){ $data['parent']=(int)$this->request->getPost('parent'); }
            if($entity==='amenities'&&$db->fieldExists('icon',$table)){ $data['icon']=trim((string)$this->request->getPost('icon')); }
            if($id>0){ $db->table($table)->where('id',$id)->update($data); } else { $db->table($table)->insert($data); }
        }
        session()->setFlashdata('saved','Saved.');
        return redirect()->to('/admin/'.$entity);
    }

    /** Create, edit or delete a package. */
    public function savePackage()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        $id=(int)$this->request->getPost('id');

        if($this->request->getPost('act')==='delete'){
            $db->table('package')->where('id',$id)->delete();
            session()->setFlashdata('saved','Package deleted. Anyone already on it keeps it until it expires.');
            return redirect()->to('/admin/packages');
        }

        $name=trim((string)$this->request->getPost('name'));
        if($name===''){ session()->setFlashdata('saved','A package needs a name.'); return redirect()->to('/admin/packages'); }

        $d=[
            'name'                 => $name,
            'price'                => max(0,(int)$this->request->getPost('price')),
            'validity'             => max(1,(int)$this->request->getPost('validity')),
            'number_of_listings'   => max(0,(int)$this->request->getPost('number_of_listings')),
            'featured'             => $this->request->getPost('featured') ? 1 : 0,
            'is_recommended'       => $this->request->getPost('is_recommended') ? 1 : 0,
            'description'          => trim((string)$this->request->getPost('description')),
        ];
        if($db->fieldExists('ability_to_add_video','package')){ $d['ability_to_add_video']=$this->request->getPost('ability_to_add_video')?1:0; }

        // only one package can carry the recommended badge
        if($d['is_recommended']){ $db->table('package')->update(['is_recommended'=>0]); }

        if($id>0){ $db->table('package')->where('id',$id)->update($d); }
        else { $db->table('package')->insert($d); }

        session()->setFlashdata('saved','Package saved.');
        return redirect()->to('/admin/packages');
    }

    /** Record a payment taken outside the site (cash, bank transfer, UPI). */
    public function offlinePayment()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();

        $uid=(int)$this->request->getPost('user_id');
        $pid=(int)$this->request->getPost('package_id');
        $pkg=$db->table('package')->where('id',$pid)->get()->getRowArray();
        $usr=$db->table('user')->where('id',$uid)->get()->getRowArray();
        if(!$pkg||!$usr){ session()->setFlashdata('saved','Pick both a member and a package.'); return redirect()->to('/admin/revenue'); }

        $paid=$this->request->getPost('amount_paid')!==null && $this->request->getPost('amount_paid')!==''
            ? (int)$this->request->getPost('amount_paid') : (int)$pkg['price'];

        $db->table('package_purchased_history')->insert([
            'user_id'        => $uid,
            'package_id'     => $pid,
            'purchase_date'  => time(),
            'expired_date'   => time() + max(1,(int)$pkg['validity'])*86400,
            'amount_paid'    => $paid,
            'payment_method' => trim((string)$this->request->getPost('payment_method')) ?: 'offline',
        ]);
        if(!empty($pkg['featured'])){ $db->table('listing')->where('user_id',$uid)->update(['is_featured'=>1]); }

        (new \App\Libraries\Mailer())->queue($usr['email'], $usr['name'] ?? 'there',
            'Your ' . $pkg['name'] . ' plan is active — HiNoida',
            '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;color:#33405a">'
            . '<h2 style="color:#101a2c">Plan activated</h2>'
            . '<p>Hi ' . esc((string)($usr['name'] ?? 'there')) . ',</p>'
            . '<p>Your <b>' . esc($pkg['name']) . '</b> plan is active for ' . (int)$pkg['validity'] . ' days'
            . ' — ' . (int)$pkg['number_of_listings'] . ' listing(s)' . (! empty($pkg['featured']) ? ', featured placement' : '') . '.</p>'
            . '<p><a href="' . base_url('dashboard/listings') . '" style="background:#014dda;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">Open my dashboard</a></p></div>');

        session()->setFlashdata('saved','Payment recorded — ' . esc($usr['name'] ?? '') . ' is now on ' . esc($pkg['name']) . '.');
        return redirect()->to('/admin/revenue');
    }

    /**
     * Bulk-import listings from a CSV.
     *
     * Columns are matched by header name, so column order does not matter and
     * unknown columns are ignored. Only Name is required. A row whose name and
     * phone already exist is updated rather than duplicated.
     */
    public function importListings()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }

        $file=$this->request->getFile('csv');
        if(!$file || !$file->isValid()){
            session()->setFlashdata('saved','Choose a CSV file to import.');
            return redirect()->to('/admin/import');
        }
        if(!in_array(strtolower((string)$file->getClientExtension()),['csv','txt'],true)){
            session()->setFlashdata('saved','That is not a CSV file.');
            return redirect()->to('/admin/import');
        }

        $fh=fopen($file->getTempName(),'r');
        if(!$fh){ session()->setFlashdata('saved','The file could not be read.'); return redirect()->to('/admin/import'); }

        $head=fgetcsv($fh);
        if(!$head){ fclose($fh); session()->setFlashdata('saved','The file is empty.'); return redirect()->to('/admin/import'); }
        // strip a UTF-8 BOM off the first header, or nothing matches
        $head[0]=preg_replace('/^\x{FEFF}/u','',(string)$head[0]);
        $head=array_map(static fn($h)=>strtolower(trim((string)$h)),$head);

        $db=\Config\Database::connect();
        $locs=[]; foreach($db->table('locality')->select('id, name')->get()->getResultArray() as $l){ $locs[strtolower($l['name'])]=(int)$l['id']; }
        $cats=[]; foreach($db->table('category')->select('id, name')->get()->getResultArray() as $c){ $cats[strtolower($c['name'])]=(int)$c['id']; }

        $added=0; $updated=0; $skipped=0; $line=1; $problems=[];
        while(($r=fgetcsv($fh))!==false){
            $line++;
            $row=[];
            foreach($head as $i=>$h){ $row[$h]=isset($r[$i])?trim((string)$r[$i]):''; }

            $name=$row['name']??'';
            if($name===''){ $skipped++; if(count($problems)<5){ $problems[]="line {$line}: no name"; } continue; }

            $d=['name'=>$name];
            foreach(['phone','email','address','website','description'] as $k){
                if(($row[$k]??'')!==''){ $d[$k]=$row[$k]; }
            }
            if(($row['locality']??'')!==''){
                $key=strtolower($row['locality']);
                if(isset($locs[$key])){ $d['locality_id']=$locs[$key]; }
                elseif(count($problems)<5){ $problems[]="line {$line}: unknown area “{$row['locality']}”"; }
            }
            if(($row['category']??'')!==''){
                $key=strtolower($row['category']);
                if(isset($cats[$key])){ $d['categories']=json_encode([(string)$cats[$key]]); }
                elseif(count($problems)<5){ $problems[]="line {$line}: unknown category “{$row['category']}”"; }
            }

            $status=strtolower($row['status']??'');
            $d['status']=in_array($status,['active','pending','rejected'],true)?$status:'pending';

            // same name AND same phone means it is the same business
            $dupe=null;
            if(!empty($d['phone'])){
                $dupe=$db->table('listing')->where('name',$name)->where('phone',$d['phone'])->get()->getRowArray();
            }
            if($dupe){ $db->table('listing')->where('id',(int)$dupe['id'])->update($d); $updated++; }
            else {
                $d['user_id']=(int)session()->get('uid');
                if($db->fieldExists('listing_type','listing')){ $d['listing_type']='general'; }
                $db->table('listing')->insert($d); $added++;
            }
        }
        fclose($fh);

        $msg="Import finished — {$added} added, {$updated} updated, {$skipped} skipped.";
        if($problems){ $msg.=' Issues: '.implode('; ',$problems).'.'; }
        session()->setFlashdata('saved',$msg);
        return redirect()->to('/admin/listings');
    }

    /** A CSV with the right headers and one example row. */
    public function importTemplate()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        return $this->csv('hinoida-import-template.csv',
            ['name','phone','email','address','locality','category','website','description','status'],
            [['Sharma Sweets','9812345678','hello@sharmasweets.in','Shop 12, Sector 18 Market',
              'Sector 18','Restaurants','https://sharmasweets.in','Family sweet shop since 1994','active']]);
    }

    /** Every recorded payment, newest first. */
    public function exportRevenue()
    {
        if(!$this->guard()){ return redirect()->to('/login'); }
        $db=\Config\Database::connect();
        if(!$db->tableExists('package_purchased_history')){ return $this->csv('revenue.csv',['Date'],[]); }
        $rows=$db->table('package_purchased_history h')
            ->select('h.*, p.name AS package_name, u.name AS user_name, u.email')
            ->join('package p','p.id = h.package_id','left')
            ->join('user u','u.id = h.user_id','left')
            ->orderBy('h.purchase_date','DESC')->get()->getResultArray();
        return $this->csv('revenue.csv',
            ['Date','Member','Email','Package','Amount','Method','Expires'],
            array_map(static fn($r)=>[
                $r['purchase_date'] ? date('Y-m-d', (int)$r['purchase_date']) : '',
                $r['user_name'] ?? '', $r['email'] ?? '', $r['package_name'] ?? '',
                (int)($r['amount_paid'] ?? 0), $r['payment_method'] ?? '',
                $r['expired_date'] ? date('Y-m-d', (int)$r['expired_date']) : '',
            ], $rows));
    }

    /** CSV report builder — mirrors the exports the original site offered. */
    private function csv(string $file, array $head, array $rows)
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, $head);
        foreach ($rows as $r) { fputcsv($out, $r); }
        rewind($out); $csv = stream_get_contents($out); fclose($out);
        return $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $file . '"')
            ->setBody("﻿" . $csv);
    }

    /** Every category with its parent and live listing count. */
    public function exportCategories()
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $cm = new \App\Models\CategoryModel();
        $db = \Config\Database::connect();
        $rows = [];
        foreach ($db->table('category c')->select('c.id, c.name, c.slug, p.name AS parent')
            ->join('category p', 'p.id = c.parent', 'left')->orderBy('p.name', 'ASC')
            ->orderBy('c.name', 'ASC')->get()->getResultArray() as $c) {
            $rows[] = [$c['id'], $c['parent'] ?: '(top level)', $c['name'], $c['slug'], $cm->countForCategory((int) $c['id'])];
        }
        return $this->csv('hinoida-categories.csv', ['ID','Parent','Category','Slug','Listings'], $rows);
    }

    /** Per-member listing counts and engagement. */
    public function exportUsers()
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect();
        $rows = [];
        foreach ($db->table('user')->orderBy('id', 'DESC')->get()->getResultArray() as $u) {
            $uid = (int) $u['id'];
            $ids = array_map(static fn ($r) => (int) $r['id'], $db->table('listing')->select('id')->where('user_id', $uid)->get()->getResultArray());
            $role = [1=>'Admin',2=>'Business',3=>'Customer'][(int) ($u['role_id'] ?? 3)] ?? 'Customer';
            $rows[] = [$uid, $u['name'] ?? '', $u['email'] ?? '', $role,
                empty($u['is_verified']) ? 'No' : 'Yes', count($ids),
                $ids && $db->tableExists('review') ? $db->table('review')->whereIn('listing_id', $ids)->countAllResults() : 0,
                $ids && $db->tableExists('booking') ? $db->table('booking')->whereIn('listing_id', $ids)->countAllResults() : 0];
        }
        return $this->csv('hinoida-members.csv', ['ID','Name','Email','Role','Verified','Listings','Reviews','Bookings'], $rows);
    }

    /** Leads: enquiries and bookings in one sheet. */
    public function exportLeads()
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect(); $rows = [];
        if ($db->tableExists('discover_enquiry')) {
            foreach ($db->table('discover_enquiry e')->select('e.*, li.name AS listing')->join('listing li', 'li.id = e.listing_id', 'left')->orderBy('e.id', 'DESC')->get()->getResultArray() as $e) {
                $rows[] = ['Enquiry', $e['listing'] ?? '', $e['name'] ?? '', $e['phone'] ?? '', $e['email'] ?? '', $e['message'] ?? '', $e['status'] ?? 'new', $e['created_at'] ?? ''];
            }
        }
        if ($db->tableExists('booking')) {
            foreach ($db->table('booking b')->select('b.*, li.name AS listing')->join('listing li', 'li.id = b.listing_id', 'left')->orderBy('b.id', 'DESC')->get()->getResultArray() as $x) {
                $rows[] = ['Booking', $x['listing'] ?? '', $x['name'] ?? '', $x['phone'] ?? '', $x['email'] ?? '',
                    trim(($x['booking_date'] ?? '') . ' ' . ($x['booking_time'] ?? '') . ' ' . ($x['message'] ?? '')), $x['status'] ?? 'pending', $x['created_at'] ?? ''];
            }
        }
        return $this->csv('hinoida-leads.csv', ['Type','Listing','Name','Phone','Email','Details','Status','Received'], $rows);
    }

    /** Reviews with their listing and any owner reply. */
    public function exportReviews()
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect(); $rows = [];
        if ($db->tableExists('review')) {
            foreach ($db->table('review r')->select('r.*, r.' . hi_review_pk($db) . ' AS id, li.name AS listing')->join('listing li', 'li.id = r.listing_id', 'left')->orderBy('r.' . hi_review_pk($db), 'DESC')->get()->getResultArray() as $r) {
                $rows[] = [$r['listing'] ?? '', $r['reviewer_name'] ?? '', $r['review_rating'] ?? '', $r['review_comment'] ?? '', $r['owner_reply'] ?? '',
                    ! empty($r['timestamp']) ? date('Y-m-d', (int) $r['timestamp']) : ''];
            }
        }
        return $this->csv('hinoida-reviews.csv', ['Listing','Reviewer','Rating','Review','Owner reply','Date'], $rows);
    }

    /**
     * Bulk SEO edit — the original's "SEO report" screen (Admin::seoreport),
     * which is an export/import pair rather than an audit: you download every
     * listing's SEO fields, edit them in a spreadsheet, and upload the file
     * back. Columns match the original's exactly so its CSVs still work.
     */
    public function seoBulk()
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect();
        hi_ensure_tables($db);

        return view('admin/seobulk', [
            'title'   => 'SEO — bulk edit',
            'total'   => $db->table('listing')->countAllResults(),
            'noTitle' => $db->table('listing')->groupStart()->where('seo_title', null)->orWhere('seo_title', '')->groupEnd()->countAllResults(),
            'noDesc'  => $db->table('listing')->groupStart()->where('meta_description', null)->orWhere('meta_description', '')->groupEnd()->countAllResults(),
            'done'    => session()->getFlashdata('done'),
            'err'     => session()->getFlashdata('err'),
        ]);
    }

    public function exportSeo()
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $db   = \Config\Database::connect();
        hi_ensure_tables($db);
        $rows = [];
        foreach ($db->table('listing')->select('id, name, tags, seo_title, seo_meta_tags, meta_description')->orderBy('id', 'ASC')->get()->getResultArray() as $r) {
            $rows[] = [$r['id'], $r['name'] ?? '', $r['tags'] ?? '', $r['seo_title'] ?? '', $r['seo_meta_tags'] ?? '', $r['meta_description'] ?? ''];
        }

        return $this->csv('hinoida-seo.csv', ['ID','Name','Tags','Seo Title','Meta Tags','Meta Description'], $rows);
    }

    public function importSeo()
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $file = $this->request->getFile('csv_file');
        if (! $file || ! $file->isValid()) {
            session()->setFlashdata('err', 'Choose a CSV file to upload.');

            return redirect()->to('/admin/seo-bulk');
        }
        if (strtolower($file->getClientExtension()) !== 'csv') {
            session()->setFlashdata('err', 'Only CSV files are allowed.');

            return redirect()->to('/admin/seo-bulk');
        }

        $db = \Config\Database::connect();
        $fh = fopen($file->getTempName(), 'r');
        if ($fh === false) {
            session()->setFlashdata('err', 'That file could not be read.');

            return redirect()->to('/admin/seo-bulk');
        }

        $updated = $skipped = 0;
        $first   = true;
        while (($d = fgetcsv($fh, 4096, ',')) !== false) {
            if ($first) { $first = false; continue; }        // header row
            $lid = (int) ($d[0] ?? 0);
            // Only update rows that name an existing listing by id. The original
            // inserted a new listing when the id column was blank, which turns a
            // stray line in a spreadsheet into a live listing — so skip instead.
            if ($lid <= 0 || ! $db->table('listing')->where('id', $lid)->countAllResults()) { $skipped++; continue; }
            $db->table('listing')->where('id', $lid)->update([
                'tags'             => (string) ($d[2] ?? ''),
                'seo_title'        => (string) ($d[3] ?? ''),
                'seo_meta_tags'    => (string) ($d[4] ?? ''),
                'meta_description' => (string) ($d[5] ?? ''),
            ]);
            $updated++;
        }
        fclose($fh);

        session()->setFlashdata('done', $updated . ' listing' . ($updated === 1 ? '' : 's') . ' updated'
            . ($skipped ? ', ' . $skipped . ' row' . ($skipped === 1 ? '' : 's') . ' skipped (no matching id)' : '') . '.');

        return redirect()->to('/admin/seo-bulk');
    }
}
