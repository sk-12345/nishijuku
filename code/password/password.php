<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit();
}

header("Location: password.html");
exit();
