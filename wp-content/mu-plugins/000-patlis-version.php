<?php
/**
 * Plugin Name: Patlis Version & Feature Flags
 * Description: Feature flags based on PATLIS_VERSION constant (comma-separated).
 */

if (!defined('PATLIS_VERSION')) {
    define('PATLIS_VERSION', '');
}

function patlis_version_has_gastro(): bool {
    return preg_match('~(^|,\s*)gastro(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_general(): bool {
    return preg_match('~(^|,\s*)general(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_hotel(): bool {
    return preg_match('~(^|,\s*)hotel(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}


function patlis_version_has_shop(): bool {
    return preg_match('~(^|,\s*)shop(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_amenities(): bool {
    return preg_match('~(^|,\s*)amenities(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_dining(): bool {
    return preg_match('~(^|,\s*)dining(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_locations(): bool {
    return preg_match('~(^|,\s*)locations(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}
