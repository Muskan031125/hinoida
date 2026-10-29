<?php
namespace App\Controllers;
class Wizard extends BaseController
{
    private array $steps=['basics','media','amenities','details','contact','location','hours','seo','done'];

    /**
     * A flat has no opening time and no "free wifi", so a property walks a
     * shorter path: the specs step stands in for details, and amenities and
     * hours drop out entirely.
     */
    private array $propertySteps=['basics','media','specs','amenities','location','contact','seo','done'];

    /** The steps this listing actually walks. */
    private function stepsFor(?array $row): array
    {
        return ($row !== null && hi_is_property($row)) ? $this->propertySteps : $this->steps;
    }

    /** The step after $step, skipping 'details' when this listing has no detail table. */
    private function nextStep(string $step, ?array $row): string
    {
        $steps = $this->stepsFor($row);
        $i = array_search($step, $steps, true);
        if ($i === false) { return $steps[1] ?? 'done'; }
        $n = $steps[$i + 1] ?? 'done';
        if($n === 'details' && ($row === null || hi_detail_table_for($row) === '')){
            $n = $steps[$i + 2] ?? 'done';
        }

        return $n;
    }
    private function uid(){ return (int)session()->get('uid'); }

    /** Admins may edit anything; everyone else only their own listings. */
    private function owns($db, int $id): bool
    {
        if((int)session()->get('role')===1){ return true; }

        return (bool)$db->table('listing')->where('id',$id)->where('user_id',$this->uid())->countAllResults();
    }

    public function step(string $step='basics', int $id=0){
        if(!$this->uid()){ return redirect()->to('/login?redirect='.urlencode(current_url())); }
        if(!in_array($step,array_merge($this->steps,$this->propertySteps),true)){ $step='basics'; }
        $db=\Config\Database::connect();
        $row=$id?$db->table('listing')->where('id',$id)->where('user_id',$this->uid())->get()->getRowArray():null;
        if($step!=='basics' && !$row){ return redirect()->to('/list'); }
        // A property is not a second shopfront — a dealer with twenty flats is
        // still one business, so properties do not eat the listing allowance.
        $wantsProperty = $this->request->getGet('kind') === 'property';
        if(!$id && !$wantsProperty && !hi_can_add_listing($this->uid(), (int)session()->get('role'))){
            session()->setFlashdata('err','Your '.hi_plan($this->uid())['name'].' plan covers '
                .hi_plan_limit($this->uid()).' listing'.(hi_plan_limit($this->uid())===1?'':'s')
                .'. Upgrade to add another.');
            return redirect()->to(hi_page('pricing'));
        }
        // a brand-new property has no row yet, so the ?kind flag carries the choice
        $isProp = $row !== null ? hi_is_property($row) : $wantsProperty;
        $data=['title'=>($isProp?'List your property':'List your business').' | HiNoida',
               'steps'=>$isProp ? $this->propertySteps : $this->steps,
               'step'=>$step,'id'=>$id,'row'=>$row,'isProp'=>$isProp,
               'err'=>session()->getFlashdata('err')];
        if($step==='specs'){
            hi_ensure_property_table($db);
            $data['prop'] = $row ? hi_property_of((int)$row['id']) : [];
        }
        if($step==='basics'){ $data['cats']=$db->table('category')->orderBy('name','ASC')->get()->getResultArray(); $data['locs']=$db->table('locality')->orderBy('name','ASC')->get()->getResultArray(); }
        if($step==='amenities'){ helper('property'); $data['amen']=hi_amenity_choices($row,$db); $cur=json_decode($row['amenities']??'',true); $data['cur']=is_array($cur)?array_map('intval',$cur):[]; }
        if($step==='media'){ $ph=json_decode($row['photos']??'[]',true); $data['photos']=is_array($ph)?$ph:[]; }
        if($step==='location'){ $data['locs']=$db->table('locality')->orderBy('name','ASC')->get()->getResultArray(); }
        if($step==='details'){
            $t = $row ? hi_detail_table_for($row) : '';
            // a plumber has no menu — send them straight on rather than showing an empty step
            if($t === ''){ return redirect()->to('/list/'.$this->nextStep('details',$row).'/'.$id); }
            hi_ensure_tables($db);
            $data['dtable'] = $t;
            $data['dmeta']  = hi_detail_meta($t);
            $data['drows']  = hi_details_for($row);
        }
        return view('frontend/wizard',$data);
    }

