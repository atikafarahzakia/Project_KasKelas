<?php
session_start();
include 'config/app.php';

// ================= AMBIL NISN =================
$nisn = $_GET['nisn'] ?? 0;

// ================= DATA SISWA =================
$siswa = query("SELECT * FROM murid WHERE nisn='$nisn'");
$siswa = $siswa[0] ?? null;

// ================= SETTING =================
$kas_per_bulan = 20000;
$total_bulan = 12;
$target = $kas_per_bulan * $total_bulan;

// ================= TOTAL SEMESTER =================
$totalBayar = query("
    SELECT SUM(jumlah) as total 
    FROM transaksi 
    WHERE nisn='$nisn' AND jenis='masuk'
")[0]['total'] ?? 0;

$progress = ($target > 0) ? ($totalBayar / $target) * 100 : 0;

// ================= DATA PER BULAN =================
$dataBulan = [];

for ($i = 1; $i <= 12; $i++) {

    $total = query("
        SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE nisn='$nisn' 
        AND jenis='masuk'
        AND MONTH(tanggal)='$i'
    ")[0]['total'] ?? 0;

    $status = "Belum";
    $warna = "danger";

    if ($total > 0 && $total < $kas_per_bulan) {
        $status = "Sebagian";
        $warna = "warning";
    } elseif ($total >= $kas_per_bulan) {
        $status = "Lunas";
        $warna = "success";
    }

    $dataBulan[] = [
        'bulan' => date('F', mktime(0, 0, 0, $i, 1)),
        'total' => $total,
        'status' => $status,
        'warna' => $warna
    ];
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Status Pembayaran</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .card {
            border-radius: 12px;
        }

        .card-bulan {
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .card-bulan:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .card-bulan:hover .badge {
            transform: scale(1.05);
        }

        .card-bulan .badge {
            transition: 0.2s;
        }

        /* SIDEBAR SAMA */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            flex-shrink: 0;
            /* 🔥 ini yang bikin dia gak mengecil */
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
                    <li><a class="nav-link" href="pengajuan.php"><i class="fa-solid fa-clock"></i>Pengajuan</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'bendahara'): ?>
                    <li><a class="nav-link" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
                    <li><a class="nav-link" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
                <?php endif; ?>

                <li><a class="nav-link" href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a></li>
                <li><a class="nav-link active" href="statusbayar.php"><i class="fa-solid fa-chart-column"></i> Status Bayar</a></li>
                <li><a class="nav-link" href="laporan.php"><i class="fas fa-file"></i> Laporan</a></li>

                <hr>

                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>

        </div>

        <!-- MAIN -->
        <div class="flex-fill p-4">

            <h4 class="mb-4">Status Pembayaran</h4>

            <a href="statusbayar.php" class="btn btn-secondary mb-3">Kembali</a>

            <!-- CARD UTAMA -->
            <div class="card p-4 mb-4">

                <h3><?= $siswa['nama'] ?? '-' ?></h3>

                <h6>Total Kas Masuk <small class="text-muted">Semester</small></h6>

                <!-- PROGRESS -->
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-primary" style="width: <?= $progress ?>%"></div>
                </div>

                <small>
                    Terkumpul Rp. <?= number_format($totalBayar) ?> / <?= number_format($target) ?>
                </small>

            </div>

            <!-- GRID BULAN -->
            <div class="row g-3">

                <?php foreach ($dataBulan as $b): ?>
                    <div class="col-md-2">
                        <div class="card card-bulan text-center p-2 <?= ($b['status'] == 'Belum') ? 'bg-light' : '' ?>">
                            <small><?= $b['bulan'] ?></small>

                            <div>
                                <?= "Rp. " . number_format($b['total']) . " / " . number_format($kas_per_bulan) ?>
                            </div>

                            <span class="badge bg-<?= $b['warna'] ?> mt-2">
                                <?= $b['status'] ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

        </div>
    </div>
</body>

</html>