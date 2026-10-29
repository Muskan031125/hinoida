<?php
/**
 * Category-aware detail engine.
 * Every group gets its OWN relevant facts instead of one generic set.
 * Values are derived deterministically from the listing id so they stay
 * stable across page loads and are consistent between card / detail / JSON-LD.
 */

/**
 * Specificity order: when a name matches several groups, the more specific
 * one wins. "Pet Shops" must be pets, not shopping ("shop"); "Grocery Stores"
 * must be everyday, not shopping ("store"). Catch-alls sit at the end.
 */
function hi_group_priority(): array
{
    return ['pets', 'spiritual', 'medical', 'education', 'beauty', 'sports',
        'automobile', 'hotels', 'food', 'realestate', 'entertainment',
        'everyday', 'services', 'govt', 'shopping', 'proservices'];
}

/** which of the 16 groups does this category name belong to? '' when nothing matches */
function hi_group_key_or_blank(string $catName): string
{
    $n = strtolower($catName);

    $hits = [];
    foreach (hi_groups() as $key => $g) {
        foreach ($g[3] as $kw) {
            if (hi_kw_hit($n, $kw)) { $hits[$key] = true; break; }
        }
    }
    if (! $hits) {
        // second pass: sub-category vocabulary the main keyword lists don't cover
        foreach (hi_group_extra_kw() as $key => $kws) {
            foreach ($kws as $kw) {
                if (hi_kw_hit($n, $kw)) { $hits[$key] = true; break; }
            }
        }
    }
    if (! $hits) { return ''; }

    foreach (hi_group_priority() as $key) {
        if (isset($hits[$key])) { return $key; }
    }
    return array_key_first($hits);
}

/** which of the 16 groups does this listing belong to? */
function hi_group_key(string $catName): string
{
    return hi_group_key_or_blank($catName) ?: 'proservices';
}

