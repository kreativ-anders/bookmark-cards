// @prepros-prepend pico-modal.js

document.addEventListener('DOMContentLoaded', function() {

  // One-Pager! Prevent form resubmission
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }

  // Search Title-Link-Tags
  document.querySelectorAll("#s_title, #s_link, #s_tags").forEach(input => {
    input.addEventListener("keyup", function() {
      var value = input.value.toLowerCase();
      Array.from(document.getElementById("bookmarks").children).filter(function(card) {
        var indexOf = card.getAttribute("data-search").toLowerCase().indexOf(value);
        if (indexOf > -1) {
          card.style.display = "block";
        } else {
          card.style.display = "none";
        }
      });
    });
  });

  // Dyn background (without background-image)
  Array.from(document.querySelectorAll("article.card-background")).map(function(card) {
    var b = null;

    if (window.getComputedStyle(card).backgroundImage === 'none') {
      b = 'linear-gradient(to bottom, white 0%,' + randomLightColor() + '  100%)';
      card.style.background = b;
    }
  });


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
    lazyloadImages = document.querySelectorAll(".lazy");

    function lazyload() {
      if (lazyloadThrottleTimeout) {
        clearTimeout(lazyloadThrottleTimeout);
      }

      lazyloadThrottleTimeout = setTimeout(function() {
        var scrollTop = window.pageYOffset;
        lazyloadImages.forEach(function(img) {
          if (img.offsetTop < (window.innerHeight + scrollTop)) {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
          }
        });
        if (lazyloadImages.length == 0) {
          document.removeEventListener("scroll", lazyload);
          window.removeEventListener("resize", lazyload);
          window.removeEventListener("orientationChange", lazyload);
        }
      }, 20);
    }

    document.addEventListener("scroll", lazyload);
    window.addEventListener("resize", lazyload);
    window.addEventListener("orientationChange", lazyload);
  }
})

/**
 * UX
 * Add https:// when missing
 * @param {*} url 
 * @returns 
 */
function checkURL(url) {
  var s = url.value;
  if (!~s.indexOf("://")) {
    s = "https://" + s;
  }
  url.value = s;
  return url
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
  document.getElementById("id").value = id;
  document.getElementById("title").value = title;
  document.getElementById("link").value = link;
  document.getElementById("tags").value = tags;
}

/**
 * FUNCTION
 * Create a light color palete for cards without background images
 * @returns colors
 */
function randomLightColor() {
  var colors = ['#ADD8E6', '#F08080', '#E0FFFF', '#FAFAD2', '#D3D3D3', '#D3D3D3', '#90EE90', '#FFB6C1', '#FFA07A', '#20B2AA', '#87CEFA', '#778899', '#778899', '#B0C4DE', '#FFFFE0'];

  return colors[Math.floor(Math.random() * colors.length)];
}

/**
 * FEATURE
 * Toggle visability of selected top tag
 * @param {*} tag 
 */
function toggleTag(tag) {

  var t = String(tag);

  document.querySelectorAll("span.tag").forEach(span => {
    span.style.opacity = 0.2;
    span.style.border = "none";
  })

  if (localStorage.getItem("tag") == t) {

    Array.from(document.getElementById("bookmarks").children).map(function(card) {
      card.style.display = "block";
    });
    localStorage.removeItem("tag");

  } else {

    Array.from(document.getElementById("bookmarks").children).map(function(card) {
      card.style.display = "none";

      var s = String(card.getAttribute("data-tags"));

      if (s.includes(t)) {
        card.style.display = "block";

        var s = "span[data-tag*='" + t + "']";
        document.querySelectorAll(s).forEach(tag => {
          tag.style.color = "var(--h1-color)";
          tag.style.opacity = 1;
        });
      }
    });

    localStorage.setItem("tag", t);
  }

  // top Tags row only
  const topTag = document.querySelector("nav span[data-tag*='" + t + "']");
  console.log(topTag);
  topTag.style.border = "1px solid hsl(205deg,15%,41%)";
  topTag.style.borderRadius = "var(--border-radius)";
  topTag.style.padding = "3px 0.75rem";
  topTag.style.opacity = 1;

  // const jumbo = document.getElementById("jumbotron").getBoundingClientRect();
  // const jumbo_y = jumbo.y + jumbo.height;
  // const y = (document.getElementById("bookmarks").getBoundingClientRect().y + jumbo_y) / 2;
  // window.scrollTo(0, y);
}

/**
 * FEATURE
 * Create tags of the most used tags at all
 */
function topTags() {
  // identify top x tags
  var arr = Array.from(document.querySelectorAll('span.tag'), span => span.textContent).map(function(e) {
    return e;
  });

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

  const ttp = document.getElementById('top-tags-placeholder');

  // create topTags next to user settings button
  topTags.forEach(tag => {
    let li = document.createElement("li");
    let span = document.createElement("span");
    span.classList.add("tag");
    li.classList.add("top-tag");
    span.dataset.tag = tag;
    span.addEventListener('click', function() {
      toggleTag(tag)
    });
    span.innerText = tag;
    li.appendChild(span);
    ttp.before(li);
  });
}