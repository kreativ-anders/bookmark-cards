// pico-modal.js is prepended to this file by `npm run build` (build.mjs)

/**
 * ANALYTICS
 * Sends a Pirsch event, silently skipped when pa.js is blocked or offline.
 * Never pass personal data (titles, links, tags, email) as meta.
 * @param {string} name
 * @param {Object} [meta]
 */
function trackEvent(name, meta) {
  if (typeof pirsch !== 'function') return;
  try {
    var result = pirsch(name, meta ? { meta: meta } : {});
    if (result && typeof result.catch === 'function') result.catch(function() {});
  } catch (e) { /* analytics must never break the app */ }
}

/**
 * Runs fn on DOMContentLoaded, or immediately when the DOM is already parsed
 * (offline.html loads main.js late, after rendering the cards).
 * @param {Function} fn
 */
function onReady(fn) {
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn, false);
  else fn();
}

onReady(function() {

  // One-Pager! Prevent form resubmission
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }

  // Search Title-Link-Tags
  // Optimize: cache card search strings and debounce input to avoid layout thrashing on each keystroke
  (function() {
    var bookmarksEl = document.getElementById('bookmarks');
    if (!bookmarksEl) return;

    var cards = Array.from(bookmarksEl.children || []);

    // cache normalized search text for each card to avoid repeated DOM reads
    var cardSearchCache = cards.map(function(card) {
      return {
        card: card,
        text: (card.getAttribute('data-search') || '').toLowerCase()
      };
    });

    // simple debounce helper
    function debounce(fn, wait) {
      var t = null;
      return function() {
        var args = arguments;
        clearTimeout(t);
        t = setTimeout(function() { fn.apply(null, args); }, wait);
      };
    }

    function performSearch(value) {
      var v = String(value || '').toLowerCase();
      if (!v) {
        cardSearchCache.forEach(function(entry) { entry.card.style.display = ''; });
        return;
      }
      cardSearchCache.forEach(function(entry) {
        entry.card.style.display = entry.text.indexOf(v) > -1 ? '' : 'none';
      });
    }

    var handler = debounce(function(e) { performSearch(e && e.target ? e.target.value : ''); }, 120);

    Array.from(document.querySelectorAll('#s_title, #s_link, #s_tags')).forEach(function(input) {
      if (!input) return;
      input.addEventListener('input', handler, { passive: true });
    });
  })();

  // FAQ: which questions visitors open
  Array.from(document.querySelectorAll('#faq details')).forEach(function(details) {
    details.addEventListener('toggle', function() {
      if (!details.open) return;
      var summary = details.querySelector('summary');
      trackEvent('Open FAQ', { question: summary ? summary.textContent.trim() : '' });
    });
  });

  // Keyboard access for clickable card tags (WCAG 2.1.1)
  Array.from(document.querySelectorAll('#bookmarks span.tag[onclick]')).forEach(function(span) {
    span.setAttribute('role', 'button');
    span.setAttribute('tabindex', '0');
    span.setAttribute('aria-pressed', 'false');
    span.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); span.click(); }
    });
  });
});

// Lazy Load Bg-Images
onReady(function() {
  var lazyloadImages;

  if ("IntersectionObserver" in window) {
    lazyloadImages = document.querySelectorAll(".lazy");
    var imageObserver = new IntersectionObserver(function(entries, observer) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          var image = entry.target;
          // If data-src is set, swap it in. For background-image lazy loading, class removal
          // can trigger CSS to reveal background via CSS variables or rules.
          if (image.dataset && image.dataset.src) {
            image.src = image.dataset.src;
          }
          image.classList.remove("lazy");
          imageObserver.unobserve(image);
        }
      });
    });

    lazyloadImages.forEach(function(image) {
      imageObserver.observe(image);
    });
  } else {
    var lazyloadThrottleTimeout;
    lazyloadImages = Array.from(document.querySelectorAll('.lazy'));

    function lazyload() {
      if (lazyloadThrottleTimeout) {
        clearTimeout(lazyloadThrottleTimeout);
      }

      lazyloadThrottleTimeout = setTimeout(function() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        lazyloadImages = lazyloadImages.filter(function(img) {
          // skip if already loaded/removed
          if (!img || img.classList.indexOf && img.classList.indexOf('lazy') === -1) return false;
          var top = img.getBoundingClientRect().top + scrollTop;
          if (top < (window.innerHeight + scrollTop)) {
            if (img.dataset && img.dataset.src) img.src = img.dataset.src;
            img.classList.remove('lazy');
            return false; // remove from list
          }
          return true; // keep
        });

        if (lazyloadImages.length === 0) {
          document.removeEventListener('scroll', lazyload);
          window.removeEventListener('resize', lazyload);
          window.removeEventListener('orientationchange', lazyload);
        }
      }, 100);
    }

    document.addEventListener('scroll', lazyload, { passive: true });
    window.addEventListener('resize', lazyload, { passive: true });
    window.addEventListener('orientationchange', lazyload, { passive: true });
  }
})

