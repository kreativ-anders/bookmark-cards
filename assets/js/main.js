// pico-modal.js is prepended by build.mjs; top-level functions stay global for inline handlers

/**
 * Pirsch event, skipped silently when pa.js is blocked or offline.
 * Never pass personal data (titles, links, tags, email) as meta.
 */
function trackEvent(name, meta) {
  if (typeof pirsch !== 'function') return;
  try {
    var result = pirsch(name, meta ? { meta: meta } : {});
    if (result && typeof result.catch === 'function') result.catch(function() {});
  } catch (e) {}
}

// offline.html loads main.js after the DOM is parsed
function onReady(fn) {
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, false);
  else fn();
}

function bookmarkCards() {
  var bookmarksEl = document.getElementById('bookmarks');
  return bookmarksEl ? Array.from(bookmarksEl.children) : [];
}

function updateSearchStatus() {
  var status = document.getElementById('search-status');
  if (!status) return;
  var cards = bookmarkCards();
  var hidden = cards.filter(function(card) { return card.style.display === 'none'; }).length;
  status.textContent = cards.length && hidden === cards.length ? 'No matching bookmarks.' : '';
}

onReady(function() {

  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }

  // search while typing into the add form: title, link and tags
  (function() {
    var cards = bookmarkCards().map(function(card) {
      return { card: card, text: (card.getAttribute('data-search') || '').toLowerCase() };
    });
    if (!cards.length) return;

    var timer = null;
    function search(value) {
      var v = String(value || '').toLowerCase();
      cards.forEach(function(entry) {
        entry.card.style.display = !v || entry.text.indexOf(v) > -1 ? '' : 'none';
      });
      updateSearchStatus();
    }

    document.querySelectorAll('#s_title, #s_link, #s_tags').forEach(function(input) {
      input.addEventListener('input', function(e) {
        clearTimeout(timer);
        timer = setTimeout(function() { search(e.target.value); }, 120);
      }, { passive: true });
    });
  })();

  document.querySelectorAll('#faq details').forEach(function(details) {
    details.addEventListener('toggle', function() {
      if (!details.open) return;
      var summary = details.querySelector('summary');
      trackEvent('Open FAQ', { question: summary ? summary.textContent.trim() : '' });
    });
  });

  // keyboard access for clickable card tags (WCAG 2.1.1)
  document.querySelectorAll('#bookmarks span.tag[onclick]').forEach(function(span) {
    span.setAttribute('role', 'button');
    span.setAttribute('tabindex', '0');
    span.setAttribute('aria-pressed', 'false');
    span.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); span.click(); }
    });
  });
});

/**
 * Adds https:// when the scheme is missing; accepts an input element or a string
 */
function checkURL(url) {
  if (!url) return url;
  var isElement = typeof url === 'object' && 'value' in url;
  var s = isElement ? String(url.value || '') : String(url);
  if (s && s.indexOf('://') === -1) {
    s = 'https://' + s;
  }
  if (isElement) url.value = s;
  return isElement ? url : s;
}

/**
 * Fills the edit modal
 */
function changeData(id, title, link, tags) {
  var el;
  el = document.getElementById('id'); if (el) el.value = id || '';
  el = document.getElementById('title'); if (el) el.value = title || '';
  el = document.getElementById('link'); if (el) el.value = link || '';
  el = document.getElementById('tags'); if (el) el.value = tags || '';
}

/**
 * Soft tint (rgb) for cards without a brand logo
 */
function randomBgColor() {
  var colors = ['100, 210, 255', '255, 159, 10', '48, 209, 88', '191, 90, 242', '255, 55, 95', '255, 214, 10', '94, 92, 230', '102, 212, 207', '172, 142, 104'];
  return colors[Math.floor(Math.random() * colors.length)];
}

function tagList(value) {
  return String(value || '').split(',').map(function(tag) { return tag.trim().toLowerCase(); });
}

/**
 * Shows only the cards with exactly this tag, the same tag again shows all cards
 */
