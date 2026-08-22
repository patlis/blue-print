(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const referrer = document.referrer;
    const storageKey = 'traffic_info';
    const maxAgeDays = 30;
    const maxAgeMs = maxAgeDays * 24 * 60 * 60 * 1000;
    const now = Date.now();

    const paidMediums = ['cpc', 'ppc', 'paid', 'ads'];
    const socialDomains = ['facebook.', 'instagram.', 'tiktok.', 'linkedin.', 'twitter.', 'pinterest.'];

    // ── Consent check ─────────────────────────────────────────────────────────
    function hasTrackingConsent() {
        const cookieStr = document.cookie.split('; ').find(r => r.startsWith('patlis-cookie='));
        if (!cookieStr) return false; // banner not yet answered
        try {
            const val = JSON.parse(decodeURIComponent(cookieStr.split('=').slice(1).join('=')));
            return !!(val.statistics || val.marketing || val.all);
        } catch (e) {
            return false;
        }
    }

    const gclid  = urlParams.get('gclid')  || '';
    const gbraid = urlParams.get('gbraid') || '';
    const wbraid = urlParams.get('wbraid') || '';
    const msclkid = urlParams.get('msclkid') || '';
    const fbclid = urlParams.get('fbclid') || '';

    let currentSource = {
        utm_source: '',
        utm_medium: '',
        utm_campaign: '',
        referrer: referrer || '',
        landing_page: window.location.pathname,
        created_at: now,
        gclid:   gclid,
        gbraid:  gbraid,
        wbraid:  wbraid,
        msclkid: msclkid,
        fbclid:  fbclid,
    };

    if (urlParams.has('utm_source')) {
        currentSource.utm_source = urlParams.get('utm_source');
        currentSource.utm_medium = urlParams.get('utm_medium') || '';
        currentSource.utm_campaign = urlParams.get('utm_campaign') || '';
    } else if (referrer) {
        const refHost = new URL(referrer).hostname;
        const currentHost = window.location.hostname;

        if (refHost === currentHost) {
            currentSource.utm_source = '(direct)';
            currentSource.utm_medium = '(none)';
            currentSource.referrer = '';
        } else if (refHost.includes('google.')) {
            currentSource.utm_source = 'google';
            currentSource.utm_medium = 'organic';
        } else if (refHost.includes('bing.')) {
            currentSource.utm_source = 'bing';
            currentSource.utm_medium = 'organic';
        } else if (socialDomains.some(domain => refHost.includes(domain))) {
            currentSource.utm_source = refHost;
            currentSource.utm_medium = 'social';
        } else {
            currentSource.utm_source = referrer; // full URL
            currentSource.utm_medium = 'referral';
        }
    } else {
        currentSource.utm_source = '(direct)';
        currentSource.utm_medium = '(none)';
        currentSource.referrer = '';
    }

    // ── Write to localStorage only with consent ────────────────────────────────
    if (hasTrackingConsent()) {
        // visitor_id: generate once, never reset
        let visitorId = localStorage.getItem('patlis_vid');
        if (!visitorId) {
            visitorId = crypto.randomUUID();
            localStorage.setItem('patlis_vid', visitorId);
        }
        const stored = localStorage.getItem(storageKey);

        if (!stored) {
            localStorage.setItem(storageKey, JSON.stringify(currentSource));
        } else {
            try {
                const storedData = JSON.parse(stored);
                const ageMs = now - storedData.created_at;

                const isExpired    = !storedData.created_at || ageMs > maxAgeMs;
                const isNewAdsClick = !!(currentSource.gclid || currentSource.gbraid || currentSource.wbraid || currentSource.msclkid || currentSource.fbclid);
                const isPaidUtm     = paidMediums.includes((currentSource.utm_medium || '').toLowerCase());

                if (isExpired || isNewAdsClick || isPaidUtm) {
                    // Keep old click IDs if no new ones came in
                    if (!currentSource.gclid   && storedData.gclid)   currentSource.gclid   = storedData.gclid;
                    if (!currentSource.gbraid  && storedData.gbraid)  currentSource.gbraid  = storedData.gbraid;
                    if (!currentSource.wbraid  && storedData.wbraid)  currentSource.wbraid  = storedData.wbraid;
                    if (!currentSource.msclkid && storedData.msclkid) currentSource.msclkid = storedData.msclkid;
                    if (!currentSource.fbclid  && storedData.fbclid)  currentSource.fbclid  = storedData.fbclid;
                    localStorage.setItem(storageKey, JSON.stringify(currentSource));
                }
                // else keep existing
            } catch (e) {
                localStorage.setItem(storageKey, JSON.stringify(currentSource));
            }
        }
    }

    // ── device_type / source_url / language: no consent needed ──────────────
    const ua = navigator.userAgent;
    const deviceType = /Mobi|Android|iPhone|iPod/.test(ua) && !/iPad/.test(ua)
        ? 'mobile'
        : /iPad|Tablet|PlayBook|Silk/.test(ua) || (/Android/.test(ua) && !/Mobile/.test(ua))
            ? 'tablet'
            : 'desktop';
    document.querySelectorAll('[name="device_type"]').forEach(f => {
        f.value = deviceType;
    });

    document.querySelectorAll('[name="source_url"]').forEach(f => {
        f.value = window.location.pathname + window.location.search;
    });

    const pllCookie = document.cookie.split('; ').find(r => r.startsWith('pll_language='));
    const language  = pllCookie ? pllCookie.split('=')[1] : '';
    document.querySelectorAll('[name="language"]').forEach(f => {
        f.value = language;
    });

    // ── Populate form fields (always — reads existing consented data) ──────────
    const trafficRaw = localStorage.getItem(storageKey);
    if (!trafficRaw) return;

    let trafficData;
    try {
        trafficData = JSON.parse(trafficRaw);
    } catch (e) {
        localStorage.removeItem(storageKey);
        return;
    }

    if (!trafficData.created_at || now - trafficData.created_at > maxAgeMs) {
        localStorage.removeItem(storageKey);
        return;
    }

    // Attach visitor_id from separate key (persists across traffic_info resets)
    trafficData.visitor_id = localStorage.getItem('patlis_vid') || '';

    const fields = document.querySelectorAll('[name="traffic_info"]');
    if (!fields.length) return;
    fields.forEach(field => {
        field.value = JSON.stringify(trafficData);
    });

})();