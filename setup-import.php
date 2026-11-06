<?php
// One-time database setup for a fresh hosting database.
// Works ONLY while the database is empty; after the first import it switches itself off.
set_time_limit(0);
mysqli_report(MYSQLI_REPORT_OFF);
$cfg = [getenv('MYSQLHOST'), getenv('MYSQLUSER'), getenv('MYSQLPASSWORD'), getenv('MYSQLDATABASE'), (int) getenv('MYSQLPORT')];
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><title>HiNoida setup</title><body style="font:16px system-ui;max-width:640px;margin:40px auto;padding:0 16px"><h2>HiNoida — database setup</h2>';
if (! $cfg[0]) { exit('<p>Database settings are missing on this server (Variables).</p>'); }
$db = @new mysqli(...$cfg);
if ($db->connect_errno) { exit('<p style="color:#b91c1c"><b>Server could not reach the database:</b> ' . htmlspecialchars($db->connect_error) . '</p>'); }
$_r = $db->query('SELECT COUNT(*) c FROM listing'); $tables = $_r ? (int) $_r->fetch_assoc()['c'] : 0;
if ($tables > 0 && ($_SERVER['REQUEST_METHOD'] !== 'POST')) { exit("<p style=\"color:#15803d\"><b>Already set up</b> — the database has $tables listings. This page is switched off.</p><p><a href=\"/\">Open the site</a></p>"); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<p>The database is empty. Pick the file <b>hinoida-railway.sql</b> from your HiNoida folder and click Upload.</p>
    <form method="post" enctype="multipart/form-data"><p><input type="file" name="sql" accept=".sql" required style="font-size:16px"></p>
    <p><button style="padding:12px 24px;font-size:17px;background:#1741a6;color:#fff;border:0;border-radius:8px">Upload</button></p></form>';
    exit;
}
if ($tables > 0) { exit('<p>Already set up — not importing again.</p>'); }
$f = $_FILES['sql']['tmp_name'] ?? '';
if (! $f || ! is_uploaded_file($f)) { exit('<p style="color:#b91c1c">Upload failed (error ' . (int) ($_FILES['sql']['error'] ?? -1) . '). <a href="">Try again</a></p>'); }
$db->set_charset('utf8mb4'); $db->query('SET FOREIGN_KEY_CHECKS=0');
$fh = fopen($f, 'r'); $buf = ''; $n = 0; $err = 0; $errs = [];
while (($line = fgets($fh)) !== false) {
    $t = rtrim($line, "\r\n");
    if ($buf === '' && ($t === '' || str_starts_with($t, '--'))) { continue; }
    $buf .= $line;
    if (str_ends_with(rtrim($t), ';')) {
        if (! $db->query($buf)) { $err++; if (count($errs) < 5) { $errs[] = $db->error; } }
        $buf = ''; $n++;
    }
}
$db->query('SET FOREIGN_KEY_CHECKS=1');
$c = (int) $db->query('SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema = DATABASE()')->fetch_assoc()['c'];
echo '<h3 style="color:' . ($err ? '#b45309' : '#15803d') . '">' . ($err ? "Finished with $err errors" : 'IMPORT_OK') . "</h3><p>$n statements, $c tables.</p>";
foreach ($errs as $e) { echo '<p style="color:#b91c1c">' . htmlspecialchars($e) . '</p>'; }
echo '<p><a href="/">Open the site</a></p>';
