<?php
/**
 * Admin - Logout
 */

require_once __DIR__ . '/../config/auth.php';

doLogout();

header('Location: login.php');
exit;
