/**
 * rxwithcode Lightweight Analytics Tracker
 * Runs only in real browsers with JS. UptimeRobot and bots never run this.
 */
(function() {
  try {
    var pageName = window.RX_PAGE_NAME || (location.pathname.indexOf('countNumber') !== -1 ? 'number-counter' : 'homepage');
    
    // Avoid double counting if user reloads within 3 seconds
    var lastTrackKey = 'rx_track_' + pageName;
    var lastTime = sessionStorage.getItem(lastTrackKey);
    var now = Date.now();
    if (lastTime && (now - parseInt(lastTime, 10)) < 4000) {
      return;
    }
    sessionStorage.setItem(lastTrackKey, now.toString());

    // ── Cookie-based Visitor ID ─────────────────────────────────────────────
    // Generates a persistent UUID stored in a 1-year cookie.
    // Returning visitors will have the same ID on subsequent visits.
    function getCookie(name) {
      var match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]*)'));
      return match ? decodeURIComponent(match[1]) : null;
    }
    function setCookie(name, value, days) {
      var expires = new Date(Date.now() + days * 864e5).toUTCString();
      document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/; SameSite=Lax';
    }
    function generateUUID() {
      if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID();
      }
      // Fallback for older browsers
      return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0;
        return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
      });
    }

    var COOKIE_NAME = 'rx_vid';
    var visitorId = getCookie(COOKIE_NAME);
    var isNewVisitor = !visitorId;
    if (!visitorId) {
      visitorId = generateUUID();
      setCookie(COOKIE_NAME, visitorId, 365); // 1 year
    }
    // ──────────────────────────────────────────────────────────────────────

    // Device Category Detection
    var ua = navigator.userAgent || '';
    var isMobile = /Android|iPhone|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i.test(ua);
    var isTablet = /(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk)/i.test(ua);
    var device = isTablet ? 'Tablet' : (isMobile ? 'Mobile' : 'Desktop');

    // Referrer
    var referrer = 'Direct';
    if (document.referrer) {
      try {
        var refUrl = new URL(document.referrer);
        if (refUrl.hostname !== location.hostname) {
          referrer = refUrl.hostname;
        }
      } catch (e) {
        referrer = document.referrer.substring(0, 80);
      }
    }

    // Resolve Tracker URL dynamically
    var trackerUrl = window.RX_TRACKER_URL;
    if (!trackerUrl) {
      if (location.pathname.indexOf('countNumber') !== -1) {
        trackerUrl = 'tracker.php';
      } else {
        trackerUrl = 'countNumber/tracker.php';
      }
    }

    var payload = JSON.stringify({
      page: pageName,
      device: device,
      referrer: referrer,
      visitor_id: visitorId,
      is_new: isNewVisitor
    });

    if (navigator.sendBeacon) {
      var blob = new Blob([payload], { type: 'application/json' });
      navigator.sendBeacon(trackerUrl, blob);
    } else {
      fetch(trackerUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: payload,
        keepalive: true
      }).catch(function() {});
    }
  } catch (err) {
    // Fail silently so user experience is never impacted
  }
})();
