(function () {
  var root = document.querySelector('.lkba-voucher-form');
  if (!root) return;

  var tiers;
  try {
    tiers = JSON.parse(root.getAttribute('data-tiers') || '[]');
  } catch (e) {
    tiers = [];
  }
  var bundleBase = parseInt(root.getAttribute('data-bundle-base') || '450', 10);
  var bundleMin = parseInt(root.getAttribute('data-bundle-min') || '10', 10);
  var bundleDiscount = parseInt(root.getAttribute('data-bundle-discount') || '430', 10);

  var entriesInput = document.getElementById('lkba_entries');
  var hint = document.getElementById('lkba_entries_hint');
  var totalEl = document.getElementById('lkba_total_display');
  var allRow = document.getElementById('lkba_all_lessons_row');
  var allInput = document.getElementById('lkba_all_lessons');
  var up = root.querySelector('.lkba-qty-up');
  var down = root.querySelector('.lkba-qty-down');
  var giftToggle = document.getElementById('lkba_is_gift');
  var invoiceToggle = document.getElementById('lkba_want_invoice');

  function selectedValue() {
    var r = root.querySelector('input[name="lkba_value"]:checked');
    return r ? parseInt(r.value, 10) : 0;
  }

  function allowedEntries(value) {
    for (var i = 0; i < tiers.length; i++) {
      if (parseInt(tiers[i].value, 10) === value) {
        return (tiers[i].entries || []).map(Number);
      }
    }
    return [1];
  }

  function snapEntries(list, current, dir) {
    if (!list.length) return 1;
    list = list.slice().sort(function (a, b) { return a - b; });
    if (list.indexOf(current) !== -1 && !dir) return current;
    if (dir > 0) {
      for (var i = 0; i < list.length; i++) {
        if (list[i] > current) return list[i];
      }
      return list[list.length - 1];
    }
    if (dir < 0) {
      for (var j = list.length - 1; j >= 0; j--) {
        if (list[j] < current) return list[j];
      }
      return list[0];
    }
    var best = list[0];
    var bestDiff = Math.abs(current - best);
    for (var k = 1; k < list.length; k++) {
      var d = Math.abs(current - list[k]);
      if (d < bestDiff) {
        best = list[k];
        bestDiff = d;
      }
    }
    return best;
  }

  function unitPrice(value, entries, allLessons) {
    if (value === bundleBase && entries >= bundleMin && !allLessons) {
      return bundleDiscount;
    }
    return value;
  }

  function setAllLessonsVisible(show) {
    if (!allRow) return;
    // Legacy darkovy-poukaz uses .slideUp / .slideUP collapse.
    allRow.classList.toggle('slideUp', !show);
    allRow.classList.toggle('slideUP', !show);
    allRow.hidden = false;
    if (!show && allInput) allInput.checked = false;
  }

  function toggleRolled(groupSel, open) {
    root.querySelectorAll(groupSel).forEach(function (el) {
      el.classList.toggle('rolled-up', !open);
    });
  }

  function update() {
    var value = selectedValue();
    var list = allowedEntries(value);
    var entries = snapEntries(list, parseInt(entriesInput.value, 10) || 1, 0);
    entriesInput.value = String(entries);
    if (hint) {
      hint.textContent = ' (' + list.join(', ') + ')';
    }
    var showBundle = value === bundleBase && entries >= bundleMin;
    setAllLessonsVisible(showBundle);
    var unit = unitPrice(value, entries, !!(allInput && allInput.checked));
    var total = unit * entries;
    if (totalEl) {
      totalEl.textContent = ' · ' + new Intl.NumberFormat('cs-CZ').format(total) + ' Kč';
    }
  }

  root.querySelectorAll('input[name="lkba_value"]').forEach(function (r) {
    r.addEventListener('change', update);
  });
  if (entriesInput) entriesInput.addEventListener('change', update);
  if (allInput) allInput.addEventListener('change', update);

  function bindQty(el, dir) {
    if (!el) return;
    el.addEventListener('click', function () {
      var list = allowedEntries(selectedValue());
      entriesInput.value = String(snapEntries(list, parseInt(entriesInput.value, 10) || 1, dir));
      update();
    });
    el.addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        el.click();
      }
    });
  }
  bindQty(up, 1);
  bindQty(down, -1);

  if (giftToggle) {
    giftToggle.addEventListener('change', function () {
      toggleRolled('.optional-gift', giftToggle.checked);
    });
  }
  if (invoiceToggle) {
    invoiceToggle.addEventListener('change', function () {
      toggleRolled('.optional', invoiceToggle.checked);
    });
  }

  update();
})();
