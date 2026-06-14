/**
 * Patlis Kiosk Runtime
 * Non-landing kiosk pages: inactivity monitoring + redirect.
 */

var PatlisKiosk = (function() {
    'use strict';

    let settings = {};
    let inactivityTimer = null;
    const ORIENTATION_SESSION_KEY = 'device-orientation';

    function isKioskLandingPath() {
        const path = (window.location.pathname || '').toLowerCase();
        // Matches /kiosk, /kiosk/, /en/kiosk, /en/kiosk/
        return /\/kiosk\/?$/.test(path);
    }

    function getCurrentOrientation() {
        if (window.matchMedia && window.matchMedia('(orientation: portrait)').matches) {
            return 'vertical';
        }

        return 'horizontal';
    }

    function syncOrientationSessionAndReloadIfNeeded() {
        const currentOrientation = getCurrentOrientation();

        // Mirror orientation to a cookie so server-side PHP (Bricks query) can filter slides.
        document.cookie = 'patlis_kiosk_orientation=' + currentOrientation + '; path=/; SameSite=Lax';

        try {
            const savedOrientation = sessionStorage.getItem(ORIENTATION_SESSION_KEY);

            if (savedOrientation !== currentOrientation) {
                sessionStorage.setItem(ORIENTATION_SESSION_KEY, currentOrientation);
                window.location.reload();
                return true;
            }
        } catch (e) {
            // Ignore storage errors (private mode, blocked storage, etc.).
        }

        return false;
    }

    function clearCookiesAndHistory() {
        const allowed = ['patlis_kiosk', 'patlis-cookie'];

        document.cookie.split(';').forEach(function(cookie) {
            const eqPos = cookie.indexOf('=');
            const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();

            if (!allowed.includes(name)) {
                document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;SameSite=Lax';
            }
        });
    }

    function init(config) {
        settings = config || {};

        // Runtime logic must not execute on kiosk landing URL.
        if (isKioskLandingPath()) {
            return;
        }

        if (!settings.inactivityTimeout) {
            settings.inactivityTimeout = 60;
        }

        if (!settings.redirectUrl) {
            settings.redirectUrl = window.location.href;
        }

        if (syncOrientationSessionAndReloadIfNeeded()) {
            return;
        }

        clearCookiesAndHistory();
        setupEventListeners();
        resetInactivityTimer();

        console.log('Patlis Kiosk Runtime initialized');
    }

    function setupEventListeners() {
        window.oncontextmenu = null;
        document.oncontextmenu = null;

        setupImageRedirectTrigger();

        const events = ['click', 'scroll', 'keydown', 'touchstart', 'mousemove'];

        events.forEach(function(event) {
            document.addEventListener(event, handleActivity, true);
        });

        window.addEventListener('focus', handleActivity, true);
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                handleActivity();
            }
        }, true);
    }

    function setupImageRedirectTrigger() {
        document.addEventListener('click', function(event) {
            const trigger = event.target.closest('img.kiosk-target-image, .kiosk-target-image img, [data-kiosk-target-image]');
            if (!trigger) {
                return;
            }

            if (!settings.imageRedirectUrl && !settings.redirectUrl) {
                return;
            }

            event.preventDefault();
            window.location.href = settings.imageRedirectUrl || settings.redirectUrl;
        }, true);
    }

    function handleActivity() {
        resetInactivityTimer();
    }

    function clearInactivityTimer() {
        if (inactivityTimer) {
            clearTimeout(inactivityTimer);
            inactivityTimer = null;
        }
    }

    function resetInactivityTimer() {
        clearInactivityTimer();

        inactivityTimer = setTimeout(function() {
            redirect();
        }, settings.inactivityTimeout * 1000);
    }

    function redirect() {
        const params = new URLSearchParams(window.location.search || '');
        if ((params.get('bricks') || '').toLowerCase() === 'run') {
            return;
        }

        const path = (window.location.pathname || '').toLowerCase();
        if (path.indexOf('/wp-admin') !== -1 || path.indexOf('/wp-login.php') !== -1) {
            return;
        }

        try {
            const targetUrl = new URL(settings.redirectUrl, window.location.origin);
            if (targetUrl.pathname.toLowerCase() === path) {
                return;
            }
        } catch (e) {
            if (path === '/kiosk' || path === '/kiosk/') {
                return;
            }
        }

        window.location.href = settings.redirectUrl;
    }

    return {
        init: init,
        redirect: redirect
    };
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof PatlisKioskSettings !== 'undefined' && !window.PatlisKioskInitialized) {
            window.PatlisKioskInitialized = true;
            PatlisKiosk.init(PatlisKioskSettings);
        }
    });
} else {
    if (typeof PatlisKioskSettings !== 'undefined' && !window.PatlisKioskInitialized) {
        window.PatlisKioskInitialized = true;
        PatlisKiosk.init(PatlisKioskSettings);
    }
}