function toggleTag(tag) {
  var t = String(tag || '').trim().toLowerCase();
  var allTags = Array.from(document.querySelectorAll('span.tag'));
  allTags.forEach(function(span) { span.setAttribute('aria-pressed', 'false'); });

  var cards = bookmarkCards();
  if (!cards.length) return;

  var current = null;
  try { current = localStorage.getItem('tag'); } catch (e) {}

  if (current !== null && current.trim().toLowerCase() === t) {
    cards.forEach(function(card) { card.style.display = ''; });
    try { localStorage.removeItem('tag'); } catch (e) {}
    updateSearchStatus();
    return;
  }

  cards.forEach(function(card) {
    card.style.display = tagList(card.getAttribute('data-tags')).indexOf(t) > -1 ? '' : 'none';
  });
  allTags.forEach(function(span) {
    if ((span.getAttribute('data-tag') || '').trim().toLowerCase() === t) span.setAttribute('aria-pressed', 'true');
  });
  try { localStorage.setItem('tag', String(tag)); } catch (e) {}
  trackEvent('Filter By Tag');
  updateSearchStatus();
}

/**
 * Most used tags next to the settings button
 */
function topTags() {
  var hist = {};
  document.querySelectorAll('span.tag').forEach(function(span) {
    var tag = span.textContent || '';
    hist[tag] = (hist[tag] || 0) + 1;
  });
  var sorted = Object.keys(hist).sort(function(a, b) { return hist[a] - hist[b]; });

  var n = Math.min(Math.round(Math.sqrt(sorted.length) / 5) * 5, 10);
  var top = sorted.slice(Math.max(sorted.length - n, 1)).reverse();

  var placeholder = document.getElementById('top-tags-placeholder');
  if (!placeholder) return;

  top.forEach(function(tag) {
    var li = document.createElement('li');
    var span = document.createElement('span');
    li.classList.add('top-tag');
    span.classList.add('tag');
    span.dataset.tag = tag;
    span.setAttribute('role', 'button');
    span.setAttribute('aria-pressed', 'false');
    span.setAttribute('tabindex', '0');
    span.addEventListener('click', function() { toggleTag(tag); });
    span.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleTag(tag); } });
    span.innerText = tag;
    li.appendChild(span);
    placeholder.before(li);
  });
}

var BRAND_COLORS_KEY = 'bookmark.cards.colors';

/**
 * Brand tint and hover glow from the logo (ColorThief), cached per logo URL,
 * so every logo is analysed only once per browser and cards are tinted offline too
 */
