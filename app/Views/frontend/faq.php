<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?= view('frontend/_pagehero', [
  'crumbs' => [['FAQ', null]],
  'title'  => esc(hi_c('faq.frequently_asked_questions', 'Frequently Asked Questions')),
  'sub'    => 'Quick answers about listings, accounts, reviews and using HiNoida.',
  'facts'  => [['circle-question', 'Questions', (string) count($faqs)], ['clock', 'Reply time', 'Within 1 working day']],
  'actions'=> [['envelope', 'Still stuck? Contact us', base_url('contact'), 'primary']],
], ['saveData' => false]) ?>
<section class="sec"><div class="wrap" style="max-width:820px">
  <?php foreach ($faqs as $f): ?>
    <details style="border:1px solid var(--line);border-radius:12px;padding:0 18px;margin-bottom:10px;background:rgba(255,255,255,.7)">
      <summary style="cursor:pointer;padding:16px 0;font-weight:700;color:var(--ink);list-style:none"><?= esc($f[0]) ?></summary>
      <p style="margin:0 0 16px;color:var(--body);line-height:1.7"><?= esc($f[1]) ?></p>
    </details>
  <?php endforeach; ?>
</div></section>
<?= $this->endSection() ?>
