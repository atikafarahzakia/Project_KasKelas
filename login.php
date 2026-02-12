<?php
session_start();
include 'config/conn.php';
if (isset($_POST['login'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];
    $q = mysqli_query($db, "SELECT * FROM user WHERE username='$u' AND password='$p'");
    if (mysqli_num_rows($q) == 1) {
        $d = mysqli_fetch_assoc($q);
        $_SESSION['login'] = true;
        $_SESSION['role'] = $d['role'];
        $_SESSION['username'] = $d['username'];
        header("location:dashboard.php");
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex justify-content-center align-items-center vh-100">
    <form method="post" class="p-4 border rounded">
        <h4 class="mb-3">Login Kas Kelas</h4>
        <?php if (isset($error)) : ?>
            <div class="alert alert-danger">Login gagal</div>
        <?php endif; ?>
        <input name="username" class="form-control mb-2" placeholder="Username" required>
        <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
        <button name="login" class="btn btn-primary w-100">Login</button>
    </form>
</body>
</html>