
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <style>
        body {
            font-family: Arial;
            background: #f0edff;
            text-align: center;
            padding-top: 100px;
        }
        .box {
            background: white;
            max-width: 400px;
            margin: auto;
            padding: 35px;
            border-radius: 18px;
        }
        a {
            display: inline-block;
            background: #6c5ce7;
            color: white;
            padding: 12px 22px;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="box">
    <h1>Hello, <?= htmlspecialchars($_SESSION['name']) ?>!</h1>
    <p>You have successfully logged in.</p>
    <a href="logout.php">Logout</a>
</div>
</body>
</html>