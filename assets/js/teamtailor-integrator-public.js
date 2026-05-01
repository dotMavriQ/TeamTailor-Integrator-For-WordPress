/**
 * TeamTailor Integrator — Public JavaScript
 *
 * Client-side filtering for the [teamtailor_jobs] shortcode.
 * No jQuery dependency — works with vanilla JS.
 *
 * @package TeamTailor_Integrator
 */
(function () {
  'use strict';

  var container = document.querySelector('.tt-jobs');
  if (!container) return;

  var grid      = document.getElementById('tt-jobs-grid');
  var empty     = document.getElementById('tt-jobs-empty');
  var searchEl  = document.getElementById('tt-filter-search');
  var depEl     = document.getElementById('tt-filter-department');
  var locEl     = document.getElementById('tt-filter-location');
  var roleEl    = document.getElementById('tt-filter-role');
  var clearBtn  = document.getElementById('tt-filter-clear');
  var countEl   = document.querySelector('.tt-jobs__count-number');

  if (!grid) return;

  var cards = Array.prototype.slice.call(grid.querySelectorAll('.tt-jobs__card'));

  /**
   * Normalize a string for loose matching.
   */
  function normalize(str) {
    return str
      .toLowerCase()
      .replace(/[^a-z0-9\s]/g, '')
      .replace(/\s+/g, ' ')
      .trim();
  }

  /**
   * Run all active filters and show/hide cards.
   */
  function filter() {
    var searchTerm = searchEl ? normalize(searchEl.value) : '';
    var department = depEl ? depEl.value.toLowerCase() : '';
    var location   = locEl ? locEl.value.toLowerCase() : '';
    var role       = roleEl ? roleEl.value.toLowerCase() : '';

    var visible = 0;

    cards.forEach(function (card) {
      var title      = (card.getAttribute('data-title') || '') + ' ' + (card.textContent || '').toLowerCase();
      var cardDep    = card.getAttribute('data-department') || '';
      var cardLoc    = card.getAttribute('data-location') || '';
      var cardRole   = card.getAttribute('data-role') || '';

      var matchSearch    = !searchTerm || normalize(title).indexOf(searchTerm) !== -1;
      var matchDep       = !department || cardDep.indexOf(department) !== -1;
      var matchLoc       = !location || cardLoc.indexOf(location) !== -1;
      var matchRole      = !role || cardRole.indexOf(role) !== -1;

      if (matchSearch && matchDep && matchLoc && matchRole) {
        card.style.display = '';
        visible++;
      } else {
        card.style.display = 'none';
      }
    });

    // Show / hide the empty state.
    if (empty) {
      empty.style.display = visible === 0 ? 'flex' : 'none';
    }

    // Update the counter in the navbar.
    if (countEl) {
      countEl.textContent = visible;
    }

    // Show / hide the Clear button.
    if (clearBtn) {
      var isActive = searchTerm || department || location || role;
      clearBtn.style.display = isActive ? 'inline-flex' : 'none';
    }
  }

  /**
   * Reset all filters to default.
   */
  function resetFilters() {
    if (searchEl) searchEl.value = '';
    if (depEl)    depEl.value = '';
    if (locEl)    locEl.value = '';
    if (roleEl)   roleEl.value = '';
    filter();
  }

  // ── Bind events ───────────────────────────────────────────────────

  if (searchEl) {
    searchEl.addEventListener('input', filter);
  }

  if (depEl) {
    depEl.addEventListener('change', filter);
  }

  if (locEl) {
    locEl.addEventListener('change', filter);
  }

  if (roleEl) {
    roleEl.addEventListener('change', filter);
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', resetFilters);
  }

  // Run initial filter (in case there are URL params or pre-selected values).
  filter();
})();