/** extra vocabulary for sub-category names (cuisines, specialities, trades…) */
function hi_group_extra_kw(): array
{
    return [
        'food'      => ['indian','chinese','italian','pizza','burger','biryani','thali','continental','mughlai','punjabi','thai','japanese','sushi','momo','chaat','juice','ice cream','dessert','coffee','tea','snack','vegetarian','seafood','barbecue','buffet','diner','eatery','kitchen','cuisine','pastry','confection','chocolate','mithai','namkeen','paratha','south'],
        'medical'   => ['cardio','ortho','neuro','gyna','pediatr','dermat','ent ','eye','ophthal','psychi','onco','uro','nephro','gastro','endocrin','radiolog','pathol','ayurveda','homeopath','unani','maternity','ivf','fertility','nursing','ambulance','optic','hearing','chemist','druggist','surgeon','therapy','rehab','blood'],
        'education' => ['iit','neet','jee','cat ','upsc','ssc','bank po','olympiad','abacus','vedic','spoken','language','computer','coding','robotics','montessori','playgroup','kindergarten','preschool','creche','daycare','university','polytechnic','iti','b.ed','mba','engineering','medical college','tutor','classes','learning','exam','entrance'],
        'beauty'    => ['hair','skin','facial','waxing','threading','pedicure','manicure','bridal','mehendi','tattoo','piercing','slimming','laser','cosmet','grooming','barber','saloon','aroma','reiki','meditation','naturopath'],
        'realestate'=> ['bhk','residen','commercial','office space','warehouse','godown','showroom space','land','farmhouse','township','society','estate','broker','realtor','rent','lease','possession','floor'],
        'hotels'    => ['motel','inn','suite','dormitor','homestay','service apartment','marriage hall','conference','oyo'],
        'automobile'=> ['auto','denting','painting','wash','accessor','spare','battery','tyre','tire','puncture','towing','rental','cab','taxi','rickshaw','bus','truck','tempo','rto','insurance claim','second hand','used','dealership','servicing','mechanic','lubricant','diesel','cng'],
        'sports'    => ['yoga','aerobic','zumba','crossfit','badminton','tennis','basketball','volleyball','hockey','athletic','karate','judo','taekwondo','boxing','wrestling','archery','shooting','cycling','running','trek','adventure','academy','coaching centre','turf','court','club'],
        'shopping'  => ['garment','apparel','saree','suit','kurta','lehenga','boutique','tailor','textile','fabric','handloom','watch','optical','bag','luggage','toy','sport goods','music instrument','crockery','utensil','hardware store','paint','tile','sanitary','plywood','glass','mattress','decor','handicraft','antique','flower','florist','cake shop','super','mall','emporium','showroom'],
        'services'  => ['ac ','refrigerat','washing machine','geyser','inverter','ro ','water purifier','chimney','microwave','television','computer repair','mobile repair','cctv','security','housekeep','maid','cook','driver','gardening','borewell','welding','fabricat','polish','carpet','sofa','curtain','false ceiling','pop ','waterproof','demolition','labour','contractor','mason','tiling','shifting','storage','transport service'],
        'proservices'=>['chartered','ca ','cs ','advocate','notary','tax','gst','audit','recruit','hr ','staffing','placement','immigration','visa','passport agent','travel agent','tour','courier service','logistics','packaging','stationery supplier','signage','flex','screen print','graphic','seo','digital market','social media','app develop','hosting','domain','call centre','bpo','manpower','architect','engineer','surveyor','valuer','insurance agent','mutual fund','share market','trading','import','export','wholesale','distributor','supplier','manufactur','fabrication unit'],
        'entertainment'=>['multiplex','theatre','banquet','farmhouse party','dj ','anchor','magician','orchestra','band','decorator','tent','caterer','photo','video','drone','album','pre wedding','birthday','kitty','game zone','vr ','pool','snooker','billiard','karaoke','disc','lounge','brewery'],
        'pets'      => ['kennel','grooming pet','pet shop','pet food','aquar','fish','bird','poultry','cattle','livestock'],
        'spiritual' => ['jain','buddh','sikh','ashram','math','dham','shiv','krishna','hanuman','durga','sai','satsang','kirtan','vastu','numerolog','tarot','palmist','kundli','horoscope','yagya','havan'],
        'govt'      => ['nagar','nigam','authority','tehsil','court','sdm','dm office','electricity','water board','sewage','fire station','army','defence','railway','metro','embassy','consulate','election','aadhaar','ration','pension','labour office','employment','census','ward','block','garden','playground','community centre'],
        'everyday'  => ['kirana','general store','provision','atta','flour','rice','pulse','spice','oil','sugar','tea shop','paan','cigarette','bakery shop','sweet shop','fruit','vegetable','meat','chicken','mutton','fish market','egg','curd','paneer','mineral water','cylinder','newspaper','photocopy','xerox','lamination','recharge','sim ','cyber','internet cafe','saloon shop','cobbler','key maker','scrap','kabadi','junk'],
    ];
}

/** stable pseudo-random pick from $opts for a given listing + salt */
function hi_pick(int $id, string $salt, array $opts)
{
    return $opts[crc32($salt . ':' . $id) % count($opts)];
}

/** stable number in a range, rounded to $step */
function hi_num(int $id, string $salt, int $min, int $max, int $step = 1): int
{
    $span = intdiv($max - $min, $step) + 1;
    return $min + (crc32($salt . '#' . $id) % $span) * $step;
}

/**
 * Category-specific "At a glance" facts.
 * Returns list of [icon, label, value].
 */
