document.addEventListener("DOMContentLoaded", function () {
    const calendarEl = document.querySelector("#reservation_div");
    if (!calendarEl || typeof flatpickr === "undefined") {
        return;
    }

    const form = calendarEl.closest("form");
    if (!form) {
        return;
    }

    const input = form.querySelector('input[name="reservation_date"]');
    if (!input) {
        return;
    }

    const minHours = parseInt(form.dataset.minHours || "0", 10);
    const baseMinTime = form.dataset.minTime || "11:00";
    const baseMaxTime = form.dataset.maxTime || "21:00";
    const minuteStep = 15;

    function getFlatpickrLocale() {
        const htmlLang = (document.documentElement.lang || "en").toLowerCase();
        let shortLang = htmlLang.split(/[-_]/)[0];
        if (shortLang === "el") shortLang = "gr";

        // Επιστρέφουμε το OBJECT από το l10ns, όχι το string "de"
        if (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns[shortLang]) {
            return flatpickr.l10ns[shortLang];
        }

        return "en"; 
    }


    function cloneDate(date) {
        return new Date(date.getTime());
    }

    function startOfDay(date) {
        const d = cloneDate(date);
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function isSameDay(a, b) {
        return (
            a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate()
        );
    }

    function parseTimeString(timeStr) {
        const parts = (timeStr || "00:00").split(":");
        return {
            hours: parseInt(parts[0] || "0", 10),
            minutes: parseInt(parts[1] || "0", 10)
        };
    }

    function timeStringToMinutes(timeStr) {
        const t = parseTimeString(timeStr);
        return t.hours * 60 + t.minutes;
    }

    function minutesToTimeString(totalMinutes) {
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        return String(hours).padStart(2, "0") + ":" + String(minutes).padStart(2, "0");
    }

    function setDateTimeFromTimeString(date, timeStr) {
        const d = cloneDate(date);
        const t = parseTimeString(timeStr);
        d.setHours(t.hours, t.minutes, 0, 0);
        return d;
    }

    function roundUpToStep(date, stepMinutes) {
        const d = cloneDate(date);
        d.setSeconds(0, 0);

        const minutes = d.getMinutes();
        const remainder = minutes % stepMinutes;

        if (remainder !== 0) {
            d.setMinutes(minutes + (stepMinutes - remainder));
        }

        return d;
    }

    function getFirstAvailableDateTime() {
        const now = new Date();

        let earliest = new Date(now.getTime() + minHours * 60 * 60 * 1000);
        earliest = roundUpToStep(earliest, minuteStep);

        const minToday = setDateTimeFromTimeString(earliest, baseMinTime);
        const maxToday = setDateTimeFromTimeString(earliest, baseMaxTime);

        if (earliest < minToday) {
            earliest = minToday;
        }

        if (earliest > maxToday) {
            const nextDay = cloneDate(earliest);
            nextDay.setDate(nextDay.getDate() + 1);
            earliest = setDateTimeFromTimeString(nextDay, baseMinTime);
        }

        earliest = roundUpToStep(earliest, minuteStep);

        return earliest;
    }

    const firstAvailable = getFirstAvailableDateTime();
    const firstAvailableDay = startOfDay(firstAvailable);
    const firstAvailableTime =
        String(firstAvailable.getHours()).padStart(2, "0") + ":" +
        String(firstAvailable.getMinutes()).padStart(2, "0");

    function getMinTimeForSelectedDate(selectedDate) {
        if (!selectedDate) {
            return baseMinTime;
        }

        if (isSameDay(selectedDate, firstAvailableDay)) {
            const firstMinutes = timeStringToMinutes(firstAvailableTime);
            const baseMinMinutes = timeStringToMinutes(baseMinTime);
            return minutesToTimeString(Math.max(firstMinutes, baseMinMinutes));
        }

        return baseMinTime;
    }

    function clampSelectedDate(instance) {
        const selected = instance.selectedDates[0];
        if (!selected) {
            return;
        }

        const currentMinTime = getMinTimeForSelectedDate(selected);
        const minMinutes = timeStringToMinutes(currentMinTime);
        const maxMinutes = timeStringToMinutes(baseMaxTime);
        const selectedMinutes = selected.getHours() * 60 + selected.getMinutes();

        if (selectedMinutes < minMinutes) {
            const corrected = setDateTimeFromTimeString(selected, currentMinTime);
            instance.setDate(corrected, true);
            return;
        }

        if (selectedMinutes > maxMinutes) {
            const corrected = setDateTimeFromTimeString(selected, baseMaxTime);
            instance.setDate(corrected, true);
        }
    }

    function updateInput(instance) {
        const selected = instance.selectedDates[0];
        if (!selected) {
            input.value = "";
            return;
        }

        input.value = instance.formatDate(selected, "Y-m-d H:i");
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function applyRules(instance) {
        const selected = instance.selectedDates[0] || firstAvailable;
        const currentMinTime = getMinTimeForSelectedDate(selected);

        instance.set("minTime", currentMinTime);
        instance.set("maxTime", baseMaxTime);

        clampSelectedDate(instance);
        updateInput(instance);
    }

    flatpickr(calendarEl, {
        locale: getFlatpickrLocale(),
        inline: true,
        enableTime: true,
        time_24hr: true,
        minuteIncrement: minuteStep,
        dateFormat: "Y-m-d H:i",
        minDate: firstAvailableDay,
        defaultDate: firstAvailable,
        minTime: getMinTimeForSelectedDate(firstAvailable),
        maxTime: baseMaxTime,

        onReady: function (selectedDates, dateStr, instance) {
            applyRules(instance);
        },

        onChange: function (selectedDates, dateStr, instance) {
            applyRules(instance);
        }
    });
});