function generateBackgroundColors() {
  var colorThief = typeof ColorThief === 'function' ? new ColorThief() : null;
  var cache = {};
  try { cache = JSON.parse(localStorage.getItem(BRAND_COLORS_KEY)) || {}; } catch (e) {}

  var cards = Array.from(document.querySelectorAll('div#bookmarks article'));
  var base = null;

  function apply(card, colors) {
    card.style.setProperty('--glow', colors.glow ? colors.glow.join(' ') : '142 142 147');
    var color = colors.color;
    // neutral (black/grey) logos keep the plain card surface
    if (Math.max.apply(null, color) - Math.min.apply(null, color) < 48) return;
    // opaque 20 % mix over the light card surface keeps card text at >= 4.5:1 (WCAG AA) in both themes
    base = base || String(window.getComputedStyle(card).getPropertyValue('--card-rgb') || '255 255 255').trim().split(/\s+/).map(Number);
    var mix = color.map(function(c, i) { return Math.round((base[i] || 255) + (c - (base[i] || 255)) * 0.2); });
    card.style.backgroundColor = 'rgb(' + mix.join(',') + ')';
  }

  function analyse(img) {
    var palette = colorThief.getPalette(img, 2) || [];
    var color = palette[1] || palette[0] || [200, 200, 200];
    // glow: the most saturated palette color, none for neutral logos
    var glow = (colorThief.getPalette(img, 5) || []).concat([color]).reduce(function(best, c) {
      var chroma = Math.max.apply(null, c) - Math.min.apply(null, c);
      return chroma > best.chroma ? { c: c, chroma: chroma } : best;
    }, { c: null, chroma: 47 }).c;
    return { color: color, glow: glow };
  }

  cards.forEach(function(card) {
    try {
      var backgroundImage = card.style.backgroundImage || window.getComputedStyle(card).backgroundImage;
      var match = backgroundImage && backgroundImage.match(/url\(["']?(.*?)["']?\)/);
      var src = match && match[1];

      if (!src) {
        var tint = randomBgColor();
        card.style.setProperty('--glow', tint.replace(/,/g, ''));
        card.style.backgroundImage = 'linear-gradient(to bottom, rgba(' + tint + ', 0) 0%, rgba(' + tint + ', .4) 100%)';
        card.style.backgroundSize = '100% 100%';
        return;
      }

      if (cache[src]) return apply(card, cache[src]);
      if (!colorThief) return;

      var img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = function() {
        try {
          cache[src] = analyse(img);
          apply(card, cache[src]);
          localStorage.setItem(BRAND_COLORS_KEY, JSON.stringify(cache));
        } catch (e) {}
      };
      img.src = src;
    } catch (e) {}
  });
}

// keeps the scroll position across the POST/redirect of add, edit and delete
(function () {
  const KEY = 'bookmark.cards.scrollY';

  window.addEventListener('beforeunload', function () {
    try { sessionStorage.setItem(KEY, String(window.scrollY || 0)); } catch (e) {}
  });

  window.addEventListener('load', function () {
    try {
      const y = sessionStorage.getItem(KEY);
      if (y !== null) {
        window.scrollTo(0, parseInt(y, 10) || 0);
        sessionStorage.removeItem(KEY);
      }
    } catch (e) {}
  });
})();

/**
 * Color theme toggle (system -> light -> dark), stored per device.
 * header.php applies the stored theme before the CSS loads.
 */
(function () {
  const KEY = 'bookmark.cards.theme';
  const MODES = ['system', 'light', 'dark'];
  const COLORS = { light: '#f5f5f7', dark: '#101012' };

  function stored() {
    try {
      const t = localStorage.getItem(KEY);
      return MODES.indexOf(t) > -1 ? t : 'system';
    } catch (e) { return 'system'; }
  }

  function apply(mode, button) {
    const root = document.documentElement;
    if (mode === 'system') root.removeAttribute('data-theme');
    else root.setAttribute('data-theme', mode);

    // browser UI color follows an explicit choice, otherwise the media queries
    document.querySelectorAll('meta[name="theme-color"]').forEach(function (meta) {
      if (!meta.dataset.media) meta.dataset.media = meta.getAttribute('media') || '';
      if (mode === 'system') {
        meta.setAttribute('media', meta.dataset.media);
        meta.setAttribute('content', meta.dataset.media.indexOf('dark') > -1 ? COLORS.dark : COLORS.light);
      } else {
        meta.removeAttribute('media');
        meta.setAttribute('content', COLORS[mode]);
      }
    });

    if (button) {
      const next = MODES[(MODES.indexOf(mode) + 1) % MODES.length];
      const label = 'Color theme: ' + mode + ' (switch to ' + next + ')';
      button.dataset.themeMode = mode;
      button.setAttribute('aria-label', label);
      button.setAttribute('title', label);
    }
  }

  function init() {
    const button = document.getElementById('theme-toggle');
    let current = stored();
    apply(current, button);
    if (!button || button.dataset.wired) return;
    button.dataset.wired = '1';
    button.hidden = false;
    button.addEventListener('click', function () {
      current = MODES[(MODES.indexOf(current) + 1) % MODES.length];
      try {
        if (current === 'system') localStorage.removeItem(KEY);
        else localStorage.setItem(KEY, current);
      } catch (e) {}
      apply(current, button);
      trackEvent('Change Theme', { theme: current });
    });
  }

  onReady(init);
})();