function hi_group_facts(array $l, string $gk): array
{
    $id = (int) $l['id'];
    $f  = [];

    switch ($gk) {
        case 'food':
            $f[] = ['utensils', 'Cuisines', implode(' · ', array_slice(hi_pick($id, 'cui', [
                ['North Indian', 'Chinese', 'Mughlai'], ['South Indian', 'Continental'],
                ['Italian', 'Pizza', 'Pasta'], ['Fast Food', 'Burgers', 'Rolls'],
                ['Biryani', 'Kebabs', 'Tandoor'], ['Cafe', 'Desserts', 'Beverages'],
            ]), 0, 3))];
            $f[] = ['indian-rupee-sign', 'Cost for two', '₹' . hi_num($id, 'c2', 300, 1600, 50) . ' approx'];
            $f[] = ['chair', 'Seating', hi_num($id, 'seat', 24, 140, 4) . ' covers'];
            $f[] = ['motorcycle', 'Delivery', hi_pick($id, 'dlv', ['Zomato · Swiggy · Direct', 'Direct delivery only', 'Takeaway & dine-in'])];
            $f[] = ['snowflake', 'Ambience', hi_pick($id, 'amb', ['Air-conditioned · Family', 'Casual dining', 'Rooftop / outdoor', 'Quick-service'])];
            $f[] = ['leaf', 'Food type', hi_pick($id, 'veg', ['Pure vegetarian', 'Veg & Non-veg', 'Veg · Jain available'])];
            break;

        case 'medical':
            $f[] = ['user-doctor', 'Consultation', '₹' . hi_num($id, 'cons', 200, 1200, 50)];
            $f[] = ['stethoscope', 'Specialities', hi_num($id, 'spec', 2, 12) . ' departments'];
            $f[] = ['truck-medical', 'Emergency', hi_pick($id, 'emg', ['24×7 available', 'Day-time only', 'On-call after hours'])];
            $f[] = ['calendar-check', 'Appointments', hi_pick($id, 'apt', ['Walk-in & online', 'By appointment', 'Walk-in accepted'])];
            $f[] = ['id-card', 'Insurance', hi_pick($id, 'ins', ['Cashless · TPA accepted', 'Reimbursement only', 'Ayushman · CGHS'])];
            $f[] = ['flask', 'On-site', hi_pick($id, 'lab', ['Pharmacy · Lab · X-ray', 'Pharmacy on site', 'Diagnostics available'])];
            break;

        case 'education':
            $f[] = ['certificate', 'Board / Affiliation', hi_pick($id, 'brd', ['CBSE', 'ICSE', 'CBSE · State Board', 'AICTE approved', 'Skill certification'])];
            $f[] = ['children', 'Levels offered', hi_pick($id, 'lvl', ['Nursery – Class XII', 'Class VI – XII', 'Class IX – XII', 'All ages', 'Graduate & above'])];
            $f[] = ['users', 'Batch size', hi_num($id, 'bat', 8, 40, 2) . ' students'];
            $f[] = ['indian-rupee-sign', 'Fee range', '₹' . hi_num($id, 'fee', 2, 18) . ',000 – ₹' . hi_num($id, 'fee2', 20, 90) . ',000 / yr'];
            $f[] = ['language', 'Medium', hi_pick($id, 'med', ['English', 'English · Hindi', 'Hindi'])];
            $f[] = ['clipboard-check', 'Admissions', hi_pick($id, 'adm', ['Open for the session', 'Merit / entrance based', 'Rolling admissions'])];
            break;

        case 'beauty':
            $f[] = ['scissors', 'Services', hi_num($id, 'svc', 12, 60, 2) . '+ on the menu'];
            $f[] = ['venus-mars', 'Salon type', hi_pick($id, 'gen', ['Unisex', 'Ladies only', 'Gents only', 'Unisex · Family'])];
            $f[] = ['indian-rupee-sign', 'Starting from', '₹' . hi_num($id, 'st', 150, 900, 50)];
            $f[] = ['house', 'Home service', hi_pick($id, 'hs', ['Available on request', 'Not available', 'Available (extra charge)'])];
            $f[] = ['calendar-check', 'Booking', hi_pick($id, 'bk', ['Walk-in & appointment', 'Appointment preferred', 'Walk-in welcome'])];
            $f[] = ['certificate', 'Products used', hi_pick($id, 'pr', ['L\'Oréal · Matrix', 'Schwarzkopf · Wella', 'Organic / herbal', 'Professional brands'])];
            break;

        case 'realestate':
            $f[] = ['building', 'Property types', hi_pick($id, 'pt', ['2/3/4 BHK Apartments', 'Builder floors · Villas', 'Plots & Land', 'Commercial & Office', 'Residential & Rental'])];
            $f[] = ['indian-rupee-sign', 'Price band', '₹' . hi_num($id, 'pb', 35, 180, 5) . ' L – ₹' . hi_num($id, 'pb2', 2, 6) . ' Cr'];
            $f[] = ['ruler-combined', 'Sizes', hi_num($id, 'sz', 600, 2400, 50) . ' – ' . hi_num($id, 'sz2', 2500, 5000, 100) . ' sq.ft'];
            $f[] = ['key', 'Deals in', hi_pick($id, 'dl', ['Buy · Sell · Rent', 'Resale & new launch', 'Rental & PG', 'New projects only'])];
            $f[] = ['file-signature', 'RERA', hi_pick($id, 'rr', ['Registered', 'Registered projects only', 'On request'])];
            $f[] = ['percent', 'Brokerage', hi_pick($id, 'bg', ['1% or as agreed', 'No brokerage (direct)', 'Standard 2%'])];
            break;

        case 'hotels':
            $f[] = ['bed', 'Rooms', hi_num($id, 'rm', 8, 120, 2) . ' keys'];
            $f[] = ['indian-rupee-sign', 'Tariff from', '₹' . hi_num($id, 'tf', 1200, 9000, 100) . ' / night'];
            $f[] = ['clock', 'Check-in / out', hi_pick($id, 'ci', ['12 PM / 11 AM', '2 PM / 12 PM', '24-hr check-in'])];
            $f[] = ['users', 'Banquet capacity', hi_num($id, 'bq', 50, 800, 25) . ' guests'];
            $f[] = ['wifi', 'Included', hi_pick($id, 'inc', ['Wi-Fi · Breakfast · Parking', 'Wi-Fi · Parking', 'Breakfast included'])];
            $f[] = ['ban-smoking', 'Policies', hi_pick($id, 'pol', ['Couple friendly · ID required', 'Family only · ID required', 'Pet friendly on request'])];
            break;

        case 'automobile':
            $f[] = ['car', 'Works on', hi_pick($id, 'wo', ['All car brands', 'Two-wheelers', 'Cars & SUVs', 'Commercial vehicles', 'EV & hybrid'])];
            $f[] = ['screwdriver-wrench', 'Services', hi_pick($id, 'as', ['Service · Repair · Denting', 'Periodic service & AMC', 'Tyres · Battery · Alignment', 'Detailing & car care'])];
            $f[] = ['indian-rupee-sign', 'Service from', '₹' . hi_num($id, 'sf', 400, 4500, 100)];
            $f[] = ['clock-rotate-left', 'Turnaround', hi_pick($id, 'ta', ['Same day', '24–48 hours', 'While you wait'])];
            $f[] = ['shield-halved', 'Warranty', hi_pick($id, 'wr', ['On parts & labour', '3 months on service', 'Manufacturer warranty safe'])];
            $f[] = ['truck', 'Pickup & drop', hi_pick($id, 'pd', ['Free within Noida', 'Available on request', 'Not available'])];
            break;

        case 'sports':
            $f[] = ['indian-rupee-sign', 'Membership', '₹' . hi_num($id, 'mb', 800, 4000, 100) . ' / month'];
            $f[] = ['ticket', 'Day pass', '₹' . hi_num($id, 'dp', 100, 600, 50)];
            $f[] = ['person-running', 'Facilities', hi_pick($id, 'fc', ['Cardio · Weights · Zumba', 'Personal training · CrossFit', 'Turf · Coaching', 'Swimming · Gym'])];
            $f[] = ['user-tie', 'Trainers', hi_num($id, 'tr', 2, 14) . ' certified'];
            $f[] = ['clock', 'Peak hours', hi_pick($id, 'ph', ['6–10 AM · 6–9 PM', 'Evening 5–9 PM', 'Morning batches'])];
            $f[] = ['venus-mars', 'Open to', hi_pick($id, 'ot', ['Unisex', 'Ladies batch available', 'All ages'])];
            break;

        case 'shopping':
            $f[] = ['tags', 'Product range', hi_num($id, 'pr', 50, 900, 25) . '+ items'];
            $f[] = ['indian-rupee-sign', 'Price range', hi_pick($id, 'prr', ['₹ Budget friendly', '₹₹ Mid-range', '₹₹₹ Premium'])];
            $f[] = ['certificate', 'Brands', hi_pick($id, 'br', ['Multi-brand', 'Exclusive brand outlet', 'Local & branded'])];
            $f[] = ['rotate-left', 'Returns', hi_pick($id, 'rt', ['7-day exchange', 'Exchange only', 'As per brand policy'])];
            $f[] = ['truck', 'Home delivery', hi_pick($id, 'hd', ['Available in Noida', 'Store pickup only', 'Same-day delivery'])];
            $f[] = ['credit-card', 'Payments', hi_pick($id, 'py', ['Cash · UPI · Cards · EMI', 'Cash · UPI · Cards', 'Cash · UPI'])];
            break;

        case 'services':
            $f[] = ['screwdriver-wrench', 'Work handled', hi_pick($id, 'wh', ['Repair · Installation · AMC', 'Full-home solutions', 'On-site & workshop', 'Emergency call-outs'])];
            $f[] = ['indian-rupee-sign', 'Visit charge', '₹' . hi_num($id, 'vc', 0, 500, 50) ?: 'Free inspection'];
            $f[] = ['bolt', 'Response time', hi_pick($id, 'rs', ['Same day', 'Within 24 hours', '2–4 hours (urgent)'])];
            $f[] = ['shield-halved', 'Warranty', hi_pick($id, 'wr', ['30 days on service', '90 days on parts', 'Service guarantee'])];
            $f[] = ['map-location-dot', 'Serves', hi_pick($id, 'sv', ['All Noida sectors', 'Noida & Greater Noida', 'Noida · Ghaziabad'])];
            $f[] = ['user-check', 'Staff', hi_pick($id, 'sf', ['Background verified', 'Trained technicians', 'In-house team'])];
            break;

        case 'proservices':
            $f[] = ['briefcase', 'Practice areas', hi_num($id, 'pa', 3, 15) . ' specialisations'];
            $f[] = ['indian-rupee-sign', 'Consultation', hi_pick($id, 'cf', ['First consult free', '₹500 – ₹2,000', 'On engagement basis'])];
            $f[] = ['users', 'Team size', hi_num($id, 'ts', 2, 60, 2) . ' professionals'];
            $f[] = ['clock-rotate-left', 'Experience', hi_num($id, 'ex', 3, 28) . '+ years'];
            $f[] = ['building-columns', 'Clients', hi_pick($id, 'cl', ['SME & Corporate', 'Individuals & SME', 'Enterprise & Startups'])];
            $f[] = ['file-contract', 'Engagement', hi_pick($id, 'en', ['Retainer & project', 'Project based', 'Hourly & retainer'])];
            break;

        case 'entertainment':
            $f[] = ['ticket', 'Entry / ticket', '₹' . hi_num($id, 'et', 100, 1500, 50) . ' onwards'];
            $f[] = ['users', 'Capacity', hi_num($id, 'cp', 40, 900, 20) . ' guests'];
            $f[] = ['calendar-days', 'Best for', hi_pick($id, 'bf', ['Birthdays · Corporate', 'Weddings & receptions', 'Weekend outings', 'Kids & family'])];
            $f[] = ['champagne-glasses', 'Facilities', hi_pick($id, 'fa', ['Catering · DJ · Decor', 'Bar · Lounge · Parking', 'AC hall · Sound system'])];
            $f[] = ['clock', 'Advance booking', hi_pick($id, 'ab', ['7 days recommended', '24 hours minimum', 'Same-day possible'])];
            $f[] = ['square-parking', 'Parking', hi_pick($id, 'pk', ['Valet available', 'Free on-site', 'Street parking'])];
            break;

        case 'pets':
            $f[] = ['paw', 'Pets handled', hi_pick($id, 'ph', ['Dogs · Cats', 'Dogs · Cats · Birds', 'All small animals'])];
            $f[] = ['indian-rupee-sign', 'Starting from', '₹' . hi_num($id, 'ps', 200, 1500, 50)];
            $f[] = ['syringe', 'Services', hi_pick($id, 'psv', ['Grooming · Vaccination', 'Boarding · Day care', 'Vet consult · Surgery', 'Food · Accessories'])];
            $f[] = ['house', 'Home visit', hi_pick($id, 'phv', ['Available', 'Not available', 'On request'])];
            $f[] = ['truck-medical', 'Emergency', hi_pick($id, 'pe', ['24×7 on call', 'Day-time only', 'By appointment'])];
            $f[] = ['calendar-check', 'Booking', 'Call or WhatsApp'];
            break;

        case 'spiritual':
            $f[] = ['sun', 'Darshan timings', hi_pick($id, 'dt', ['5 AM – 12 PM · 4 – 9 PM', '6 AM – 9 PM', 'Open all day'])];
            $f[] = ['fire', 'Aarti', hi_pick($id, 'ar', ['Morning & evening', 'Evening aarti daily', 'On festival days'])];
            $f[] = ['calendar-days', 'Special days', hi_pick($id, 'sd', ['Tuesday · Saturday', 'Monday · Purnima', 'Festivals & Ekadashi'])];
            $f[] = ['users', 'Facilities', hi_pick($id, 'sf', ['Prasad · Parking · Shoe stand', 'Hall for events', 'Langar / bhandara'])];
            $f[] = ['hands-praying', 'Services', hi_pick($id, 'ss', ['Puja & havan booking', 'Pandit on request', 'Community programmes'])];
            $f[] = ['ticket', 'Entry', 'Free for all'];
            break;

        case 'govt':
            $f[] = ['building-columns', 'Office type', hi_pick($id, 'ot', ['Government office', 'Public utility', 'NGO / Trust', 'Civic facility'])];
            $f[] = ['clock', 'Public hours', hi_pick($id, 'pubh', ['10 AM – 5 PM (Mon–Fri)', '9 AM – 6 PM', 'Open all days'])];
            $f[] = ['file-lines', 'Documents needed', hi_pick($id, 'dn', ['ID proof & address proof', 'Aadhaar & application form', 'As per service'])];
            $f[] = ['ticket', 'Charges', hi_pick($id, 'ch', ['Government rates apply', 'Free service', 'Nominal fee'])];
            $f[] = ['globe', 'Online option', hi_pick($id, 'oo', ['Available on portal', 'In-person only', 'Partly online'])];
            $f[] = ['circle-info', 'Helpdesk', 'Available at reception'];
            break;

        case 'everyday':
            $f[] = ['basket-shopping', 'Sells', hi_pick($id, 'sl', ['Groceries & daily needs', 'Fresh produce', 'Dairy & essentials', 'Household & stationery'])];
            $f[] = ['truck', 'Home delivery', hi_pick($id, 'hd', ['Free above ₹500', 'Available in sector', 'Pickup only'])];
            $f[] = ['clock', 'Open', hi_pick($id, 'op', ['All 7 days', 'Mon–Sat', 'Daily incl. holidays'])];
            $f[] = ['indian-rupee-sign', 'Pricing', hi_pick($id, 'pg', ['MRP or below', 'Wholesale rates', 'Market rates'])];
            $f[] = ['credit-card', 'Payments', 'Cash · UPI'];
            $f[] = ['bolt', 'Delivery time', hi_pick($id, 'dt', ['Within 30 mins', 'Same day', '1–2 hours'])];
            break;

        default:
            $f[] = ['indian-rupee-sign', 'Price range', hi_pick($id, 'g1', ['₹ Budget', '₹₹ Mid-range', '₹₹₹ Premium'])];
            $f[] = ['credit-card', 'Payments', 'Cash · UPI · Cards'];
            $f[] = ['language', 'Languages', 'Hindi · English'];
    }

    return $f;
}

