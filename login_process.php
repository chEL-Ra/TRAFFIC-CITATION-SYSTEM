<?php
session_start();
require 'db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['pass'] ?? ''; 
    $errors = [];

    if ($username === '') $errors[] = "Username is required";
    if ($password === '') $errors[] = "Password is required";

    if (!empty($errors)) {
        $_SESSION['ERRMSG_ARR'] = $errors;
        header("Location: login.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC); 

        // Important: Using 'pass' as the column name
        if ($user && password_verify($password, $user['pass'])) {
            
            session_regenerate_id(true); 
            
            $_SESSION['SESS_MEMBER_ID'] = $user['id'];
            $_SESSION['SESS_NAME']      = $user['name'];
            $_SESSION['SESS_POSITION']  = $user['position'];
            $_SESSION['SESS_USERNAME']  = $user['username'];

            header("Location: dashboard.php");
            exit();
            
        } else {
            $_SESSION['ERRMSG_ARR'] = ["Invalid username or password"];
            header("Location: login.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['ERRMSG_ARR'] = ["Database error: " . $e->getMessage()];
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}