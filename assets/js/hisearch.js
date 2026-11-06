/* HiNoida search suggestions.
   Shows a small dropdown under the search box while the visitor types:
   a smart line ("Restaurants in Sector 18"), matching categories, areas and
   places. The data comes from /ajax/search-index once (on first focus) and
   everything after that runs in the browser — typing never hits the server. */
(function () {
  var form = document.querySelector('[data-hisearch]'); if (!form) return;
  var input = form.querySelector('input[name="q"]'), drop = document.getElementById('hsDrop');
  var IDX = null, loading = false, items = [], cur = -1;

  // everyday words → category id (the category's own page / search term)
  var WORDS = [
    [67, 'pg pgs hostel hostels|paying guest'], [66, 'flat flats 1bhk 2bhk 3bhk apartment|flat for rent|room for rent'],
    [110, 'coworking|co working|shared office|office space'], [65, 'dealer dealers broker brokers|property dealer|real estate'],
    [68, 'commercial|shop for rent|office for rent'], [12, 'cafe cafes coffee'], [15, 'bakery bakeries cake cakes pastry'],
    [13, 'chaat momos samosa|street food'], [24, 'bar bars pub pubs beer'],
    [1, 'restaurant restaurants food dhaba dinner lunch khana'], [40, 'hospital hospitals emergency'],
    [43, 'dentist dentists dental teeth tooth'], [42, 'lab labs diagnostic xray scan|blood test'],
    [41, 'clinic clinics doctor doctors'], [3, 'medical chemist medicine dawai pharmacy'],
    [50, 'school schools playschool|play school'], [51, 'college colleges university'], [52, 'coaching tuition tuitions classes'],
    [105, 'yoga'], [104, 'gym gyms fitness workout'], [106, 'salon salons parlour parlor spa haircut beauty'],
    [32, 'clothes clothing kapde garments'], [33, 'shoes footwear chappal'], [31, 'mall malls'],
    [38, 'market bazaar kirana grocery|general store'], [34, 'jeweller jewellers jewellery jewelry gold diamond'],
    [71, 'banquet banquets|banquet hall|party hall|marriage hall|wedding venue'], [139, 'hotel hotels'],
    [102, 'interior interiors|interior designer'], [85, 'movers packers shifting'], [79, 'plumber plumbers'],
    [78, 'electrician electricians'], [94, 'vet veterinary|pet doctor'], [93, 'pets|pet shop'], [58, 'taxi cab cabs'],
    [73, 'photographer photographers photoshoot'], [87, 'mehndi mehendi'], [9, 'wedding weddings shaadi']
  ];
  // shorter names for the top line, so it fits on a phone
  var SHORT = { 67: 'PGs & Hostels', 66: 'Flats for rent', 110: 'Coworking spaces', 104: 'Gyms', 106: 'Salons & spas',
    50: 'Schools', 51: 'Colleges', 52: 'Coaching centres', 42: 'Diagnostic labs', 71: 'Banquet halls', 102: 'Interior designers' };
  function short(c) { return SHORT[c[0]] || c[1]; }
  var VOCAB = {}, PHRASES = [];
  WORDS.forEach(function (w) { w[1].split('|').forEach(function (part, i) {
    (i === 0 ? part.split(' ') : [part]).forEach(function (t) { if (t) { VOCAB[t] = w[0]; PHRASES.push(t); } }); }); });
  PHRASES.sort(function (a, b) { return b.length - a.length; });

  function lev(a, b) { if (Math.abs(a.length - b.length) > 2) return 9; var p = [], i, j;
    for (j = 0; j <= b.length; j++) p[j] = j;
    for (i = 1; i <= a.length; i++) { var c = [i]; for (j = 1; j <= b.length; j++) c[j] = Math.min(p[j] + 1, c[j - 1] + 1, p[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1)); p = c; }
    return p[b.length]; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  // matches only at the start of a word: "res" finds Restaurants, not Clothing Stores
  function at(text, q) { var m = new RegExp('(^|[^a-z0-9])' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i').exec(text);
    return m ? m.index + m[1].length : -1; }
  function mark(text, q) { var i = at(text, q); if (!q || i < 0) return esc(text);
    return esc(text.slice(0, i)) + '<b>' + esc(text.slice(i, i + q.length)) + '</b>' + esc(text.slice(i + q.length)); }

  // load once per visit; the local dev server sometimes drops a request, so retry
  function ready(d) { IDX = d; IDX.cat = {}; d.c.forEach(function (c) { IDX.cat[c[0]] = c; }); loading = false; render(); }
  function load(attempt) {
    if (IDX || (loading && !attempt)) return; loading = true; attempt = attempt || 0;
    try { var s = sessionStorage.getItem('hs_idx_v1'); if (s) { ready(JSON.parse(s)); return; } } catch (e) {}
    fetch(form.getAttribute('data-hisearch')).then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .then(function (d) { try { sessionStorage.setItem('hs_idx_v1', JSON.stringify(d)); } catch (e) {} ready(d); })
      .catch(function () { if (attempt < 3) setTimeout(function () { load(attempt + 1); }, 700 * (attempt + 1)); else loading = false; });
  }

  // the dropdown lives on <body>, placed under the box, so no banner can clip it
  document.body.appendChild(drop);
  function place() { var r = form.getBoundingClientRect();
    drop.style.left = r.left + 'px'; drop.style.top = (r.bottom + 8) + 'px'; drop.style.width = r.width + 'px'; }
  window.addEventListener('scroll', function () { if (!drop.hidden) place(); }, { passive: true });
  window.addEventListener('resize', function () { if (!drop.hidden) place(); });

  // read the sentence: a type, an area and what is left over
  function read(raw) {
    var q = ' ' + raw.toLowerCase().replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim() + ' ', r = { rest: '' };
    var m = q.match(/\b(?:sector|sec|sect|s)\s*-?\s*(\d{1,3})\b/) || q.match(/\b(?:in|near|at|around)\s+(\d{1,3})\b/);
    if (m) { var nm = 'Sector ' + m[1]; IDX.a.some(function (a) { if (a[1] === nm) { r.area = a; return true; } }); q = q.replace(m[0], ' '); }
    if (!r.area) { IDX.a.slice().sort(function (a, b) { return b[1].length - a[1].length; }).some(function (a) {
        var al = a[1].toLowerCase(); if (al.length > 3 && q.indexOf(' ' + al + ' ') > -1) { r.area = a; q = q.replace(al, ' '); return true; } }); }
    PHRASES.some(function (p) { if (q.indexOf(' ' + p + ' ') > -1 && IDX.cat[VOCAB[p]]) { r.cat = IDX.cat[VOCAB[p]]; q = q.replace(' ' + p + ' ', ' '); return true; } });
    var rest = q.replace(/\b(in|near|at|around|for|the|a|an|me|show|find|best|top|good|noida|with|and|of)\b/g, ' ').replace(/\s+/g, ' ').trim();
    if (!r.cat) { rest.split(' ').some(function (w) { if (w.length < 4) return false; var best = null, bd = 9;
        PHRASES.forEach(function (p) { if (p.indexOf(' ') > -1) return; var d = lev(w, p); if (d < bd) { bd = d; best = p; } });
        if (best && bd <= (w.length >= 7 ? 2 : 1) && IDX.cat[VOCAB[best]]) { r.cat = IDX.cat[VOCAB[best]]; r.fixed = best; rest = rest.replace(w, '').trim(); return true; } }); }
    r.gender = /\b(girls?|ladies|women)\b/.test(q) ? 'girl' : (/\b(boys?|gents)\b/.test(q) ? 'boy' : '');
    r.rest = rest.replace(/\b(girls?|ladies|women|boys?|gents)\b/g, '').trim();
    return r;
  }

  function row(icon, html, sub, href) { return { icon: icon, html: html, sub: sub, href: href }; }
  function render() {
    var raw = input.value.trim(), ql = raw.toLowerCase();
    if (raw.length < 2) { close(); return; }
    if (!IDX) { load(); return; }
    var r = read(raw), out = [];
    // 1. the smart line, when the sentence named a type and/or an area
    if (r.cat && r.area) {
      out.push(row('magnifying-glass', '<b>' + esc(short(r.cat)) + '</b> in <b>' + esc(r.area[1]) + '</b>', r.fixed ? 'Showing results for “' + esc(r.fixed) + '”' : '',
        IDX.search + '?q=' + encodeURIComponent(r.cat[1]) + '&area=' + r.area[0]));
    } else if (r.cat) {
      out.push(row('magnifying-glass', 'All <b>' + esc(short(r.cat)) + '</b>', (r.fixed ? 'Showing results for “' + esc(r.fixed) + '” · ' : '') + r.cat[3] + ' places', r.cat[2]));
    } else if (r.area) {
      out.push(row('location-dot', 'Everything in <b>' + esc(r.area[1]) + '</b>', 'Area', r.area[2]));
    }
    // 2. categories whose name contains what was typed
    if (!r.cat) {
      IDX.c.filter(function (c) { return at(c[1], ql) > -1; }).sort(function (a, b) { return b[3] - a[3]; }).slice(0, 3)
        .forEach(function (c) { out.push(row('tag', mark(c[1], ql), c[3] + ' places', c[2])); });
    }
    // 3. areas, while no area has been picked yet
    if (!r.area && !r.cat) {
      IDX.a.filter(function (a) { return at(a[1], ql) > -1; }).slice(0, 2)
        .forEach(function (a) { out.push(row('location-dot', mark(a[1], ql), 'Area', a[2])); });
    }
    // 4. places: by name, or of the type/area the sentence asked for
    var name = r.rest.toLowerCase(), places = IDX.l.filter(function (l) {
      var n = l[0].toLowerCase();
      if (r.cat && !l[3].some(function (id) { return id === r.cat[0] || (IDX.cat[id] && IDX.cat[id][4] === r.cat[0]); })) return false;
      if (r.area && l[2] !== r.area[1]) return false;
      if (r.gender === 'girl' && !/(girl|ladies|women)/.test(n)) return false;
      if (r.gender === 'boy' && !/(boy|gents)/.test(n)) return false;
      if (!r.cat && !r.area) return at(n, ql) > -1;
      return !name || n.indexOf(name) > -1;
    }).slice(0, 8 - out.length > 4 ? 4 : Math.max(2, 8 - out.length));
    places.forEach(function (l) { out.push(row('store', (r.cat || r.area) ? esc(l[0]) : mark(l[0], ql), l[2] || '', l[1])); });

    items = out; cur = -1;
    if (!out.length) { drop.innerHTML = '<div class="hs-none">No matches. Press Enter to search all of HiNoida.</div>'; place(); drop.hidden = false; return; }
    drop.innerHTML = out.map(function (it, i) {
      return '<a class="hs-it" role="option" id="hs' + i + '" href="' + esc(it.href) + '"><i class="fa-solid fa-' + it.icon + '"></i>' +
        '<span class="hs-tx"><span>' + it.html + '</span>' + (it.sub ? '<small>' + it.sub + '</small>' : '') + '</span></a>'; }).join('');
    place(); drop.hidden = false; input.setAttribute('aria-expanded', 'true');
  }
  function close() { drop.hidden = true; drop.innerHTML = ''; items = []; cur = -1; input.setAttribute('aria-expanded', 'false'); }
  function move(d) { if (!items.length) return; cur = (cur + d + items.length) % items.length;
    [].forEach.call(drop.querySelectorAll('.hs-it'), function (a, i) { a.classList.toggle('on', i === cur); });
    input.setAttribute('aria-activedescendant', 'hs' + cur); }

  input.setAttribute('autocomplete', 'off'); input.setAttribute('role', 'combobox'); input.setAttribute('aria-controls', 'hsDrop'); input.setAttribute('aria-expanded', 'false');
  input.addEventListener('focus', function () { load(); });
  input.addEventListener('input', render);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); move(1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
    else if (e.key === 'Escape') { close(); }
    else if (e.key === 'Enter' && cur > -1 && items[cur]) { e.preventDefault(); location.href = items[cur].href; }
  });
  document.addEventListener('click', function (e) { if (!form.contains(e.target) && !drop.contains(e.target)) close(); });
})();
