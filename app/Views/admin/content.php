<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box}body{margin:0;font-family:'Inter',sans-serif;background:linear-gradient(180deg,#eaf0f9,#f0ecf8,#eaf4f1) fixed;color:#1c2432}
.wrap{max-width:1180px;margin:0 auto;padding:26px 24px}
.top{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px}
.top a.back{color:#1741a6;font-weight:600;text-decoration:none}
h1{font-family:'Inter',system-ui,sans-serif;font-weight:600;font-size:32px;margin:0}
.grid{display:grid;grid-template-columns:1fr 380px;gap:22px}
@media(max-width:820px){.grid{grid-template-columns:1fr}}
.panel{background:rgba(255,255,255,.72);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.6);border-radius:16px;box-shadow:0 6px 20px rgba(15,28,51,.06);overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:13.5px}
th{text-align:left;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#9aa6bd;padding:13px 16px;background:#fafbfd;border-bottom:1px solid #eceef3}
td{padding:12px 16px;border-bottom:1px solid #f1f3f7;color:#33405a;vertical-align:top}
.form{padding:20px}
.form h3{margin:0 0 14px;font-family:'Inter',system-ui,sans-serif;font-size:22px}
label{display:block;font-size:12px;font-weight:700;color:#5a6b86;margin:12px 0 5px;text-transform:uppercase;letter-spacing:.03em}
input[type=text],input[type=date],input[type=time],textarea,input[type=file]{width:100%;border:1px solid #dfe4ec;border-radius:9px;padding:10px 12px;font-size:14px;font-family:inherit;background:#fff}
textarea{min-height:96px;resize:vertical}
.btn{margin-top:16px;background:#1741a6;color:#fff;border:0;border-radius:9px;padding:11px 18px;font-weight:700;cursor:pointer;width:100%}
.abtn{display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;border:0;cursor:pointer}
.gh{background:#eef3fc;color:#1741a6}.no{background:#fdecef;color:#c0324b}
.saved{background:#e6f6ec;color:#1a7a3d;border:1px solid #c4e8d1;border-radius:10px;padding:11px 15px;margin-bottom:16px}
.errbox{background:#fdecec;color:#a12626;border:1px solid #f5c9c9;border-radius:10px;padding:11px 15px;margin-bottom:16px}
.errbox ul{margin:6px 0 0;padding-left:18px}
.thumb{width:54px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #e6e9f0}
.chip{font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px;background:#eef3fc;color:#1741a6}
.subnav{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px}
.subnav a{padding:8px 14px;border-radius:9px;font-size:13.5px;font-weight:600;text-decoration:none;color:#33405a;background:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.6)}
.subnav a.on{background:#1741a6;color:#fff}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1><i class="fa-solid fa-pen-to-square" style="color:#1741a6"></i> <?= esc($ctitle) ?></h1>
    <a class="back" href="<?= base_url('admin') ?>"><i class="fa-solid fa-arrow-left"></i> Back to console</a>
  </div>
  <div class="subnav">
    <?php foreach (['blog'=>'Blog / News','events'=>'Events','offers'=>'Offers','qa'=>'Q&A'] as $k=>$lbl): ?>
      <a href="<?= base_url('admin/content/'.$k) ?>" class="<?= $type===$k?'on':'' ?>"><?= esc($lbl) ?></a>
    <?php endforeach; ?>
  </div>
  <?php if (! empty($saved)): ?><div class="saved"><i class="fa-solid fa-circle-check"></i> <?= esc($saved) ?></div><?php endif; ?>
  <?php $fErrs = session()->getFlashdata('errors'); if ($fErrs): ?><div class="errbox"><b>Not saved</b><ul><?php foreach ((array) $fErrs as $fe): ?><li><?= esc($fe) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

  <?php if ($type === 'qa'): ?>
    <div class="panel"><table>
      <tr><th>Business</th><th>Question</th><th>Answer</th></tr>
      <?php if (! $rows): ?><tr><td colspan="3" style="text-align:center;color:#9aa6bd;padding:36px">No questions yet.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><b><?= esc($r['listing_name'] ?? ('#'.$r['listing_id'])) ?></b><br><span style="color:#9aa6bd;font-size:12px">by <?= esc($r['name'] ?: 'user') ?></span></td>
          <td><?= esc($r['question']) ?></td>
          <td>
            <form method="post" action="<?= base_url('admin/content/qa/answer/'.(int)$r['id']) ?>">
              <textarea name="answer" style="min-height:52px"><?= esc($r['answer'] ?? '') ?></textarea>
              <button class="btn" style="margin-top:8px;padding:8px">Save answer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table></div>

  <?php else: $e = $edit; ?>
    <div class="grid">
      <div class="panel"><table>
        <tr><th>Img</th><th>Title</th><th style="text-align:right">Actions</th></tr>
        <?php if (! $rows): ?><tr><td colspan="3" style="text-align:center;color:#9aa6bd;padding:36px">Nothing yet — add one on the right.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r):
          $img = $type==='blog' ? ($r['blog_thumbnail']??'') : ($r['image']??'');
          $src = $img ? base_url($img) : hi_topic_img($type, (int) $r['id'], 80, 60, $r['title'] ?? ''); ?>
          <tr>
            <td><img class="thumb" src="<?= esc($src) ?>" alt=""></td>
            <td><b><?= esc($r['title']) ?></b> <?php if(!empty($r['status'])): ?><span class="chip"><?= esc($r['status']) ?></span><?php endif; ?><br>
              <span style="color:#9aa6bd;font-size:12px"><?= esc($r['slug'] ?? '') ?></span></td>
            <td style="text-align:right;white-space:nowrap">
              <a class="abtn gh" href="<?= base_url('admin/content/'.$type.'?edit='.(int)$r['id']) ?>"><i class="fa-solid fa-pen"></i></a>
              <form method="post" action="<?= base_url('admin/content/'.$type.'/delete/'.(int)$r['id']) ?>" style="display:inline" onsubmit="return confirm('Delete this?')"><button class="abtn no"><i class="fa-solid fa-trash"></i></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table></div>

      <div class="panel"><form class="form" method="post" action="<?= base_url('admin/content/'.$type.'/save') ?>" enctype="multipart/form-data">
        <h3><?= $e ? 'Edit' : 'Add new' ?></h3>
        <input type="hidden" name="id" value="<?= $e ? (int)$e['id'] : '' ?>">
        <?php foreach ($schema['fields'] as $f): [$name,$lbl,$ftype]=$f; $req = ($f[3] ?? '')==='req'; $val = $e ? ($e[$name] ?? '') : old($name, ''); ?>
          <label><?= esc($lbl) ?><?= $req ? ' <span style="color:#c0392b">*</span>' : '' ?></label>
          <?php if ($ftype==='textarea'): ?><textarea name="<?= $name ?>" <?= $req?'required':'' ?>><?= esc($val) ?></textarea>
          <?php else: ?><input type="<?= in_array($ftype,['date','time'],true) ? $ftype : 'text' ?>" name="<?= $name ?>" value="<?= esc($val) ?>" <?= $req?'required':'' ?>><?php endif; ?>
        <?php endforeach; ?>
        <label>Image (JPG/PNG/WebP)</label>
        <?php $cur = $e ? ($type==='blog' ? ($e['blog_thumbnail']??'') : ($e['image']??'')) : ''; ?>
        <?php if ($cur): ?><img class="thumb" style="width:90px;height:64px;margin-bottom:8px" src="<?= base_url($cur) ?>" alt=""><?php endif; ?>
        <input type="file" name="image" accept="image/*">
        <button class="btn"><?= $e ? 'Save changes' : 'Create' ?></button>
        <?php if ($e): ?><a href="<?= base_url('admin/content/'.$type) ?>" style="display:block;text-align:center;margin-top:10px;font-size:13px;color:#8b98b0">Cancel edit</a><?php endif; ?>
      </form></div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
