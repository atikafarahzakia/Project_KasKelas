<?php
session_start();
include 'config/app.php';

$bulan = $_GET['bulan'] ?? '';
$tahun = $_GET['tahun'] ?? '';

$dataMasuk = [];
$dataKeluar = [];
$masuk = 0;
$keluar = 0;
$saldo = 0;

if ($bulan && $tahun) {

    // ================= RINGKASAN =================
    $qMasuk = mysqli_query($db, "SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='masuk' AND bulan='$bulan' AND tahun='$tahun'");
    $masuk = mysqli_fetch_assoc($qMasuk)['total'] ?? 0;

    $qKeluar = mysqli_query($db, "SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='keluar' AND bulan='$bulan' AND tahun='$tahun'");
    $keluar = mysqli_fetch_assoc($qKeluar)['total'] ?? 0;

    $saldo = $masuk - $keluar;

    // ================= DATA =================
    $dataMasuk = query("SELECT * FROM transaksi 
        WHERE jenis='masuk' AND bulan='$bulan' AND tahun='$tahun'
        ORDER BY tanggal DESC, id_transaksi DESC");

    $dataKeluar = query("SELECT * FROM transaksi 
        WHERE jenis='keluar' AND bulan='$bulan' AND tahun='$tahun'
        ORDER BY tanggal DESC, id_transaksi DESC");

    // ================= CHART =================
    $label = ['MASUK', 'KELUAR'];
    $dataChartFix = [$masuk, $keluar];
}
?>

<!doctype html>
<html lang="en">

<head>
    <title>Arus Kas</title>
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

        .chart-card {
            height: 350px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

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
            <h4 class="text-center">Kas Kelas</h4>

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
                    <li><a class="nav-link" href="pengajuan.php"><i class="fa-solid fa-clock"></i>Pengajuan</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'bendahara'): ?>
                    <li><a class="nav-link" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
                    <li><a class="nav-link" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
                <?php endif; ?>

                <li><a class="nav-link active" href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a></li>
                <li><a class="nav-link" href="statusbayar.php"><i class="fa-solid fa-chart-column"></i> Status Bayar</a></li>
                <li><a class="nav-link" href="laporan.php"><i class="fas fa-file"></i> Laporan</a></li>

                <hr>
                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- CONTENT -->
        <div class="flex-fill p-4">

            <h4 class="mb-4">Arus Kas</h4>

            <!-- FILTER -->
            <form method="GET" class="card p-3 mb-4">
                <div class="row">

                    <div class="col-md-4">
                        <label>Bulan</label>
                        <select name="bulan" class="form-control">
                            <option value="">-- Pilih Bulan --</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= ($bulan == $i ? 'selected' : '') ?>>
                                    <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Tahun</label>
                        <select name="tahun" class="form-control">
                            <option value="">-- Pilih Tahun --</option>
                            <?php for ($t = date('Y'); $t >= date('Y') - 5; $t--): ?>
                                <option value="<?= $t ?>" <?= ($tahun == $t ? 'selected' : '') ?>>
                                    <?= $t ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Filter</button>
                    </div>

                </div>
            </form>

            <?php if ($bulan && $tahun): ?>

                <!-- RINGKASAN -->
                <div class="row text-center mb-4">
                    <div class="col-md-4">
                        <div class="card p-3">
                            <h6 class="text-success">Kas Masuk</h6>
                            <h5>Rp <?= number_format($masuk) ?></h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-3">
                            <h6 class="text-danger">Kas Keluar</h6>
                            <h5>Rp <?= number_format($keluar) ?></h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-3">
                            <h6>Saldo</h6>
                            <h5>Rp <?= number_format($saldo) ?></h5>
                        </div>
                    </div>
                </div>

                <!-- CHART -->
                <div class="row mb-4">

                    <!-- BAR CHART -->
                    <div class="col-md-6">
                        <div class="card p-3 chart-card">
                            <h6>Grafik Bar</h6>
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>

                    <!-- PIE CHART -->
                    <div class="col-md-6">
                        <div class="card p-3 chart-card text-center">
                            <h6>Perbandingan</h6>
                            <div style="max-width:220px; margin:auto;">
                                <canvas id="pieChart"></canvas>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- TABEL SAMPINGAN -->
                <div class="row">

                    <!-- KAS MASUK -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="text-success">Kas Masuk</h6>

                                <?php if ($dataMasuk): ?>
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Keterangan</th>
                                                <th>Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dataMasuk as $d): ?>
                                                <tr>
                                                    <td><?= $d['tanggal'] ?></td>
                                                    <td><?= $d['keterangan'] ?></td>
                                                    <td>Rp <?= number_format($d['jumlah']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada data</p>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <!-- KAS KELUAR -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="text-danger">Kas Keluar</h6>

                                <?php if ($dataKeluar): ?>
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Keterangan</th>
                                                <th>Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dataKeluar as $d): ?>
                                                <tr>
                                                    <td><?= $d['tanggal'] ?></td>
                                                    <td><?= $d['keterangan'] ?></td>
                                                    <td>Rp <?= number_format($d['jumlah']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted">Tidak ada data</p>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                </div>

            <?php else: ?>
                <div class="alert alert-info text-center">
                    Silakan pilih bulan & tahun dulu
                </div>
            <?php endif; ?>

        </div>
    </div>

    <?php if ($bulan && $tahun): ?>
        <script>
            new Chart(document.getElementById('barChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($label) ?>,
                    datasets: [{
                        label: 'Total',
                        data: <?= json_encode($dataChartFix) ?>
                    }]
                }
            });

            new Chart(document.getElementById('pieChart'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode($label) ?>,
                    datasets: [{
                        data: <?= json_encode($dataChartFix) ?>
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true
                }
            });
        </script>
    <?php endif; ?>

</body>

</html>