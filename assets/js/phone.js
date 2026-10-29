/* Phone field: searchable country code + national-number length cap.
   Submits one combined value (e.g. "+91 9876543210") in the field's own name. */
(function () {
  function init(root) {
    var out    = root.querySelector('[data-phone-out]'),
        num    = root.querySelector('[data-phone-num]'),
        btn    = root.querySelector('[data-phone-toggle]'),
        pop    = root.querySelector('[data-phone-pop]'),
        dial   = root.querySelector('[data-phone-dial]'),
        search = root.querySelector('[data-phone-search]'),
        list   = root.querySelector('[data-phone-list]'),
        none   = root.querySelector('[data-phone-none]');
    if (!out || !num || !btn || !pop) return;

    function sync() {
      var max = parseInt(num.getAttribute('maxlength'), 10) || 15;
      num.value = num.value.replace(/\D/g, '').slice(0, max);
      out.value = num.value ? dial.textContent + ' ' + num.value : '';
    }
    num.addEventListener('input', sync);
    num.addEventListener('blur', sync);

    function filter(q) {
      q = (q || '').trim().toLowerCase();
      var shown = 0;
      list.querySelectorAll('li').forEach(function (li) {
        var hit = !q || li.dataset.search.indexOf(q) > -1;
        li.style.display = hit ? '' : 'none';
        if (hit) shown++;
      });
      none.hidden = shown > 0;
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (pop.hasAttribute('hidden')) {
        pop.removeAttribute('hidden');
        btn.setAttribute('aria-expanded', 'true');
        search.value = ''; filter(''); search.focus();
      } else {
        pop.setAttribute('hidden', '');
        btn.setAttribute('aria-expanded', 'false');
      }
    });

    search.addEventListener('input', function () { filter(search.value); });
    search.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      var items = list.querySelectorAll('li'), first = null;
      for (var i = 0; i < items.length; i++) {
        if (items[i].style.display !== 'none') { first = items[i]; break; }
      }
      if (first) first.click();
    });

    list.addEventListener('click', function (e) {
      var li = e.target.closest('li');
      if (!li) return;
      dial.textContent = li.dataset.dial;
      // the required length follows the country, so the floor, the ceiling and
      // the pattern all have to move together
      num.setAttribute('maxlength', li.dataset.len);
      num.setAttribute('minlength', li.dataset.len);
      num.setAttribute('pattern', '[0-9]{' + li.dataset.len + '}');
      num.setAttribute('title', 'Enter the ' + li.dataset.len + '-digit number, without the country code');
      sync();
      pop.setAttribute('hidden', '');
      btn.setAttribute('aria-expanded', 'false');
      num.focus();
    });

    document.addEventListener('click', function (e) {
      if (!root.contains(e.target)) {
        pop.setAttribute('hidden', '');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { pop.setAttribute('hidden', ''); btn.setAttribute('aria-expanded', 'false'); }
    });

    if (num.value) sync();
    var f = root.closest('form');
    if (f) f.addEventListener('submit', sync);
  }

  document.querySelectorAll('.phonefield').forEach(init);
})();
