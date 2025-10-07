<?php
session_start();
echo "<pre>";
echo "Session Data:\n";
print_r($_SESSION);
echo "POST Data:\n";
print_r($_POST);
echo "GET Data:\n";
print_r($_GET);
echo "</pre>";
?>