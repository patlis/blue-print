<?php
if (!defined('ABSPATH')) {
    exit;
}

function patlis_normalize_translation_key(string $key): string
{
    $key = trim($key);
    $key = strtolower($key);
    $key = str_replace([' ', '-'], '_', $key);
    $key = preg_replace('/[^a-z0-9_]/', '', $key);
    $key = preg_replace('/_+/', '_', $key);

    return trim((string) $key, '_');
}

function patlis_is_valid_translation_key(string $key): bool
{
    if ($key === '') {
        return false;
    }

    if (strlen($key) < 4 || strlen($key) > 100) {
        return false;
    }

    if (!preg_match('/^[a-z0-9_]+$/', $key)) {
        return false;
    }

    return strpos($key, 'patlis_') === 0;
}

function patlis_get_default_language(): string
{
    if (function_exists('pll_default_language')) {
        $lang = pll_default_language();

        if (is_string($lang) && $lang !== '') {
            return $lang;
        }
    }

    return '';
}

function patlis_get_current_language(): string
{
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();

        if (is_string($lang) && $lang !== '') {
            return $lang;
        }
    }

    return '';
}

function patlis_get_manual_translation_keys(): array
{
    $keys = [];

    if (function_exists('patlis_core_get_translation_keys_from_db')) {
        $keys = patlis_core_get_translation_keys_from_db();
    }

    $keys = array_map('patlis_normalize_translation_key', $keys);
    $keys = array_filter($keys, 'patlis_is_valid_translation_key');
    $keys = array_values(array_unique($keys));
    sort($keys);

    return $keys;
}

function patlis_get_translations(): array
{
    static $translations_cache = null;

    if (is_array($translations_cache)) {
        return $translations_cache;
    }

    if (function_exists('patlis_core_get_translations_from_db')) {
        $translations_cache = patlis_core_get_translations_from_db();
        return is_array($translations_cache) ? $translations_cache : [];
    }

    return [];
}

if (!function_exists('patlis_transl')) {
    function patlis_transl(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            return '';
        }

        $translations = patlis_get_translations();

        if (isset($translations[$key]) && is_array($translations[$key])) {
            $current_lang = patlis_get_current_language();

            if (
                $current_lang !== ''
                && isset($translations[$key][$current_lang])
                && is_string($translations[$key][$current_lang])
                && $translations[$key][$current_lang] !== ''
            ) {
                return $translations[$key][$current_lang];
            }

            $default_lang = patlis_get_default_language();

            if (
                $default_lang !== ''
                && isset($translations[$key][$default_lang])
                && is_string($translations[$key][$default_lang])
                && $translations[$key][$default_lang] !== ''
            ) {
                return $translations[$key][$default_lang];
            }
        }

        return $key;
    }
}
