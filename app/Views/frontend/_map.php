<?php
/**
 * Map of the current results. Leaflet is fetched only when the map is first
 * opened, so pages that nobody maps stay as light as they were.
 *
 * @var array $listings
 */
helper('property');
$pins = [];
foreach ($listings as $_ml) {
    $lat = (float) ($_ml['latitude'] ?? 0);
    $lng = (float) ($_ml['longitude'] ?? 0);
    if (! $lat || ! $lng) { continue; }
    $pins[] = [
        'n' => (string) $_ml['name'],
        'a' => (string) ($_ml['locality_name'] ?? ''),
        'u' => hi_listing_url($_ml),
        'y' => $lat,
        'x' => $lng,
        'r' => round((float) ($_ml['_ravg'] ?? 0), 1),
        // a flat has no star rating — the price is what its pin should say
        'p' => hi_is_property($_ml) ? hi_property_price(hi_property_of((int) $_ml['id'])) : '',
    ];
}
if ($pins):
?>
<div class="maptoggle">
  <button type="button" id="mapbtn"><i class="fa-solid fa-map-location-dot"></i> Show <?= count($pins) ?> on map</button>
</div>
<div id="resultmap" class="mapfull" hidden role="dialog" aria-modal="true" aria-label="Map of results"><div class="mapbar"><span class="maptitle"><i class="fa-solid fa-map-location-dot"></i> <?= count($pins) ?> places</span><a class="mapext" id="mapext" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Google Maps</a><button type="button" class="mapclose" id="mapclose" aria-label="Close map"><i class="fa-solid fa-xmark"></i> Close</button></div></div>
<script>
(function(){
  var btn = document.getElementById('mapbtn'), box = document.getElementById('resultmap'), made = false;
  var pins = <?= json_encode($pins, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  function draw(){
    // give Leaflet its own canvas so the close button is not swallowed
    var canvas = document.createElement('div');
    canvas.className = 'mapcanvas';
    box.appendChild(canvas);
    <?php
      // zoom and starting point come from admin > Map (Admin::map_settings in
      // the original); these were hard-coded
      $_mz  = (int) (hi_setting('map_min_zoom_listings') ?: 13);
      $_mx  = (int) (hi_setting('map_max_zoom') ?: 18);
      $_loc = trim((string) hi_setting('map_default_location')) ?: '28.5355,77.3910';
      [$_dy, $_dx] = array_pad(array_map('trim', explode(',', $_loc)), 2, '0');
    ?>
    var home = [<?= (float) $_dy ?>, <?= (float) $_dx ?>];
    var start = (pins.length && pins[0].y && pins[0].x) ? [pins[0].y, pins[0].x] : home;
    var map = L.map(canvas).setView(start, <?= $_mz > 0 ? $_mz : 13 ?>);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: <?= $_mx > 0 ? $_mx : 18 ?>, attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    var group = [];
    pins.forEach(function(p){
      var m = L.marker([p.y, p.x]).addTo(map);
      m.bindPopup('<b>' + p.n + '</b>' + (p.a ? '<br><span style="color:#7c8aa3">' + p.a + '</span>' : '')
        + (p.p ? '<br><b>' + p.p + '</b>' : (p.r ? '<br>★ ' + p.r : '')) + '<br><a href="' + p.u + '" style="color:#1741a6;font-weight:700">View details</a>');
      group.push([p.y, p.x]);
    });
    if (group.length > 1) { map.fitBounds(group, {padding: [30, 30]}); }
  }

  function closeMap(){
    if (box.hidden) { return; }
    box.hidden = true;
    btn.innerHTML = '<i class="fa-solid fa-map-location-dot"></i> Show <?= count($pins) ?> on map';
    document.documentElement.style.overflow = '';
  }
  document.getElementById('mapclose').addEventListener('click', function(){ closeMap(); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { closeMap(); } });

  btn.addEventListener('click', function(){
    if (!box.hidden) { closeMap(); return; }
    box.hidden = false;
    btn.innerHTML = '<i class="fa-solid fa-map-location-dot"></i> Show <?= count($pins) ?> on map';
    document.documentElement.style.overflow = 'hidden';
    var ext = document.getElementById('mapext');
    if (ext) { ext.href = 'https://www.google.com/maps/search/?api=1&query=' + pins[0].y + ',' + pins[0].x; }
    if (made) { return; }
    made = true;

    var css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(css);

    var js = document.createElement('script');
    js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    js.onload = draw;
    js.onerror = function(){ box.innerHTML = '<p style="padding:20px;color:#7c8aa3"><?= esc(hi_c('_map.p_the_map_could_not_be_loaded', 'The map could not be loaded.')) ?></p>'; };
    document.body.appendChild(js);
  });
})();
</script>
<?php endif; ?>
