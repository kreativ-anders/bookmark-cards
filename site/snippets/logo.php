<?php
/**
 * Brand mark "Board": three card tiles (currentColor) + bookmark ribbon (orange).
 * Decorative – the surrounding link carries the accessible name.
 * Master file: assets/images/logo.svg. Keep in sync with offline.html.
 */
?>
<svg class="logo" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="logo-ribbon" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#ffb340"/>
      <stop offset="1" stop-color="#ff7a00"/>
    </linearGradient>
  </defs>
  <rect x="5" y="16" width="25" height="43" rx="7" fill="currentColor"/>
  <rect x="34" y="16" width="25" height="20" rx="7" fill="currentColor" fill-opacity=".45"/>
  <rect x="34" y="40" width="25" height="19" rx="7" fill="currentColor"/>
  <path d="M12 5h11v25l-5.5-4-5.5 4z" fill="url(#logo-ribbon)"/>
</svg>
