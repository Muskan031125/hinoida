<?php
// One-time helper: adds 2 more SAMPLE homes for sale (local database only). Safe to delete.
if (! in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8');
$db = @new mysqli('127.0.0.1', 'root', '', 'hinoidanew');
if ($db->connect_errno) { exit('DB error: ' . $db->connect_error); }
$db->set_charset('utf8mb4');
$homes = [
  ['Sample · 3 BHK Flat, Sector 75', 'Ready-to-move 3 BHK in a gated society with a clubhouse, pool and two covered parkings.', 'Sample Residency, Sector 75, Noida', '28.5760', '77.3880', 'Sector75', 'flat', 3, 3, 2, 1650, 'semi', 14, 24, 'east', 5, 13500000, 'Sample Residency'],
  ['Sample · 2 BHK Flat, Sector 62', 'Corner 2 BHK close to offices and the Sector 62 metro, with lift and power backup.', 'Tower D, Sector 62, Noida', '28.6270', '77.3680', 'Sector62', 'flat', 2, 2, 1, 1100, 'unfurnished', 6, 16, 'north', 7, 8500000, 'Sample Towers'],
];
foreach ($homes as [$name, $desc, $addr, $lat, $lng, $loc, $kind, $bed, $bath, $bal, $area, $furn, $fl, $tf, $face, $age, $price, $proj]) {
  $c = $db->prepare('SELECT id FROM listing WHERE name = ? LIMIT 1'); $c->bind_param('s', $name); $c->execute();
  if ($c->get_result()->fetch_row()) { echo "Already there: $name\n"; continue; }
  $r = $db->prepare("SELECT id FROM locality WHERE REPLACE(REPLACE(name,' ',''),'-','') = ? LIMIT 1"); $r->bind_param('s', $loc); $r->execute();
  $locId = (int) (($r->get_result()->fetch_row())[0] ?? 0);
  $html = '<p>SAMPLE LISTING for testing only. ' . $desc . '</p>'; $cats = '["6"]';
  $s = $db->prepare("INSERT INTO listing (name, description, categories, address, latitude, longitude, locality_id, status, listing_type, created_by, created_at, update_at, is_featured) VALUES (?,?,?,?,?,?,?,'active','property',1,NOW(),NOW(),0)");
  $s->bind_param('ssssssi', $name, $html, $cats, $addr, $lat, $lng, $locId); $s->execute(); $id = $db->insert_id;
  $p = $db->prepare("INSERT INTO listing_property (listing_id, deal, kind, bedrooms, bathrooms, balconies, carpet_area, area_unit, furnishing, floor_no, total_floors, facing, age_years, possession, price, price_period, project, created_at, updated_at) VALUES (?, 'sale', ?, ?, ?, ?, ?, 'sqft', ?, ?, ?, ?, ?, 'ready', ?, 'total', ?, NOW(), NOW())");
  $p->bind_param('isiiidsiisids', $id, $kind, $bed, $bath, $bal, $area, $furn, $fl, $tf, $face, $age, $price, $proj); $p->execute();
  echo "Added #$id: $name\n";
}
echo "Done.\n";
