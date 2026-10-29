<?php
function hi_groups(): array {
    return [
        'realestate'   => ['Real Estate & Property','building','apartment',['propert','flat','apartment','plot','real estate','pg','co-living','builder','villa','house']],
        'food'         => ['Food & Drink','utensils','restaurant',['restaurant','cafe','food','bakery','sweet','cloud kitchen','dhaba','bar','pub ','tiffin','catering']],
        'medical'      => ['Health & Medical','heart-pulse','hospital',['hospital','clinic','doctor','dental','pharmacy','medical','diagnostic','physio','eldercare','veterinary','lab','health','special needs']],
        'beauty'       => ['Beauty & Wellness','spa','spa',['salon','spa','beauty','parlour','makeup','nail','massage','wellness','yoga','ayurved']],
        'education'    => ['Education & Coaching','graduation-cap','study',['school','college','coaching','tuition','institute','academy','training','education','library','hobby','skill','dance','music']],
        'shopping'     => ['Shopping & Retail','bag-shopping','shopping',['shop','store','retail','clothing','fashion','footwear','jewel','electronic','mobile','gift','book','market','bazaar','furniture','furnishing']],
        'automobile'   => ['Automobile & Transport','car','automobile',['car','bike','scooter','vehicle','garage','tyre','taxi','transport','driving','showroom','fuel','ev','charging','petrol']],
        'entertainment'=> ['Entertainment & Events','masks-theater','concert',['cinema','movie','gaming','club','nightlife','event','party','amusement','bowling','arcade','wedding','photograph']],
        'hotels'       => ['Hotels & Stays','hotel','hotel',['hotel','resort','guest','stay','lodge','banquet','hostel']],
        'govt'         => ['Government & Public','landmark','government',['government','govt','ngo','social','trust','municipal','public','police','passport','post','citizen','panchayat','park']],
        'spiritual'    => ['Temples & Spiritual','place-of-worship','temple',['worship','temple','mandir','church','mosque','gurudwara','astrolog','spiritual','pandit','puja','religious']],
        'services'     => ['Home Services & Repairs','screwdriver-wrench','tools',['repair','service','plumb','electrician','carpenter','cleaning','pest','appliance','packer','mover','laundry','interior','renovation','improvement','hardware','building']],
        'proservices'  => ['Professional & Business','briefcase','office',['legal','lawyer','account','consult','marketing','advertis','printing','web','it','software','insurance','finance','loan','bank','telecom','internet','office','coworking','industrial','b2b']],
        'sports'       => ['Sports & Fitness','dumbbell','gym',['gym','fitness','sport','swim','cricket','football','martial','skating','stadium']],
        'pets'         => ['Pets & Animals','paw','dog',['pet','dog','cat','animal','aquarium']],
        'everyday'     => ['Daily Needs & Local','store','market',['grocery','daily','kirana','vegetable','dairy','milk','water','gas','stationery','tailor','courier','agricultur','nursery','plant','lpg']],
    ];
}

/** word-aware match: multiword kw = substring; single = exact word, plural, or (len>=4) prefix */
function hi_kw_hit(string $name, string $kw): bool {
    $kw = trim($kw);
    if (str_contains($kw, ' ')) { return str_contains($name, $kw); }
    foreach (preg_split('/[^a-z0-9]+/', $name) as $w) {
        if ($w === '') { continue; }
        if ($w === $kw) { return true; }
        if (rtrim($w, 's') === $kw) { return true; }        // bars -> bar
        if (strlen($kw) >= 4 && str_starts_with($w, $kw)) { return true; } // propert -> property
    }
    return false;
}

function hi_bucket(array $cats): array {
    $groups = hi_groups(); $out = [];
    foreach ($groups as $key => $g) { $out[$key] = ['key'=>$key,'label'=>$g[0],'icon'=>$g[1],'img'=>$g[2],'cats'=>[],'count'=>0]; }
    foreach ($cats as $c) {
        $n = strtolower($c['name']); $best = 'proservices';
        foreach ($groups as $key => $g) {
            foreach ($g[3] as $kw) { if (hi_kw_hit($n, $kw)) { $best=$key; break 2; } }
        }
        $out[$best]['cats'][]=$c; $out[$best]['count']+=$c['listing_count'];
    }
    return array_values(array_filter($out, static fn($g)=>count($g['cats'])>0));
}
function hi_group_by_key(string $key): ?array { return hi_groups()[$key] ?? null; }
