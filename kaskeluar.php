<?php include 'config/app.php';
session_start();

// tambah
if (isset($_POST['simpan'])) {
    if ($_POST['jumlah'] <= getSaldo()) {
        query("INSERT INTO transaksi VALUES(NULL, NULL, '$_POST[tanggal]', 'keluar', '$_POST[jumlah]', 'Kas Keluar', '$_POST[jumlah]' )");
    }

    // agar data tidak ke-duplicate saat reload
    header("Location: kaskeluar.php?success=tambah");
    exit;
}

// edit
if (isset($_POST['update'])) {
    query("UPDATE transaksi SET
        tanggal = '$_POST[tanggal]',
        jumlah  = '$_POST[jumlah]',
        deskripsi  = '$_POST[deskripsi]'
        WHERE id_transaksi = '$_POST[id_transaksi]'
        AND jenis='keluar'
");

    header("Location: kaskeluar.php?success=update");
    exit;
}

// ambil data edit
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(
        query("SELECT * FROM transaksi 
               WHERE id_transaksi='$_GET[edit]' 
               AND jenis='keluar'")
    );
}

// delete
if (isset($_GET['hapus'])) {
    query("DELETE FROM transaksi WHERE id_transaksi='$_GET[hapus]'");

    header("Location: kaskeluar.php?success=delete");
    exit;
}

//ringkasan kas keluar
$keluar = ringkasanKasKeluar();
?>

<!doctype html>
<html lang="en">

<head>
    <title>Kas Keluar</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.2.1 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style/style.css">
</head>

<body>
    <!-- sidebar -->
    <div class="d-flex min-vh-100">

        <!-- SIDEBAR -->
        <div class="collapse show bg-dark text-white shadow" id="sidebar">
            <div class="p-3">
                <h4 class="text-center">Kas Kelas</h4>
                <hr>
                <!-- profile -->
                <div class="d-flex align-items-center px-2 mb-2">
                    <img src="assets/piploy.jpg"
                        class="rounded-circle me-2"
                        style="width:45px;height:45px;object-fit:cover;">
                    <p class="fs-5 mb-0"><?= $_SESSION['role']; ?></p>
                </div>
                <hr>
                <!-- menu -->
                <ul class="nav nav-pills flex-column gap-2">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item mt-4">
                        <small class="ms-3">Menu Utama</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="datamurid.php">Data Murid</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kasmasuk.php">Kas Masuk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="kaskeluar.php">Kas Keluar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="iuran_khusus.php">Iuran Khusus</a>
                    </li>
                    <li class="nav-item mt-4">
                        <small class="ms-3">Laporan</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="aruskas.php">Arus Kas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="statusbayar.php">Status Bayar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php">Laporan</a>
                    </li>
                    <hr>
                    <li class="nav-item">
                        <small class="ms-3">Logout</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">Log Out</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="flex-fill d-flex flex-column">

            <!-- TOP NAV -->
            <nav class="navbar navbar-dark bg-dark">
                <div class="container-fluid">
                    <button class="navbar-toggler"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#sidebar">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <span class="navbar-brand ms-2">SMK NEGERI 7 SAMARINDA</span>
                </div>
            </nav>

            <!-- main -->
            <div class="p-4">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
                        <?php
                        if ($_GET['success'] == 'tambah') echo 'Kas masuk berhasil ditambahkan!';
                        if ($_GET['success'] == 'update') echo 'Data murid berhasil diupdate!';
                        if ($_GET['success'] == 'delete') echo 'Data murid berhasil dihapus!';
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <script>
                        // Auto dismiss alert setelah 3 detik
                        setTimeout(() => {
                            const alert = document.getElementById('successAlert');
                            if (alert) {
                                const bsAlert = new bootstrap.Alert(alert);
                                bsAlert.close();
                            }
                        }, 3000);
                    </script>
                <?php endif; ?>
                <!-- ringkasan -->
                <h3 class="mb-4">Ringkasan</h3>
                <div class="row g-3">
                    <!-- Kas keluar -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                                <div class="fs-3 text-danger">
                                    <i class="fa-solid fa-arrow-trend-down"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Kas Keluar</h6>
                                    <h5 class="fw-bold mb-0">Rp <?= number_format($keluar['totalKeluar'], 0, ',', '.'); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Belum Dibayar -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                                <div class="fs-3 text-secondary">
                                    <i class="fa-solid fa-chart-area"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Total kas keluar bulan ini</h6>
                                    <h5 class="fw-bold mb-0">Rp <?= number_format($keluar['keluarBulanIni'], 0, ',', '.'); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jumlah Bayar -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                                <div class="fs-3 text-info">
                                    <i class="fa-solid fa-arrow-down-wide-short"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Jumlah transaksi</h6>
                                    <h5 class="fw-bold mb-0"><?= $keluar['jumlahTransaksi']; ?> Transaksi</h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pembayaran Terakhir -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                                <div class="fs-3 text-warning">
                                    <i class="fa-solid fa-sack-dollar"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-1">Sisa Saldo</h6>
                                    <h5 class="fw-bold mb-0">Rp <?= number_format($keluar['saldo'], 0, ',', '.'); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- modal tambah kas -->
                <div class="d-grid gap-2 mt-5">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKas">Tambah Kas Keluar</button>
                </div>
                <!-- Modal -->
                <div class="modal fade" id="modalTambahKas" tabindex="-1" aria-labelledby="modalTambahKasLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content">
                            <!-- Header -->
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTambahKasLabel">Tambah Kas Keluar</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <!-- Body -->
                            <form action="" method="POST">
                                <input type="hidden" name="id_transaksi" value="<?= $edit['id_transaksi'] ?? '' ?>">
                                <div class="modal-body">
                                    <!-- Tanggal -->
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date"
                                            name="tanggal"
                                            class="form-control"
                                            value="<?= $edit['tanggal'] ?? '' ?>"
                                            required>
                                    </div>
                                    <!-- Jumlah -->
                                    <div class="mb-3">
                                        <label class="form-label">Jumlah Uang</label>
                                        <input type="number" name="jumlah" class="form-control" placeholder="Contoh: 10000" value="<?= $edit['jumlah'] ?? '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea class="form-control" name="deskripsi" rows="2"><?= $edit['deskripsi'] ?? '' ?></textarea>
                                    </div>
                                </div>
                                <!-- Footer -->
                                <div class="modal-footer">
                                    <button type="submit"
                                        name="<?= $edit ? 'update' : 'simpan' ?>"
                                        class="btn <?= $edit ? 'btn-warning' : 'btn-success' ?>">
                                        <?= $edit ? 'Update' : 'Simpan' ?>
                                    </button>

                                </div>
                            </form>

                        </div>
                    </div>
                </div>

                <!--  -->
                <form action="">
                    <div class="mt-3">

                        <div class="card mt-3">
                            <div class="card-body">
                                <h5>Daftar Kas Keluar</h5>
                                <hr>
                                <div class="mb-6">
                                    <input type="text" class="form-control" placeholder="Search">
                                </div>
                                <!-- tabel -->
                                <table class="table table-hover table-striped border mt-3">
                                    <thead>
                                        <tr>
                                            <th scope="col">Tanggal</th>
                                            <th scope="col">Jumlah</th>
                                            <!-- <th scope="col">Sisa Kas</th> -->
                                            <th scope="col">Deskripsi</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <?php
                                            $kasKeluar = mysqli_query(
                                                $db,
                                                "SELECT id_transaksi, tanggal, jumlah, deskripsi
                                                    FROM transaksi 
                                                    WHERE jenis='keluar' 
                                                    ORDER BY tanggal DESC"
                                            );
                                            while ($row = mysqli_fetch_assoc($kasKeluar)) :
                                            ?>
                                        <tr>
                                            <td><?= $row['tanggal']; ?></td>
                                            <td><?= number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                            <td><?= $row['deskripsi']; ?></td>
                                            <td>
                                                <a href="?edit=<?= $row['id_transaksi']; ?>"
                                                    class="btn btn-sm btn-warning">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="?hapus=<?= $row['id_transaksi']; ?>"
                                                    onclick="return confirm('Hapus data?')"
                                                    class="btn btn-sm btn-danger">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <footer class="border-top py-2 text-center text-muted small mt-5">
                © Kas Kelas Atika
            </footer>
        </div>
    </div>
    </div>
    <script
        src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+"
        crossorigin="anonymous"></script>
    <script>
        // Buka modal otomatis jika ada data edit
        <?php if ($edit): ?>
            const modalTambahKas = new bootstrap.Modal(document.getElementById('modalTambahKas'));
            modalTambahKas.show();
        <?php endif; ?>
    </script>
</body>

</html>