/** Category-aware "Good to know" highlight lines. */
function hi_group_highlights(array $l, string $gk, string $loc): array
{
    $id = (int) $l['id'];
    $map = [
        'food'      => [['motorcycle', 'Dine-in & delivery', 'Order at the table or get it delivered across ' . $loc . '.'], ['users', 'Group friendly', 'Comfortable for families and larger groups.']],
        'medical'   => [['calendar-check', 'Book before you go', 'Call ahead to confirm doctor availability and cut waiting time.'], ['id-card', 'Carry your reports', 'Bring past prescriptions and insurance details for a faster consult.']],
        'education' => [['clipboard-check', 'Admission guidance', 'Speak to the counsellor about eligibility, fees and the current batch.'], ['location-dot', 'Convenient for ' . $loc, 'Well connected for students living nearby.']],
        'beauty'    => [['calendar-check', 'Weekend slots fill fast', 'Book a day ahead for evening and weekend appointments.'], ['sparkles', 'Professional products', 'Trained staff using salon-grade products.']],
        'realestate'=> [['file-signature', 'Verify documents', 'Ask for RERA number, title papers and the latest layout before booking.'], ['location-dot', 'Site visits', 'Arrange a site visit in ' . $loc . ' before you decide.']],
        'hotels'    => [['id-card', 'ID mandatory', 'A valid government photo ID is required for all guests at check-in.'], ['square-parking', 'Parking on site', 'On-premise parking for guests.']],
        'automobile'=> [['file-invoice', 'Get an estimate first', 'Ask for a written estimate before the work starts.'], ['shield-halved', 'Warranty safe', 'Servicing here does not affect your manufacturer warranty.']],
        'sports'    => [['ticket', 'Try before you join', 'Most centres allow a trial session or day pass.'], ['user-tie', 'Certified trainers', 'Guided sessions for beginners and regulars.']],
        'shopping'  => [['rotate-left', 'Check the return policy', 'Confirm exchange terms and keep the invoice.'], ['tags', 'Compare in store', 'Wide selection available to see in person.']],
        'services'  => [['user-check', 'Verified technicians', 'Trained staff, transparent charges, no surprise costs.'], ['bolt', 'Quick response', 'Same-day visits available across Noida.']],
        'proservices'=>[['file-contract', 'Clarify scope & fees', 'Agree on deliverables and fees in writing before you start.'], ['clock-rotate-left', 'Experienced team', 'Years of practice serving clients in Noida.']],
        'entertainment'=>[['calendar-days', 'Book in advance', 'Weekend and festival dates get taken early.'], ['champagne-glasses', 'Packages available', 'Ask about combined catering and decor packages.']],
        'pets'      => [['syringe', 'Carry vaccination card', 'Bring your pet\'s health record for the first visit.'], ['paw', 'Pet-friendly staff', 'Experienced handlers for anxious pets.']],
        'spiritual' => [['sun', 'Best time to visit', 'Mornings and evening aarti are the most peaceful.'], ['shoe-prints', 'Facilities on site', 'Shoe stand, drinking water and parking available.']],
        'govt'      => [['file-lines', 'Carry your documents', 'Original plus photocopies of ID and address proof.'], ['clock', 'Go early', 'Queues are shortest in the first working hour.']],
        'everyday'  => [['truck', 'Doorstep delivery', 'Delivery available within the sector.'], ['clock', 'Open daily', 'Reliable for everyday essentials.']],
    ];
    return $map[$gk] ?? [];
}

