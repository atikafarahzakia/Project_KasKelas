<?php
session_start();
include 'config/app.php';
// echo "FILE JALAN";

// ================= TAMBAH PENGAJUAN =================
if (isset($_POST['simpan'])) {

    $jumlah = (int)$_POST['jumlah'];
    $kategori = $_POST['kategori'];
    $keterangan = $_POST['keterangan'];

    // ================= UPLOAD BUKTI =================
    $bukti = '';

    if (!empty($_FILES['bukti']['name'])) {

        $namaFile = $_FILES['bukti']['name'];
        $tmp = $_FILES['bukti']['tmp_name'];
        $size = $_FILES['bukti']['size'];
        $error = $_FILES['bukti']['error'];

        if ($error === 0) {

            $ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];

            if (!in_array($ext, $allowed)) {
                header("Location: kaskeluar.php?error=format");
                exit;
            }

            if ($size > 2000000) {
                header("Location: kaskeluar.php?error=besar");
                exit;
            }

            $namaBaru = uniqid() . '.' . $ext;

            if (!is_dir('upload')) {
                mkdir('upload', 0777, true);
            }

            move_uploaded_file($tmp, 'upload/' . $namaBaru);
            $bukti = $namaBaru;
        }
    }

    // ================= SIMPAN KE DATABASE =================
    if ($jumlah > 0) {

        $bulan = date('n');
        $tahun = date('Y');

        $sql = "INSERT INTO pengajuan 
    (tanggal, bulan, tahun, jumlah, kategori, keterangan, bukti, status)
    VALUES (NOW(), ?, ?, ?, ?, ?, ?, 'pending')";

        // PAKAI $conn atau $db HARUS SESUAI config kamu
        $stmt = mysqli_prepare($db, $sql);

        if (!$stmt) {
            die("Prepare gagal: " . mysqli_error($db));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iiisss",
            $bulan,
            $tahun,
            $jumlah,
            $kategori,
            $keterangan,
            $bukti
        );

        if (!mysqli_stmt_execute($stmt)) {
            die("Execute gagal: " . mysqli_error($db));
        }

        mysqli_stmt_close($stmt);

        header("Location: kaskeluar.php?success=tambah");
        exit;
    }
}

// ambil ringkasan kas keluar
$keluar = ringkasanKasKeluar();

// ================= DATA =================
$pengajuan = query("SELECT * FROM pengajuan ORDER BY tanggal DESC");

// TAMBAHKAN INI
$search = $_GET['search'] ?? '';

$dataEnum = query("SHOW COLUMNS FROM pengajuan LIKE 'kategori'");
$type = $dataEnum[0]['Type'];

// ambil isi enum
$type = str_replace(["enum('", "')"], '', $type);
$kategoriList = explode("','", $type);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Kas Keluar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .card {
            border-radius: 12px;
        }

        /* SIDEBAR ASLI (TIDAK DIUBAH) */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, #0d6efd, #0b5ed7);
            color: white;
            position: sticky;
            top: 0;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 12px 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: white;
            color: #0d6efd !important;
        }

        .sidebar .profile img {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
        }
    </style>
</head>

