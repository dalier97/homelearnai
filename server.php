<?php

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

// Handle logging with error suppression to prevent broken pipe errors
$formattedDateTime = date('D M j H:i:s Y');
$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

// Use error suppression and check if stdout is available before writing
// This prevents broken pipe errors during testing and when connections are closed
if (defined('STDOUT') && is_resource(STDOUT)) {
    @file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");
} elseif (php_sapi_name() === 'cli-server') {
    // For PHP built-in server, try to write to stdout stream directly with error suppression
    @file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");
}

require_once $publicPath.'/index.php';
