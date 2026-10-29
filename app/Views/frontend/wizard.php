<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<style>
/* the type picker and the property specs form — kept with the wizard so the
   page never depends on a separate stylesheet upload landing */
.wz-types{display:grid;grid-template-columns:repeat(auto-fit,minmax(148px,1fr));gap:10px;margin-top:2px}
.wz-type{position:relative;display:flex;flex-direction:column;align-items:center;gap:4px;text-align:center;
  border:1.5px solid var(--line);border-radius:12px;padding:15px 10px;cursor:pointer;background:#fff}
.wz-type input{position:absolute;opacity:0;pointer-events:none}
.wz-type i{font-size:19px;color:#8fa3c4}
.wz-type b{font-size:13.5px;color:var(--ink)}
.wz-type span{font-size:11.5px;color:var(--muted);line-height:1.35}
.wz-type:hover{border-color:#b9cdf0;background:#f8fbff}
.wz-type.on{border-color:var(--blue);background:#f2f6fd}
.wz-type.on i,.wz-type.on b{color:var(--blue)}
.wz-deal{display:flex;gap:9px;flex-wrap:wrap;margin-top:2px}
.wz-deal label{border:1.5px solid var(--line);border-radius:10px;padding:9px 16px;font-size:13.5px;
  font-weight:600;color:var(--ink);cursor:pointer;background:#fff}
.wz-deal label.on{border-color:var(--blue);background:var(--blue);color:#fff}
.wz-deal input{margin-right:6px}
.wz-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:0 16px}
.wz-check{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--line);border-radius:10px;
  padding:12px 14px;font-size:14px;background:#fff;cursor:pointer;width:100%}
</style>
<?php
// every entry in $steps needs a label here — a missing one is a fatal on PHP 8,
// which is what took the whole wizard down when the "details" step was added
$labels = ['basics' => 'Business', 'media' => 'Media', 'amenities' => 'Amenities',
           'details' => 'Details', 'contact' => 'Contact', 'location' => 'Location',
           'hours' => 'Hours', 'seo' => 'SEO', 'specs' => 'Specs', 'done' => 'Done'];
$isProp = ! empty($isProp);
if ($isProp) { $labels['basics'] = 'Property'; $labels['media'] = 'Photos'; }
$idx = array_search($step, $steps, true);
$inp = 'width:100%;border:1px solid var(--line);border-radius:10px;padding:12px 14px;font-size:14.5px;font-family:inherit';
$lab = 'font-size:13px;font-weight:600;color:#444;display:block;margin:12px 0 6px';
$g   = static fn ($k) => isset($row[$k]) ? esc($row[$k]) : '';
$btn = 'width:100%;margin-top:18px;background:var(--blue);color:#fff;border:0;border-radius:10px;padding:13px;font-weight:700;cursor:pointer';
?>
<section style="background:linear-gradient(120deg,#0a2a6b,#123a8f);color:#fff;padding:40px 20px"><div class="wrap"><h1 style="color:#fff;margin:0"><?= $step === 'done' ? 'You&rsquo;re all set!' : 'List your business' ?></h1></div></section>
<section class="sec"><div class="wrap" style="max-width:720px">
  <ol style="display:flex;list-style:none;padding:0;margin:0 0 24px;justify-content:space-between">
    <?php foreach ($steps as $i => $s): ?>
      <li style="display:flex;flex-direction:column;align-items:center;gap:6px;flex:1">
        <span style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;<?= $i < $idx ? 'background:#1741a6;color:#fff' : ($i === $idx ? 'border:2px solid #1741a6;color:#1741a6;background:#fff' : 'border:2px solid #e3e9f2;color:#9aa6b6;background:#fff') ?>"><?= $i < $idx ? '&#10003;' : $i + 1 ?></span>
        <span style="font-size:11.5px;font-weight:600;color:<?= $i === $idx ? '#1741a6' : '#9aa6b6' ?>"><?= esc($labels[$s] ?? ucfirst($s)) ?></span>
      </li>
    <?php endforeach; ?>
  </ol>
  <div style="border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:var(--shadow);background:#fff">
    <?php if ($err): ?><div style="background:#fdecec;color:#c0392b;border-radius:10px;padding:11px 14px;margin-bottom:14px"><?= esc($err) ?></div><?php endif; ?>

    <?php if ($step === 'basics'): ?>
      <form method="post" action="<?= base_url($id ? 'list/basics/' . $id : 'list') ?>">
        <label style="<?= $lab ?>">Business name *</label><input name="name" required value="<?= $g('name') ?>" style="<?= $inp ?>">
        <?php $curCat = (int) (json_decode((string) ($row['categories'] ?? '[]'), true)[0] ?? 0); ?>
        <label style="<?= $lab ?>">Category *</label><select name="category" required style="<?= $inp ?>"><option value="">Select&hellip;</option><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $curCat === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option><?php endforeach; ?></select>
        <?php
          // What kind of business this is (the original's add_listing_type step).
          // It decides which detail step appears later — menu, rooms, services
          // or products. Left as "Something else" there is simply no such step.
          $curType = strtolower((string) ($row['listing_type'] ?? ''));
          $types = ['restaurant'=>'Restaurant or cafe — has a menu','hotel'=>'Hotel or guest house — has rooms',
                    'beauty'=>'Salon or spa — has services','shop'=>'Shop — has products','general'=>'Something else'];
        ?>
        <?php /* A dropdown was fine for four near-identical shops. Property
                 sends the whole wizard down a different path, so the choice is
                 made on cards you can actually see. */ ?>
        <?php
          $cards = [
            'restaurant' => ['utensils', 'Restaurant', 'has a menu'],
            'hotel'      => ['hotel', 'Hotel', 'has rooms'],
            'beauty'     => ['spa', 'Salon / Spa', 'has services'],
            'shop'       => ['bag-shopping', 'Shop', 'has products'],
            'property'   => ['building', 'Property', 'flat · PG · plot · office'],
            'general'    => ['store', 'Something else', ''],
          ];
          if ($curType === '' && $isProp) { $curType = 'property'; }
        ?>
        <label style="<?= $lab ?>">What are you listing?</label>
        <div class="wz-types">
          <?php foreach ($cards as $tk => [$ic, $tt, $sub]): $on = $curType === $tk || ($curType === '' && $tk === 'general'); ?>
            <label class="wz-type<?= $on ? ' on' : '' ?>">
              <input type="radio" name="listing_type" value="<?= $tk ?>" <?= $on ? 'checked' : '' ?>>
              <i class="fa-solid fa-<?= $ic ?>"></i>
              <b><?= esc($tt) ?></b>
              <?php if ($sub !== ''): ?><span><?= esc($sub) ?></span><?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="wz-typenote" style="font-size:12.5px;color:var(--muted);margin:8px 0 0">
          Picking <b>Property</b> asks for BHK, area and rent instead of opening hours.</p>
        <label style="<?= $lab ?>">Area</label><select name="locality" style="<?= $inp ?>"><option value="">Select&hellip;</option><?php foreach ($locs as $l): ?><option value="<?= (int) $l['id'] ?>" <?= (int) ($row['locality_id'] ?? 0) === (int) $l['id'] ? 'selected' : '' ?>><?= esc($l['name']) ?></option><?php endforeach; ?></select>
        <label style="<?= $lab ?>">Address</label><input name="address" value="<?= $g('address') ?>" style="<?= $inp ?>">
        <label style="<?= $lab ?>">Phone</label><input name="phone" value="<?= $g('phone') ?>" style="<?= $inp ?>">
        <label style="<?= $lab ?>">Description</label><textarea name="description" rows="3" style="<?= $inp ?>"><?= $g('description') ?></textarea>
        <button style="<?= $btn ?>">Continue &rarr;</button>
      </form>
    <?php elseif ($step === 'specs'): ?>
      <?php
        $pr     = $prop ?? [];
        $labs   = hi_property_labels();
        $kinds  = hi_property_kinds();
        $curKind = (string) ($pr['kind'] ?? 'flat');
        $curDeal = (string) ($pr['deal'] ?? 'rent');
        // one panel per group, all rendered, only the matching one shown — so
        // switching kind is instant and nothing is lost on the way
        $groups = ['home', 'pg', 'land', 'commercial'];
        $pv = static fn ($k) => isset($pr[$k]) ? (string) $pr[$k] : '';
      ?>
      <form method="post" action="<?= base_url('list/specs/' . $id) ?>" id="specForm">
        <label style="<?= $lab ?>">What is it?</label>
        <select name="kind" id="propKind" style="<?= $inp ?>">
          <?php foreach ($kinds as $kk => $kv): ?>
            <option value="<?= $kk ?>" data-group="<?= esc($kv[1]) ?>" <?= $curKind === $kk ? 'selected' : '' ?>><?= esc($kv[0]) ?></option>
          <?php endforeach; ?>
        </select>

        <label style="<?= $lab ?>">Offered for</label>
        <div class="wz-deal">
          <?php foreach (hi_property_deals() as $dk => $dv): ?>
            <label class="<?= $curDeal === $dk ? 'on' : '' ?>">
              <input type="radio" name="deal" value="<?= $dk ?>" <?= $curDeal === $dk ? 'checked' : '' ?>> <?= esc($dv) ?>
            </label>
          <?php endforeach; ?>
        </div>

        <?php foreach ($groups as $grp): ?>
          <div class="wz-spec" data-group="<?= $grp ?>" <?= hi_property_group($curKind) === $grp ? '' : 'hidden' ?>>
            <div class="wz-grid">
              <?php foreach (hi_property_fields($grp) as $fld): [$flabel, $ftype, $opts] = $labs[$fld]; ?>
                <div>
                  <label style="<?= $lab ?>"><?= esc($flabel) ?></label>
                  <?php if ($ftype === 'select'): ?>
                    <select name="<?= $fld ?>" style="<?= $inp ?>">
                      <option value="">&mdash;</option>
                      <?php foreach ($opts as $ok => $ov): ?>
                        <option value="<?= esc($ok) ?>" <?= $pv($fld) === (string) $ok ? 'selected' : '' ?>><?= esc($ov) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php elseif ($ftype === 'check'): ?>
                    <label class="wz-check"><input type="checkbox" name="<?= $fld ?>" value="1" <?= $pv($fld) === '1' ? 'checked' : '' ?>> Yes</label>
                  <?php else: ?>
                    <input type="<?= $ftype === 'number' ? 'text' : esc($ftype) ?>" name="<?= $fld ?>"
                           value="<?= esc($pv($fld)) ?>" style="<?= $inp ?>"
                           <?= $ftype === 'number' ? 'inputmode="numeric"' : '' ?>>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <button style="<?= $btn ?>">Continue &rarr;</button>
      </form>
      <script>
      // only the panel for the chosen kind is on screen; the others keep their
      // values so nothing is lost if the owner changes their mind
      (function(){
        var sel = document.getElementById('propKind');
        if (!sel) return;
        var panels = document.querySelectorAll('.wz-spec');
        var sync = function () {
          var g = sel.options[sel.selectedIndex].dataset.group;
          panels.forEach(function (p) {
            var on = p.dataset.group === g;
            p.hidden = !on;
            p.querySelectorAll('input,select').forEach(function (i) { i.disabled = !on; });
          });
        };
        sel.addEventListener('change', sync); sync();
      })();
      </script>
    <?php elseif ($step === 'media'): ?>
      <form method="post" action="<?= base_url('list/media/' . $id) ?>" enctype="multipart/form-data">
        <label style="<?= $lab ?>">Business photos <span style="color:var(--muted);font-weight:400">(JPG/PNG/WebP, up to 10, max 5&nbsp;MB each)</span></label>
        <?php if (! empty($photos)): ?>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:10px;margin-bottom:12px">
            <?php foreach ($photos as $ph): ?>
              <label style="position:relative;display:block;border-radius:10px;overflow:hidden;border:1px solid var(--line);cursor:pointer">
                <img src="<?= base_url($ph) ?>" style="width:100%;height:80px;object-fit:cover;display:block" alt="">
                <input type="checkbox" name="keep[]" value="<?= esc($ph) ?>" checked style="position:absolute;top:5px;left:5px;transform:scale(1.2)" title="Keep this photo">
                <?php /* which photo heads the listing and every card */ ?>
                <span style="position:absolute;left:0;right:0;bottom:0;background:rgba(15,28,51,.72);color:#fff;font-size:10.5px;display:flex;align-items:center;justify-content:center;gap:4px;padding:3px 0">
                  <input type="radio" name="cover" value="<?= esc($ph) ?>" <?= (string)($row['listing_cover'] ?? '') === $ph ? 'checked' : '' ?>> cover
                </span>
              </label>
            <?php endforeach; ?>
          </div>
          <p style="font-size:12px;color:var(--muted);margin:-4px 0 12px">Untick a photo to remove it. The one marked <b>cover</b> is what people see first.</p>
        <?php endif; ?>
        <input type="file" name="photos[]" accept="image/*" multiple style="<?= $inp ?>;padding:9px">
        <?php $canVid = hi_can_add_video((int) session()->get('uid'), (int) session()->get('role')); ?>
        <label style="<?= $lab ?>">Video URL (YouTube / Vimeo)</label>
        <?php if ($canVid): ?>
          <input name="video_url" value="<?= $g('video_url') ?>" style="<?= $inp ?>" placeholder="https://youtube.com/watch?v=...">
        <?php else: ?>
          <input disabled style="<?= $inp ?>;background:#f5f7fb;color:var(--muted)" placeholder="<?= esc(hi_c('wizard.ph_available_on_a_plan_that_includes_video', 'Available on a plan that includes video'), 'attr') ?>">
          <p style="font-size:12.5px;color:var(--muted);margin:6px 0 0"><a href="<?= hi_page('pricing') ?>">See plans</a> to add a video to your listing.</p>
        <?php endif; ?>
        <button style="<?= $btn ?>">Continue &rarr;</button>
      </form>
    <?php elseif ($step === 'amenities'): ?>
      <form method="post" action="<?= base_url('list/amenities/' . $id) ?>">
        <label style="<?= $lab ?>">What do you offer?</label>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px">
          <?php foreach ($amen as $a): $aid = (int) $a['id']; ?><label style="display:flex;align-items:center;gap:8px;background:#f7f9fc;border:1px solid #eef1f6;border-radius:9px;padding:9px 11px;font-size:13.5px;cursor:pointer"><input type="checkbox" name="amenities[]" value="<?= $aid ?>" <?= in_array($aid, $cur, true) ? 'checked' : '' ?>> <?= esc($a['name']) ?></label><?php endforeach; ?>
        </div>
        <button style="<?= $btn ?>">Continue &rarr;</button>
      </form>
    <?php elseif ($step === 'contact'): ?>
      <?php
        // prefill from the stored JSON — without this, saving the step again
        // with empty boxes wiped whatever was already there
        $soc = json_decode($row['social'] ?? '', true);
        $soc = is_array($soc) ? $soc : [];
      ?>
      <form method="post" action="<?= base_url('list/contact/' . $id) ?>">
        <?php /* Email and the rest of the social profiles are shown on the
                 listing page but the owner had no way to set them — only an
                 admin could, straight in the database. */ ?>
        <label style="<?= $lab ?>">Email</label>
        <input type="email" name="email" value="<?= $g('email') ?>" style="<?= $inp ?>" placeholder="<?= esc(hi_c('wizard.ph_you_yourbusiness_com', 'you@yourbusiness.com'), 'attr') ?>">
        <label style="<?= $lab ?>">Website</label>
        <input name="website" value="<?= $g('website') ?>" style="<?= $inp ?>" placeholder="<?= esc(hi_c('wizard.ph_yourbusiness_com', 'yourbusiness.com'), 'attr') ?>">
        <label style="<?= $lab ?>">WhatsApp <span style="color:var(--muted);font-weight:400">— number only</span></label>
        <input name="whatsapp" value="<?= esc($soc['whatsapp'] ?? '') ?>" style="<?= $inp ?>" placeholder="9876543210">
        <label style="<?= $lab ?>">Facebook</label>
        <input name="facebook" value="<?= esc($soc['facebook'] ?? '') ?>" style="<?= $inp ?>" placeholder="<?= esc(hi_c('wizard.ph_https_facebook_com', 'https://facebook.com/…'), 'attr') ?>">
        <label style="<?= $lab ?>">Instagram</label>
        <input name="instagram" value="<?= esc($soc['instagram'] ?? '') ?>" style="<?= $inp ?>" placeholder="<?= esc(hi_c('wizard.ph_https_instagram_com', 'https://instagram.com/…'), 'attr') ?>">
        <label style="<?= $lab ?>">X / Twitter</label>
        <input name="twitter" value="<?= esc($soc['twitter'] ?? '') ?>" style="<?= $inp ?>" placeholder="<?= esc(hi_c('wizard.ph_https_x_com', 'https://x.com/…'), 'attr') ?>">
        <label style="<?= $lab ?>">LinkedIn</label>
        <input name="linkedin" value="<?= esc($soc['linkedin'] ?? '') ?>" style="<?= $inp ?>" placeholder="<?= esc(hi_c('wizard.ph_https_linkedin_com_company', 'https://linkedin.com/company/…'), 'attr') ?>">
        <label style="<?= $lab ?>">YouTube</label>
        <input name="youtube" value="<?= esc($soc['youtube'] ?? '') ?>" style="<?= $inp ?>" placeholder="<?= esc(hi_c('wizard.ph_https_youtube_com', 'https://youtube.com/@…'), 'attr') ?>">
        <button style="<?= $btn ?>">Continue &rarr;</button>
      </form>
    <?php elseif ($step === 'location'): ?>
      <form method="post" action="<?= base_url('list/location/' . $id) ?>">
        <label style="<?= $lab ?>">Street address</label>
        <input name="address" value="<?= $g('address') ?>" style="<?= $inp ?>">
        <label style="<?= $lab ?>">Area</label>
        <select name="locality" style="<?= $inp ?>">
          <option value="">Select&hellip;</option>
          <?php foreach ($locs as $lc): ?>
            <option value="<?= (int) $lc['id'] ?>" <?= (int) ($row['locality_id'] ?? 0) === (int) $lc['id'] ? 'selected' : '' ?>><?= esc($lc['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <label style="<?= $lab ?>">Map position</label>
        <p style="font-size:12.5px;color:var(--muted);margin:0 0 8px"><?= esc(hi_c('wizard.p_without_these_your_business_has_no_pin_on_th', 'Without these your business has no pin on the map and can\'t give directions. Drag the marker, or paste coordinates from Google Maps.')) ?></p>
        <div style="display:flex;gap:9px">
          <input name="latitude" id="wlat" value="<?= esc($row['latitude'] ?? '') ?>" placeholder="<?= esc(hi_c('wizard.ph_latitude', 'Latitude'), 'attr') ?>" style="<?= $inp ?>">
          <input name="longitude" id="wlng" value="<?= esc($row['longitude'] ?? '') ?>" placeholder="<?= esc(hi_c('wizard.ph_longitude', 'Longitude'), 'attr') ?>" style="<?= $inp ?>">
        </div>
        <div id="wmap" style="height:300px;border:1px solid var(--line);border-radius:12px;margin-top:10px"></div>
        <button style="<?= $btn ?>">Continue &rarr;</button>
      </form>
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
      <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
      <script>
      (function(){
        <?php $_md = array_map('trim', explode(',', hi_setting('map_default_location', '28.5355,77.3910'))); ?>
        var lat = parseFloat(document.getElementById('wlat').value) || <?= (float) ($_md[0] ?? 28.5355) ?>;
        var lng = parseFloat(document.getElementById('wlng').value) || <?= (float) ($_md[1] ?? 77.3910) ?>;
        if (typeof L === 'undefined') { document.getElementById('wmap').style.display = 'none'; return; }
        var map = L.map('wmap').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: <?= (int) hi_setting('map_max_zoom', '18') ?>, minZoom: <?= (int) hi_setting('map_min_zoom', '1') ?>, attribution: '&copy; OpenStreetMap'}).addTo(map);
        var m = L.marker([lat, lng], {draggable: true}).addTo(map);
        function put(ll){
          document.getElementById('wlat').value = ll.lat.toFixed(6);
          document.getElementById('wlng').value = ll.lng.toFixed(6);
        }
        // Seed the boxes from wherever the marker starts. Without this a seller
        // who never touched the map saved a blank position, and the listing lost
        // both its map pin and its "What's nearby" — the two things a flat is
        // judged on. Dragging still overrides it.
        if (! document.getElementById('wlat').value || ! document.getElementById('wlng').value) {
          put(m.getLatLng());
        }
        m.on('dragend', function(){ put(m.getLatLng()); });
        map.on('click', function(e){ m.setLatLng(e.latlng); put(e.latlng); });
      })();
      </script>

    <?php elseif ($step === 'seo'): ?>
      <?php $sv = static fn($k, $d = '') => esc($row[$k] ?? '') !== '' ? esc($row[$k]) : $d; ?>
      <form method="post" action="<?= base_url('list/seo/' . $id) ?>">
        <p style="font-size:13.5px;color:var(--body);line-height:1.6;margin:0 0 14px"><?= esc(hi_c('wizard.p_how_your_listing_reads_in_google_leave_these', 'How your listing reads in Google. Leave these blank and we build them from the business name, category and area — which is usually fine.')) ?></p>
        <label style="<?= $lab ?>">Page title <span style="color:var(--muted);font-weight:400">(up to 70 characters)</span></label>
        <input name="seo_title" maxlength="70" value="<?= $sv('seo_title') ?>" style="<?= $inp ?>"
               placeholder="<?= esc(($row['name'] ?? 'Your business') . ' — Noida') ?>">
        <label style="<?= $lab ?>">Meta description <span style="color:var(--muted);font-weight:400">(up to 160)</span></label>
        <textarea name="meta_description" maxlength="160" rows="3" style="<?= $inp ?>"
                  placeholder="<?= esc(hi_c('wizard.ph_one_or_two_sentences_someone_would_read_in_s', 'One or two sentences someone would read in search results.'), 'attr') ?>"><?= $sv('meta_description') ?></textarea>
        <label style="<?= $lab ?>">Keywords <span style="color:var(--muted);font-weight:400">(comma separated)</span></label>
        <input name="tags" value="<?= $sv('tags') ?>" style="<?= $inp ?>" placeholder="<?= esc(hi_c('wizard.ph_pizza_italian_sector_18', 'pizza, italian, sector 18'), 'attr') ?>">
        <button style="<?= $btn ?>">Finish &amp; publish &rarr;</button>
      </form>
    <?php elseif ($step === 'details'): ?>
      <?php $dm = $dmeta; $dflds = $dm['fields']; ?>
      <form method="post" action="<?= base_url('list/details/' . $id) ?>">
        <label style="<?= $lab ?>"><?= esc($dm['title']) ?></label>
        <p style="font-size:12.5px;color:var(--muted);margin:0 0 12px">
          Leave a row's <?= esc(strtolower($dflds['name'])) ?> blank to remove it. Price can be a number or text like &ldquo;On request&rdquo;.
        </p>
        <div id="drows">
          <?php
            $blank = array_fill_keys(array_keys($dflds), '');
            $rows  = $drows ?: [$blank];
            foreach ($rows as $r):
          ?>
            <div class="drow" style="border:1px solid var(--line);border-radius:11px;padding:11px 12px;margin-bottom:9px;background:#fff">
              <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px">
                <?php foreach ($dflds as $fk => $flabel): ?>
                  <label style="font-size:12px;color:var(--muted)"><?= esc($flabel) ?>
                    <input name="d_<?= esc($fk) ?>[]" value="<?= esc($r[$fk] ?? '') ?>" style="<?= $inp ?>;margin:3px 0 0">
                  </label>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="d_photo[]" value="<?= esc($r['photo'] ?? '') ?>">
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" id="daddrow" style="background:#fff;border:1px solid var(--line);color:var(--ink);padding:9px 15px;border-radius:9px;font-weight:600;font-size:13.5px;cursor:pointer">+ <?= esc($dm['add']) ?></button>
        <button style="<?= $btn ?>">Continue &rarr;</button>
      </form>
      <script>
      (function(){
        var box = document.getElementById('drows'), add = document.getElementById('daddrow');
        add.addEventListener('click', function(){
          var c = box.firstElementChild.cloneNode(true);
          c.querySelectorAll('input').forEach(function(i){ i.value = ''; });
          box.appendChild(c);
          var f = c.querySelector('input'); if (f) { f.focus(); }
        });
      })();
      </script>
    <?php elseif ($step === 'hours'): ?>
      <?php
        $wk  = hi_hours($row ?: []);
        $hhm = static fn($m) => sprintf('%02d:%02d', intdiv((int)$m, 60) % 24, (int)$m % 60);
        $allSame = hi_hours_same_all_week($wk);
        // A day with no range means "closed" only once hours have actually been
        // saved. On a listing that has never reached this step every day is
        // empty, and ticking Closed for all seven told the owner their business
        // was shut all week — and saved it that way if they just hit Continue,
        // which then kept them out of every "Open now" result.
        $everSaved = trim((string) ($row['hours_json'] ?? '')) !== '';
      ?>
      <form method="post" action="<?= base_url('list/hours/' . $id) ?>">
        <label style="<?= $lab ?>">When are you open?</label>
        <label style="display:flex;align-items:center;gap:9px;margin:0 0 14px;font-size:14px;color:var(--body)">
          <input type="checkbox" name="same_all_week" id="samewk" value="1" <?= $allSame ? 'checked' : '' ?>>
          Same hours every day
        </label>

        <div id="hrows">
          <?php foreach (hi_days() as $dk => $dl): $rr = $wk[$dk] ?? null; $r1 = $rr[0] ?? null; $r2 = $rr[1] ?? null; ?>
            <div class="hrow" data-day="<?= $dk ?>" style="margin-bottom:12px;<?= $dk !== 'mon' && $allSame ? 'display:none' : '' ?>">
              <div style="display:flex;gap:9px;align-items:center">
                <span style="flex:0 0 92px;font-size:13.5px;color:var(--body)"><?= $dl ?></span>
                <input type="time" name="<?= $dk ?>_open"  value="<?= $r1 ? $hhm($r1[0]) : '' ?>" style="<?= $inp ?>;margin:0">
                <span style="color:var(--muted)">&ndash;</span>
                <input type="time" name="<?= $dk ?>_close" value="<?= $r1 ? $hhm($r1[1]) : '' ?>" style="<?= $inp ?>;margin:0">
                <label class="hclosed" style="display:flex;align-items:center;gap:5px;font-size:12.5px;color:var(--muted);white-space:nowrap">
                  <input type="checkbox" name="<?= $dk ?>_closed" value="1" <?= (! $rr && $everSaved) ? 'checked' : '' ?>> Closed
                </label>
              </div>
              <div style="display:flex;gap:9px;align-items:center;margin-top:6px">
                <span style="flex:0 0 92px;font-size:12px;color:var(--muted);text-align:right;padding-right:4px">and again</span>
                <input type="time" name="<?= $dk ?>_2_open"  value="<?= $r2 ? $hhm($r2[0]) : '' ?>" style="<?= $inp ?>;margin:0">
                <span style="color:var(--muted)">&ndash;</span>
                <input type="time" name="<?= $dk ?>_2_close" value="<?= $r2 ? $hhm($r2[1]) : '' ?>" style="<?= $inp ?>;margin:0">
                <span style="font-size:12px;color:var(--muted);white-space:nowrap">optional</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <p style="font-size:12.5px;color:var(--muted);margin:4px 0 0"><?= esc(hi_c('wizard.p_closing_for_lunch_use_the_second_row_9_00_am', 'Closing for lunch? Use the second row — 9:00 AM to 1:00 PM, then 3:00 PM to 8:00 PM. Closing after midnight? Put the time as it is — 8:00 PM to 2:00 AM works.')) ?></p>
        <button style="<?= $btn ?>">Continue &rarr;</button>
      </form>
      <script>
      (function(){
        var same = document.getElementById('samewk');
        function sync(){
          document.querySelectorAll('.hrow').forEach(function(r){
            r.style.display = (same.checked && r.dataset.day !== 'mon') ? 'none' : 'flex';
          });
          var m = document.querySelector('.hrow[data-day="mon"] span');
          if (m) { m.textContent = same.checked ? 'Every day' : 'Monday'; }
          // "Same hours every day" makes the server ignore the Closed box, so
          // showing it invites a tick that silently does nothing
          document.querySelectorAll('.hclosed').forEach(function(l){
            l.style.visibility = same.checked ? 'hidden' : 'visible';
          });
        }
        same.addEventListener('change', sync); sync();
      })();
      </script>
    <?php else: ?>
      <div style="text-align:center;padding:14px 0"><div style="font-size:52px;color:#22c55e;margin-bottom:8px">&#10003;</div><h2 style="margin:0 0 8px"><?= esc(hi_c('wizard.submitted_for_review', 'Submitted for review')) ?></h2><p style="color:#666"><?= esc(hi_c('wizard.p_your_business_is_pending_admin_approval_and_', 'Your business is pending admin approval and will go live shortly.')) ?></p><a href="<?= base_url('dashboard') ?>" style="display:inline-block;margin-top:14px;background:var(--blue);color:#fff;padding:12px 24px;border-radius:10px;font-weight:700">Go to dashboard</a></div>
    <?php endif; ?>
  </div>
</div></section>
<?= $this->endSection() ?>