<body>

    <div class="d-flex">

        <!-- SIDEBAR -->
        <div class="sidebar p-3">
            <h4 class="text-center mb-3">Kas Kelas</h4>
            <hr>

            <div class="profile text-center mb-3">
                <img src="assets/profile.jpg">
                <p><?= htmlspecialchars($_SESSION['role']) ?></p>
            </div>

            <hr>

            <ul class="nav flex-column gap-2">
                <li><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

                <?php if ($_SESSION['role'] == 'wali kelas'): ?>
                    <li><a class="nav-link" href="datamurid.php"><i class="fas fa-users"></i> Data Murid</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'bendahara'): ?>
                    <li><a class="nav-link" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
                    <li><a class="nav-link active" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
                <?php endif; ?>

                <li><a class="nav-link" href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a></li>
                <li><a class="nav-link" href="statusbayar.php"><i class="fas fa-chart-bar"></i> Status Bayar</a></li>
                <li><a class="nav-link" href="laporan.php"><i class="fas fa-file"></i> Laporan</a></li>

                <hr>
                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- CONTENT -->
        <div class="flex-fill p-4">

            <h4 class="mb-4">Kas Keluar</h4>

            <!-- RINGKASAN -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6>Total Kas Keluar</h6>
                                <h4 class="text-danger">
                                    Rp <?= number_format($keluar['totalKeluar']) ?>
                                </h4>
                            </div>
                            <i class="bi bi-arrow-up-circle fs-1 text-danger"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6>Saldo</h6>
                                <h4 class="text-success">
                                    Rp <?= number_format($keluar['saldo']) ?>
                                </h4>
                            </div>
                            <i class="bi bi-wallet2 fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BUTTON -->
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
                Tambah Kas Keluar
            </button>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                    <?php
                    if ($_GET['error'] == 'saldo') echo 'Saldo tidak cukup untuk melakukan transaksi!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <script>
                    setTimeout(() => {
                        const alert = document.getElementById('errorAlert');
                        if (alert) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 3000);
                </script>
            <?php endif; ?>

            <!-- FILTER -->
            <form method="GET" class="row g-2 mb-2 mt-3">

                <!-- SEARCH -->
                <div class="col-md-4">
                    <input type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari jumlah / keterangan..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>

                <!-- BULAN -->
                <div class="col-md-3">
                    <input type="date" name="tanggal" class="form-control"
                        value="<?= $_GET['tanggal'] ?? '' ?>">
                </div>

                <!-- TAHUN -->
                <!-- <div class="col-md-2">
                    <select name="kategori" class="form-control">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategori as $cat): ?>
                            <option value="<?= $cat['id_kategori'] ?>"
                                <?= (($_GET['kategori'] ?? '') == $cat['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div> -->

                <!-- BUTTON -->
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary me-2">Filter</button>
                    <a href="kaskeluar.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <!-- TABEL -->
            <div class="card">
                <div class="card-body">

                    <!-- TABEL -->
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Bukti</th>
                            </tr>
                        </thead>

                        <?php foreach ($pengajuan as $row): ?>
                            <tr>
                                <td><?= $row['tanggal'] ?></td>
                                <td>Rp <?= number_format($row['jumlah']) ?></td>
                                <td><?= $row['kategori'] ?></td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($row['status'] == 'disetujui'): ?>
                                        <span class="badge bg-success">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['bukti']): ?>
                                        <img src="upload/<?= $row['bukti'] ?>" width="60">
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="modalTambah">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">

                <div class="modal-header">
                    <h5>Tambah Pengajuan</h5>
                </div>

                <div class="modal-body">

                    <div class="mb-2">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label>Kategori</label>
                        <select name="kategori" class="form-control" required>
                            <?php foreach ($kategoriList as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control"></textarea>
                    </div>

                    <div class="mb-2">
                        <label>Bukti</label>
                        <input type="file" name="bukti" class="form-control">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="simpan" class="btn btn-success">
                        Simpan
                    </button>
                </div>

            </form>
        </div>
    </div>

    <?php foreach ($pengajuan as $row): ?>

        <div class="modal fade" id="edit<?= $row['id_transaksi'] ?>">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content">

                    <input type="hidden" name="id_transaksi" value="<?= $row['id_transaksi'] ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Kas Keluar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <!-- <?php if ($hasKategoriColumn): ?>
                            <div class="mb-3">
                                <label>Kategori</label>
                                <select name="id_kategori" class="form-control">
                                    <?php foreach ($kategori as $cat): ?>
                                        <option value="<?= $cat['id_kategori'] ?>"
                                            <?= $row['id_kategori'] == $cat['id_kategori'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?> -->

                        <div class="mb-3">
                            <label>Jumlah</label>
                            <input type="number" name="jumlah" value="<?= $row['jumlah'] ?>" class="form-control">
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button name="update" class="btn btn-warning">Update</button>
                    </div>

                </form>
            </div>
        </div>

    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>