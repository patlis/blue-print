(function () {
    var flatpickrLocaleLoading = false;

    function applyFlatpickrLocale() {
        var pageLang = (document.documentElement.lang || 'en').toLowerCase().split('-')[0];
        var localeMap = {
            el: 'gr',
            de: 'de',
            en: 'default'
        };
        var locale = localeMap[pageLang] || pageLang;

        if (locale === 'default') return true;

        if (window.flatpickr && window.flatpickr.l10ns[locale]) {
            document.querySelectorAll('input.flatpickr').forEach(function (input) {
                if (input._flatpickr) {
                    input._flatpickr.set('locale', window.flatpickr.l10ns[locale]);
                }
            });
            return true;
        }

        if (flatpickrLocaleLoading) return false;

        flatpickrLocaleLoading = true;
        var script = document.createElement('script');
        script.src = '/wp-content/themes/bricks/assets/js/libs/flatpickr-l10n/' + locale + '.min.js';
        script.onload = function () {
            flatpickrLocaleLoading = false;
            applyFlatpickrLocale();
        };
        script.onerror = function () {
            flatpickrLocaleLoading = false;
        };
        document.head.appendChild(script);

        return false;
    }

    function applyOfferSelection() {
        var offerId = new URLSearchParams(window.location.search).get('offer_id');
        if (!offerId) return true;

        var select = document.querySelector('select[name="offers_package"]');
        if (!select) return false;

        if (String(select.value) === String(offerId)) return true;

        for (var i = 0; i < select.options.length; i++) {
            if (String(select.options[i].value) !== String(offerId)) continue;

            select.value = offerId;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            return true;
        }

        return false;
    }

    function applyMealPlanVisibility() {
        var select = document.querySelector('select[name="diet_type_id"]');
        if (!select) return false;

        var hasMealPlan = false;

        for (var i = 0; i < select.options.length; i++) {
            if (String(select.options[i].value).trim() !== '') {
                hasMealPlan = true;
                break;
            }
        }

        var group = select.closest('.form-group') || select.parentElement;
        if (group) {
            group.style.display = hasMealPlan ? '' : 'none';
        }

        return true;
    }

    function applyCheckInDate() {
        var input = document.querySelector('input[name="check_in"], input[name="check_in_date"]');
        if (!input || !input._flatpickr) return false;

        var daysBeforeField = document.querySelector('input[name="patlis_acc_booking_days_before"]');
        var daysBefore = daysBeforeField ? parseInt(daysBeforeField.value, 10) || 0 : 0;
        var minimumDate = new Date();
        minimumDate.setHours(0, 0, 0, 0);
        minimumDate.setDate(minimumDate.getDate() + Math.max(0, daysBefore));

        input._flatpickr.set('minDate', minimumDate);

        if (!input._flatpickr.selectedDates.length) {
            input._flatpickr.setDate(minimumDate, true);
        }

        return true;
    }

    function applyOfferVisibility() {
        var select = document.querySelector('select[name="offers_package"]');
        if (!select) return false;

        var hasOffer = false;

        for (var i = 0; i < select.options.length; i++) {
            if (String(select.options[i].value) !== '0') {
                hasOffer = true;
                break;
            }
        }

        var group = select.closest('.form-group') || select.parentElement;
        if (group) {
            group.style.display = hasOffer ? '' : 'none';
        }

        return true;
    }

    function init() {
        applyFlatpickrLocale();
        applyCheckInDate();
        applyMealPlanVisibility();
        applyOfferVisibility();

        if (applyOfferSelection()) return;

        var attempts = 0;
        var interval = setInterval(function () {
            attempts++;
            applyFlatpickrLocale();
            applyCheckInDate();
            applyMealPlanVisibility();
            applyOfferVisibility();

            if (applyOfferSelection() || attempts >= 120) {
                clearInterval(interval);
            }
        }, 250);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input.flatpickr').forEach(function (input) {
        if (input._flatpickr) {
            input._flatpickr.set('dateFormat', 'd.m.Y');
        }
    });
});
