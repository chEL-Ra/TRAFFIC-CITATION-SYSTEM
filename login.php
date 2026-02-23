<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login | LITCOM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            width: 100%;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #eeebeb;
            overflow-x: hidden;
        }

        .fade-in {
            opacity: 0;
            animation: fadeIn 1s ease-in forwards;
        }

        @keyframes fadeIn { to { opacity: 1; } }

        .login-page {
            display: flex;
            width: 100vw;
            height: calc(100vh - 80px); /* Adjust for footer height */
        }

        /* LEFT SIDE */
        .login-left {
            flex: 1 1 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #fff;
            padding: 5%;
            background: linear-gradient(135deg, #2c3e50, #000000); /* Darker professional look */
            position: relative;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 30px;
            z-index: 2;
        }

        .logo-container img {
            width: clamp(100px, 15vw, 180px);
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5));
            transition: transform 0.3s;
        }

        .logo-container img:hover { transform: scale(1.05); }

        .page-title {
            font-size: clamp(1.8rem, 3vw, 3rem);
            font-weight: 900;
            line-height: 1.2;
            z-index: 2;
        }

        .page-title::after {
            content: "";
            display: block;
            height: 4px;
            width: 100px;
            margin: 15px auto;
            background-color: #FFD700;
            border-radius: 2px;
        }

        /* RIGHT SIDE */
        .login-right {
            flex: 1 1 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #eeebeb;
        }

        .login-card {
            width: 90%;
            max-width: 400px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 15px;
            background-color: #fff;
        }

        .login-card h2 {
            font-weight: 700;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-control {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background-color: #d32f2f;
            color: #fff;
            border: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-login:hover { background-color: #b71c1c; }

        .error-msg {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border: 1px solid #f5c6cb;
        }

        /* FOOTER */
        .login-footer {
            height: 80px;
            background: #fff;
            border-top: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 0.85rem;
            color: #666;
        }

        @media (max-width: 900px) {
            .login-page { flex-direction: column; height: auto; }
            .login-left { padding: 40px 20px; }
        }
    </style>
</head>
<body class="fade-in">

<div class="login-page">
    <div class="login-left">
        <div class="logo-container">
            <img src="logos/municipality_seal.png" alt="Municipality Logo">
            <img src="logos/litcom_logo.png" alt="Liloan Traffic Commission Logo">
        </div>
        <div class="page-title">
            LILOAN TRAFFIC COMMISSION<br>MANAGEMENT SYSTEM
        </div>
    </div>

    <div class="login-right">
        <div class="login-card">
            <h2>Officer Login</h2>

            <?php
            if(isset($_SESSION['ERRMSG_ARR']) && is_array($_SESSION['ERRMSG_ARR'])){
                foreach($_SESSION['ERRMSG_ARR'] as $error){
                    echo "<div class='error-msg'>$error</div>";
                }
                unset($_SESSION['ERRMSG_ARR']);
            }
            ?>

            <form method="POST" action="login_process.php">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="pass" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" name="login" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</div>

<div class="login-footer">
    <p class="mb-0">Admin: <strong>MAYOR ALJEW FRASCO</strong> | Head: <strong>MR. NEIL CAÑETE</strong></p>
    <p class="mb-0">Developed by: Edwin G. Yuson - ICT &copy; <?php echo date("Y"); ?></p>
</div>

</body>
</html>