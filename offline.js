console.log("You are Offline!");

let user = null;

// Try to load user JSON from network, fallback to Cache API or localStorage when offline.
fetch("/user.json")
  .then(response => {
    if (!response || !response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .then(function(json) {
    // Print JSON
    user = json;
    printBookmarks(user.Bookmarks || []);

    // Load main.js after rendering the offline bookmark list so any dynamic behaviors can attach.
    var script = document.createElement('script');
    script.src = "assets/js/main.min.js";
    document.head.appendChild(script);
  })
  .catch(function(err) {
    // Network failed — try Cache API (if available) then localStorage as last resort.
    console.warn('Unable to fetch /user.json from network:', err && err.message);

    // Helper to attempt a JSON Response -> object
    function tryFromResponse(resp) {
      if (!resp) return Promise.reject(new Error('no response'));
      try {
        return resp.json();
      } catch (e) {
        return Promise.reject(e);
      }
    }

    if (typeof caches !== 'undefined' && caches.match) {
      caches.match('/user.json').then(function(cached) {
        if (cached) {
          return tryFromResponse(cached);
        }
        return Promise.reject(new Error('no cached user.json'));
      }).then(function(json) {
        user = json;
        printBookmarks(user.Bookmarks || []);
      }).catch(function() {
        // fallback to localStorage
        try {
          const raw = localStorage.getItem('user');
          if (raw) {
            const parsed = JSON.parse(raw);
            user = parsed;
            printBookmarks(user.Bookmarks || []);
            return;
          }
        } catch (e) {
          console.warn('localStorage read failed', e && e.message);
        }
        // final fallback: show a friendly message
        const container = document.getElementById('bookmarks');
        if (container) {
          const notice = document.createElement('p');
          notice.className = 'notice';
          notice.textContent = 'No bookmarks available offline.';
          container.appendChild(notice);
        }
      });
    } else {
      // caches not supported, try localStorage directly
      try {
        const raw = localStorage.getItem('user');
        if (raw) {
          const parsed = JSON.parse(raw);
          user = parsed;
          printBookmarks(user.Bookmarks || []);
          return;
        }
      } catch (e) {
        console.warn('localStorage read failed', e && e.message);
      }
      const container = document.getElementById('bookmarks');
      if (container) {
        const notice = document.createElement('p');
        notice.className = 'notice';
        notice.textContent = 'No bookmarks available offline.';
        container.appendChild(notice);
      }
    }
  });

/**
 * 
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
    article.dataset.search = title + ';' + link + ';' + tags;
    article.dataset.tags = tags;
    if (title) article.setAttribute('brand', title.toLowerCase());

    // Bookmark Header
    const header = document.createElement('header');
    const header_anker = document.createElement('a');
    header_anker.classList.add('card-title');
    header_anker.rel = 'noopener noreferrer';
    header_anker.target = '_self';
    header_anker.href = link || '#';
    header_anker.textContent = title || link || 'Untitled';
    header.appendChild(header_anker);

    // Bookmark Middle
    const middle_anker = document.createElement('a');
    middle_anker.rel = 'noopener noreferrer';
    middle_anker.target = '_self';
    middle_anker.href = link || '#';
    const middle_anker_span = document.createElement('span');
    middle_anker_span.classList.add('card-spanner');
    middle_anker.appendChild(middle_anker_span);

    article.appendChild(header);
    article.appendChild(middle_anker);

    frag.appendChild(article);
  }

  container.appendChild(frag);
}