/**
 * The word that goes with a star rating — "Excellent", "Very good".
 *
 * Ported from Frontend_model::get_rating_wise_quality, which looks the
 * average up in a review_wise_quality band table. The bands are editable in
 * admin; these are the fallback if the table is empty.
 */
function hi_rating_quality(float $avg, int $count = 1): string
{
    if ($count < 1 || $avg <= 0) { return ''; }

    static $bands = null;
    if ($bands === null) {
        $bands = [];
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('review_wise_quality')) {
                $bands = $db->table('review_wise_quality')->orderBy('rating_from', 'ASC')->get()->getResultArray();
            }
        } catch (\Throwable $e) { $bands = []; }
    }

    foreach ($bands as $b) {
        if ($avg >= (float) $b['rating_from'] && $avg <= (float) $b['rating_to']) {
            return (string) $b['quality'];
        }
    }

    // same wording the original shipped with
    if ($avg >= 4.5) { return 'Excellent'; }
    if ($avg >= 4.0) { return 'Very good'; }
    if ($avg >= 3.0) { return 'Good'; }
    if ($avg >= 2.0) { return 'Average'; }

    return 'Poor';
}

/**
 * What a booking on this kind of business actually is.
 *
 * Keyed off the category group, not listing.listing_type — that column holds
 * the literal string "listing" for essentially every row, so anything keyed
 * off it silently falls through to the generic form.
 *
 * @return array{0:string,1:string,2:string,3:string} group, heading, button, what the date means
 */
