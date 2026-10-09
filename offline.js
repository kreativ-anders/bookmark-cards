console.log("You are Offline!");

let user = null;

// Brand logos (token => URL) and matching rules from /brands.json and /brands-rules.json,
// cached by the service worker. Same rules as BrandLogos::find() in site/plugins/brands:
// a domain label of the link first, then whole words of the title, the longest token wins.
let brandLogos = {};
let brandRules = { aliases: {}, domainOnly: [] };
function loadJson(url) {
  return fetch(url)
    .then(response => (response && response.ok) ? response : (typeof caches !== 'undefined' ? caches.match(url) : null))
    .catch(() => (typeof caches !== 'undefined' ? caches.match(url) : null))
    .then(response => response ? response.json() : null)
    .catch(() => null);
}
const brandsLoaded = Promise.all([loadJson('/brands.json'), loadJson('/brands-rules.json')])
  .then(([logos, rules]) => {
    brandLogos = logos || {};
    if (rules) brandRules = { aliases: rules.aliases || {}, domainOnly: rules.domainOnly || [] };
  });

function brandToken(value) {
  return value.toLowerCase().replace(/[^a-z]+/g, '');
}

// longest candidate with a logo, the first one on a tie
function longestBrand(candidates) {
  let match = '';
  for (const token of candidates) {
    if (token.length > match.length && Object.prototype.hasOwnProperty.call(brandLogos, token)) match = token;
  }
  return match;
}

