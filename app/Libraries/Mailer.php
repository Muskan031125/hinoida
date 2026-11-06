<?php
namespace App\Libraries;

/**
 * Mailer — reads SMTP config from the `settings` table (same keys hinoida uses:
 * smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, mail_from, mail_from_name)
 * and delivers queued mail. Emails are stored in `email_queue` and sent by the
 * `email:send` spark command (run from cron). Local dev with placeholder creds
 * falls back to writing an .eml file under writable/maillog and marks it "logged".
 */
class Mailer
{
    /**
     * How old a queued email may be before it is dropped instead of sent.
     * A verification code is useless after this, and a burst of days-old
     * notifications is worse than none.
     */
    public const MAX_AGE_HOURS = 6;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->ensureTable();
    }

    /**
     * The line that actually explains an SMTP failure.
     *
     * A 4xx/5xx reply is the reason; anything else is the transcript around it.
     * Falls back to the last non-empty line so something useful is always kept.
     */
    protected static function smtpReason(string $debug): string
    {
        $debug = trim(strip_tags($debug));
        if ($debug === '') { return 'Send failed with no reply from the mail server.'; }

        $lines = preg_split('/\R/', $debug) ?: [];
        $bad   = [];
        foreach ($lines as $l) {
            $l = trim($l);
            if ($l === '') { continue; }
            // The explaining line is often prefixed with the command that got
            // it — "data: 550-5.4.5 Daily user sending limit exceeded" — so
            // anchoring on the code alone kept only the continuation lines and
            // threw the actual reason away.
            if (preg_match('/(?:^|\s)[45]\d\d[ -]/', $l)) { $bad[] = $l; }
        }
        if ($bad) {
            // longest first: that is the line carrying the human explanation
            usort($bad, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

            return mb_substr(implode(' | ', array_unique($bad)), 0, 400);
        }

        // no status line — keep the tail, which is where the failure lands
        $tail = array_values(array_filter(array_map('trim', $lines)));

        return mb_substr($tail ? end($tail) : $debug, 0, 400);
    }

    /**
     * Can this server even open the SMTP port, and what does the host say?
     *
     * Distinguishes "the host blocks outbound mail" from "the credentials are
     * wrong" — the two produce the same useless message otherwise.
     */
    protected static function probe(string $host, int $port, string $user = '', string $pass = ''): string
    {
        if ($host === '') { return 'no SMTP host configured'; }
        if (! extension_loaded('openssl')) {
            return 'PHP has no openssl extension, so STARTTLS cannot run — ask the host to enable it';
        }

        $fp = @stream_socket_client('tcp://' . $host . ':' . $port, $eno, $estr, 8);
        if (! $fp) {
            return 'cannot reach ' . $host . ':' . $port . ' from this server ('
                . ($estr ?: 'connection refused') . ') — the host is most likely blocking outbound SMTP';
        }
        stream_set_timeout($fp, 8);

        $read = static function () use ($fp): string {
            $out = '';
            while (($line = fgets($fp, 515)) !== false) {
                $out .= $line;
                if (strlen($line) < 4 || $line[3] !== '-') { break; }
            }
            return trim($out);
        };
        $say = static function (string $cmd) use ($fp, $read): string {
            fwrite($fp, $cmd . "\r\n");
            return $read();
        };

        $greeting = $read();
        if (! str_starts_with($greeting, '220')) { fclose($fp); return 'server answered "' . mb_substr($greeting, 0, 80) . '" instead of a 220 greeting'; }

        $say('EHLO ' . (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost'));
        $tls = $say('STARTTLS');
        if (! str_starts_with($tls, '220')) { fclose($fp); return 'STARTTLS refused: ' . mb_substr($tls, 0, 90); }
        if (! @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return 'TLS handshake failed — the server certificate could not be negotiated';
        }
        $say('EHLO ' . (parse_url(base_url(), PHP_URL_HOST) ?: 'localhost'));

        if ($user === '' || $pass === '') { fclose($fp); return 'TLS is fine; no credentials set to test'; }

        $say('AUTH LOGIN');
        $say(base64_encode($user));
        $auth = $say(base64_encode($pass));
        fwrite($fp, "QUIT\r\n");
        fclose($fp);

        if (str_starts_with($auth, '235')) {
            return 'connection, TLS and login all succeeded — the credentials are good, so the failure is in the message itself';
        }

        return 'login rejected: ' . mb_substr($auth, 0, 160);
    }

    protected function ensureTable(): void
    {
        if ($this->db->tableExists('email_queue')) { return; }

        // AUTOINCREMENT is SQLite's spelling; MySQL wants AUTO_INCREMENT and
        // rejects the statement outright otherwise. This ran on every Mailer
        // construction, so on the live database it killed the whole request —
        // signup, contact and every other mail path returned a bare 500.
        $inc = $this->db->DBDriver === 'MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        try {
            $this->db->query('CREATE TABLE IF NOT EXISTS email_queue (
                id INTEGER PRIMARY KEY ' . $inc . ',
                to_email TEXT, to_name TEXT, subject TEXT, body TEXT,
                status VARCHAR(20) DEFAULT \'pending\', attempts INT DEFAULT 0,
                error TEXT, created_at INT, sent_at INT
            )');
        } catch (\Throwable $e) {
            // never let a missing queue table take the page down with it
            log_message('error', 'Mailer: could not create email_queue — ' . $e->getMessage());
        }
    }

    /**
     * Read a config value. .env wins over the DB so credentials can live outside
     * the database (env keys: smtp.host, smtp.port, smtp.secure, smtp.user,
     * smtp.pass, smtp.from, smtp.fromName).
     */
    /**
     * Where a setting came from: 'env' or 'database'.
     *
     * .env silently wins over the admin screen, so a value edited in the admin
     * appears to save and then has no effect. Without knowing which won, that
     * looks like the change was ignored.
     */
    public function settingSource(string $key): string
    {
        $envMap = [
            'smtp_host' => 'smtp.host', 'smtp_port' => 'smtp.port', 'smtp_secure' => 'smtp.secure',
            'smtp_user' => 'smtp.user', 'smtp_pass' => 'smtp.pass',
            'mail_from' => 'smtp.from', 'mail_from_name' => 'smtp.fromName',
        ];
        if (isset($envMap[$key])) {
            $v = env($envMap[$key]);
            if ($v !== null && $v !== false && trim((string) $v) !== '') {
                return 'env (' . $envMap[$key] . ' in .env — this overrides the admin screen)';
            }
        }

        return 'database';
    }

    public function setting(string $key, string $default = ''): string
    {
        $envMap = [
            'smtp_host' => 'smtp.host', 'smtp_port' => 'smtp.port', 'smtp_secure' => 'smtp.secure',
            'smtp_user' => 'smtp.user', 'smtp_pass' => 'smtp.pass',
            'mail_from' => 'smtp.from', 'mail_from_name' => 'smtp.fromName',
        ];
        if (isset($envMap[$key])) {
            $v = env($envMap[$key]);
            if ($v !== null && $v !== false && trim((string) $v) !== '') { return trim((string) $v); }
        }
        $row = $this->db->table('settings')->where('type', $key)->get()->getRowArray();
        return $row['description'] ?? $default;
    }

    /** Queue an email for the cron sender. Returns the new queue id. */
    public function queue(string $toEmail, string $toName, string $subject, string $bodyHtml): int
    {
        $this->db->table('email_queue')->insert([
            'to_email' => $toEmail, 'to_name' => $toName,
            'subject' => $subject, 'body' => $bodyHtml,
            'status' => 'pending', 'attempts' => 0, 'created_at' => time(),
        ]);
        return (int) $this->db->insertID();
    }

    /** True when SMTP creds look like unconfigured placeholders (local dev). */
    protected function isPlaceholder(string $user, string $pass): bool
    {
        if ($user === '' || $pass === '') return true;
        if (in_array(strtolower($pass), ['sec', 'password', 'changeme'], true)) return true;
        // exact placeholder addresses only — never match on a substring like "x.com",
        // which would wrongly swallow real addresses and silently skip sending.
        if (in_array(strtolower($user), ['u@x.com', 'user@x.com', 'test@x.com'], true)) return true;
        return false;
    }

    /** Process up to $limit pending emails. Returns [sent, logged, failed]. */
    public function processQueue(int $limit = 25): array
    {
        // Anything that has sat in the queue too long is dropped rather than
        // sent. While mail was broken these piled up, and flushing them the
        // moment SMTP started working would deliver a burst of stale messages —
        // verification codes that have long expired, notices about decisions
        // taken days ago. Nobody wants that arriving at once.
        $cutoff = time() - (self::MAX_AGE_HOURS * 3600);
        $stale  = $this->db->table('email_queue')
            ->whereIn('status', ['pending', 'failed'])
            ->where('COALESCE(created_at,0) <', $cutoff)
            ->where('COALESCE(created_at,0) >', 0)
            ->countAllResults(false);
        if ($stale > 0) {
            $this->db->table('email_queue')
                ->whereIn('status', ['pending', 'failed'])
                ->where('COALESCE(created_at,0) <', $cutoff)
                ->where('COALESCE(created_at,0) >', 0)
                ->update(['status' => 'expired', 'error' => 'Not sent — older than ' . self::MAX_AGE_HOURS . ' hours']);
            log_message('info', 'Mailer: expired ' . $stale . ' stale queued email(s) instead of sending them');
        }

        // NOTE: on MySQL a NULL `attempts` makes `attempts < 5` evaluate to NULL (never true),
        // which would silently strand every queued email. COALESCE guards against that.
        $rows = $this->db->table('email_queue')
            ->whereIn('status', ['pending', 'failed'])
            ->where('COALESCE(attempts,0) <', 5)
            ->where('COALESCE(created_at,0) >=', $cutoff)
            // newest first: a sign-up sends one code and processes one email
            // straight away — oldest-first sent an earlier, failed code instead
            ->orderBy('id', 'DESC')->limit($limit)->get()->getResultArray();

        $sent = 0; $logged = 0; $failed = 0;
        foreach ($rows as $r) {
            [$ok, $note] = $this->deliver($r);
            $status = $ok === true ? 'sent' : ($ok === 'logged' ? 'logged' : 'failed');
            if ($status === 'sent') $sent++; elseif ($status === 'logged') $logged++; else $failed++;
            $this->db->table('email_queue')->where('id', $r['id'])->update([
                'status' => $status,
                'attempts' => (int) ($r['attempts'] ?? 0) + 1,
                'error' => $ok === true || $ok === 'logged' ? null : $note,
                'sent_at' => in_array($status, ['sent', 'logged'], true) ? time() : null,
            ]);
        }
        return [$sent, $logged, $failed];
    }

    /** @return array{0:bool|string,1:string} */
    protected function deliver(array $r): array
    {
        $host = $this->setting('smtp_host');
        $user = $this->setting('smtp_user');
        $pass = $this->setting('smtp_pass');
        $fromEmail = $this->setting('mail_from', $this->setting('system_email', 'no-reply@new.hinoida.com'));
        $fromName  = $this->setting('mail_from_name', 'HiNoida');

        // Local / unconfigured → write to maillog so the flow is testable.
        if ($host === '' || $this->isPlaceholder($user, $pass)) {
            $dir = WRITEPATH . 'maillog';
            if (! is_dir($dir)) @mkdir($dir, 0777, true);
            $file = $dir . DIRECTORY_SEPARATOR . 'mail-' . $r['id'] . '.html';
            @file_put_contents($file,
                "To: {$r['to_name']} <{$r['to_email']}>\nSubject: {$r['subject']}\n\n" . $r['body']);
            return ['logged', $file];
        }

        try {
            $email = \Config\Services::email();
            $email->initialize([
                'protocol'    => 'smtp',
                'SMTPHost'    => $host,
                'SMTPUser'    => $user,
                'SMTPPass'    => $pass,
                'SMTPPort'    => (int) ($this->setting('smtp_port', '587')),
                'SMTPCrypto'  => $this->setting('smtp_secure', 'tls'),
                'mailType'    => 'html',
                'charset'     => 'UTF-8',
                'wordWrap'    => true,
                'SMTPTimeout' => 8,     // fail fast instead of hanging the request if the host/port is slow or blocked
                'SMTPKeepAlive' => false,
            ]);
            $email->setFrom($fromEmail, $fromName);
            $email->setTo($r['to_email']);
            $email->setSubject($r['subject']);
            $email->setMessage($r['body']);
            if ($email->send(false)) return [true, ''];

            // Running on this computer (localhost) and the mail server could
            // not be reached — usually XAMPP's PHP has no SSL certificate
            // list. Save the email as a file instead so sign-up still works
            // while testing; a live server never takes this path.
            if ($this->isLocal()) { return ['logged', $this->writeLocal($r)]; }

            // Ask for the debug messages only. Including 'headers' buried the
            // SMTP reply under the message headers, so the stored error was the
            // greeting or a Date: line — never the reason.
            $why = self::smtpReason($email->printDebugger([]));

            // No status line means the conversation never got far enough to
            // have one. Probe the port: shared hosts frequently block outbound
            // SMTP to outside providers, and that looks identical to a bad
            // password unless you check.
            if (! preg_match('/[45]\d\d/', $why)) {
                $why .= ' — ' . self::probe($host, (int) $this->setting('smtp_port', '587'), $user, $pass);
            }

            return [false, $why];
        } catch (\Throwable $e) {
            if ($this->isLocal()) { return ['logged', $this->writeLocal($r)]; }
            return [false, $e->getMessage()];
        }
    }

    /** Is this the developer's own computer (php spark serve / XAMPP on localhost)? */
    protected function isLocal(): bool
    {
        $h = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $h = preg_replace('/:\d+$/', '', $h);

        return in_array($h, ['localhost', '127.0.0.1', '[::1]', '::1'], true) || str_ends_with($h, '.localhost') || str_ends_with($h, '.test');
    }

    /** Write the email to writable/maillog/ and return the file path. */
    protected function writeLocal(array $r): string
    {
        $dir = WRITEPATH . 'maillog';
        if (! is_dir($dir)) { @mkdir($dir, 0777, true); }
        $file = $dir . DIRECTORY_SEPARATOR . 'mail-' . $r['id'] . '.html';
        @file_put_contents($file, "To: {$r['to_name']} <{$r['to_email']}>\nSubject: {$r['subject']}\n\n" . $r['body']);

        return $file;
    }
}
