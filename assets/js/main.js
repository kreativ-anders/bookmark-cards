// @prepros-prepend pico-modal.js

document.addEventListener('DOMContentLoaded', function() {

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
        cardSearchCache.forEach(function(entry) { entry.card.style.display = 'block'; });
        return;
      }
      cardSearchCache.forEach(function(entry) {
        entry.card.style.display = entry.text.indexOf(v) > -1 ? 'block' : 'none';
      });
    }

    var handler = debounce(function(e) { performSearch(e && e.target ? e.target.value : ''); }, 120);

    Array.from(document.querySelectorAll('#s_title, #s_link, #s_tags')).forEach(function(input) {
      if (!input) return;
      input.addEventListener('input', handler, { passive: true });
    });
  })();
}, false);

// Lazy Load Bg-Images
document.addEventListener("DOMContentLoaded", function() {
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
  // Light colors
  var colors = ['#ADD8E6', '#F08080', '#E0FFFF', '#FAFAD2', '#D3D3D3', '#90EE90', '#FFB6C1', '#FFA07A', '#20B2AA', '#87CEFA', '#778899', '#B0C4DE', '#FFFFE0'];

  /*if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches && document.documentElement.getAttribute("data-theme") != "light") {
    var colors = ['#235e71', '#650c0c', '#00e0e0', '#b9b915', '#545454', '#116e11', '#b6001b', '#7a2300', '#0d4643', '#054f7d', '#07090a', '#2a4465', '#e0e000'];
  }*/

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
    span.style.opacity = '0.7';
    span.style.border = 'none';
    span.style.color = 'unset';
    span.setAttribute('aria-selected', 'false');
  });

  var bookmarksEl = document.getElementById('bookmarks');
  if (!bookmarksEl) return;

  var current = localStorage.getItem('tag');
  if (current === t) {
    Array.from(bookmarksEl.children).forEach(function(card) { card.style.display = 'block'; });
    localStorage.removeItem('tag');
    return;
  }

  Array.from(bookmarksEl.children).forEach(function(card) {
    card.style.display = 'none';
    var s = String(card.getAttribute('data-tags') || '');
    if (s.indexOf(t) > -1) {
      card.style.display = 'block';
      var selector = "span[data-tag*='" + CSS.escape ? CSS.escape(t) : t + "']";
      try {
        document.querySelectorAll("span[data-tag*='" + t + "']").forEach(function(tagEl) {
          tagEl.style.color = 'var(--pico-primary)';
          tagEl.style.opacity = '1';
          tagEl.setAttribute('aria-selected', 'true');
        });
      } catch (e) {
        // fallback: ignore selectors that may throw
      }
    }
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
    span.setAttribute('aria-controls', tag);
    span.setAttribute('aria-selected', 'false');
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
  // Initialize ColorThief instance
  // defensive: ColorThief is optional; bail out if not present
  if (typeof ColorThief !== 'function') return;
  var colorThief = new ColorThief();

  // Select all bookmarks with background images
  var bookmarks = Array.from(document.querySelectorAll('div#bookmarks article'));

  bookmarks.forEach(function(bookmark) {
    try {
      var style = window.getComputedStyle(bookmark);
      var backgroundImage = style && style.backgroundImage ? style.backgroundImage : 'none';

      // If no background-image, set gradient
      if (backgroundImage === 'none' || !backgroundImage || backgroundImage === '') {
        bookmark.style.background = 'linear-gradient(to bottom, white 0%,' + randomBgColor() + ' 100%)';
        return;
      }

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
          bookmark.style.backgroundColor = 'rgba(' + color[0] + ',' + color[1] + ',' + color[2] + ',0.33)';
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