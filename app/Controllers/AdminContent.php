<?php
namespace App\Controllers;

class AdminContent extends BaseController
{
    private function guard(){ return (int) session()->get('role') === 1; }

    /** field schema for each content type */
    private function schema(string $type): ?array
    {
        $s = [
            'blog' => ['table' => 'blog', 'title' => 'Blog / News', 'fields' => [
                ['title','Title','text','req'], ['slug','Slug (blank = auto)','text'],
                ['blog_type','Type (news/guide)','text'], ['status','Status (active/draft)','text'],
                ['blog_text','Body (HTML allowed)','textarea','req'],
            ]],
            'events' => ['table' => 'event', 'title' => 'Events', 'fields' => [
                ['title','Title','text','req'], ['slug','Slug (blank = auto)','text'],
                ['event_date','Date','date','req'], ['event_time','Time','time'],
                ['venue','Venue','text','req'], ['locality','Locality','text'],
                ['price','Entry / Price','text'], ['category','Category','text'],
                ['description','Description','textarea'],
            ]],
            'offers' => ['table' => 'offer', 'title' => 'Offers & Deals', 'fields' => [
                ['title','Title','text','req'], ['slug','Slug (blank = auto)','text'],
                ['business','Business','text','req'], ['locality','Locality','text'],
                ['discount','Discount (e.g. 40% OFF)','text','req'], ['code','Coupon code','text'],
                ['valid_till','Valid till','date'], ['category','Category','text'],
                ['description','Description','textarea'],
            ]],
        ];
        return $s[$type] ?? null;
    }

    public function index(string $type = 'blog')
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect();

        if ($type === 'qa') {
            $rows = $db->tableExists('listing_qa')
                ? $db->table('listing_qa lq')->select('lq.*, li.name AS listing_name')
                    ->join('listing li', 'li.id = lq.listing_id', 'left')
                    ->orderBy('lq.id', 'DESC')->limit(100)->get()->getResultArray()
                : [];
            return view('admin/content', ['type' => 'qa', 'title' => 'Q&A — HiNoida Admin', 'ctitle' => 'Questions & Answers', 'rows' => $rows, 'schema' => null, 'edit' => null, 'saved' => session()->getFlashdata('saved')]);
        }

        $sc = $this->schema($type);
        if (! $sc) { return redirect()->to('/admin'); }
        if (! $db->tableExists($sc['table'])) { $rows = []; }
        else { $rows = $db->table($sc['table'])->orderBy('id', 'DESC')->limit(100)->get()->getResultArray(); }
        $edit = null;
        $eid = (int) $this->request->getGet('edit');
        if ($eid && $db->tableExists($sc['table'])) { $edit = $db->table($sc['table'])->where('id', $eid)->get()->getRowArray(); }

        return view('admin/content', [
            'type' => $type, 'title' => $sc['title'] . ' — HiNoida Admin',
            'ctitle' => $sc['title'], 'rows' => $rows, 'schema' => $sc, 'edit' => $edit,
            'saved' => session()->getFlashdata('saved'),
        ]);
    }

    public function save(string $type)
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $sc = $this->schema($type);
        if (! $sc) { return redirect()->to('/admin'); }
        $db = \Config\Database::connect();

        $data = [];
        foreach ($sc['fields'] as $f) { $data[$f[0]] = (string) $this->request->getPost($f[0]); }

        // Nothing here was ever checked, so a blank offer with no business and
        // no discount saved happily and showed up on the public page.
        $errors = [];
        foreach ($sc['fields'] as $f) {
            [$fname, $flabel, $ftype] = $f;
            $req = ($f[3] ?? '') === 'req';
            $val = trim((string) $data[$fname]);
            if ($req && $val === '') { $errors[] = $flabel . ' is required.'; continue; }
            if ($ftype === 'date' && $val !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                $errors[] = $flabel . ' must be a date (YYYY-MM-DD).';
            }
        }
        if ($errors) {
            session()->setFlashdata('errors', $errors);
            return redirect()->to('/admin/content/' . $type)->withInput();
        }

        // optional image upload -> writable is not web-served, so store under public/uploads
        $imgField = $type === 'blog' ? 'blog_thumbnail' : 'image';
        $file = $this->request->getFile('image');
        if ($file && $file->isValid() && ! $file->hasMoved() && in_array($file->getExtension(), ['jpg','jpeg','png','webp','gif'], true)) {
            $dir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . $type;
            if (! is_dir($dir)) { @mkdir($dir, 0777, true); }
            $nm = $file->getRandomName();
            $file->move($dir, $nm);
            $data[$imgField] = 'uploads/' . $type . '/' . $nm;
        }
        // auto slug + defaults
        if (isset($data['slug']) && trim($data['slug']) === '') {
            $data['slug'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim((string) $this->request->getPost('title'))));
            $data['slug'] = trim($data['slug'], '-');
        }
        if ($type === 'blog' && trim((string) ($data['status'] ?? '')) === '') { $data['status'] = 'active'; }
        if (($type === 'events' || $type === 'offers') && ! isset($data['status'])) { $data['status'] = 'active'; }

        $id = (int) $this->request->getPost('id');
        if ($id > 0) { $db->table($sc['table'])->where('id', $id)->update($data); }
        else {
            if ($type === 'blog') { $data['status'] = $data['status'] ?? 'active'; $data['added_date'] = date('Y-m-d'); $data['user_id'] = (int) session()->get('uid'); }
            if ($type !== 'blog') { $data['status'] = 'active'; }
            $db->table($sc['table'])->insert($data);
        }
        session()->setFlashdata('saved', ucfirst(rtrim($type, 's')) . ' saved.');
        return redirect()->to('/admin/content/' . $type);
    }

    public function delete(string $type, int $id)
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $sc = $this->schema($type);
        if ($sc) { \Config\Database::connect()->table($sc['table'])->where('id', $id)->delete(); }
        session()->setFlashdata('saved', 'Deleted.');
        return redirect()->to('/admin/content/' . $type);
    }

    public function answer(int $id)
    {
        if (! $this->guard()) { return redirect()->to('/login'); }
        $db = \Config\Database::connect();
        $db->table('listing_qa')->where('id', $id)->update(['answer' => trim((string) $this->request->getPost('answer'))]);
        session()->setFlashdata('saved', 'Answer posted.');
        return redirect()->to('/admin/content/qa');
    }
}
