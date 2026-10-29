<?php
namespace App\Controllers;

class VCard extends BaseController
{
    /** Digital business card with QR code for a listing. */
    public function show(int $id): string
    {
        if (! hi_addon('vcard')) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        $db = \Config\Database::connect();
        $l = $db->table('listing li')->select('li.*, l.name AS locality_name')
            ->join('locality l', 'l.id = li.locality_id', 'left')
            ->where('li.id', $id)->where('li.status', 'active')->get()->getRowArray();
        if (! $l) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }

        $vcard = $this->payload($l, $id);

        return view('frontend/vcard', [
            'title' => $l['name'] . ' — Digital Card | HiNoida',
            'metaDescription' => 'Digital visiting card for ' . $l['name'] . ' in ' . esc($l['locality_name'] ?: 'Noida') . ', Noida — save the contact by scanning the QR code.',
            'canonical' => base_url('card/' . $id),
            'robots' => 'noindex,follow',
            'l' => $l,
            'qr' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=0&data=' . rawurlencode($vcard),
        ]);
    }

    /**
     * The vCard text itself.
     *
     * Line endings are CRLF and long lines are not folded — RFC 6350 asks for
     * both, and iOS in particular is fussy about the CRLF.
     */
    private function payload(array $l, int $id): string
    {
        $esc = static fn ($v) => str_replace(["\\", ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], trim((string) $v));

        $lines = ['BEGIN:VCARD', 'VERSION:3.0'];
        $lines[] = 'FN:' . $esc($l['name']);
        $lines[] = 'ORG:' . $esc($l['name']);
        if (! empty($l['phone']))   { $lines[] = 'TEL;TYPE=WORK,VOICE:' . preg_replace('/[^0-9+]/', '', $l['phone']); }
        if (! empty($l['email']))   { $lines[] = 'EMAIL;TYPE=WORK:' . $esc($l['email']); }
        if (! empty($l['website'])) { $lines[] = 'URL:' . $l['website']; }
        $lines[] = 'ADR;TYPE=WORK:;;' . $esc($l['address'] ?? '') . ';' . $esc($l['locality_name'] ?: 'Noida') . ';;;India';
        if (! empty($l['latitude']) && ! empty($l['longitude'])) {
            $lines[] = 'GEO:' . (float) $l['latitude'] . ';' . (float) $l['longitude'];
        }
        $lines[] = 'NOTE:Listed on HiNoida — ' . hi_listing_url($id);
        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines);
    }

    /** Serve the card as a .vcf file — the QR only helps someone holding a phone. */
    public function download(int $id)
    {
        if (! hi_addon('vcard')) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        $db = \Config\Database::connect();
        $l  = $db->table('listing li')->select('li.*, l.name AS locality_name')
            ->join('locality l', 'l.id = li.locality_id', 'left')
            ->where('li.id', $id)->where('li.status', 'active')->get()->getRowArray();
        if (! $l) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) $l['name'])) ?: 'contact';

        return $this->response
            ->setHeader('Content-Type', 'text/vcard; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . trim($slug, '-') . '.vcf"')
            ->setBody($this->payload($l, $id));
    }
}
