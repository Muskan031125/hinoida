<?php
namespace App\Controllers;
use App\Models\CategoryModel;
use App\Models\ListingModel;
class Home extends BaseController
{
    public function index(): string
    {
        helper(['taxonomy','seo']);
        $catModel=new CategoryModel(); $listingModel=new ListingModel();
        $db=\Config\Database::connect();
        // Speed: the home page re-counted everything on every visit (12–15 s).
        // These numbers barely change, so keep them for a while.
        $cache=\Config\Services::cache();
        $remember=static function(string $key,int $ttl,callable $fn) use ($cache){
            $v=$cache->get($key);
            if ($v===null) { $v=$fn(); $cache->save($key,$v,$ttl); }
            return $v;
        };
        $cats=$remember('home_cats',600,static fn()=>$catModel->topLevel(300));
        $cats=array_values(array_filter($cats,static fn($c)=>$c['listing_count']>0));
        $groups=hi_bucket($cats);
        usort($groups,static fn($a,$b)=>$b['count']-$a['count']);
        $totalListings=$remember('home_total',600,static fn()=>$listingModel->where('status','active')->countAllResults());
        $topCats=(static function(array $c){
                usort($c,static fn($a,$b)=>(int)$b['listing_count']<=>(int)$a['listing_count']);
                usort($c,static fn($a,$b)=>(int)(stripos((string)$b['name'],'propert')===0)
                                       <=> (int)(stripos((string)$a['name'],'propert')===0));
                return array_slice($c,0,12);
            })($cats);

        // Popular listings per category, for the tabs on the home page:
        // the most-viewed places this month, split by the busiest categories.
        $trendingByCat=$remember('home_trending_cat2_v6',3600,static function() use ($db,$listingModel,$topCats){
            if (! $db->tableExists('profile_views')) { return []; }
            $since=date('Y-m-d H:i:s', strtotime('-30 days'));
            $views=[];
            $fetch=static function(?string $from) use ($db){
                $q=$db->table('profile_views')->select('listing_id, COUNT(*) AS n');
                if ($from!==null) { $q->where('created_at >=',$from); }
                $o=[]; foreach ($q->groupBy('listing_id')->orderBy('n','DESC')->limit(600)->get()->getResultArray() as $r) { $o[(int)$r['listing_id']]=(int)$r['n']; }
                return $o;
            };
            $views=$fetch($since);
            if (count($views)<60) { $views=$fetch(null); }   // a quiet month: fall back to all-time views
            if (! $views) { return []; }
            $rows=$listingModel->whereIn('id',array_keys($views))->where('status','active')->findAll();
            foreach ($rows as &$_r) { $_r['_views']=$views[(int)$_r['id']] ?? 0; } unset($_r);
            usort($rows,static fn($a,$b)=>$b['_views']<=>$a['_views']);
            $out=[];
            foreach (array_slice($topCats,0,7) as $c) {
                $cid=(int)$c['id']; $pick=[];
                foreach ($rows as $r) {
                    $ids=json_decode((string)($r['categories'] ?? ''),true);
                    if (! is_array($ids)) { preg_match_all('/\d+/',(string)($r['categories'] ?? ''),$_m); $ids=$_m[0]; }
                    $ids=array_map('intval',$ids);
                    if (in_array($cid,$ids,true)) { $pick[]=$r; if (count($pick)>=6) break; }
                }
                if (count($pick)>=3) { $out[]=['name'=>html_entity_decode((string)$c['name'],ENT_QUOTES|ENT_HTML5,'UTF-8'),'slug'=>$c['slug'],'rows'=>$listingModel->attachRatings($pick)]; }
            }
            return $out;
        });

        $news=$remember('home_news',600,static function() use ($db){
            $q=hi_content_q('blog',$db);
            $rows=hi_content_rows($q?$q->orderBy('added_date','DESC')->limit(60)->get()->getResultArray():[]);
            return ['latest'=>$rows,'cats'=>hi_content_cats($rows)];
        });

        return view('frontend/home',[
            'title'=>'HiNoida — Noida Business Directory | Restaurants, Hospitals, Shopping & More',
            'metaDescription'=>'HiNoida is Noida\'s local business directory. Find '.number_format($totalListings).'+ businesses — restaurants, hospitals, salons, real estate, shopping and more — with address, phone, timings, photos and reviews.',
            'canonical'=>base_url('/'),
            'groups'=>$groups,
            // flat "Explore Categories" grid — busiest first, as the original
            // site leads with. The grouped rows below stay for browsing by theme.
            // Property leads the grid whatever its count — it is the vertical this
            // directory is built around; the rest follow by how busy they are.
            'topCats'=>$topCats,
            'featured'=>$remember('home_featured_v4',300,static fn()=>$listingModel->featured(4)),
            // What people are actually looking at, and what has just arrived.
            // 290k profile_views were recorded and nothing ever showed them,
            // so a busy directory read as a static list.
            'trendingByCat'=>$trendingByCat,
            'newsLatest'=>$news['latest'] ?? [], 'newsCats'=>$news['cats'] ?? [],
            'trending'=>$remember('home_trending_v6',3600,static function() use ($db, $listingModel){
                if (! $db->tableExists('profile_views')) { return []; }
                $since = date('Y-m-d H:i:s', strtotime('-30 days'));
                $ids = [];
                foreach ($db->table('profile_views')->select('listing_id, COUNT(*) AS n')
                    ->where('created_at >=', $since)->groupBy('listing_id')
                    ->orderBy('n', 'DESC')->limit(6)->get()->getResultArray() as $r) {
                    $ids[(int) $r['listing_id']] = (int) $r['n'];
                }
                if (! $ids) { return []; }
                $rows = $listingModel->whereIn('id', array_keys($ids))->where('status','active')->findAll(4);
                foreach ($rows as &$_r) { $_r['_views'] = $ids[(int) $_r['id']] ?? 0; }
                unset($_r);
                usort($rows, static fn($a,$b) => $b['_views'] <=> $a['_views']);
                return $listingModel->attachRatings($rows);
            }),
            // newest per category — feeds the tabs on "New on HiNoida"
            // "Stay & work in Noida": PGs, coworking, commercial, dealers — the Property family
            // the hero's trust line: real counts, refreshed every 10 minutes
            'heroStats'=>$remember('home_hero_stats',600,static function() use ($db){
                $sectors=(int)($db->query("SELECT COUNT(DISTINCT locality_id) n FROM listing WHERE status='active' AND locality_id>0")->getRow()->n ?? 0);
                $homes=0;
                if (function_exists('hi_only_property')) { $b=$db->table('listing li')->where('li.status','active'); hi_only_property($b,'li'); $homes=(int)$b->countAllResults(); }
                return ['sectors'=>$sectors,'homes'=>$homes];
            }),
            'stays'=>$remember('home_stays_v6',600,static function() use ($db,$listingModel,$topCats){
                $prop=null; foreach ($topCats as $c) { if (stripos((string)$c['name'],'propert')===0) { $prop=$c; break; } }
                if (! $prop || ! $db->tableExists('category')) { return []; }
                $kids=$db->table('category')->select('id,name,slug')->where('parent',(int)$prop['id'])->get()->getResultArray();
                $out=[];
                foreach ($kids as $k) {
                    $cid=(int)$k['id'];
                    $rows=$listingModel->where('status','active')->like('categories',(string)$cid)->orderBy('is_featured','DESC')->orderBy('id','DESC')->findAll(80);
                    $rows=array_values(array_filter($rows,static function($r) use ($cid){ preg_match_all('/\d+/',(string)($r['categories'] ?? ''),$m); return in_array($cid,array_map('intval',$m[0]),true); }));
                    if (! $rows) { continue; }
                    $out[]=['name'=>html_entity_decode((string)$k['name'],ENT_QUOTES|ENT_HTML5,'UTF-8'),'slug'=>$k['slug'],'n'=>count($rows),'rows'=>$listingModel->attachRatings(array_slice($rows,0,6))];
                }
                usort($out,static fn($a,$b)=>$b['n']<=>$a['n']);
                return ['prop'=>['name'=>html_entity_decode((string)$prop['name'],ENT_QUOTES|ENT_HTML5,'UTF-8'),'slug'=>$prop['slug'],'n'=>(int)$prop['listing_count']],'kinds'=>$out];
            }),
            // "Popular localities": the busiest sectors, with a photo and how many homes they have
            'localities'=>$remember('home_localities_v3',600,static function() use ($db,$listingModel){
                if (! $db->tableExists('locality')) { return []; }
                $rows=$db->query("SELECT lo.id, lo.name, lo.slug, COUNT(li.id) AS n,
                        SUM(CASE WHEN li.listing_type='property' THEN 1 ELSE 0 END) AS homes
                    FROM listing li JOIN locality lo ON lo.id=li.locality_id
                    WHERE li.status='active' AND lo.slug<>'' GROUP BY lo.id, lo.name, lo.slug ORDER BY n DESC LIMIT 12")->getResultArray();
                foreach ($rows as &$_r) {
                    $_r['cover']=$listingModel->where('status','active')->where('locality_id',(int)$_r['id'])
                        ->orderBy('is_featured','DESC')->orderBy('id','DESC')->first() ?: [];
                } unset($_r);
                return $rows;
            }),
            // after you find a place: set it up
            'moveIn'=>$remember('home_movein_v2',600,static function() use ($catModel){
                $want=[['mover','Movers & packers','truck'],['packer','Movers & packers','truck'],['interior','Interiors','couch'],['furnit','Furniture','chair'],
                    ['clean','House cleaning','broom'],['pest','Pest control','bug'],['plumb','Plumbers','faucet-drip'],['electrician','Electricians','plug'],
                    ['carpent','Carpenters','hammer'],['ac repair','AC repair','snowflake'],['paint','Painters','paint-roller'],['internet','Internet','wifi'],
                    ['home serv','Home services','screwdriver-wrench'],['security','Security','shield-halved']];
                $all=(new \App\Models\CategoryModel())->select('id,name,slug')->findAll(2000);
                $out=[]; $seen=[];
                foreach ($want as [$k,$label,$icon]) {
                    if (isset($seen[$label])) { continue; }
                    foreach ($all as $c) {
                        if (stripos((string)$c['name'],$k)===false) { continue; }
                        $n=$catModel->countForCategory((int)$c['id']);
                        if ($n<1) { continue; }
                        $out[]=['name'=>$label,'slug'=>$c['slug'],'n'=>$n,'icon'=>$icon]; $seen[$label]=1; break;
                    }
                    if (count($out)>=12) { break; }
                }
                return $out;
            }),
            'newestByCat'=>$remember('home_newest_cat_v6',300,static function() use ($listingModel,$topCats){
                $rows=$listingModel->where('status','active')->where('date_added >',0)->orderBy('date_added','DESC')->findAll(400);
                $out=[];
                foreach (array_slice($topCats,0,7) as $c) {
                    $cid=(int)$c['id']; $pick=[];
                    foreach ($rows as $r) {
                        $ids=json_decode((string)($r['categories'] ?? ''),true);
                        if (! is_array($ids)) { preg_match_all('/\d+/',(string)($r['categories'] ?? ''),$_m); $ids=$_m[0]; }
                        if (in_array($cid,array_map('intval',$ids),true)) { $pick[]=$r; if (count($pick)>=6) break; }
                    }
                    if (count($pick)>=2) { $out[]=['name'=>html_entity_decode((string)$c['name'],ENT_QUOTES|ENT_HTML5,'UTF-8'),'slug'=>$c['slug'],'rows'=>$listingModel->attachRatings($pick)]; }
                }
                return $out;
            }),
            'newest'=>$remember('home_newest_v4',300,static function() use ($db, $listingModel){
                $rows = $listingModel->where('status','active')->where('date_added >', 0)
                    ->orderBy('date_added','DESC')->findAll(4);
                return $listingModel->attachRatings($rows);
            }),
            'stats'=>['groups'=>count($groups),'categories'=>count($cats),'listings'=>$totalListings],
            'jsonld'=>[hi_website_schema(), hi_org_schema()],
        ]);
    }
}
