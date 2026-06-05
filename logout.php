<?php
require_once 'config/config.php';
$_SESSION = [];
session_destroy();
redirect(BASE_URL . '/login.php');
