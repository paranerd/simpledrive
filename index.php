<?php

/**
 * Copyright (c) 2016, Kevin Schulz <paranerd.development@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

date_default_timezone_set('Europe/Berlin');
header('Content-Type: text/html; charset=UTF-8');
define('LOG', (__DIR__) . '/logs/status.log');

require_once 'php/database.class.php';

// Set interface language
$lang_code = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && in_array($_SERVER['HTTP_ACCEPT_LANGUAGE'], array('de', 'en'))) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : 'en';
$lang = json_decode(file_get_contents('lang/lang_' . $lang_code . '.json'), true);

// Extract endpoint and action
$request = $_REQUEST['request'];
$args = explode('/', rtrim($request, '/'));
$view = array_shift($args);
$base = rtrim(dirname($_SERVER['PHP_SELF']), "/") . "/";

if (!$view) {
	header('Location: ' . $base . 'files');
}
else if (!file_exists('config/config.json')) {
	if ($view !== 'setup') {
		header('Location: ' . $base . 'setup');
	}
	require_once 'views/setup.php';
}
else if (!preg_match('/(\.|\.\.\/)/', $view) && file_exists('views/' . $view . '.php')) {
	$token		= (isset($_COOKIE['token'])) ? $_COOKIE['token'] : null;
	$db			= Database::getInstance();
	$user		= $db->user_get_by_token($token, true);
	require_once 'views/' . $view . '.php';
}
else {
	header('Location: ' . $base . '404');
}