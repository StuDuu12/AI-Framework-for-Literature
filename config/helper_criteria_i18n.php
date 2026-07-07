<?php

/**
 * Resolve bilingual criteria labels stored as JSON {"vi":"...","en":"..."}
 * or plain text / "VI | EN" fallback.
 */
function criteriaResolveLang(?string $lang = null): string
{
    if ($lang && in_array($lang, ['vi', 'en'], true)) {
        return $lang;
    }
    if (!empty($_COOKIE['app_language']) && in_array($_COOKIE['app_language'], ['vi', 'en'], true)) {
        return $_COOKIE['app_language'];
    }
    return 'vi';
}

function criteriaLabel(?string $raw, ?string $lang = null): string
{
    if ($raw === null || $raw === '') {
        return '';
    }

    $raw = trim($raw);
    $lang = criteriaResolveLang($lang);

    if ($raw[0] === '{') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            if (!empty($decoded[$lang])) {
                return (string) $decoded[$lang];
            }
            if (!empty($decoded['vi'])) {
                return (string) $decoded['vi'];
            }
            if (!empty($decoded['en'])) {
                return (string) $decoded['en'];
            }
        }
    }

    if (str_contains($raw, ' | ')) {
        $parts = array_map('trim', explode(' | ', $raw, 2));
        return $lang === 'en' && isset($parts[1]) && $parts[1] !== '' ? $parts[1] : $parts[0];
    }

    return $raw;
}

function criteriaDescription(?string $raw, ?string $lang = null): string
{
    return criteriaLabel($raw, $lang);
}
