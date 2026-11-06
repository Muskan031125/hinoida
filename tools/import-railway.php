<?php
$LOG = __DIR__ . '/../import-log.txt';
function say($m){ global $LOG; echo $m; file_put_contents($LOG, $m, FILE_APPEND); }
// Loads hinoida-railway.sql into Railway MySQL using PHP (works with MySQL 8/9 logins)
$host = $argv[1] ?? 'switchyard.proxy.rlwy.net';
$port = (int) ($argv[2] ?? 52167);
$file = $argv[3] ?? __DIR__ . '/../hinoida-railway.sql';
echo "Paste the Railway MySQL password and press Enter: ";
$pass = trim(fgets(STDIN));
mysqli_report(MYSQLI_REPORT_OFF);
$db = @new mysqli($host, 'root', $pass, 'railway', $port);
if ($db->connect_errno) { say("\nCONNECT_FAILED: " . $db->connect_error . "\n"); exit(1); }
$db->set_charset('utf8mb4');
$db->query('SET FOREIGN_KEY_CHECKS=0');
$fh = fopen($file, 'r');
if (! $fh) { say("NO_SQL_FILE\n"); exit(1); }
$buf = ''; $n = 0; $err = 0;
while (($line = fgets($fh)) !== false) {
    $t = rtrim($line, "\r\n");
    if ($buf === '' && ($t === '' || str_starts_with($t, '--'))) { continue; }
    $buf .= $line;
    if (str_ends_with(rtrim($t), ';')) {
        if (! $db->query($buf)) { $err++; if ($err <= 5) { say("\nERR: " . $db->error . "\n"); } }
        $buf = ''; $n++;
        if ($n % 50 === 0) { echo '.'; }
    }
}
$db->query('SET FOREIGN_KEY_CHECKS=1');
$r = $db->query("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema='railway'");
$tables = $r ? $r->fetch_assoc()['c'] : '?';
say("\nRan $n statements, $err errors, $tables tables now in Railway.\n");
say($err === 0 ? "IMPORT_OK\n" : "IMPORT_WITH_ERRORS\n");