function brandFromLink(link) {
  let host = '';
  try {
    host = new URL(/^[a-z][a-z0-9+.-]*:\/\//i.test(link) ? link : 'http://' + link).hostname.toLowerCase().replace(/\.$/, '');
  } catch (e) {
    return '';
  }
  if (!host) return '';
  for (const domain in brandRules.aliases) {
    const token = brandRules.aliases[domain];
    if ((host === domain || host.endsWith('.' + domain)) && Object.prototype.hasOwnProperty.call(brandLogos, token)) return token;
  }
  return longestBrand(host.split('.').slice(0, -1).map(brandToken));
}

function brandFromTitle(title) {
  const words = title.replace(/(\p{Ll})(?=\p{Lu})/gu, '$1 ').toLowerCase().split(/[^a-z]+/).filter(Boolean);
  const max = Math.max(0, ...Object.keys(brandLogos).map(token => token.length));
  const runs = [];
  for (let i = 0; i < words.length; i++) {
    let run = '';
    for (let j = i; j < words.length && (run += words[j]).length <= max; j++) {
      if (!brandRules.domainOnly.includes(run)) runs.push(run);
    }
  }
  return longestBrand(runs);
}

function brandLogo(title, link) {
  const match = brandFromLink(link || '') || brandFromTitle(title || '');
  return match ? brandLogos[match] : null;
}

// Same rule as Bookmarks::isSafe(): javascript:, vbscript: and data: links never become a live href
function safeHref(link) {
  return /^(javascript|vbscript|data):/i.test(link.replace(/[\x00-\x20]+/g, '')) ? '#' : link;
}

/**
 * Load user JSON: network (the service worker answers from its cache when offline),
 * then Cache API, then localStorage. Resolves null when nothing is available.
 */
function loadUser() {
  return fetch('/user.json')
    .then(response => {
      if (!response || !response.ok) throw new Error('Network response was not ok');
      return response.json();
    })
    .catch(err => {
      console.warn('Unable to fetch /user.json from network:', err && err.message);
      if (typeof caches === 'undefined' || !caches.match) return Promise.reject(err);
      return caches.match('/user.json').then(cached => {
        if (!cached) throw new Error('no cached user.json');
        return cached.json();
      });
    })
    .catch(() => {
      try {
        const raw = localStorage.getItem('user');
        if (raw) return JSON.parse(raw);
      } catch (e) {
        console.warn('localStorage read failed', e && e.message);
      }
      return null;
    });
}

/**
 * main.js (search, tag filter, theme toggle) and the same init the footer runs online.
 * main.js is loaded after the cards exist, its search caches the cards on init.
 */
function loadMain() {
  const script = document.createElement('script');
  script.src = '/assets/js/main.min.js';
  script.onload = function() {
    if (typeof topTags === 'function') topTags();
    if (typeof generateBackgroundColors === 'function') generateBackgroundColors();
  };
  document.head.appendChild(script);
}

function showNotice(text) {
  const container = document.getElementById('bookmarks');
  if (!container) return;
  const notice = document.createElement('p');
  notice.className = 'notice';
  notice.textContent = text;
  container.appendChild(notice);
}

function ready(fn) {
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
  else fn();
}

Promise.all([loadUser(), brandsLoaded]).then(function(results) {
  ready(function() {
    user = results[0];
    const bookmarks = (user && Array.isArray(user.Bookmarks)) ? user.Bookmarks : [];

    // Premium badge like the online header (every tier except the free one)
    const subscription = user && user.User ? String(user.User.Subscription || '') : '';
    const premium = document.getElementById('premium-meta');
    if (premium && subscription && subscription !== 'Free') premium.hidden = false;

    if (bookmarks.length) printBookmarks(bookmarks);
    else showNotice('No bookmarks available offline.');

    loadMain();
  });
});

/**
 * Renders the cards like site/snippets/bookmarks.php, without edit/delete (needs the server).
 * @param {*} bookmarks array
 */
function printBookmarks(bookmarks) {
  if (!bookmarks || !bookmarks.length) return;

  const container = document.getElementById('bookmarks');
  if (!container) return;

  // Build nodes in a fragment to reduce layout thrashing
  const frag = document.createDocumentFragment();

  const l = bookmarks.length;
  for (let i = 0; i < l; ++i) {
    const bookmark = bookmarks[i] || {};

    // Bookmark Wrapper
    const article = document.createElement('article');
    article.classList.add('bookmark', 'card-background', 'lazy');
    const title = (bookmark.title || '').toString().trim();
    const link = (bookmark.link || '').toString();
    const tags = (bookmark.tags || '').toString();
    const href = safeHref(link) || '#';
    article.dataset.search = title + ';' + link + ';' + tags;
    article.dataset.tags = tags;
    const logo = (title || link) ? brandLogo(title, link) : null;
    if (logo) article.style.backgroundImage = "url('" + logo + "')";

    // Bookmark Header
    const header = document.createElement('header');
    const header_anker = document.createElement('a');
    header_anker.classList.add('card-title');
    header_anker.rel = 'noopener noreferrer';
    header_anker.target = '_self';
    header_anker.href = href;
    header_anker.textContent = title || link || 'Untitled';
    header.appendChild(header_anker);

    // Bookmark Middle
    const middle_anker = document.createElement('a');
    middle_anker.rel = 'noopener noreferrer';
    middle_anker.target = '_self';
    middle_anker.href = href;
    middle_anker.setAttribute('aria-label', title || link || 'Untitled');
    const middle_anker_span = document.createElement('span');
    middle_anker_span.classList.add('card-spanner');
    middle_anker.appendChild(middle_anker_span);

    // Bookmark Footer: tags (filter via main.js toggleTag, keyboard access added by main.js)
    const footer = document.createElement('footer');
    const grid = document.createElement('div');
    grid.classList.add('grid', 'card-grid');
    tags.split(',').map(t => t.trim()).filter(Boolean).forEach(function(tag) {
      const span = document.createElement('span');
      span.classList.add('tag');
      span.dataset.tag = tag;
      span.setAttribute('onclick', "toggleTag(this.getAttribute('data-tag'))");
      span.textContent = tag;
      grid.appendChild(span);
    });
    footer.appendChild(grid);

    article.appendChild(header);
    article.appendChild(middle_anker);
    article.appendChild(footer);

    frag.appendChild(article);
  }

  container.appendChild(frag);
}
