<?php
namespace App\Controllers;
use App\Models\CategoryModel;
class Groups extends BaseController
{
    public function show(string $key): string
    {
        helper(['taxonomy','seo']);
        $g=hi_group_by_key($key);
        if(!$g){ throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        $catModel=new CategoryModel();
        $cats=$catModel->topLevel(300);
        $cats=array_values(array_filter($cats,static fn($c)=>$c['listing_count']>0));
        $groups=hi_bucket($cats); $mine=[]; $total=0;
        foreach($groups as $grp){ if($grp['key']===$key){ $mine=$grp['cats']; $total=$grp['count']; break; } }
        $url=base_url('g/'.$key);
        $names=implode(', ',array_slice(array_column($mine,'name'),0,6));
        return view('frontend/group',[
            'title'=>$g[0].' in Noida — '.count($mine).' Categories, '.number_format($total).' Businesses | HiNoida',
            'metaDescription'=>$g[0].' in Noida: '.$names.'. Browse '.number_format($total).' businesses with address, phone, timings, photos & reviews on HiNoida.',
            'canonical'=>$url,
            'intro'=>'Explore '.strtolower($g[0]).' in Noida on HiNoida. This section brings together '.count($mine).' categories covering '.$names.' and more — '.number_format($total).' listings in total, each with verified contact details, opening hours, ratings and genuine reviews.',
            'group'=>$g,'gkey'=>$key,'cats'=>$mine,
            'jsonld'=>[hi_breadcrumb(['Home'=>base_url('/'), $g[0]=>$url])],
        ]);
    }
}
