<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\Mailer;

/**
 * Cron target. Add to crontab:
 *   * * * * * cd /path/to/app && php spark email:send >> writable/logs/mail-cron.log 2>&1
 */
class EmailSend extends BaseCommand
{
    protected $group       = 'HiNoida';
    protected $name        = 'email:send';
    protected $description = 'Send queued emails via SMTP settings (verification, approvals).';

    public function run(array $params)
    {
        $limit = (int) ($params[0] ?? 25);
        [$sent, $logged, $failed] = (new Mailer())->processQueue($limit);
        CLI::write("Mail queue processed — sent: {$sent}, logged: {$logged}, failed: {$failed}", 'green');
    }
}
