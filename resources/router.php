<?php

/**
 * Router script for the built-in PHP web server.
 *
 * The built-in web server should only be used for development and testing as it
 * has a number of limitations that makes running Chiron on it highly insecure
 * and somewhat limited.
 *
 * Note that:
 * - The server is single-threaded, any requests made during the execution of
 *   the main request will hang until the main request has been completed.
 * - The web server does not enforce any of the settings in .htaccess in
 *   particular a remote user will be able to download files that normally would
 *   be protected from direct access.
 *
 * The router script is needed to work around a bug in PHP, see
 * https://bugs.php.net/bug.php?id=61286
 *
 * Usage:
 * php -S localhost:8080 router.php
 *
 * @see http://php.net/manual/en/features.commandline.webserver.php
 */

// @codeCoverageIgnoreStart
// Avoid this file run when listing commands
if (PHP_SAPI === 'cli') {
    return;
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Front Controller path - expected to be in the default folder
$fcpath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR;

// Full path
$path = $fcpath . ltrim($uri, '/');

// If $path is an existing file or folder within the public folder
// then let the request handle it like normal.
if ($uri !== '/' && (is_file($path) || is_dir($path))) {
    return false;
}

// Otherwise, we'll load the index file and let
// the framework handle the request from here.
require_once $fcpath . 'index.php';
// @codeCoverageIgnoreEnd
