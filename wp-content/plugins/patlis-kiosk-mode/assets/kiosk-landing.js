/**
 * Patlis Kiosk Landing
 * Landing page only: consent enforcement + periodic refresh + orientation sync.
 */

(function() {
    'use strict';

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
            // Ignore storage errors.
        }

        return false;
    }

    function getCookie(name) {
        const row = document.cookie.split('; ').find(function(item) {
            return item.indexOf(name + '=') === 0;
        });

        return row ? row.substring(name.length + 1) : null;
    }

    function isFullAcceptCookie(raw) {
        if (!raw) {
            return false;
        }

        try {
            const v = JSON.parse(raw);
            return !!(v && v.all === true && v.necessary === true && v.preferences === true && v.statistics === true && v.marketing === true);
        } catch (e) {
            return false;
        }
    }

    function ensureConsentOrReload() {
        const cookieRaw = getCookie('patlis-cookie');

        if (isFullAcceptCookie(cookieRaw)) {
            return true;
        }

        const cookieValue = '{"all":true,"necessary":true,"preferences":true,"statistics":true,"marketing":true}';
        const date = new Date();
        date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
        document.cookie = 'patlis-cookie=' + cookieValue + '; path=/; expires=' + date.toUTCString() + '; SameSite=Lax';
        window.location.reload();

        return false;
    }

    function scheduleLandingAutoRefresh() {
        setTimeout(function() {
            window.location.reload();
        }, 15 * 60 * 1000);  // SOS - 2 minutes for debugging, should be 15 minutes in production.
    }

    /**
     * Auto-close #languages-modal after 15s if the user leaves it open.
     * Bricks Extras uses class "x-modal_open" to mark an open modal and
     * data-x-modal-close on the backdrop to trigger closing.
     */
    function watchLanguagesModal() {
        const MODAL_AUTO_CLOSE_MS = 15 * 1000;
        let autoCloseTimer = null;

        const modal = document.getElementById('languages-modal');
        if (!modal) {
            return;
        }

        function closeModal() {
            const backdrop = modal.querySelector('[data-x-modal-close]');
            if (backdrop) {
                backdrop.click();
            }
        }

        function onMutation() {
            const isOpen = modal.classList.contains('x-modal_open');

            if (isOpen && !autoCloseTimer) {
                autoCloseTimer = setTimeout(function() {
                    autoCloseTimer = null;
                    closeModal();
                }, MODAL_AUTO_CLOSE_MS);
            } else if (!isOpen && autoCloseTimer) {
                clearTimeout(autoCloseTimer);
                autoCloseTimer = null;
            }
        }

        new MutationObserver(onMutation).observe(modal, {
            attributes: true,
            attributeFilter: ['class'],
        });

        // Handle the case where the modal is already open on page load.
        onMutation();
    }

    function init() {
        if (!isKioskLandingPath()) {
            return;
        }

        if (syncOrientationSessionAndReloadIfNeeded()) {
            return;
        }

        if (!ensureConsentOrReload()) {
            return;
        }

        scheduleLandingAutoRefresh();
        watchLanguagesModal();
        console.log('Patlis Kiosk Landing initialized');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
