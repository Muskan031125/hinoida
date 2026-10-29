<?php
namespace App\Controllers;

class Blog extends BaseController
{
    /** @param string|null $catSlug from /news/category/{slug} */
    public function index(?string $catSlug = null): string
    {
        helper('seo');
        $db = \Config\Database::connect();
        $q    = hi_content_q('blog', $db);
        $rows = hi_content_rows($q ? $q->orderBy('added_date', 'DESC')->limit(60)->get()->getResultArray() : []);
        // per-page title and description written in the live `seo` table;
        // index pages only, where our own titles are generic
        $latest = $rows;   // newest posts, before any filter — for the Noida Today brief
        $q    = trim((string) $this->request->getGet('q'));
        $total= count($rows);
        $cats = hi_content_cats($rows);            // before the category filter
        $cat  = (int) $this->request->getGet('cat');
        if ($catSlug !== null && $catSlug !== '') {
            $cat = hi_cat_id_by_slug($catSlug, $cats);
            if ($cat === 0) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        }
        if ($cat > 0) {
            $rows = array_values(array_filter($rows, static fn ($r) => in_array($cat, array_map('intval', preg_split('/[,\s]+/', (string) ($r['category_id'] ?? '')) ?: []), true)));
        }
        $rows = hi_content_filter($rows, $q);
        $shown = count($rows);
        $pg    = hi_paginate($rows, 12);
        $rows  = $pg['rows'];
        $seo  = hi_page_seo('news');
        return view('frontend/blog_list', [
            'title' => $seo['title'] ?? 'News & Local Guides for Noida | HiNoida',
            'metaDescription' => $seo['metaDescription'] ?? 'Latest news, updates and local guides for Noida — metro, real estate, food, events and more on HiNoida.',
            'canonical' => hi_post_category_url('news', $catSlug),
            'posts' => $rows, 'latest' => $latest, 'pg' => $pg, 'shown' => $shown, 'q' => $q, 'cats' => $cats, 'cat' => $cat, 'total' => $total, 'catSlug' => $catSlug,
            'jsonld' => [hi_breadcrumb(['Home' => base_url('/'), 'News & Guides' => hi_page('news')])],
        ]);
    }

    /** @param string $key the id from /news/post/{id}/{slug}, or a bare slug */
    public function show(string $key): string
    {
        helper('seo');
        $db   = \Config\Database::connect();
        $post = hi_content_find('blog', $key, $db);
        if (! $post) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $mq   = hi_content_q('blog', $db);
        $more = hi_content_rows($mq ? $mq->where('id !=', (int) $post['id'])->orderBy('added_date', 'DESC')->limit(3)->get()->getResultArray() : []);
        $this->ensureComments($db);
        $comments = $db->table('blog_comment c')
            ->select('c.*, u.name AS user_name')
            ->join('user u', 'u.id = c.user_id', 'left')
            ->where('c.blog_id', (int) $post['id'])->where('c.status', 'approved')
            ->orderBy('c.id', 'ASC')->get()->getResultArray();
        $plain = trim(preg_replace('/\s+/', ' ', hi_rich_text($post['blog_text'] ?? '')));
        $url = hi_news_url($post);
        return view('frontend/blog_detail', [
            'title' => $post['title'] . ' | HiNoida News',
            'metaDescription' => mb_substr($plain, 0, 155),
            'canonical' => $url,
            'ogType' => 'article',
            'post' => $post,
            'more' => $more,
            'comments' => $comments,
            'flash'    => session()->getFlashdata('flash'),
            'cerr'     => session()->getFlashdata('cerr'),
            'jsonld' => [
                hi_breadcrumb(['Home' => base_url('/'), 'News' => hi_page('news'), $post['title'] => $url]),
                [
                    '@context' => 'https://schema.org', '@type' => 'NewsArticle',
                    'headline' => $post['title'], 'datePublished' => $post['added_date'] ?? '',
                    'author' => ['@type' => 'Organization', 'name' => 'HiNoida'],
                    'publisher' => ['@type' => 'Organization', 'name' => 'HiNoida'],
                    'mainEntityOfPage' => $url,
                    'image' => hi_topic_img('blog', (int) $post['id'], 1000, 500),
                ],
            ],
        ]);
    }

    /** The original site had no comments table — create it on first use. */
    private function ensureComments($db): void
    {
        if ($db->tableExists('blog_comment')) { return; }
        $inc = $db->DBDriver === 'MySQLi' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $db->query('CREATE TABLE blog_comment (id INTEGER PRIMARY KEY ' . $inc . ',
            blog_id INT, user_id INT, name VARCHAR(190), email VARCHAR(190),
            comment TEXT, status VARCHAR(20) DEFAULT \'pending\', created_at DATETIME)');
    }

    /** Post a comment. Held for moderation unless the author is signed in. */
    public function comment(string $key)
    {
        $db   = \Config\Database::connect();
        $post = hi_content_find('blog', $key, $db);
        if (! $post) { throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(); }
        $back = hi_news_url($post) . '#comments';
        $this->ensureComments($db);

        // honeypot — a real browser leaves it empty
        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->to($back);
        }

        $uid  = (int) session()->get('uid');
        $name = trim((string) $this->request->getPost('name'));
        $mail = trim((string) $this->request->getPost('email'));
        $body = trim((string) $this->request->getPost('comment'));

        if ($uid) {
            $me   = $db->table('user')->select('name, email')->where('id', $uid)->get()->getRowArray() ?: [];
            $name = $name ?: (string) ($me['name'] ?? '');
            $mail = $mail ?: (string) ($me['email'] ?? '');
        }

        if ($name === '' || mb_strlen($body) < 5) {
            session()->setFlashdata('cerr', 'Please give your name and write at least a few words.');
            return redirect()->to($back);
        }
        if ($mail !== '' && ! filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            session()->setFlashdata('cerr', 'That email address does not look right.');
            return redirect()->to($back);
        }

        // a signed-in member is known, so their comment goes straight up;
        // anonymous ones wait for an admin
        $db->table('blog_comment')->insert([
            'blog_id'    => (int) $post['id'],
            'user_id'    => $uid ?: null,
            'name'       => $name,
            'email'      => $mail,
            'comment'    => $body,
            'status'     => $uid ? 'approved' : 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        session()->setFlashdata('flash', $uid
            ? 'Thanks — your comment is up.'
            : 'Thanks — your comment will appear once it has been checked.');

        return redirect()->to($back);
    }
}
