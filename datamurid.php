<?php
session_start();
include 'config/app.php';

// create
if (isset($_POST['simpan'])) {
    query("INSERT INTO murid VALUES(
        NULL,
        '$_POST[nama]',
        '$_POST[kelas]',
        'aktif'
        )");

    // agar data ga ke duplicate waktu reload
    header("Location: datamurid.php?success=tambah");
    exit;
}

// // update
if (isset($_POST['update'])) {
    query("UPDATE murid SET
            nama='$_POST[nama]',
            kelas='$_POST[kelas]',
            status='$_POST[status]'
            WHERE id_murid = '$_POST[id_murid]'
        ");

    header("Location: datamurid.php?success=update");
    exit;
}

// delete
if (isset($_GET['hapus'])) {
    query("DELETE FROM murid WHERE id_murid='$_GET[hapus]'");

    header("Location: datamurid.php?success=delete");
    exit;
}

// // ambil data edit
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(
        query("SELECT * FROM murid WHERE id_murid='$_GET[edit]'")
    );
}

// read
$data = query("SELECT * FROM murid");

// nomor urut mulai dari 1
$no = 1;
// total murid
$totalMurid = mysqli_fetch_assoc(query("SELECT COUNT(*) AS total FROM murid"))['total'];
// murid aktif
$totalAktif = mysqli_fetch_assoc(query("SELECT COUNT(*) AS total FROM murid WHERE status='aktif'"))['total'];
// murid tidak aktif
$totalNonaktif = mysqli_fetch_assoc(query("SELECT COUNT(*) AS total FROM murid WHERE status='Tidak Aktif'"))['total'];
?>

<!doctype html>
<html lang="en">

<head>
    <title>Data Murid</title>
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
                        <a class="nav-link active" href="datamurid.php">Data Murid</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kasmasuk.php">Kas Masuk</a>
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
                        if ($_GET['success'] == 'tambah') echo 'Data murid berhasil ditambahkan!';
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

                <div class="">
                    <h3 class="mb-4">Ringkasan</h3>

                    <div class="row g-4 text-center">

                        <!-- Total Murid -->
                        <div class="col-12 col-md-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body border border-primary rounded d-flex flex-column justify-content-center">
                                    <h6 class="text-muted mb-1">Total Murid</h6>
                                    <h5 class="fw-bold mb-0"><?= $totalMurid ?></h5>
                                </div>
                            </div>
                        </div>

                        <!-- Murid Aktif -->
                        <div class="col-12 col-md-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body border border-success rounded d-flex flex-column justify-content-center">
                                    <h6 class="text-muted mb-1">Murid Aktif</h6>
                                    <h5 class="fw-bold mb-0"><?= $totalAktif ?></h5>
                                </div>
                            </div>
                        </div>

                        <!-- Murid Tidak Aktif -->
                        <div class="col-12 col-md-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body border border-danger rounded d-flex flex-column justify-content-center">
                                    <h6 class="text-muted mb-1">Murid Tidak Aktif</h6>
                                    <h5 class="fw-bold mb-0"><?= $totalNonaktif ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- modal murid -->
                <div class="d-grid gap-2 mt-5">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKas">Tambah Murid</button>
                </div>
                <!-- Modal -->
                <div class="modal fade" id="modalTambahKas" tabindex="-1" aria-labelledby="modalTambahKasLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content">
                            <!-- Header -->
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTambahKasLabel"><?= $edit ? 'Edit Murid' : 'Tambah Murid' ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <!-- Body -->
                            <form action="" method="POST">
                                <input type="hidden" name="id_murid" value="<?= $edit['id_murid'] ?? '' ?>">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Siswa</label>
                                        <input type="text" name="nama" class="form-control" value="<?= $edit['nama'] ?? '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kelas</label>
                                        <input type="text" name="kelas" class="form-control" value="<?= $edit['kelas'] ?? '' ?>" required>
                                    </div>
                                    <?php if ($edit): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-control">
                                                <option <?= $edit['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                                <option <?= $edit['status'] == 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <?php if ($edit): ?>
                                        <a href="datamurid.php" class="btn btn-secondary">Batal</a>
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
                                <h5>Daftar Murid</h5>
                                <hr>
                                <div class="row">
                                    <!-- cari -->
                                    <div class="col">
                                        <input type="search" class="form-control" placeholder="Search">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="inputState" class="form-select">
                                            <option selected>Status</option>
                                            <!-- statis tar ganti yg dari database -->
                                            <option>Januari</option>
                                            <option>Februari</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- tabel -->
                                <table class="table table-hover table-striped border mt-3">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Nama Siswa</th>
                                            <th scope="col">Kelas</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <tbody>
                                        <?php foreach ($data as $m):
                                            if (strtolower($m['status']) == 'aktif') {
                                                $s = "Aktif";
                                                $w = "success";
                                            } else {
                                                $s = "Tidak Aktif";
                                                $w = "danger";
                                            }
                                        ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= $m['nama']; ?></td>
                                                <td><?= $m['kelas']; ?></td>
                                                <td><span class="badge bg-<?= $w ?>"><?= $s ?></span></td>
                                                <td>
                                                    <a href="?edit=<?= $m['id_murid']; ?>"
                                                        class="btn btn-sm btn-warning">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="?hapus=<?= $m['id_murid']; ?>"
                                                        onclick="return confirm('hapus data?')"
                                                        class="btn btn-sm btn-danger">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>

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