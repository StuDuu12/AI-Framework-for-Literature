<?php
/**
 * Helper functions for redirects with toast notifications
 */

/**
 * Redirect with error toast message
 * 
 * @param string $url Redirect URL
 * @param string $errorCode Error code (will be mapped to message in JS)
 */
function redirectWithError($url, $errorCode)
{
    $separator = strpos($url, '?') !== false ? '&' : '?';
    header('Location: ' . $url . $separator . 'error=' . urlencode($errorCode));
    exit;
}

/**
 * Redirect with success toast message
 * 
 * @param string $url Redirect URL
 * @param string $successCode Success code (will be mapped to message in JS)
 */
function redirectWithSuccess($url, $successCode)
{
    $separator = strpos($url, '?') !== false ? '&' : '?';
    header('Location: ' . $url . $separator . 'success=' . urlencode($successCode));
    exit;
}

/**
 * Redirect with custom toast message
 * 
 * @param string $url Redirect URL
 * @param string $message Custom message to display
 * @param string $type Toast type: success, error, warning, info
 */
function redirectWithToast($url, $message, $type = 'info')
{
    $separator = strpos($url, '?') !== false ? '&' : '?';
    header('Location: ' . $url . $separator . 'message=' . urlencode($message) . '&type=' . urlencode($type));
    exit;
}

/**
 * Build URL with parameters
 * Helper function to construct URLs with query parameters
 * 
 * @param string $baseUrl Base URL
 * @param array $params Associative array of parameters
 * @return string Complete URL with parameters
 */
function buildUrl($baseUrl, $params = [])
{
    if (empty($params)) {
        return $baseUrl;
    }

    $queryString = http_build_query($params);
    $separator = strpos($baseUrl, '?') !== false ? '&' : '?';

    return $baseUrl . $separator . $queryString;
}
