<?php
session_start();
include 'config/conn.php';

$alert = "";

if (isset($_POST['login'])) {

    $u = trim($_POST['username']);
    $p = trim($_POST['password']);

    if ($u == "" && $p == "") {
        $alert = "
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Username dan Password tidak boleh kosong!',
            scrollbarPadding: false,
            heightAuto: false
        });";
    } elseif ($u == "") {
        $alert = "
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Username tidak boleh kosong!',
            scrollbarPadding: false,
            heightAuto: false
        });";
    } elseif ($p == "") {
        $alert = "
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Password tidak boleh kosong!',
            scrollbarPadding: false,
            heightAuto: false
        });";
    } else {

        $stmt = mysqli_prepare($db, "SELECT * FROM user WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $u);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $data = mysqli_fetch_assoc($result);

                if ($data['password'] == $p) {

                    $_SESSION['login'] = true;
                    $_SESSION['role'] = $data['role'];
                    $_SESSION['username'] = $data['username'];

                    $alert = "
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Login berhasil',
                        timer: 1500,
                        showConfirmButton: false,
                        scrollbarPadding: false,
                        heightAuto: false
                    }).then(() => {
                        window.location.href='dashboard.php';
                    });";
                } else {
                $alert = "
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Password salah',
                    scrollbarPadding: false,
                    heightAuto: false
                });";
            }
        } else {
            $alert = "
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Username tidak ditemukan',
                scrollbarPadding: false,
                heightAuto: false
            });";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            overflow-y: scroll;
            background: whitesmoke;
        }

        .login-box {
            width: 320px;
            background: white;
            border-radius: 15px;
            padding: 25px;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center vh-100">

    <form method="post" class="login-box shadow">
        <h4 class="mb-3 text-center">Login Kas Kelas</h4>

        <input name="username" class="form-control mb-2" placeholder="Username">
        <input type="password" name="password" class="form-control mb-3" placeholder="Password">

        <button name="login" class="btn btn-primary w-100">Login</button>
    </form>

    <!-- ALERT -->
    <script>
        <?php if (!empty($alert)) echo $alert; ?>
    </script>

</body>

</html>