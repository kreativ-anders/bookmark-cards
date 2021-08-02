$(document).ready(function () {

  $("#suchen").click(function () {
    Suchen($("#search").val());
  });

  // Check for click events on the navbar burger icon
  $(".navbar-burger").click(function () {

    // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
    $(".navbar-burger").toggleClass("is-active");
    $(".navbar-menu").toggleClass("is-active");
  });

  // One-Pager! Prevent form resubmission
  if ( window.history.replaceState ) {
    window.history.replaceState( null, null, window.location.href );
  }

  // Search Title-Link-Tags
  $("#s_title, #s_link, #s_tags").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#bookmarks > div").filter(function() {
      $(this).toggle($(this).attr("data-search").toLowerCase().indexOf(value) > -1)
    });
  });

  // Dyn background (without background-image)
  $("div.card").each(function() {
	  var b = null;
    
    if ($(this).css('background-image') === 'none') {  
      b = 'linear-gradient(to bottom, white 0%,' + randomLightColor() + '  100%)';     
      $(this).css('background', b);
    }  
  });  
});

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
    
    function lazyload () {
      if(lazyloadThrottleTimeout) {
        clearTimeout(lazyloadThrottleTimeout);
      }    

      lazyloadThrottleTimeout = setTimeout(function() {
        var scrollTop = window.pageYOffset;
        lazyloadImages.forEach(function(img) {
            if(img.offsetTop < (window.innerHeight + scrollTop)) {
              img.src = img.dataset.src;
              img.classList.remove('lazy');
            }
        });
        if(lazyloadImages.length == 0) { 
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

function checkURL(url) {
  var s = url.value;
  if (!~s.indexOf("://")){
      s = "https://" + s;
  }
  url.value = s;
  return url
}

function changeData(id, title, link, tags) {
  $("#id").attr("value", id);
  $("#title").attr("value", title);
  $("#link").attr("value", link);
  $("#tags").attr("value", tags);
}

function toggleTag(tag) {

  var t = String(tag);
  $("#bookmarks").find("span.tag").css("border", "none");
  $("#bookmarks").find("span.tag").css("opacity", "0.2");

  if (localStorage.getItem("tag") == t) {
    $("#bookmarks").children().show();
    localStorage.removeItem("tag");
  }
  else {
    $("#bookmarks").children().hide();

    $("#bookmarks").children().each(function() {

      var s = String($(this).attr("data-tags"));
            
      if (s.includes(t)) {
        $(this).show();
        
        var s = "span:contains('" + t + "')";
        $(s).css("border", "1px solid black");
        $(s).css("opacity", "0.5");
      }
    });
  
    localStorage.setItem("tag", t);
  }
}

function randomLightColor() {
  var colors = ['#ADD8E6', '#F08080', '#E0FFFF', '#FAFAD2', '#D3D3D3', '#D3D3D3', '#90EE90', '#FFB6C1', '#FFA07A', '#20B2AA', '#87CEFA', '#778899', '#778899', '#B0C4DE', '#FFFFE0'];

  return colors[Math.floor(Math.random() * colors.length)];
}