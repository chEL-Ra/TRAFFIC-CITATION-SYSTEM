<?php
// index.php
session_start();

// Check if officer is logged in
if(isset($_SESSION['SESS_MEMBER_ID'])){
    // Already logged in, go to dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Not logged in, go to login page
    header("Location: login.php");
    exit();
}
?>
