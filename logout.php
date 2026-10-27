<?php
/** logout.php – ends the session and returns to login. */
require_once __DIR__ . '/includes/auth.php';

$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
