<?php
session_start();

// すでにログインしてたらホームへ
if (isset($_SESSION['user'])) {
    header("Location: ../home/home.html"); // 分離後
    exit;
}

// 未ログインならログイン画面へ
header("Location: login.html");
exit;
