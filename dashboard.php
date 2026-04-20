<?php
session_start();
if (!isset($_SESSION['login']))
    header("location:login.php");

include 'config/app.php';

// Ringkasan
$ringkasan = ringkasanKasMasuk();
$masukBulanIni = kasMasukBulanIni();
$keluarBulanIni = kasKeluarBulanIni();
$totalsaldo = totalsaldo();

// QUERY AKTIVITAS TERBARU
$historiMasuk = query("SELECT t.*, m.nama FROM transaksi t 
                       JOIN murid m ON t.nisn = m.nisn 
                       WHERE t.jenis='masuk' 
                       ORDER BY t.tanggal DESC LIMIT 5");

$historiKeluar = query("SELECT * FROM transaksi 
                        WHERE jenis='keluar' 
                        ORDER BY tanggal DESC LIMIT 5");

$target_kas = 7920000; // semester

$kas_wajib = 20000; // sesuaikan per bulan / semester
$statusBayar = ringkasanStatusBayar($kas_wajib);

$lunas = $statusBayar['lunas'];
$sebagian = $statusBayar['sebagian'];
$belum = $statusBayar['belum'];

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';


// SEARCH
$where = "";
if ($search) {
    $search_escaped = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where = "WHERE murid.nama LIKE '%$search_escaped%'";
}

// QUERY UTAMA
$q = query("
    SELECT murid.nisn, nama, IFNULL(SUM(jumlah),0) as total
    FROM murid
    LEFT JOIN transaksi 
        ON murid.nisn = transaksi.nisn
        AND jenis='masuk'
        AND MONTH(tanggal) = MONTH(CURDATE())
        AND YEAR(tanggal) = YEAR(CURDATE())
    $where
    GROUP BY murid.nisn
    ORDER BY murid.nama ASC
");

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Dashboard</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
                <li><a class="nav-link active" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

                <?php if ($_SESSION['role'] == 'wali kelas'): ?>
                    <li><a class="nav-link" href="datamurid.php"><i class="fas fa-users"></i> Data Murid</a></li>
                    <li><a class="nav-link" href="pengajuan.php"><i class="fa-solid fa-clock"></i>Pengajuan</a></li>
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

            <h4 class="mb-4">Selamat Datang, <?= $_SESSION['username']; ?> </h4>

            <!-- RINGKASAN -->
            <div class="row mt-4 g-3 text-center">

                <div class="col-md-3">
                    <div class="card summary-card">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-success">Saldo Kas</h6>
                                <h5>Rp <?= number_format($totalsaldo); ?></h5>
                            </div>
                            <i class="bi bi-wallet2 fs-1 text-success"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card summary-card">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-primary">Kas Masuk</h6>
                                <h5>Rp <?= number_format($masukBulanIni); ?></h5>

                            </div>
                            <i class="bi bi-arrow-down-circle fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card summary-card">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-danger">Kas Keluar</h6>
                                <h5>Rp <?= number_format($keluarBulanIni); ?></h5>
                            </div>
                            <i class="bi bi-arrow-up-circle fs-1 text-danger"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card summary-card">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-info">Total Siswa</h6>
                                <h5><?= dataSiswa(); ?></h5>
                            </div>
                            <i class="bi bi-people fs-1 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TARGET KAS  -->
            <?php
            $persen = ($totalsaldo / $target_kas) * 100;
            if ($persen > 100) $persen = 100;
            ?>

            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="fw-bold">Progress Target Kas</h6>
                    <p class="small text-muted">Target: Rp <?= number_format($target_kas) ?></p>

                    <div class="progress">
                        <div class="progress-bar bg-success"
                            style="width: <?= $persen ?>%">
                            <?= round($persen) ?>%
                        </div>
                    </div>
                </div>
            </div>

            <!-- AKTIVITAS RIWAYAT KAS MASUK -->
            <div class="row mt-2 g-3">
                <!-- <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold"><i class="fas fa-history text-success"></i> Riwayat Kas Masuk</h6>
                                <a href="kasmasuk.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover table-borderless align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Siswa</th>
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($historiMasuk)): ?>
                                            <tr>
                                                <td colspan="2" class="text-center text-muted small">Belum ada data</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($historiMasuk as $hm): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold small"><?= htmlspecialchars($hm['nama']) ?></div>
                                                    <small class="text-muted"><?= date('d/m/Y', strtotime($hm['tanggal'])) ?></small>
                                                </td>
                                                <td class="text-success fw-bold small">+Rp <?= number_format($hm['jumlah']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- AKTIVITAS RIWAYAT KAS KELUAR -->
                <!-- <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold"><i class="fas fa-history text-danger"></i> Riwayat Kas Keluar</h6>
                                <a href="kaskeluar.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover table-borderless align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Keterangan</th>
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($historiKeluar)): ?>
                                            <tr>
                                                <td colspan="2" class="text-center text-muted small">Belum ada data</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($historiKeluar as $hk): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold small"><?= htmlspecialchars($hk['keterangan']) ?></div>
                                                    <small class="text-muted"><?= date('d/m/Y', strtotime($hk['tanggal'])) ?></small>
                                                </td>
                                                <td class="text-danger fw-bold small">-Rp <?= number_format($hk['jumlah']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div> -->


                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold"><i class="fas fa-history"></i> Perbandingan Kas Masuk & Keluar</h6>
                                <a href="aruskas.php" class="btn btn-outline-primary btn-sm">lihat Detail</a>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="w-30">
                                    <canvas id="chartKasBulat"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold"><i class="fas fa-history"></i> Perbandingan Status Bayar Siswa</h6>
                                <a href="statusbayar.php" class="btn btn-outline-primary btn-sm">Lihat Detail</a>
                            </div>
                            <div class="d-flex justify-content-center">
                                <div class="w-30">
                                    <canvas id="chartStatus" style="max-width:300px; margin:auto;"></canvas>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="badge bg-success">Lunas: <?= $lunas ?></span>
                                <span class="badge bg-warning text-dark">Sebagian: <?= $sebagian ?></span>
                                <span class="badge bg-danger">Belum: <?= $belum ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                <script>
                    // 1
                    const ctx = document.getElementById('chartKasBulat');
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Kas Masuk', 'Kas Keluar'],
                            datasets: [{
                                data: [<?= $masukBulanIni ?>, <?= $keluarBulanIni ?>],
                                backgroundColor: [
                                    '#198754', // hijau
                                    '#dc3545' // merah
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });

                    // 2
                    const ctxStatus = document.getElementById('chartStatus');
                    new Chart(ctxStatus, {
                        type: 'pie',
                        data: {
                            labels: ['Lunas', 'Sebagian', 'Belum'],
                            datasets: [{
                                data: [<?= $lunas ?>, <?= $sebagian ?>, <?= $belum ?>],
                                backgroundColor: [
                                    '#198754', // hijau
                                    '#ffc107', // kuning
                                    '#dc3545' // merah
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                </script>


            </div>
        </div>
    </div>
</body>

</html>