function hi_booking_flavour(string $groupKey): array
{
    $map = [
        'hotels'   => ['hotel',   'Book a room',          'Request booking',     'Check-in'],
        'food'     => ['table',   'Reserve a table',      'Request a table',     'Date'],
        'beauty'   => ['slot',    'Book an appointment',  'Request appointment', 'Preferred date'],
        'medical'  => ['slot',    'Book an appointment',  'Request appointment', 'Preferred date'],
        'sports'   => ['slot',    'Book a session',       'Request a session',   'Preferred date'],
        'pets'     => ['slot',    'Book an appointment',  'Request appointment', 'Preferred date'],
        'automobile'=> ['service','Book a service',       'Request a service',   'Drop-off date'],
        'education'=> ['enquiry', 'Enquire about admission','Send enquiry',      'Preferred date'],
        'realestate'=> ['visit',  'Schedule a visit',     'Request a visit',     'Preferred date'],
        'entertainment'=> ['slot','Book tickets',         'Request booking',     'Preferred date'],
        'services' => ['visit',   'Book a visit',         'Request a visit',     'Preferred date'],
        'proservices'=> ['enquiry','Book a consultation', 'Request consultation','Preferred date'],
        'spiritual'=> ['enquiry', 'Enquire',              'Send enquiry',        'Preferred date'],
        'shopping' => ['enquiry', 'Enquire',              'Send enquiry',        'Preferred date'],
        'everyday' => ['enquiry', 'Enquire',              'Send enquiry',        'Preferred date'],
        'govt'     => ['enquiry', 'Enquire',              'Send enquiry',        'Preferred date'],
    ];

    return $map[$groupKey] ?? ['enquiry', 'Book / Enquire', 'Send request', 'Preferred date'];
}

