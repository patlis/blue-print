/**
 * Patlis Kiosk Mode
 * Inactivity redirect only
 */

var PatlisKiosk = (function() {
    'use strict';

    let settings = {};
    let inactivityTimer = null;

    // --- GDPR: Clear cookies and history except allowed cookies ---
    function clearCookiesAndHistory() {
        // Allowed cookies
        const allowed = ['patlis_kiosk', 'patlis-cookie'];
        // Delete all cookies except allowed
        document.cookie.split(';').forEach(function(cookie) {
            const eqPos = cookie.indexOf('=');
            const name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
            if (!allowed.includes(name)) {
                document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;SameSite=Lax';
            }
        });
    }

    /**
     * Initialize kiosk mode
     */
    function init(config) {
        settings = config || {};
        
        if (!settings.inactivityTimeout) {
            settings.inactivityTimeout = 60;
        }
        if (!settings.redirectUrl) {
            settings.redirectUrl = window.location.href;
        }

            // GDPR: Clear cookies and history on kiosk page load
            clearCookiesAndHistory();

        setupEventListeners();
        startInactivityMonitoring();

        console.log('Patlis Kiosk Mode initialized');
    }

    /**
     * Setup event listeners for activity detection
     */
    function setupEventListeners() {
        // Defensive reset in case an old script version set contextmenu handlers.
        window.oncontextmenu = null;
        document.oncontextmenu = null;

        setupImageRedirectTrigger();

        const events = ['click', 'scroll', 'keydown', 'touchstart', 'mousemove'];
        
        events.forEach(function(event) {
            document.addEventListener(event, handleActivity, true);
        });

        window.addEventListener('focus', handleWindowFocus, true);
        window.addEventListener('blur', handleWindowBlur, true);
        document.addEventListener('visibilitychange', handleVisibilityChange, true);
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

    /**
     * Handle user activity
     */
    function handleActivity(event) {
        resetInactivityTimer();
    }

    function handleWindowFocus() {
        resetInactivityTimer();
    }

    function handleWindowBlur() {
        // Do not clear the timer on blur. We want it to redirect even if they clicked outside.
    }

    function handleVisibilityChange() {
        if (document.visibilityState === 'visible') {
            resetInactivityTimer();
        }
    }

    /**
     * Start monitoring inactivity
     */
    function startInactivityMonitoring() {
        resetInactivityTimer();
    }

    function clearInactivityTimer() {
        if (inactivityTimer) {
            clearTimeout(inactivityTimer);
            inactivityTimer = null;
        }
    }

    /**
     * Reset inactivity timer
     */
    function resetInactivityTimer() {
        clearInactivityTimer();

        inactivityTimer = setTimeout(function() {
            onInactivity();
        }, settings.inactivityTimeout * 1000);
    }

    /**
     * Handle inactivity event
     */
    function onInactivity() {
        redirect();
    }

    /**
     * Redirect to home/configured URL
     */
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
            // Fallback to old behavior if target URL parsing fails.
            if (path === '/kiosk' || path === '/kiosk/') {
                return;
            }
        }

        window.location.href = settings.redirectUrl;
    }

    // Public API
    return {
        init: init,
        redirect: redirect
    };
})();

// Auto-initialize when DOM is ready
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