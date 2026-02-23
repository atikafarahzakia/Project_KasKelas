<?php include 'config/app.php';
session_start();

// Ambil data murid
$murid = query("SELECT * FROM murid");

// Tambah data kas masuk
if (isset($_POST['simpan'])) {
    query("INSERT INTO transaksi (id_murid, tanggal, jenis, jumlah, keterangan, deskripsi) 
           VALUES ('$_POST[id_murid]', '$_POST[tanggal]', 'masuk', '$_POST[jumlah]', 'Kas Masuk', '$_POST[deskripsi]')");
    // agar data tidak ke-duplicate saat reload
    header("Location: kasmasuk.php?success=tambah");
    exit;
}

// // update
if (isset($_POST['update'])) {
    query("UPDATE transaksi SET
        tanggal = '$_POST[tanggal]',
        jumlah  = '$_POST[jumlah]',
        deskripsi  = '$_POST[deskripsi]'
        WHERE id_transaksi = '$_POST[id_transaksi]'
    ");

    header("Location: kasmasuk.php?success=update");
    exit;
}

// delete
if (isset($_GET['hapus'])) {
    query("DELETE FROM transaksi WHERE id_transaksi='$_GET[hapus]'");

    header("Location: kasmasuk.php?success=delete");
    exit;
}

// // ambil data edit
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(
        query("SELECT * FROM transaksi WHERE id_transaksi='$_GET[edit]'")
    );
}

// read
$data = query("SELECT * FROM transaksi");
?>


<!doctype html>
<html lang="en">

<head>
    <title>Kas Masuk</title>
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
                        <a class="nav-link active" href="kasmasuk.php">Kas Masuk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kaskeluar.php">Kas Keluar</a>
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
                        if ($_GET['success'] == 'update') echo 'Kas masuk berhasil diupdate!';
                        if ($_GET['success'] == 'delete') echo 'Kas masuk berhasil dihapus!';
                        // 
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
                    <!-- Kas Masuk -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                                <div class="fs-3 text-success">📈</div>
                                <div>
                                    <h6 class="text-muted mb-1">Kas Masuk</h6>
                                    <!-- <h4 class="fw-bold mb-0"><?= number_format(dataSiswa()); ?></h4> -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Belum Dibayar -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                                <div class="fs-3 text-danger">❌</div>
                                <div>
                                    <h6 class="text-muted mb-1">Belum Bayar</h6>
                                    <!-- <h4 class="fw-bold mb-0"><?= number_format(dataSiswa()); ?></h4> -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jumlah Bayar -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                                <div class="fs-3 text-warning">💰</div>
                                <div>
                                    <h6 class="text-muted mb-1">Jumlah Bayar</h6>
                                    <!-- <h4 class="fw-bold mb-0"><?= number_format(dataSiswa()); ?></h4> -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pembayaran Terakhir -->
                    <div class="col-12 col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                                <div class="fs-3 text-primary">🔄</div>
                                <div>
                                    <h6 class="text-muted mb-1">Pembayaran Terakhir</h6>
                                    <!-- <h4 class="fw-bold mb-0"><?= number_format(dataSiswa()); ?></h4> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Button Tambah Kas -->
                <div class="d-grid gap-2 mt-5">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKas">
                        Tambah Uang Kas
                    </button>
                </div>

                <!-- Modal Tambah Kas -->
                <div class="modal fade" id="modalTambahKas" tabindex="-1" aria-labelledby="modalTambahKasLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content">
                            <!-- header -->
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTambahKasLabel"><?= $edit ? 'Edit Kas' : 'Tambah Kas Masuk' ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <!-- body -->
                            <form action="" method="POST">
                                <input type="hidden" name="id_transaksi" value="<?= $edit['id_transaksi'] ?? '' ?>">
                                <div class="modal-body">
                                    <!-- Nama Siswa -->
                                    <div class="mb-3">
                                        <label class="form-label">Nama Siswa</label>
                                        <select name="id_murid" class="form-control" <?= $edit ? 'disabled' : '' ?> required>
                                            <option value="">-- Pilih Siswa --</option>
                                            <?php foreach ($murid as $m): ?>
                                                <option value="<?= $m['id_murid']; ?>"
                                                    <?= isset($edit) && $m['id_murid'] == $edit['id_murid'] ? 'selected' : ''; ?>>
                                                    <?= $m['nama']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Tanggal -->
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" name="tanggal" class="form-control" value="<?= $edit['tanggal'] ?? '' ?>" required>
                                    </div>
                                    <!-- Jumlah -->
                                    <div class="mb-3">
                                        <label class="form-label">Jumlah</label>
                                        <input type="number" name="jumlah" class="form-control" value="<?= $edit['jumlah'] ?? '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea class="form-control" name="deskripsi" rows="2"><?= $edit['deskripsi'] ?? 'Kas Masuk' ?></textarea>
                                    </div>
                                </div>
                                <!-- Footer -->
                                <div class="modal-footer">
                                    <?php if ($edit): ?>
                                        <a href="kasmasuk.php" class="btn btn-secondary">Batal</a>
                                        <button name="update" class="btn btn-warning">Update</button>
                                    <?php else: ?>
                                        <button name="simpan" class="btn btn-primary">Simpan</button>
                                    <?php endif; ?>
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
                                <h5>Daftar Kas Masuk</h5>
                                <hr>
                                <div class="mb-6">
                                    <input type="text" class="form-control" placeholder="Search">
                                </div>
                                <!-- tabel -->
                                <table class="table table-hover table-striped border mt-3">
                                    <thead>
                                        <tr>
                                            <th scope="col">Tanggal</th>
                                            <th scope="col">Nama Siswa</th>
                                            <th scope="col">Jumlah</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php
                                        $kasMasuk = mysqli_query(
                                            $db,
                                            "SELECT t.id_transaksi, t.tanggal, m.nama, t.jumlah, t.deskripsi
                                                FROM transaksi t
                                                JOIN murid m ON t.id_murid = m.id_murid
                                                WHERE t.jenis='masuk'
                                                ORDER BY t.tanggal"
                                        );

                                        while ($row = mysqli_fetch_assoc($kasMasuk)) :
                                        ?>
                                            <tr>
                                                <td><?= $row['tanggal']; ?></td>
                                                <td><?= $row['nama']; ?></td>
                                                <td><?= number_format($row['jumlah'], 0, ',', '.'); ?></td>
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