/** The "how many people / what for" field, per flavour. */
function hi_booking_party(string $flavour): array
{
    return [
        'hotel'   => ['guests', 'number', 'Guests'],
        'table'   => ['guests', 'number', 'Number of people'],
        'slot'    => ['guests', 'text',   'What do you need? (e.g. haircut)'],
        'service' => ['guests', 'text',   'What needs doing? (e.g. full service)'],
        'visit'   => ['guests', 'number', 'People visiting'],
        'enquiry' => ['', '', ''],
    ][$flavour] ?? ['', '', ''];
}

/**
 * What the catalog section is called for this kind of business.
 *
 * The original had separate inner-page templates that each titled this block
 * differently — hotel_room_details, special_food_menus, beauty services. Those
 * read type-specific tables that were never created in production; the live
 * data all sits in `service`, so only the wording carries over.
 */
function hi_catalog_heading(string $groupKey): string
{
    return [
        'hotels'     => 'Rooms & rates',
        'food'       => 'Menu & prices',
        'beauty'     => 'Services & prices',
        'medical'    => 'Treatments & fees',
        'automobile' => 'Services & rates',
        'education'  => 'Courses & fees',
        'sports'     => 'Classes & fees',
        'pets'       => 'Services & prices',
        'realestate' => 'Properties & prices',
        'entertainment' => 'Tickets & prices',
    ][$groupKey] ?? 'Services & pricing';
}
