<?php
session_start();
include 'config/app.php';

// ================= ACC =================
if (isset($_GET['acc'])) {
    $id = $_GET['acc'];

    $data = query("SELECT * FROM pengajuan WHERE id_pengajuan=$id")[0];

    // masukkan ke transaksi
    query("INSERT INTO transaksi 
        (tanggal, jumlah, jenis, kategori, keterangan, id_pengajuan)
        VALUES 
        (NOW(), '{$data['jumlah']}', 'keluar', '{$data['kategori']}', '{$data['keterangan']}', '$id')");

    // update status
    query("UPDATE pengajuan SET status='disetujui' WHERE id_pengajuan=$id");

    header("Location: pengajuan.php");
}

// ================= TOLAK =================
if (isset($_GET['tolak'])) {
    $id = $_GET['tolak'];
    query("UPDATE pengajuan SET status='ditolak' WHERE id_pengajuan=$id");

    header("Location: pengajuan.php");
}

// ================= DATA =================
$pengajuan = query("SELECT * FROM pengajuan ORDER BY tanggal DESC");
?>

<!doctype html>
<html lang="en">

<head>
    <title>Pengajuan</title>
    <meta charset="utf-8" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background: #f5f7fb;
        }

        .card {
            border-radius: 12px;
        }

        /* SIDEBAR SAMA */
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
                <p><?= $_SESSION['role']; ?></p>
            </div>

            <hr>

            <ul class="nav flex-column gap-2">
                <li><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

                <?php if ($_SESSION['role'] == 'wali kelas'): ?>
                    <li><a class="nav-link" href="datamurid.php"><i class="fas fa-users"></i> Data Murid</a></li>
                    <li><a class="nav-link active" href="pengajuan.php"><i class="fa-solid fa-clock"></i>Pengajuan</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'bendahara'): ?>

                    <li><a class="nav-link" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
                    <li><a class="nav-link" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
                <?php endif; ?>

                <li><a class="nav-link" href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a></li>
                <li><a class="nav-link" href="statusbayar.php"><i class="fa-solid fa-chart-column"></i> Status Bayar</a></li>
                <li><a class="nav-link" href="laporan.php"><i class="fas fa-file"></i> Laporan</a></li>

                <hr>

                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

            </ul>
        </div>

        <!-- CONTENT -->
        <div class="flex-fill p-4">
            <h4 class="mb-4">Pengajuan</h4>

            <!-- RINGKASAN -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6>Diterima</h6>
                                <!-- <h4 class="text-primary">
                                    Rp <?= number_format($ringkasan['totalKasMasuk']) ?>
                                </h4> -->
                            </div>
                            <i class="bi bi-arrow-down-circle fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6>Pending</h6>
                                <!-- <h4 class="text-success">
                                    Rp <?= number_format($ringkasan['saldo']) ?>
                                </h4> -->
                            </div>
                            <i class="bi bi-wallet2 fs-1 text-success"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6>Ditolak</h6>
                                <!-- <h4 class="text-success">
                                    Rp <?= number_format($ringkasan['saldo']) ?>
                                </h4> -->
                            </div>
                            <i class="bi bi-wallet2 fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ALERT -->
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

            <!-- FILTER -->
            <form method="GET" class="row g-2 mb-2 mt-3">

                <div class="col-md-4">
                    <label>Cari Pengajuan</label>
                    <input type="text" name="search" class="form-control"
                        value="<?= $_GET['search'] ?? '' ?>">
                </div>

                <div class="col-md-3">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control"
                        value="<?= $_GET['tanggal'] ?? '' ?>">
                </div>

                <div class="col-md-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="disetujui" <?= $status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak" <?= $status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary me-2">Filter</button>
                    <a href="pengajuan.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <!-- TABLE -->
            <div class="card">
                <div class="card-body">

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <!-- <th>Tanggal</th> -->
                                <th>Jumlah</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <!-- <th>Bukti</th> -->
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <?php foreach ($pengajuan as $p): ?>
                            <tr>
                                <td><?= $p['jumlah'] ?></td>
                                <td><?= $p['kategori'] ?></td>
                                <td>
                                    <?php if ($p['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($p['status'] == 'disetujui'): ?>
                                        <span class="badge bg-success">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#detail<?= $row['id_pengajuan'] ?>">
                                        Detail
                                    </button>
                                    <?php if ($p['status'] == 'pending'): ?>
                                        <a class="btn btn-success btn-sm" href="?acc=<?= $p['id_pengajuan'] ?>">Terima</a>
                                        <a class="btn btn-danger btn-sm" href="?tolak=<?= $p['id_pengajuan'] ?>">Tolak</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

        </div>


</body>

</html>