    public function save(string $step, int $id){
        if(!$this->uid()){ return redirect()->to('/login?redirect='.urlencode(current_url())); }
        $db=\Config\Database::connect(); $p=$this->request; $next=$id;

        // One guard for every step. basics and media checked ownership inline
        // but amenities, contact and hours did not, so any signed-in user could
        // overwrite another business's details by posting to its id.
        if($id > 0 && !$this->owns($db, $id)){
            session()->setFlashdata('err','That listing is not yours.');
            return redirect()->to('/dashboard/listings');
        }
        if($step==='basics'){
            $name=trim((string)$p->getPost('name')); $cat=(int)$p->getPost('category');
            if($name===''||!$cat){ session()->setFlashdata('err','Enter a name and category.'); return redirect()->to('/list'); }
            // a phone number that is not one is worse than none — it is the
            // thing people tap on the card
            $ph=trim((string)$p->getPost('phone'));
            $em=trim((string)$p->getPost('email'));
            if($ph!=='' && !hi_valid_phone($ph)){
                session()->setFlashdata('err','Enter a 10-digit mobile or a landline with its STD code.');
                return redirect()->to($id ? '/list/basics/'.$id : '/list')->withInput();
            }
            if($em!=='' && !hi_valid_email($em)){
                session()->setFlashdata('err','That email address does not look right.');
                return redirect()->to($id ? '/list/basics/'.$id : '/list')->withInput();
            }
            $d=['name'=>$name,'categories'=>json_encode([(string)$cat]),'locality_id'=>(int)$p->getPost('locality'),'address'=>trim((string)$p->getPost('address')),'phone'=>trim((string)$p->getPost('phone')),'email'=>trim((string)$p->getPost('email')),'description'=>trim((string)$p->getPost('description'))];
            // the four types the detail step understands, plus "property" and "general"
            $lt=strtolower(trim((string)$p->getPost('listing_type')));
            if(in_array($lt,['restaurant','hotel','beauty','shop','property','general'],true)){ $d['listing_type']=$lt; }
            if($id){
                $cur=$db->table('listing')->where('id',$id)->get()->getRowArray() ?: [];
                // A live listing's identity — name, phone, address, category —
                // is held for admin approval, as the original does. Everything
                // else, and every draft, saves straight away.
                if(hi_needs_approval($cur,(int)session()->get('role'))){
                    // Split the form: identity fields wait for approval, the rest
                    // (listing_type) still saves — otherwise picking a business
                    // type on a live listing would silently do nothing.
                    $held=hi_change_fields();
                    $direct=array_diff_key($d,array_flip(array_values($held)));
                    if($direct){ $db->table('listing')->where('id',$id)->where('user_id',$this->uid())->update($direct); }
                    $n=hi_queue_change($db,$cur,$d,$this->uid());
                    if($n){
                        session()->setFlashdata('err',$n.' change'.($n===1?'':'s').' sent for approval — the listing stays as it is until an admin reviews it.');
                        return redirect()->to('/list/media/'.$id);
                    }
                }else{
                    $db->table('listing')->where('id',$id)->where('user_id',$this->uid())->update($d);
                }
                $next=$id;
            }
            else {
                // re-checked on save, not just on the form — the limit is the rule, not the UI.
                // Properties are exempt: a dealer with twenty flats is still one
                // business, and their business listing is what the plan covers.
                if(($d['listing_type'] ?? '') !== 'property'
                   && !hi_can_add_listing($this->uid(), (int)session()->get('role'))){
                    session()->setFlashdata('err','Your plan does not cover another listing.');
                    return redirect()->to(hi_page('pricing'));
                }
                $d['user_id']=$this->uid(); $d['status']='pending';
                $d['listing_type']=$d['listing_type'] ?? 'general';   // keep what they picked
                $db->table('listing')->insert($d); $next=(int)$db->insertID();
            }
            $fresh = $db->table('listing')->where('id',$next)->get()->getRowArray();
            return redirect()->to('/list/'.$this->nextStep('basics',$fresh).'/'.$next);
        }
        if($step==='media'){
            $row=$db->table('listing')->where('id',$id)->where('user_id',$this->uid())->get()->getRowArray();
            $photos=json_decode($row['photos']??'[]',true); $photos=is_array($photos)?$photos:[];
            // remove any photos the user deleted
            $keep=$p->getPost('keep'); if(is_array($keep)){ $photos=array_values(array_intersect($photos,$keep)); }
            // handle new uploads
            $dir=FCPATH.'uploads'.DIRECTORY_SEPARATOR.'listings'.DIRECTORY_SEPARATOR.$id;
            foreach(($p->getFileMultiple('photos')??[]) as $file){
                if($file && $file->isValid() && !$file->hasMoved()){
                    if(!in_array($file->getExtension(),['jpg','jpeg','png','webp','gif'],true)) continue;
                    if($file->getSize()>5*1024*1024) continue; // 5MB cap
                    if(!is_dir($dir)) @mkdir($dir,0777,true);
                    $nm=$file->getRandomName();
                    $file->move($dir,$nm);
                    $photos[]='uploads/listings/'.$id.'/'.$nm;
                }
            }
            $photos=array_slice($photos,0,10);
            // the column was missing entirely, so saving this step 500'd
            foreach(['video_url'=>'TEXT','video_provider'=>'VARCHAR(30)'] as $col=>$type){
                if(!$db->fieldExists($col,'listing')){
                    try { $db->query('ALTER TABLE listing ADD COLUMN '.$col.' '.$type); } catch(\Throwable $e){}
                }
            }
            $vurl=trim((string)$p->getPost('video_url'));
            // only a plan that includes video may set one
            if($vurl!=='' && !hi_can_add_video($this->uid(), (int)session()->get('role'))){ $vurl=''; }
            $upd=['photos'=>json_encode($photos),'video_url'=>$vurl,'video_provider'=>hi_video_provider($vurl)];
            // the owner picks the cover; fall back to the first photo when they
            // have not, or when the one they picked has just been removed
            $pick=(string)$p->getPost('cover');
            if($photos){ $upd['listing_cover']=in_array($pick,$photos,true) ? $pick : ($row['listing_cover'] ?? ''); }
            if($photos && empty($upd['listing_cover'])) $upd['listing_cover']=$photos[0];
            $db->table('listing')->where('id',$id)->update($upd);
            return redirect()->to('/list/'.$this->nextStep('media',$row).'/'.$id);
        }
        if($step==='amenities'){
            $a=$p->getPost('amenities');
            $db->table('listing')->update(['amenities'=>json_encode(is_array($a)?array_map('strval',array_map('intval',$a)):[])],['id'=>$id]);
            $row=$db->table('listing')->where('id',$id)->get()->getRowArray();
            return redirect()->to('/list/'.$this->nextStep('amenities',$row).'/'.$id);
        }
        if($step==='details'){
            $row=$db->table('listing')->where('id',$id)->get()->getRowArray();
            $t=$row?hi_detail_table_for($row):'';
            if($t===''){ return redirect()->to('/list/contact/'.$id); }
            hi_ensure_tables($db);
            $fields=array_keys(hi_detail_meta($t)['fields']);
            $names=(array)$p->getPost('d_name');
            // rewritten wholesale: the rows posted are the rows kept, so deleting
            // one in the form actually removes it
            $db->table($t)->where('listing_id',$id)->delete();
            $order=0;
            foreach($names as $k=>$nm){
                $nm=trim((string)$nm);
                if($nm===''){ continue; }   // blank name = an unused repeater row
                $rec=['listing_id'=>$id];
                // sort_order is our addition; the live tables do not have it
                if($db->fieldExists('sort_order',$t)){ $rec['sort_order']=$order; }
                $order++;
                foreach($fields as $f){
                    if($f==='name'){ $rec['name']=$nm; continue; }
                    $vals=(array)$p->getPost('d_'.$f);
                    $rec[$f]=trim((string)($vals[$k]??''));
                }
                $old=(array)$p->getPost('d_photo');
                $rec['photo']=trim((string)($old[$k]??''));
                $db->table($t)->insert($rec);
            }
            return redirect()->to('/list/contact/'.$id);
        }
        if($step==='specs'){
            hi_ensure_property_table($db);
            $in = [];
            foreach(hi_property_columns() as $col){
                if($p->getPost($col) !== null || in_array($col,['meals','boundary'],true)){
                    $in[$col] = $p->getPost($col);
                }
            }
            hi_property_save($id, $in);
            // save() has no $row of its own — fetch it so nextStep() knows this
            // is a property and sends them to Location, not Details
            $prow = $db->table('listing')->where('id',$id)->get()->getRowArray();
            // keep the listing itself in step — the price drives the card and
            // the sort, so it is worth having on the row we already query
            $price = (float) preg_replace('/[^0-9.]/','', (string)$p->getPost('price'));
            if($price > 0 && $db->fieldExists('price_range','listing')){
                $db->table('listing')->where('id',$id)->update(['price_range'=>(string)$price]);
            }
            return redirect()->to('/list/'.$this->nextStep('specs',$prow).'/'.$id);
        }

        if($step==='contact'){
            $email = trim((string)$p->getPost('email'));
            $site  = trim((string)$p->getPost('website'));
            // the same rules the admin form uses — an owner could put anything
            // here before, and it went straight onto their public page
            $errs = [];
            if($email!=='' && !hi_valid_email($email)){ $errs[]='That email address does not look right.'; }
            if($site !=='' && !hi_valid_website($site)){ $errs[]='That website address does not look right.'; }
            if($errs){
                session()->setFlashdata('err', implode(' ', $errs));
                return redirect()->to('/list/contact/'.$id)->withInput();
            }
            $soc=[];
            foreach(['facebook','instagram','twitter','linkedin','youtube','whatsapp'] as $k){
                $v=trim((string)$p->getPost($k));
                if($v==='') { continue; }
                $soc[$k] = $k==='whatsapp' ? preg_replace('/[^0-9+]/','',$v) : hi_normalise_url($v);
            }
            $db->table('listing')->where('id',$id)->update([
                'email'   => $email,
                'website' => $site==='' ? '' : hi_normalise_url($site),
                'social'  => json_encode($soc),
            ]);
            return redirect()->to('/list/location/'.$id);
        }

        if($step==='location'){
            $num=static fn($v)=>is_numeric(trim((string)$v)) ? (float)$v : null;
            $db->table('listing')->where('id',$id)->update([
                'address'=>trim((string)$p->getPost('address')),
                'locality_id'=>(int)$p->getPost('locality'),
                'latitude'=>$num($p->getPost('latitude')),
                'longitude'=>$num($p->getPost('longitude')),
            ]);
            return redirect()->to('/list/hours/'.$id);
        }

        if($step==='hours'){
            $tomin=fn($t)=>preg_match('/^(\d{1,2}):(\d{2})$/',(string)$t,$m)?((int)$m[1]*60+(int)$m[2]):null;

            // "same every day" fills all seven from Monday's boxes
            $same = (bool)$p->getPost('same_all_week');
            $week = [];
            foreach(array_keys(hi_days()) as $d){
                $src  = $same ? 'mon' : $d;
                $shut = $same ? false : (bool)$p->getPost($d.'_closed');
                if($shut){ $week[$d]=null; continue; }
                $ranges=[];
                // second slot lets a shop close for lunch, as the original's
                // second_<day> columns did
                foreach(['','2_'] as $slot){
                    $o=$tomin($p->getPost($src.'_'.$slot.'open')); $c=$tomin($p->getPost($src.'_'.$slot.'close'));
                    if($o!==null && $c!==null && $o!==$c){ $ranges[]=[$o,$c]; }
                }
                $week[$d] = $ranges ?: null;
            }

            // keep the single pair in step with the first open day, so anything
            // still reading the old columns stays correct
            $first = null; foreach($week as $wk){ if($wk){ $first=$wk[0]; break; } }
            $set = ['opened_minutes' => $first[0] ?? 0, 'closed_minutes' => $first[1] ?? 0];
            // the ALTER this used to attempt fails on listing.update_at's legacy
            // default, so the column stayed missing and this whole update failed
            // silently — the owner's hours were never saved
            if (hi_ensure_hours_column($db)) { $set['hours_json'] = json_encode($week); }
            $db->table('listing')->where('id',$id)->update($set);
            return redirect()->to('/list/seo/'.$id);
        }

        if($step==='seo'){
            foreach(['seo_title','meta_description','tags'] as $col){
                if(!$db->fieldExists($col,'listing')){
                    try { $db->query('ALTER TABLE listing ADD COLUMN '.$col.' TEXT'); } catch(\Throwable $e){}
                }
            }
            $db->table('listing')->where('id',$id)->update([
                'seo_title'=>mb_substr(trim((string)$p->getPost('seo_title')),0,70),
                'meta_description'=>mb_substr(trim((string)$p->getPost('meta_description')),0,160),
                'tags'=>trim((string)$p->getPost('tags')),
            ]);
            return redirect()->to('/list/done/'.$id);
        }

        return redirect()->to('/list/done/'.$id);
    }
}