/**
 * UX
 * Add https:// when missing
 * @param {*} url 
 * @returns 
 */
function checkURL(url) {
  // Accept either an input element or a string. Return normalized string for compatibility.
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
 * MODAL
 * Push initial values to modal form
 * @param {*} id 
 * @param {*} title 
 * @param {*} link 
 * @param {*} tags 
 */
function changeData(id, title, link, tags) {
  // Defensive: only set values if elements exist
  var el;
  el = document.getElementById('id'); if (el) el.value = id || '';
  el = document.getElementById('title'); if (el) el.value = title || '';
  el = document.getElementById('link'); if (el) el.value = link || '';
  el = document.getElementById('tags'); if (el) el.value = tags || '';
}

/**
 * FUNCTION
 * Create a color palete for cards without background images
 * @returns colors
 */
function randomBgColor() {
  // Soft tints (rgb), painted at low opacity over the light card surface
  var colors = ['100, 210, 255', '255, 159, 10', '48, 209, 88', '191, 90, 242', '255, 55, 95', '255, 214, 10', '94, 92, 230', '102, 212, 207', '172, 142, 104'];

  return colors[Math.floor(Math.random() * colors.length)];
}

/**
 * FEATURE
 * Toggle visability of selected top tag
 * @param {*} tag 
 */
function toggleTag(tag) {
  var t = String(tag || '');
  var allTags = Array.from(document.querySelectorAll('span.tag'));
  allTags.forEach(function(span) {
    span.setAttribute('aria-pressed', 'false');
  });

  var bookmarksEl = document.getElementById('bookmarks');
  if (!bookmarksEl) return;

  var current = localStorage.getItem('tag');
  if (current === t) {
    Array.from(bookmarksEl.children).forEach(function(card) { card.style.display = ''; });
    localStorage.removeItem('tag');
    return;
  }

  Array.from(bookmarksEl.children).forEach(function(card) {
    var s = String(card.getAttribute('data-tags') || '');
    card.style.display = s.indexOf(t) > -1 ? '' : 'none';
  });
  trackEvent('Filter By Tag');
  allTags.forEach(function(span) {
    if ((span.getAttribute('data-tag') || '').indexOf(t) > -1) span.setAttribute('aria-pressed', 'true');
  });
  localStorage.setItem('tag', t);
}

/**
 * FEATURE
 * Create tags of the most used tags at all
 */
function topTags() {
  // identify top x tags
  var arr = Array.from(document.querySelectorAll('span.tag')).map(function(span) { return span.textContent || ''; });

  var hist = {};
  arr.map(function(a) {
    if (a in hist) hist[a]++;
    else hist[a] = 1;
  });
  var sort = Object.keys(hist).sort(function(a, b) { return hist[a] - hist[b]; });

  let n = Math.round(Math.sqrt(Object.keys(hist).length) / 5) * 5;
  n = n > 10 ? 10 : n;
  var topTags = sort.slice(Math.max(sort.length - n, 1));
  topTags = topTags.reverse();

  var ttp = document.getElementById('top-tags-placeholder');
  if (!ttp) return;

  // create topTags next to user settings button
  topTags.forEach(function(tag) {
    var li = document.createElement('li');
    var span = document.createElement('span');
    span.classList.add('tag');
    li.classList.add('top-tag');
    span.dataset.tag = tag;
    span.setAttribute('role', 'button');
    span.setAttribute('aria-pressed', 'false');
    span.setAttribute('tabindex', '0');
    span.addEventListener('click', function() { toggleTag(tag); });
    span.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleTag(tag); } });
    span.innerText = tag;
    li.appendChild(span);
    ttp.before(li);
  });
}

