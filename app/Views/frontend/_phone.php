<?php
/**
 * Reusable phone input: searchable country-code picker + national number.
 * Expects (all optional): $name, $value, $required, $cls, $ph
 * Submits a single combined value in the given field name, e.g. "+91 9876543210".
 */
$pName = $name  ?? 'phone';
$pVal  = (string) ($value ?? '');
$pReq  = ! empty($required);
$pCls  = $cls ?? 'sc-in';
$pPh   = $ph ?? 'Phone number';
$uid   = 'ph' . bin2hex(random_bytes(3));

// split an existing value into dial code + national number
$dial = '+91'; $natl = $pVal;
if (preg_match('~^\s*(\+\d{1,4})[\s-]*(.*)$~', $pVal, $m)) { $dial = $m[1]; $natl = $m[2]; }
$natl = preg_replace('/\D/', '', (string) $natl);

$countries = [
    ['India','IN','+91',10],['United States','US','+1',10],['United Kingdom','GB','+44',10],
    ['United Arab Emirates','AE','+971',9],['Canada','CA','+1',10],['Australia','AU','+61',9],
    ['Singapore','SG','+65',8],['Saudi Arabia','SA','+966',9],['Qatar','QA','+974',8],
    ['Kuwait','KW','+965',8],['Oman','OM','+968',8],['Bahrain','BH','+973',8],
    ['Nepal','NP','+977',10],['Bangladesh','BD','+880',10],['Sri Lanka','LK','+94',9],
    ['Pakistan','PK','+92',10],['Malaysia','MY','+60',9],['Indonesia','ID','+62',11],
    ['Thailand','TH','+66',9],['China','CN','+86',11],['Japan','JP','+81',10],
    ['South Korea','KR','+82',10],['Germany','DE','+49',11],['France','FR','+33',9],
    ['Italy','IT','+39',10],['Spain','ES','+34',9],['Netherlands','NL','+31',9],
    ['Switzerland','CH','+41',9],['Sweden','SE','+46',9],['Norway','NO','+47',8],
    ['Denmark','DK','+45',8],['Ireland','IE','+353',9],['New Zealand','NZ','+64',9],
    ['South Africa','ZA','+27',9],['Nigeria','NG','+234',10],['Kenya','KE','+254',9],
    ['Egypt','EG','+20',10],['Turkey','TR','+90',10],['Russia','RU','+7',10],
    ['Brazil','BR','+55',11],['Mexico','MX','+52',10],['Argentina','AR','+54',10],
    ['Israel','IL','+972',9],['Philippines','PH','+63',10],['Vietnam','VN','+84',9],
    ['Hong Kong','HK','+852',8],['Mauritius','MU','+230',8],['Maldives','MV','+960',7],
    ['Bhutan','BT','+975',8],['Afghanistan','AF','+93',9],
];
$curLen = 10;
foreach ($countries as $c) { if ($c[2] === $dial) { $curLen = $c[3]; break; } }
?>
<div class="phonefield" id="<?= $uid ?>">
  <input type="hidden" name="<?= esc($pName) ?>" value="<?= esc($pVal) ?>" data-phone-out>
  <div class="pf-row">
    <button type="button" class="pf-code" data-phone-toggle aria-haspopup="listbox" aria-expanded="false">
      <span data-phone-dial><?= esc($dial) ?></span><i class="fa-solid fa-chevron-down"></i>
    </button>
    <?php /* maxlength alone let a half-typed number through — the count for the
             chosen country is now a floor as well as a ceiling. */ ?>
    <input type="tel" class="<?= esc($pCls) ?> pf-num" data-phone-num inputmode="numeric" autocomplete="tel-national"
           placeholder="<?= esc($pPh) ?>" value="<?= esc($natl) ?>"
           maxlength="<?= (int) $curLen ?>" minlength="<?= (int) $curLen ?>"
           pattern="[0-9]{<?= (int) $curLen ?>}"
           title="Enter the <?= (int) $curLen ?>-digit number, without the country code"<?= $pReq ? ' required' : '' ?>>
  </div>
  <div class="pf-pop" data-phone-pop hidden>
    <input type="text" class="pf-search" data-phone-search placeholder="<?= esc(hi_c('_phone.ph_search_country_or_code', 'Search country or code…'), 'attr') ?>" autocomplete="off">
    <ul class="pf-list" data-phone-list role="listbox">
      <?php foreach ($countries as $c): ?>
        <li role="option" data-dial="<?= esc($c[2]) ?>" data-len="<?= (int) $c[3] ?>"
            data-search="<?= esc(strtolower($c[0] . ' ' . $c[1] . ' ' . $c[2])) ?>">
          <span class="pf-cn"><?= esc($c[0]) ?></span><span class="pf-cd"><?= esc($c[2]) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="pf-none" data-phone-none hidden>No match</div>
  </div>
</div>
