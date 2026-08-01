<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// Inline SVG sprite - BotBlocker new admin UI icon set (bbcs-i-* symbols).
return static function (): void {
?>
<svg xmlns="http://www.w3.org/2000/svg" style="display:none">
  <defs>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- SECTION 1 - Standard Navigation, Core & UI Icons              -->
    <!-- ═══════════════════════════════════════════════════════════════ -->

    <symbol id="bbcs-i-shield" viewBox="0 0 24 24">
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
    </symbol>
    <symbol id="bbcs-i-shieldCheck" viewBox="0 0 24 24">
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
        <path d="m9 12 2 2 4-4" />
    </symbol>
    <symbol id="bbcs-i-check" viewBox="0 0 24 24">
      <path d="M20 6 9 17l-5-5" />
    </symbol>
    <symbol id="bbcs-i-bolt" viewBox="0 0 24 24">
      <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" />
    </symbol>
    <symbol id="bbcs-i-list" viewBox="0 0 24 24">
      <path d="M3 5h.01" />
        <path d="M3 12h.01" />
        <path d="M3 19h.01" />
        <path d="M8 5h13" />
        <path d="M8 12h13" />
        <path d="M8 19h13" />
    </symbol>
    <symbol id="bbcs-i-puzzle" viewBox="0 0 24 24">
      <path d="M15.39 4.39a1 1 0 0 0 1.68-.474 2.5 2.5 0 1 1 3.014 3.015 1 1 0 0 0-.474 1.68l1.683 1.682a2.414 2.414 0 0 1 0 3.414L19.61 15.39a1 1 0 0 1-1.68-.474 2.5 2.5 0 1 0-3.014 3.015 1 1 0 0 1 .474 1.68l-1.683 1.682a2.414 2.414 0 0 1-3.414 0L8.61 19.61a1 1 0 0 0-1.68.474 2.5 2.5 0 1 1-3.014-3.015 1 1 0 0 0 .474-1.68l-1.683-1.682a2.414 2.414 0 0 1 0-3.414L4.39 8.61a1 1 0 0 1 1.68.474 2.5 2.5 0 1 0 3.014-3.015 1 1 0 0 1-.474-1.68l1.683-1.682a2.414 2.414 0 0 1 3.414 0z" />
    </symbol>
    <symbol id="bbcs-i-gear" viewBox="0 0 24 24">
      <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915" />
        <circle cx="12" cy="12" r="3" />
    </symbol>
    <symbol id="bbcs-i-search" viewBox="0 0 24 24">
      <path d="m21 21-4.34-4.34" />
        <circle cx="11" cy="11" r="8" />
    </symbol>
    <symbol id="bbcs-i-bell" viewBox="0 0 24 24">
      <path d="M10.268 21a2 2 0 0 0 3.464 0" />
        <path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
    </symbol>
    <symbol id="bbcs-i-globe" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
        <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
        <path d="M2 12h20" />
    </symbol>
    <symbol id="bbcs-i-chevron" viewBox="0 0 24 24">
      <path d="m6 9 6 6 6-6" />
    </symbol>
    <symbol id="bbcs-i-chevrons-down-up" viewBox="0 0 24 24">
      <path d="m7 20 5-5 5 5" />
      <path d="m7 4 5 5 5-5" />
    </symbol>
    <symbol id="bbcs-i-chevrons-up-down" viewBox="0 0 24 24">
      <path d="m7 15 5 5 5-5" />
      <path d="m7 9 5-5 5 5" />
    </symbol>
    <symbol id="bbcs-i-chevronR" viewBox="0 0 24 24">
      <path d="m9 18 6-6-6-6" />
    </symbol>
    <symbol id="bbcs-i-arrowL" viewBox="0 0 24 24">
      <path d="m12 19-7-7 7-7" />
        <path d="M19 12H5" />
    </symbol>
    <symbol id="bbcs-i-arrowR" viewBox="0 0 24 24">
      <path d="M5 12h14" />
        <path d="m12 5 7 7-7 7" />
    </symbol>
    <symbol id="bbcs-i-plus" viewBox="0 0 24 24">
      <path d="M5 12h14" />
        <path d="M12 5v14" />
    </symbol>
    <symbol id="bbcs-i-x" viewBox="0 0 24 24">
      <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
    </symbol>
    <symbol id="bbcs-i-user" viewBox="0 0 24 24">
      <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
        <circle cx="12" cy="7" r="4" />
    </symbol>
    <symbol id="bbcs-i-chart" viewBox="0 0 24 24">
      <path d="M3 3v16a2 2 0 0 0 2 2h16" />
        <path d="M18 17V9" />
        <path d="M13 17V5" />
        <path d="M8 17v-3" />
    </symbol>
    <symbol id="bbcs-i-warning" viewBox="0 0 24 24">
      <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
        <path d="M12 9v4" />
        <path d="M12 17h.01" />
    </symbol>
    <symbol id="bbcs-i-lock" viewBox="0 0 24 24">
      <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </symbol>
    <symbol id="bbcs-i-ban" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
        <path d="M4.929 4.929 19.07 19.071" />
    </symbol>
    <symbol id="bbcs-i-flag" viewBox="0 0 24 24">
      <path d="M4 22V4a1 1 0 0 1 .4-.8A6 6 0 0 1 8 2c3 0 5 2 7.333 2q2 0 3.067-.8A1 1 0 0 1 20 4v10a1 1 0 0 1-.4.8A6 6 0 0 1 16 16c-3 0-5-2-8-2a6 6 0 0 0-4 1.528" />
    </symbol>
    <symbol id="bbcs-i-eye" viewBox="0 0 24 24">
      <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
        <circle cx="12" cy="12" r="3" />
    </symbol>
    <symbol id="bbcs-i-bot" viewBox="0 0 24 24">
      <path d="M12 8V4H8" />
        <rect width="16" height="12" x="4" y="8" rx="2" />
        <path d="M2 14h2" />
        <path d="M20 14h2" />
        <path d="M15 13v2" />
        <path d="M9 13v2" />
    </symbol>
    <symbol id="bbcs-i-telegram" viewBox="0 0 24 24">
      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M15 10l-4 4l6 6l4 -16l-18 7l4 2l2 6l3 -4" />
    </symbol>
    <symbol id="bbcs-i-sparkle" viewBox="0 0 24 24">
      <path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z" />
        <path d="M20 2v4" />
        <path d="M22 4h-4" />
        <circle cx="4" cy="20" r="2" />
    </symbol>
    <symbol id="bbcs-i-clock" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
        <path d="M12 6v6l4 2" />
    </symbol>
    <symbol id="bbcs-i-command" viewBox="0 0 24 24">
      <path d="M15 6v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0-3-3" />
    </symbol>
    <symbol id="bbcs-i-star" viewBox="0 0 24 24">
      <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
    </symbol>
    <symbol id="bbcs-i-home" viewBox="0 0 24 24">
      <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" />
        <path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
    </symbol>
    <symbol id="bbcs-i-grid" viewBox="0 0 24 24">
      <rect width="18" height="18" x="3" y="3" rx="2" />
        <path d="M3 9h18" />
        <path d="M3 15h18" />
        <path d="M9 3v18" />
        <path d="M15 3v18" />
    </symbol>
    <symbol id="bbcs-i-database" viewBox="0 0 24 24">
      <ellipse cx="12" cy="5" rx="9" ry="3" />
        <path d="M3 5V19A9 3 0 0 0 21 19V5" />
        <path d="M3 12A9 3 0 0 0 21 12" />
    </symbol>
    <symbol id="bbcs-i-sliders" viewBox="0 0 24 24">
      <path d="M10 8h4" />
        <path d="M12 21v-9" />
        <path d="M12 8V3" />
        <path d="M17 16h4" />
        <path d="M19 12V3" />
        <path d="M19 21v-5" />
        <path d="M3 14h4" />
        <path d="M5 10V3" />
        <path d="M5 21v-7" />
    </symbol>
    <symbol id="bbcs-i-plug" viewBox="0 0 24 24">
      <path d="M12 22v-5" />
        <path d="M15 8V2" />
        <path d="M17 8a1 1 0 0 1 1 1v4a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1z" />
        <path d="M9 8V2" />
    </symbol>
    <symbol id="bbcs-i-headset" viewBox="0 0 24 24">
      <path d="M3 11h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Zm0 0a9 9 0 1 1 18 0m0 0v5a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3Z" />
        <path d="M21 16v2a4 4 0 0 1-4 4h-5" />
    </symbol>
    <symbol id="bbcs-i-crown" viewBox="0 0 24 24">
      <path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z" />
        <path d="M5 21h14" />
    </symbol>
    <symbol id="bbcs-i-copy" viewBox="0 0 24 24">
      <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
        <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
    </symbol>
    <symbol id="bbcs-i-external" viewBox="0 0 24 24">
      <path d="M15 3h6v6" />
        <path d="M10 14 21 3" />
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
    </symbol>
    <symbol id="bbcs-i-refresh" viewBox="0 0 24 24">
      <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
        <path d="M21 3v5h-5" />
        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
        <path d="M8 16H3v5" />
    </symbol>
    <symbol id="bbcs-i-upload" viewBox="0 0 24 24">
      <path d="M12 3v12" />
        <path d="m17 8-5-5-5 5" />
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
    </symbol>
    <symbol id="bbcs-i-trash" viewBox="0 0 24 24">
      <path d="M10 11v6" />
        <path d="M14 11v6" />
        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
        <path d="M3 6h18" />
        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
    </symbol>
    <symbol id="bbcs-i-key" viewBox="0 0 24 24">
      <path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4" />
        <path d="m21 2-9.6 9.6" />
        <circle cx="7.5" cy="15.5" r="5.5" />
    </symbol>
    <symbol id="bbcs-i-doc" viewBox="0 0 24 24">
      <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" />
        <path d="M14 2v5a1 1 0 0 0 1 1h5" />
        <path d="M10 9H8" />
        <path d="M16 13H8" />
        <path d="M16 17H8" />
    </symbol>
    <symbol id="bbcs-i-dollar" viewBox="0 0 24 24">
      <line x1="12" x2="12" y1="2" y2="22" />
        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
    </symbol>
    <symbol id="bbcs-i-wp" viewBox="0 0 24 24">
      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M9.5 9h3" />
        <path d="M4 9h2.5" />
        <path d="M11 9l3 11l4 -9" />
        <path d="M5.5 9l3.5 11l3 -7" />
        <path d="M18 11c.177 -.528 1 -1.364 1 -2.5c0 -1.78 -.776 -2.5 -1.875 -2.5c-.898 0 -1.125 .812 -1.125 1.429c0 1.83 2 2.058 2 3.571z" />
        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
    </symbol>
    <symbol id="bbcs-i-fly" viewBox="0 0 24 24">
      <path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z" />
        <path d="m21.854 2.147-10.94 10.939" />
    </symbol>
    <symbol id="bbcs-i-palette" viewBox="0 0 24 24">
      <path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z" />
        <circle cx="13.5" cy="6.5" r=".5" fill="currentColor" />
        <circle cx="17.5" cy="10.5" r=".5" fill="currentColor" />
        <circle cx="6.5" cy="12.5" r=".5" fill="currentColor" />
        <circle cx="8.5" cy="7.5" r=".5" fill="currentColor" />
    </symbol>
    <symbol id="bbcs-i-image" viewBox="0 0 24 24">
      <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
        <circle cx="9" cy="9" r="2" />
        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
    </symbol>
    <symbol id="bbcs-i-shapes" viewBox="0 0 24 24">
      <path d="M8.3 10a.7.7 0 0 1-.626-1.079L11.4 3a.7.7 0 0 1 1.198-.043L16.3 8.9a.7.7 0 0 1-.572 1.1Z" />
        <rect x="3" y="14" width="7" height="7" rx="1" />
        <circle cx="17.5" cy="17.5" r="3.5" />
    </symbol>
    <symbol id="bbcs-i-calc" viewBox="0 0 24 24">
      <rect width="16" height="20" x="4" y="2" rx="2" />
        <line x1="8" x2="16" y1="6" y2="6" />
        <line x1="16" x2="16" y1="14" y2="18" />
        <path d="M16 10h.01" />
        <path d="M12 10h.01" />
        <path d="M8 10h.01" />
        <path d="M12 14h.01" />
        <path d="M8 14h.01" />
        <path d="M12 18h.01" />
        <path d="M8 18h.01" />
    </symbol>
    <symbol id="bbcs-i-hand" viewBox="0 0 24 24">
      <path d="M18 11V6a2 2 0 0 0-2-2a2 2 0 0 0-2 2" />
        <path d="M14 10V4a2 2 0 0 0-2-2a2 2 0 0 0-2 2v2" />
        <path d="M10 10.5V6a2 2 0 0 0-2-2a2 2 0 0 0-2 2v8" />
        <path d="M18 8a2 2 0 1 1 4 0v6a8 8 0 0 1-8 8h-2c-2.8 0-4.5-.86-5.99-2.34l-3.6-3.6a2 2 0 0 1 2.83-2.82L7 15" />
    </symbol>
    <symbol id="bbcs-i-server" viewBox="0 0 24 24">
      <rect width="20" height="8" x="2" y="2" rx="2" ry="2" />
        <rect width="20" height="8" x="2" y="14" rx="2" ry="2" />
        <line x1="6" x2="6.01" y1="6" y2="6" />
        <line x1="6" x2="6.01" y1="18" y2="18" />
    </symbol>
    <symbol id="bbcs-i-gauge" viewBox="0 0 24 24">
      <path d="m12 14 4-4" />
        <path d="M3.34 19a10 10 0 1 1 17.32 0" />
    </symbol>
    <symbol id="bbcs-i-captcha" viewBox="0 0 24 24">
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
        <path d="M9.1 9a3 3 0 0 1 5.82 1c0 2-3 3-3 3" />
        <path d="M12 17h.01" />
    </symbol>
    <symbol id="bbcs-i-vpn" viewBox="0 0 24 24">
      <path d="M15.686 15A14.5 14.5 0 0 1 12 22a14.5 14.5 0 0 1 0-20 10 10 0 1 0 9.542 13" />
        <path d="M2 12h8.5" />
        <path d="M20 6V4a2 2 0 1 0-4 0v2" />
        <rect width="8" height="5" x="14" y="6" rx="1" />
    </symbol>
    <symbol id="bbcs-i-tor" viewBox="0 0 24 24">
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
        <path d="M12 22V2" />
    </symbol>
    <symbol id="bbcs-i-bruteforce" viewBox="0 0 24 24">
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
        <path d="M12 8v4" />
        <path d="M12 16h.01" />
    </symbol>
    <symbol id="bbcs-i-login" viewBox="0 0 24 24">
      <path d="m10 17 5-5-5-5" />
        <path d="M15 12H3" />
        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
    </symbol>
    <symbol id="bbcs-i-asn" viewBox="0 0 24 24">
      <rect x="16" y="16" width="6" height="6" rx="1" />
        <rect x="2" y="16" width="6" height="6" rx="1" />
        <rect x="9" y="2" width="6" height="6" rx="1" />
        <path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3" />
        <path d="M12 12V8" />
    </symbol>
    <symbol id="bbcs-i-llm" viewBox="0 0 24 24">
      <path d="M12 18V5" />
        <path d="M15 13a4.17 4.17 0 0 1-3-4 4.17 4.17 0 0 1-3 4" />
        <path d="M17.598 6.5A3 3 0 1 0 12 5a3 3 0 1 0-5.598 1.5" />
        <path d="M17.997 5.125a4 4 0 0 1 2.526 5.77" />
        <path d="M18 18a4 4 0 0 0 2-7.464" />
        <path d="M19.967 17.483A4 4 0 1 1 12 18a4 4 0 1 1-7.967-.517" />
        <path d="M6 18a4 4 0 0 1-2-7.464" />
        <path d="M6.003 5.125a4 4 0 0 0-2.526 5.77" />
    </symbol>
    <symbol id="bbcs-i-hosting" viewBox="0 0 24 24">
      <path d="M10 12h4" />
        <path d="M10 8h4" />
        <path d="M14 21v-3a2 2 0 0 0-4 0v3" />
        <path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2" />
        <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16" />
    </symbol>
    <symbol id="bbcs-i-device" viewBox="0 0 24 24">
      <path d="M18 8V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h8" />
        <path d="M10 19v-3.96 3.15" />
        <path d="M7 19h5" />
        <rect width="6" height="10" x="16" y="12" rx="2" />
    </symbol>
    <symbol id="bbcs-i-proxy" viewBox="0 0 24 24">
      <path d="M8 3 4 7l4 4" />
        <path d="M4 7h16" />
        <path d="m16 21 4-4-4-4" />
        <path d="M20 17H4" />
    </symbol>
    <symbol id="bbcs-i-path" viewBox="0 0 24 24">
      <path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2" />
    </symbol>
    <symbol id="bbcs-i-speed" viewBox="0 0 24 24">
      <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" />
    </symbol>
    <symbol id="bbcs-i-payment" viewBox="0 0 24 24">
      <rect width="20" height="14" x="2" y="5" rx="2" />
        <line x1="2" x2="22" y1="10" y2="10" />
    </symbol>
    <symbol id="bbcs-i-incognito" viewBox="0 0 24 24">
      <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49" />
        <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242" />
        <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143" />
        <path d="m2 2 20 20" />
    </symbol>
    <symbol id="bbcs-i-adblock" viewBox="0 0 24 24">
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
        <path d="m14.5 9.5-5 5" />
        <path d="m9.5 9.5 5 5" />
    </symbol>
    <symbol id="bbcs-i-antidetect" viewBox="0 0 24 24">
      <path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4" />
        <path d="M14 13.12c0 2.38 0 6.38-1 8.88" />
        <path d="M17.29 21.02c.12-.6.43-2.3.5-3.02" />
        <path d="M2 12a10 10 0 0 1 18-6" />
        <path d="M2 16h.01" />
        <path d="M21.8 16c.2-2 .131-5.354 0-6" />
        <path d="M5 19.5C5.5 18 6 15 6 12a6 6 0 0 1 .34-2" />
        <path d="M8.65 22c.21-.66.45-1.32.57-2" />
        <path d="M9 6.8a6 6 0 0 1 9 5.2v2" />
    </symbol>
    <symbol id="bbcs-i-recaptcha" viewBox="0 0 24 24">
      <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
        <path d="M21 3v5h-5" />
        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
        <path d="M8 16H3v5" />
    </symbol>
    <symbol id="bbcs-i-2fa" viewBox="0 0 24 24">
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
        <path d="m9 12 2 2 4-4" />
    </symbol>
    <symbol id="bbcs-i-transient" viewBox="0 0 24 24">
      <ellipse cx="12" cy="5" rx="9" ry="3" />
        <path d="M3 5V19A9 3 0 0 0 21 19V5" />
        <path d="M3 12A9 3 0 0 0 21 12" />
    </symbol>
    <symbol id="bbcs-i-system" viewBox="0 0 24 24">
      <path d="M12 19h8" />
        <path d="m4 17 6-6-6-6" />
    </symbol>
    <symbol id="bbcs-i-mail" viewBox="0 0 24 24">
      <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7" />
        <rect x="2" y="4" width="20" height="16" rx="2" />
    </symbol>
    <symbol id="bbcs-i-sync" viewBox="0 0 24 24">
      <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
        <path d="M21 3v5h-5" />
        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
        <path d="M8 16H3v5" />
    </symbol>
    <symbol id="bbcs-i-malware" viewBox="0 0 24 24">
      <path d="M12 20v-9" />
        <path d="M14 7a4 4 0 0 1 4 4v3a6 6 0 0 1-12 0v-3a4 4 0 0 1 4-4z" />
        <path d="M14.12 3.88 16 2" />
        <path d="M21 21a4 4 0 0 0-3.81-4" />
        <path d="M21 5a4 4 0 0 1-3.55 3.97" />
        <path d="M22 13h-4" />
        <path d="M3 21a4 4 0 0 1 3.81-4" />
        <path d="M3 5a4 4 0 0 0 3.55 3.97" />
        <path d="M6 13H2" />
        <path d="m8 2 1.88 1.88" />
        <path d="M9 7.13V6a3 3 0 1 1 6 0v1.13" />
    </symbol>
    <symbol id="bbcs-i-https" viewBox="0 0 24 24">
      <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </symbol>
    <symbol id="bbcs-i-geo" viewBox="0 0 24 24">
      <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0" />
        <circle cx="12" cy="10" r="3" />
    </symbol>
    <symbol id="bbcs-i-os" viewBox="0 0 24 24">
      <rect width="20" height="14" x="2" y="3" rx="2" />
        <line x1="8" x2="16" y1="21" y2="21" />
        <line x1="12" x2="12" y1="17" y2="21" />
    </symbol>
    <symbol id="bbcs-i-browser" viewBox="0 0 24 24">
      <path d="M10.88 21.94 15.46 14" />
        <path d="M21.17 8H12" />
        <path d="M3.95 6.06 8.54 14" />
        <circle cx="12" cy="12" r="10" />
        <circle cx="12" cy="12" r="4" />
    </symbol>
    <symbol id="bbcs-i-traffic" viewBox="0 0 24 24">
      <path d="m21 16-4 4-4-4" />
        <path d="M17 20V4" />
        <path d="m3 8 4-4 4 4" />
        <path d="M7 4v16" />
    </symbol>
    <symbol id="bbcs-i-language" viewBox="0 0 24 24">
      <path d="m5 8 6 6" />
        <path d="m4 14 6-6 2-3" />
        <path d="M2 5h12" />
        <path d="M7 2h1" />
        <path d="m22 22-5-10-5 10" />
        <path d="M14 18h6" />
    </symbol>
    <symbol id="bbcs-i-ptr" viewBox="0 0 24 24">
      <path d="M8 3 4 7l4 4" />
        <path d="M4 7h16" />
        <path d="m16 21 4-4-4-4" />
        <path d="M20 17H4" />
    </symbol>
    <symbol id="bbcs-i-referer" viewBox="0 0 24 24">
      <path d="M9 17H7A5 5 0 0 1 7 7h2" />
        <path d="M15 7h2a5 5 0 1 1 0 10h-2" />
        <line x1="8" x2="16" y1="12" y2="12" />
    </symbol>
    <symbol id="bbcs-i-ip" viewBox="0 0 24 24">
      <path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4" />
        <path d="M14 13.12c0 2.38 0 6.38-1 8.88" />
        <path d="M17.29 21.02c.12-.6.43-2.3.5-3.02" />
        <path d="M2 12a10 10 0 0 1 18-6" />
        <path d="M2 16h.01" />
        <path d="M21.8 16c.2-2 .131-5.354 0-6" />
        <path d="M5 19.5C5.5 18 6 15 6 12a6 6 0 0 1 .34-2" />
        <path d="M8.65 22c.21-.66.45-1.32.57-2" />
        <path d="M9 6.8a6 6 0 0 1 9 5.2v2" />
    </symbol>
    <symbol id="bbcs-i-iframe" viewBox="0 0 24 24">
      <rect width="18" height="18" x="3" y="3" rx="2" />
        <path d="M3 9h18" />
        <path d="M9 21V9" />
    </symbol>
    <symbol id="bbcs-i-utm" viewBox="0 0 24 24">
      <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" />
        <circle cx="7.5" cy="7.5" r=".5" fill="currentColor" />
    </symbol>
    <symbol id="bbcs-i-robots" viewBox="0 0 24 24">
      <path d="M12 8V4H8" />
        <rect width="16" height="12" x="4" y="8" rx="2" />
        <path d="M2 14h2" />
        <path d="M20 14h2" />
        <path d="M15 13v2" />
        <path d="M9 13v2" />
    </symbol>
    <symbol id="bbcs-i-cookie2" viewBox="0 0 24 24">
      <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5" />
        <path d="M8.5 8.5v.01" />
        <path d="M16 15.5v.01" />
        <path d="M12 12v.01" />
        <path d="M11 17v.01" />
        <path d="M7 14v.01" />
    </symbol>
    <symbol id="bbcs-i-expires" viewBox="0 0 24 24">
      <path d="M16 14v2.2l1.6 1" />
        <path d="M16 2v4" />
        <path d="M21 7.5V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h3.5" />
        <path d="M3 10h5" />
        <path d="M8 2v4" />
        <circle cx="16" cy="16" r="6" />
    </symbol>
    <symbol id="bbcs-i-allow" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
        <path d="m9 12 2 2 4-4" />
    </symbol>
    <symbol id="bbcs-i-block" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
        <path d="m15 9-6 6" />
        <path d="m9 9 6 6" />
    </symbol>
    <symbol id="bbcs-i-grey" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
        <path d="M8 12h8" />
    </symbol>
    <symbol id="bbcs-i-alert" viewBox="0 0 24 24">
      <path d="M10.268 21a2 2 0 0 0 3.464 0" />
        <path d="M22 8c0-2.3-.8-4.3-2-6" />
        <path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
        <path d="M4 2C2.8 3.7 2 5.7 2 8" />
    </symbol>
    <symbol id="bbcs-i-support" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
        <path d="M12 17h.01" />
    </symbol>
    <symbol id="bbcs-i-legal" viewBox="0 0 24 24">
      <path d="m14 13-8.381 8.38a1 1 0 0 1-3.001-3l8.384-8.381" />
        <path d="m16 16 6-6" />
        <path d="m21.5 10.5-8-8" />
        <path d="m8 8 6-6" />
        <path d="m8.5 7.5 8 8" />
    </symbol>
    <symbol id="bbcs-i-code" viewBox="0 0 24 24">
      <path d="m18 16 4-4-4-4" />
        <path d="m6 8-4 4 4 4" />
        <path d="m14.5 4-5 16" />
    </symbol>
    <symbol id="bbcs-i-ddos" viewBox="0 0 24 24">
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
        <path d="M12 8v4" />
        <path d="M12 16h.01" />
    </symbol>
    <symbol id="bbcs-i-csp" viewBox="0 0 24 24">
      <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
        <path d="M8 12h.01" />
        <path d="M12 12h.01" />
        <path d="M16 12h.01" />
    </symbol>
    <symbol id="bbcs-i-changelog" viewBox="0 0 24 24">
      <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
        <path d="M3 3v5h5" />
        <path d="M12 7v5l4 2" />
    </symbol>
    <symbol id="bbcs-i-about" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" />
        <path d="M12 16v-4" />
        <path d="M12 8h.01" />
    </symbol>
    <symbol id="bbcs-i-export" viewBox="0 0 24 24">
      <path d="m16 17 5-5-5-5" />
        <path d="M21 12H9" />
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
    </symbol>
    <symbol id="bbcs-i-import" viewBox="0 0 24 24">
      <path d="m10 17 5-5-5-5" />
        <path d="M15 12H3" />
        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
    </symbol>
    <symbol id="bbcs-i-salt" viewBox="0 0 24 24">
      <path d="m15.5 7.5 2.3 2.3a1 1 0 0 0 1.4 0l2.1-2.1a1 1 0 0 0 0-1.4L19 4" />
        <path d="m21 2-9.6 9.6" />
        <circle cx="7.5" cy="15.5" r="5.5" />
    </symbol>
    <symbol id="bbcs-i-secret" viewBox="0 0 24 24">
      <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49" />
        <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242" />
        <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143" />
        <path d="m2 2 20 20" />
    </symbol>
    <symbol id="bbcs-i-wordpress-core" viewBox="0 0 24 24">
      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <path d="M9.5 9h3" />
        <path d="M4 9h2.5" />
        <path d="M11 9l3 11l4 -9" />
        <path d="M5.5 9l3.5 11l3 -7" />
        <path d="M18 11c.177 -.528 1 -1.364 1 -2.5c0 -1.78 -.776 -2.5 -1.875 -2.5c-.898 0 -1.125 .812 -1.125 1.429c0 1.83 2 2.058 2 3.571z" />
        <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
    </symbol>
    <symbol id="bbcs-i-debug" viewBox="0 0 24 24">
      <path d="M12 20v-9" />
        <path d="M14 7a4 4 0 0 1 4 4v3a6 6 0 0 1-12 0v-3a4 4 0 0 1 4-4z" />
        <path d="M14.12 3.88 16 2" />
        <path d="M21 21a4 4 0 0 0-3.81-4" />
        <path d="M21 5a4 4 0 0 1-3.55 3.97" />
        <path d="M22 13h-4" />
        <path d="M3 21a4 4 0 0 1 3.81-4" />
        <path d="M3 5a4 4 0 0 0 3.55 3.97" />
        <path d="M6 13H2" />
        <path d="m8 2 1.88 1.88" />
        <path d="M9 7.13V6a3 3 0 1 1 6 0v1.13" />
    </symbol>
    <symbol id="bbcs-i-fix" viewBox="0 0 24 24">
      <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.106-3.105c.32-.322.863-.22.983.218a6 6 0 0 1-8.259 7.057l-7.91 7.91a1 1 0 0 1-2.999-3l7.91-7.91a6 6 0 0 1 7.057-8.259c.438.12.54.662.219.984z" />
    </symbol>
    <symbol id="bbcs-i-reinstall" viewBox="0 0 24 24">
      <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
        <path d="M21 3v5h-5" />
        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
        <path d="M8 16H3v5" />
    </symbol>
    <symbol id="bbcs-i-health" viewBox="0 0 24 24">
      <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />
    </symbol>
    <symbol id="bbcs-i-heart" viewBox="0 0 24 24">
      <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />
    </symbol>
    <symbol id="bbcs-i-broom" viewBox="0 0 24 24">
      <path d="m11 10 3 3" />
        <path d="M6.5 21A3.5 3.5 0 1 0 3 17.5a2.62 2.62 0 0 1-.708 1.792A1 1 0 0 0 3 21z" />
        <path d="M9.969 17.031 21.378 5.624a1 1 0 0 0-3.002-3.002L6.967 14.031" />
    </symbol>
    <symbol id="bbcs-i-cloud-download" viewBox="0 0 24 24">
      <path d="M12 13v8l-4-4" />
        <path d="m12 21 4-4" />
        <path d="M4.393 15.269A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.436 8.284" />
    </symbol>
    <symbol id="bbcs-i-link" viewBox="0 0 24 24">
      <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
    </symbol>
    <symbol id="bbcs-i-memory" viewBox="0 0 24 24">
      <path d="M12 20v2" />
        <path d="M12 2v2" />
        <path d="M17 20v2" />
        <path d="M17 2v2" />
        <path d="M2 12h2" />
        <path d="M2 17h2" />
        <path d="M2 7h2" />
        <path d="M20 12h2" />
        <path d="M20 17h2" />
        <path d="M20 7h2" />
        <path d="M7 20v2" />
        <path d="M7 2v2" />
        <rect x="4" y="4" width="16" height="16" rx="2" />
        <rect x="8" y="8" width="8" height="8" rx="1" />
    </symbol>
    <symbol id="bbcs-i-scan" viewBox="0 0 24 24">
      <path d="M3 7V5a2 2 0 0 1 2-2h2" />
        <path d="M17 3h2a2 2 0 0 1 2 2v2" />
        <path d="M21 17v2a2 2 0 0 1-2 2h-2" />
        <path d="M7 21H5a2 2 0 0 1-2-2v-2" />
    </symbol>
    <symbol id="bbcs-i-copyright" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="9"/>
      <path d="M13 8.5c-.5-.3-1.1-.5-1.8-.5-1.8 0-3.2 1.5-3.2 3.5s1.4 3.5 3.2 3.5c.7 0 1.3-.2 1.8-.5M13 8.5v1.5M13 14v1.5"/>
    </symbol>

  </defs>
</svg>
<?php
};
