<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box}body{margin:0;font-family:'Inter',sans-serif;background:linear-gradient(180deg,#eaf0f9,#f0ecf8,#eaf4f1) fixed;color:#1c2432}
.wrap{max-width:940px;margin:0 auto;padding:26px 24px}
.top{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.top a.back{color:#1741a6;font-weight:600;text-decoration:none}
h1{font-family:'Inter',system-ui,sans-serif;font-weight:600;font-size:32px;margin:0}
.panel{background:rgba(255,255,255,.75);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.6);border-radius:16px;box-shadow:0 6px 20px rgba(15,28,51,.06);padding:24px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:640px){.row{grid-template-columns:1fr}}
label{display:block;font-size:12px;font-weight:700;color:#5a6b86;margin:14px 0 5px;text-transform:uppercase;letter-spacing:.03em}
input[type=text],input[type=time],textarea,select,input[type=file]{width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:10px 12px;font-size:14px;font-family:inherit;background:#fff}
textarea{min-height:100px;resize:vertical}
.chk{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px}
.chk label{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #dfe4ec;border-radius:8px;padding:7px 11px;font-size:13px;text-transform:none;letter-spacing:0;color:#33405a;margin:0;cursor:pointer}
.btn{margin-top:20px;background:#1741a6;color:#fff;border:0;border-radius:10px;padding:13px 22px;font-weight:700;cursor:pointer}
.saved{background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:10px;padding:11px 15px;margin-bottom:16px}
.photos{display:grid;grid-template-columns:repeat(auto-fill,minmax(84px,1fr));gap:10px;margin:6px 0 10px}
.photos label{position:relative;margin:0;border-radius:9px;overflow:hidden;border:1px solid #e6e9f0;cursor:pointer}
.photos img{width:100%;height:66px;object-fit:cover;display:block}
.photos input{position:absolute;top:5px;left:5px;transform:scale(1.2)}
.photos .cov{position:absolute;left:0;right:0;bottom:0;background:rgba(15,28,51,.72);color:#fff;
  font-size:10.5px;text-align:center;padding:3px 0;display:flex;align-items:center;justify-content:center;gap:4px}
.photos .cov input{position:static;transform:scale(1)}
h2.sect{font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#1741a6;
  margin:26px 0 2px;padding-top:18px;border-top:1px solid #e6eaf2;font-weight:700}
.opt{font-weight:400;text-transform:none;letter-spacing:0;color:#8593ab}
.hint{margin:5px 0 0;font-size:12px;color:#7b8aa5}
.err{background:#fdecec;color:#a12626;border:1px solid #f5c9c9;border-radius:10px;padding:11px 15px;margin-bottom:16px}
.err ul{margin:6px 0 0;padding-left:18px}
.hours{margin-top:8px;display:grid;gap:7px}
.hrow{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.hday{width:42px;font-size:12px;font-weight:700;color:#5a6b86;text-transform:uppercase}
.hrow input[type=time]{width:auto;flex:0 0 auto;padding:7px 9px}
.hsep,.hsep2{color:#8593ab;font-size:12px}
.hshut{display:inline-flex;align-items:center;gap:5px;margin:0;font-size:12px;
  text-transform:none;letter-spacing:0;color:#5a6b86}
.hours.same .hrow:not([data-day=mon]){display:none}
</style>
</head>
<body>
<div class="wrap">
<?php $g=static fn($k,$d='')=>isset($row[$k])?$row[$k]:$d; $cids=json_decode($g('categories','[]'),true); $primary=is_array($cids)&&$cids?(int)$cids[0]:0; $curAmen=json_decode($g('amenities','[]'),true); $curAmen=is_array($curAmen)?array_map('intval',$curAmen):[]; $photos=json_decode($g('photos','[]'),true); $photos=is_array($photos)?$photos:[]; $fmt=static fn($m)=>$m?sprintf('%02d:%02d',intdiv((int)$m,60),(int)$m%60):''; ?>
  <div class="top">
    <h1><i class="fa-solid fa-store" style="color:#1741a6"></i> <?= $row?'Edit listing':'Add listing' ?></h1>
    <a class="back" href="<?= base_url('admin/listings') ?>"><i class="fa-solid fa-arrow-left"></i> Back to listings</a>
  </div>
  <?php if(!empty($saved)): ?><div class="saved"><i class="fa-solid fa-circle-check"></i> <?= esc($saved) ?></div><?php endif; ?>
  <?php if(!empty($errors)): ?><div class="err"><b><i class="fa-solid fa-circle-exclamation"></i> Not saved</b>
    <ul><?php foreach((array)$errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <div class="panel"><form method="post" action="<?= base_url('admin/listing/save') ?>" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $row?(int)$row['id']:'' ?>">
    <div class="row">
      <div><label>Business name</label><input type="text" name="name" value="<?= esc($g('name')) ?>" required></div>
      <?php /* Free text let "listing", "Listing" and typos all through, and the
               type decides the URL prefix and which detail table applies. */ ?>
      <div><label>Listing type</label>
        <?php $_lt = strtolower(trim((string) $g('listing_type', 'general'))); ?>
        <select name="listing_type" id="ltype">
          <?php foreach (['general' => 'General business', 'restaurant' => 'Restaurant — has a menu',
                          'hotel' => 'Hotel — has rooms', 'beauty' => 'Salon / spa — has services',
                          'shop' => 'Shop — has products', 'property' => 'Property — flat, PG, plot, office'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $_lt === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="hint">Property swaps the hours and amenities below for BHK, area and rent.</span>
      </div>
    </div>
    <div class="row">
      <div><label>Category</label><select name="category">
        <option value="">— select —</option>
        <?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $primary===(int)$c['id']?'selected':'' ?>><?= esc($c['name']) ?></option><?php endforeach; ?>
      </select></div>
      <div><label>Locality / Area</label><select name="locality_id">
        <option value="">— select —</option>
        <?php foreach($locs as $l): ?><option value="<?= (int)$l['id'] ?>" <?= (int)$g('locality_id')===(int)$l['id']?'selected':'' ?>><?= esc($l['name']) ?></option><?php endforeach; ?>
      </select></div>
    </div>
    <div class="row">
      <div><label>Place / market <span class="opt">— inside the locality</span></label><select name="area_id">
        <option value="">— none —</option>
        <?php foreach(($areas ?? []) as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (int)$g('area_id')===(int)$a['id']?'selected':'' ?>><?= esc($a['name']) ?><?= !empty($a['locality_name'])?' · '.esc($a['locality_name']):'' ?></option><?php endforeach; ?>
      </select></div>
      <div><label>Tagline <span class="opt">— the one line under the name</span></label><input type="text" name="head_point" value="<?= esc($g('head_point')) ?>" placeholder="Beauty parlour in Sector 18"></div>
    </div>
    <label>Address</label><input type="text" name="address" value="<?= esc($g('address')) ?>">
    <div class="row">
      <div><label>Phone</label>
        <input type="text" name="phone" value="<?= esc($g('phone')) ?>" inputmode="tel"
               placeholder="9876543210" pattern="[0-9+()\-,\s]{6,60}">
        <span class="hint">10-digit mobile or a landline with STD code. Separate two numbers with a comma.</span></div>
      <div><label>Email</label><input type="email" name="email" value="<?= esc($g('email')) ?>" placeholder="name@example.com"></div>
    </div>
    <label>Website</label><input type="text" name="website" value="<?= esc($g('website')) ?>" placeholder="example.com">

    <?php /* Everything below here is shown on the public listing page but had
             no field in this form, so it could only ever be set in the database. */ ?>
    <h2 class="sect">Social profiles</h2>
    <?php $soc=json_decode($g('social','[]'),true); $soc=is_array($soc)?$soc:[]; $sv=static fn($k)=>(string)($soc[$k]??''); ?>
    <div class="row">
      <div><label>Facebook</label><input type="text" name="social_facebook" value="<?= esc($sv('facebook')) ?>" placeholder="https://facebook.com/…"></div>
      <div><label>Instagram</label><input type="text" name="social_instagram" value="<?= esc($sv('instagram')) ?>" placeholder="https://instagram.com/…"></div>
    </div>
    <div class="row">
      <div><label>X / Twitter</label><input type="text" name="social_twitter" value="<?= esc($sv('twitter')) ?>" placeholder="https://x.com/…"></div>
      <div><label>LinkedIn</label><input type="text" name="social_linkedin" value="<?= esc($sv('linkedin')) ?>" placeholder="https://linkedin.com/company/…"></div>
    </div>
    <div class="row">
      <div><label>YouTube</label><input type="text" name="social_youtube" value="<?= esc($sv('youtube')) ?>" placeholder="https://youtube.com/@…"></div>
      <div><label>WhatsApp <span class="opt">— number only</span></label><input type="text" name="social_whatsapp" value="<?= esc($sv('whatsapp')) ?>" placeholder="9876543210"></div>
    </div>

    <h2 class="sect">Video &amp; price</h2>
    <div class="row">
      <div><label>Video URL <span class="opt">— YouTube or Vimeo</span></label><input type="text" name="video_url" value="<?= esc($g('video_url')) ?>" placeholder="https://youtube.com/watch?v=…">
        <span class="hint">The provider is worked out from the link.</span></div>
      <div><label>Price range</label><select name="price_range">
        <?php $pr=(string)$g('price_range','no'); foreach(['no'=>'— not shown —','1'=>'₹ — budget','2'=>'₹₹ — moderate','3'=>'₹₹₹ — expensive','4'=>'₹₹₹₹ — fine dining'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $pr===$k?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select></div>
    </div>
    <label>Description</label><textarea name="description"><?= esc($g('description')) ?></textarea>
    <?php /* Everything a property needs, shown only when the type is Property.
             Same columns the owner's wizard writes, so both sides agree. */ ?>
    <div id="propBlock" <?= $_lt === 'property' ? '' : 'hidden' ?>>
      <h2 class="sect">Property details</h2>
      <?php
        helper('property');
        $pr   = $prop ?? [];
        $labs = hi_property_labels();
        $pk   = (string) ($pr['kind'] ?? 'flat');
        $pv   = static fn ($k) => isset($pr[$k]) ? (string) $pr[$k] : '';
      ?>
      <div class="row">
        <div><label>What is it</label>
          <select name="kind" id="admKind">
            <?php foreach (hi_property_kinds() as $kk => $kv): ?>
              <option value="<?= $kk ?>" data-group="<?= esc($kv[1]) ?>" <?= $pk === $kk ? 'selected' : '' ?>><?= esc($kv[0]) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div><label>Offered for</label>
          <select name="deal">
            <?php foreach (hi_property_deals() as $dk => $dv): ?>
              <option value="<?= $dk ?>" <?= (string) ($pr['deal'] ?? 'rent') === $dk ? 'selected' : '' ?>><?= esc($dv) ?></option>
            <?php endforeach; ?>
          </select></div>
      </div>
      <?php foreach (['home', 'pg', 'land', 'commercial'] as $grp): ?>
        <div class="pgroup" data-group="<?= $grp ?>" <?= hi_property_group($pk) === $grp ? '' : 'hidden' ?>>
          <div class="row" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
            <?php foreach (hi_property_fields($grp) as $fld): [$flabel, $ftype, $opts] = $labs[$fld]; ?>
              <div>
                <label><?= esc($flabel) ?></label>
                <?php if ($ftype === 'select'): ?>
                  <select name="<?= $fld ?>">
                    <option value="">&mdash;</option>
                    <?php foreach ($opts as $ok => $ov): ?>
                      <option value="<?= esc($ok) ?>" <?= $pv($fld) === (string) $ok ? 'selected' : '' ?>><?= esc($ov) ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php elseif ($ftype === 'check'): ?>
                  <div class="chk"><label><input type="checkbox" name="<?= $fld ?>" value="1" <?= $pv($fld) === '1' ? 'checked' : '' ?>> Yes</label></div>
                <?php else: ?>
                  <input type="<?= $ftype === 'date' ? 'date' : 'text' ?>" name="<?= $fld ?>" value="<?= esc($pv($fld)) ?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div id="bizBlock" <?= $_lt === 'property' ? 'hidden' : '' ?>>
    <h2 class="sect">Opening hours</h2>
    <?php
      // hi_hours() reads hours_json, then live's time_configuration, then the
      // single opened/closed pair — so an existing listing loads whatever it has
      helper('hours');
      $week = $row ? hi_hours($row) : [];
      $hm   = static fn($m) => $m === null ? '' : sprintf('%02d:%02d', intdiv((int)$m,60), (int)$m%60);
    ?>
    <label class="chk" style="margin-top:6px"><label><input type="checkbox" name="same_all_week" value="1" id="sameWeek" <?= $row?'':'checked' ?>> Same hours every day</label></label>
    <div class="hours">
      <?php foreach(hi_days() as $dk => $dname): $r0=$week[$dk][0]??null; $r1=$week[$dk][1]??null; $shut=$row && empty($week[$dk]); ?>
        <div class="hrow" data-day="<?= $dk ?>">
          <span class="hday"><?= esc($dname) ?></span>
          <input type="time" name="<?= $dk ?>_open"  value="<?= $hm($r0[0]??null) ?>">
          <span class="hsep">–</span>
          <input type="time" name="<?= $dk ?>_close" value="<?= $hm($r0[1]??null) ?>">
          <span class="hsep2">and</span>
          <input type="time" name="<?= $dk ?>_2_open"  value="<?= $hm($r1[0]??null) ?>" title="Second slot, e.g. after a lunch break">
          <span class="hsep">–</span>
          <input type="time" name="<?= $dk ?>_2_close" value="<?= $hm($r1[1]??null) ?>">
          <label class="hshut"><input type="checkbox" name="<?= $dk ?>_closed" value="1" <?= $shut?'checked':'' ?>> Closed</label>
        </div>
      <?php endforeach; ?>
    </div>
    <p class="hint">Leave the second pair empty unless the place shuts for a break. Ticking “same every day” copies Monday to the whole week.</p>
    </div><!-- /bizBlock: hours and amenities do not apply to a property -->
    <div class="row">
      <div><label>Latitude</label><input type="text" name="latitude" value="<?= esc($g('latitude')) ?>"></div>
      <div><label>Longitude</label><input type="text" name="longitude" value="<?= esc($g('longitude')) ?>"></div>
    </div>
    <?php if($amen): ?><label>Amenities</label><div class="chk">
      <?php foreach($amen as $a): ?><label><input type="checkbox" name="amenities[]" value="<?= (int)$a['id'] ?>" <?= in_array((int)$a['id'],$curAmen,true)?'checked':'' ?>> <?= esc($a['name']) ?></label><?php endforeach; ?>
    </div><?php endif; ?>
    <h2 class="sect">Photos</h2>
    <label>Photos <?= $photos?'— untick to remove, or pick the cover':'' ?></label>
    <?php if($photos): $cover=(string)$g('listing_cover'); ?><div class="photos"><?php foreach($photos as $ph): ?>
      <label>
        <img src="<?= base_url($ph) ?>" alt="">
        <input type="checkbox" name="keep[]" value="<?= esc($ph) ?>" checked>
        <?php /* which one heads the listing page and every card */ ?>
        <span class="cov"><input type="radio" name="cover" value="<?= esc($ph) ?>" <?= $cover===$ph?'checked':'' ?>> cover</span>
      </label>
    <?php endforeach; ?></div>
    <p class="hint">The ticked photos are kept. The one marked <b>cover</b> is used on cards and at the top of the listing.</p><?php endif; ?>
    <input type="file" name="photos[]" accept="image/*" multiple>
    <label style="margin-top:10px">Or add photo paths / URLs <span style="font-weight:400;color:#7b8aa5">— one per line</span></label>
    <textarea name="photo_paths" rows="3" placeholder="assets/img/cat/food-1.jpg&#10;https://example.com/photo.jpg"></textarea>
    <p style="margin:4px 0 0;font-size:12px;color:#7b8aa5">
      Use a path relative to the site root (e.g. <code>assets/img/cat/food-1.jpg</code>) or a full https:// URL.
      The first photo in the list becomes the cover image.
    </p>
    <h2 class="sect">Search engines</h2>
    <div class="row">
      <div><label>SEO title <span class="opt">— overrides the generated one</span></label><input type="text" name="seo_title" value="<?= esc($g('seo_title')) ?>" maxlength="180"></div>
      <div><label>Tags / keywords</label><input type="text" name="tags" value="<?= esc($g('tags')) ?>" placeholder="cafe noida, coffee sector 18"></div>
    </div>
    <label>Meta description <span class="opt">— about 155 characters</span></label>
    <textarea name="meta_description" rows="2" maxlength="320"><?= esc($g('meta_description')) ?></textarea>
    <label>Meta keywords <span class="opt">— the original’s seo_meta_tags</span></label>
    <input type="text" name="seo_meta_tags" value="<?= esc($g('seo_meta_tags')) ?>">

    <h2 class="sect">Publishing</h2>
    <div class="row">
      <div><label>Status</label><select name="status">
        <?php foreach(['active'=>'Active','pending'=>'Pending','rejected'=>'Rejected'] as $k=>$v): ?><option value="<?= $k ?>" <?= $g('status','active')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
      </select></div>
      <div><label>Featured</label><div class="chk"><label><input type="checkbox" name="is_featured" value="1" <?= $g('is_featured')?'checked':'' ?>> Show as Featured</label></div></div>
    </div>
    <button class="btn"><i class="fa-solid fa-floppy-disk"></i> <?= $row?'Save changes':'Create listing' ?></button>
    <?php if($row): ?><a href="<?= hi_listing_url($row) ?>" target="_blank" style="margin-left:12px;color:#1741a6;font-weight:600;text-decoration:none">View live <i class="fa-solid fa-arrow-up-right-from-square"></i></a><?php endif; ?>
  </form></div>
</div>
<script>
// "same every day" hides the other six rows — Monday's boxes are copied
// across on save, which is what the business wizard does too.
(function(){
  var cb = document.getElementById('sameWeek'), box = document.querySelector('.hours');
  if (!cb || !box) return;
  var sync = function(){ box.classList.toggle('same', cb.checked); };
  cb.addEventListener('change', sync); sync();
})();
// Property and business ask for different things — show one set, not both.
(function(){
  var t = document.getElementById('ltype'),
      prop = document.getElementById('propBlock'),
      biz = document.getElementById('bizBlock');
  if (!t || !prop || !biz) return;
  var sync = function(){
    var on = t.value === 'property';
    prop.hidden = !on; biz.hidden = on;
    // a hidden field must not post, or an empty hours grid would wipe real hours
    biz.querySelectorAll('input,select,textarea').forEach(function(i){ i.disabled = on; });
    prop.querySelectorAll('input,select').forEach(function(i){ i.disabled = !on; });
    if (on) { kindSync(); }
  };
  var kindSync = function(){
    var k = document.getElementById('admKind');
    if (!k) return;
    var g = k.options[k.selectedIndex].dataset.group;
    prop.querySelectorAll('.pgroup').forEach(function(p){
      var show = p.dataset.group === g;
      p.hidden = !show;
      p.querySelectorAll('input,select').forEach(function(i){ i.disabled = !show; });
    });
  };
  t.addEventListener('change', sync);
  var k = document.getElementById('admKind');
  if (k) { k.addEventListener('change', kindSync); }
  sync();
})();
</script>
</body>
</html>
