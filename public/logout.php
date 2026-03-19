<?php
require_once __DIR__ . '/../includes/functions.php';

session_start();
$_SESSION = [];
session_destroy();

redirectTo('login.php');