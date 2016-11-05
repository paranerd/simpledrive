<?php
/**
 * Copyright (c) 2016, Kevin Schulz <paranerd.development@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once 'php/database.class.php';

// Clear cache and end sessions
if (isset($_GET['t'])) {
	$db = Database::getInstance();
	$db->session_end($_GET['t']);
	unset($_COOKIE['token']);
	setcookie('token', null, -1, '/');
}

header('Location: login');