/**
 * FEATURE
 * Create tags of the most used tags at all
 */
function generateBackgroundColors() {
  // ColorThief is optional (CDN, unavailable offline): without it only cards without a logo get a tint
  var colorThief = typeof ColorThief === 'function' ? new ColorThief() : null;

  // Select all bookmarks with background images
  var bookmarks = Array.from(document.querySelectorAll('div#bookmarks article'));

  bookmarks.forEach(function(bookmark) {
    try {
      var style = window.getComputedStyle(bookmark);
      var backgroundImage = style && style.backgroundImage ? style.backgroundImage : 'none';

      // If no background-image, set gradient
      if (backgroundImage === 'none' || !backgroundImage || backgroundImage === '') {
        var tint = randomBgColor();
        bookmark.style.setProperty('--glow', tint.replace(/,/g, ''));
        bookmark.style.backgroundImage = 'linear-gradient(to bottom, rgba(' + tint + ', 0) 0%, rgba(' + tint + ', .4) 100%)';
        bookmark.style.backgroundSize = '100% 100%';
        return;
      }

      if (!colorThief) return;

      // Extract URL from background-image: url("...")
      var match = backgroundImage.match(/url\((?:\")?(.*?)(?:\")?\)/);
      var src = match && match[1] ? match[1] : null;
      if (!src) return;

      var img = new Image();
      img.crossOrigin = 'anonymous';
      img.src = src;

      img.onload = function() {
        try {
          var palette = colorThief.getPalette(img, 2) || [];
          var color = palette[1] || palette[0] || [200,200,200];
          // neutral brand colors (black/grey logos) keep the plain card surface
          var max = Math.max.apply(null, color), min = Math.min.apply(null, color);
          // hover glow: the most saturated palette color (grey for black/neutral logos, visible in both themes)
          var glow = (colorThief.getPalette(img, 5) || []).concat([color]).reduce(function(best, c) {
            var chroma = Math.max.apply(null, c) - Math.min.apply(null, c);
            return chroma > best.chroma ? { c: c, chroma: chroma } : best;
          }, { c: null, chroma: 47 }).c;
          bookmark.style.setProperty('--glow', glow ? glow.join(' ') : '142 142 147');
          if (max - min < 48) return;
          // opaque mix (20 % brand color over the light card surface): cards stay light in dark mode
          // and card text keeps >= 4.5:1 contrast (WCAG AA)
          var base = String(window.getComputedStyle(bookmark).getPropertyValue('--card-rgb') || '255 255 255').trim().split(/\s+/).map(Number);
          var mix = color.map(function(c, i) { return Math.round((base[i] || 255) + (c - (base[i] || 255)) * 0.2); });
          bookmark.style.backgroundColor = 'rgb(' + mix.join(',') + ')';
        } catch (e) {
          // ignore palette extraction errors
        }
      };
    } catch (e) {
      // keep page robust if any unexpected error occurs
    }
  });
}

// remember scroll position across reloads
/**
 * Preserve scroll position across a single reload/navigation.
 * Stores Y on beforeunload and restores once on next load. Uses sessionStorage.
 */
(function () {
  const KEY = 'bookmark.cards.scrollY';

  // before leaving the page (form submit, reload, navigation, ...)
  window.addEventListener('beforeunload', function () {
    try { sessionStorage.setItem(KEY, String(window.scrollY || 0)); } catch (e) { /* noop */ }
  });

  // when you come back (after POST/redirect)
  window.addEventListener('load', function () {
    try {
      const y = sessionStorage.getItem(KEY);
      if (y !== null) {
        window.scrollTo(0, parseInt(y, 10) || 0);
        sessionStorage.removeItem(KEY); // only restore once
      }
    } catch (e) { /* noop */ }
  });
})();
/**
 * FEATURE
 * Color theme toggle (system -> light -> dark), stored per device.
 * header.php applies the stored theme before the CSS loads; this wires the button.
 * Runs on DOMContentLoaded or immediately (offline.html loads main.js late).
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
      } catch (e) { /* storage blocked: still switch for this page view */ }
      apply(current, button);
      trackEvent('Change Theme', { theme: current });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
