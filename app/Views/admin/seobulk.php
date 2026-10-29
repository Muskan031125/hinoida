<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box}body{margin:0;font-family:'Inter',sans-serif;color:#1c2432;background:linear-gradient(180deg,#eaf0f9,#f0ecf8,#eaf4f1) fixed}
.wrap{max-width:900px;margin:0 auto;padding:26px 24px}
.top{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.top a.back{color:#1741a6;font-weight:600;text-decoration:none}
h1{font-weight:600;font-size:30px;margin:0}
.panel{background:rgba(255,255,255,.75);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,.6);border-radius:15px;box-shadow:0 6px 18px rgba(15,28,51,.05);overflow:hidden;margin-bottom:20px}
.panel h3{margin:0;padding:15px 20px;font-size:15px;border-bottom:1px solid #eef1f6}
.pad{padding:20px}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px}
.stat{background:rgba(255,255,255,.75);border:1px solid rgba(255,255,255,.6);border-radius:13px;padding:16px 18px}
.stat b{display:block;font-size:26px;font-weight:700;color:#1741a6;line-height:1.1}
.stat span{font-size:12.5px;color:#7c8aa3}
.stat.warn b{color:#b26a00}
.btn{display:inline-block;background:#1741a6;color:#fff;padding:11px 18px;border-radius:9px;font-size:13.5px;font-weight:700;text-decoration:none;border:0;cursor:pointer}
.btn-soft{background:#eef3fc;color:#1741a6}
.saved{background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:10px;padding:11px 15px;margin-bottom:16px}
.err{background:#fdecef;color:#c0324b;border:1px solid #f6ccd5;border-radius:10px;padding:11px 15px;margin-bottom:16px}
p.hint{font-size:13px;color:#7c8aa3;line-height:1.6;margin:0 0 14px}
code{background:#f2f5fa;padding:2px 6px;border-radius:5px;font-size:12.5px}
input[type=file]{width:100%;padding:11px;border:1px solid #dde3ec;border-radius:9px;background:#fff;font-size:13.5px}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1>SEO &mdash; bulk edit</h1>
    <a class="back" href="<?= base_url('admin') ?>">&larr; Console</a>
  </div>

  <?php if (! empty($done)): ?><div class="saved"><?= esc($done) ?></div><?php endif; ?>
  <?php if (! empty($err)): ?><div class="err"><?= esc($err) ?></div><?php endif; ?>

  <div class="stats">
    <div class="stat"><b><?= number_format($total) ?></b><span>listings</span></div>
    <div class="stat <?= $noTitle ? 'warn' : '' ?>"><b><?= number_format($noTitle) ?></b><span>no SEO title</span></div>
    <div class="stat <?= $noDesc ? 'warn' : '' ?>"><b><?= number_format($noDesc) ?></b><span>no meta description</span></div>
  </div>

  <div class="panel">
    <h3>1. Download</h3>
    <div class="pad">
      <p class="hint">Every listing's SEO fields as a spreadsheet. Edit the columns you want and keep the <code>ID</code> column exactly as it is &mdash; that is how each row is matched back.</p>
      <a class="btn" href="<?= base_url('admin/seo-bulk/export') ?>"><i class="fa-solid fa-download"></i> Export CSV</a>
    </div>
  </div>

  <div class="panel">
    <h3>2. Upload the edited file</h3>
    <div class="pad">
      <p class="hint">
        Columns: <code>ID, Name, Tags, Seo Title, Meta Tags, Meta Description</code> &mdash; the same order as the export.
        <b>Name is not imported</b>; it is there so you can tell the rows apart. Rows whose ID does not match an existing
        listing are skipped rather than added, so a stray line cannot create a listing.
      </p>
      <form method="post" action="<?= base_url('admin/seo-bulk/import') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="file" name="csv_file" accept=".csv" required style="margin-bottom:14px">
        <button class="btn" type="submit"><i class="fa-solid fa-upload"></i> Import CSV</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
