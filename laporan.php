<?php
session_start();
include 'config/app.php';

$search = $_GET['search'] ?? '';
$jenis = $_GET['jenis'] ?? '';

// FILTER
$where = "WHERE 1=1";

if ($search) {
    $search_escaped = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND keterangan LIKE '%$search_escaped%'";
}

if ($jenis) {
    $where .= " AND jenis='$jenis'";
}

// QUERY
$q = query("SELECT * FROM transaksi $where ORDER BY tanggal DESC");

// RINGKASAN
$qMasuk = mysqli_query($db, "SELECT SUM(jumlah) as total FROM transaksi WHERE jenis='masuk'");
$dMasuk = mysqli_fetch_assoc($qMasuk);
$masuk = $dMasuk['total'] ?? 0;

$qKeluar = mysqli_query($db, "SELECT SUM(jumlah) as total FROM transaksi WHERE jenis='keluar'");
$dKeluar = mysqli_fetch_assoc($qKeluar);
$keluar = $dKeluar['total'] ?? 0;

$saldo = $masuk - $keluar;

// CHART
$dataChart = query("
    SELECT jenis, SUM(jumlah) as total
    FROM transaksi
    GROUP BY jenis
");

$label = [];
$data = [];

foreach ($dataChart as $d) {
    $label[] = strtoupper($d['jenis']);
    $data[] = $d['total'];
}
?>

<!doctype html>
<html lang="en">

<head>
    <title>Laporan</title>
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
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'bendahara'): ?>
                    
                    <li><a class="nav-link" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
                    <li><a class="nav-link" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
                <?php endif; ?>

                <li><a class="nav-link" href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a></li>
                <li><a class="nav-link" href="statusbayar.php"><i class="fas fa-chart-bar"></i> Status Bayar</a></li>
                <li><a class="nav-link active" href="laporan.php"><i class="fas fa-file"></i> Laporan</a></li>

                <hr>

                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

            </ul>
        </div>

        <!-- CONTENT -->
        <div class="flex-fill p-4">

            <h4 class="mb-4">Laporan Kas</h4>

            <!-- RINGKASAN (SAMA STYLE) -->
            <div class="row g-3 mb-4 text-center">

                <div class="col-md-4">
                    <div class="card p-3">
                        <h6>Total Masuk</h6>
                        <h5>Rp <?= number_format($masuk) ?></h5>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3">
                        <h6>Total Keluar</h6>
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

            <!-- FILTER -->
            <form method="GET" class="card p-3 mb-4">
                <div class="row g-2">

                    <div class="col-md-8">
                        <small>Cari keterangan</small>
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search); ?>">
                    </div>

                    <div class="col-md-2">
                        <small>Jenis</small>
                        <select name="jenis" class="form-select">
                            <option value="">Semua</option>
                            <option value="masuk">Masuk</option>
                            <option value="keluar">Keluar</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100">Cari</button>
                    </div>

                </div>
            </form>

            <!-- TABEL -->
            <div class="card mb-4">
                <div class="card-body">

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Jenis</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($q as $d): ?>
                                <tr>
                                    <td><?= $d['tanggal'] ?></td>
                                    <td><?= $d['keterangan'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $d['jenis'] == 'masuk' ? 'success' : 'danger' ?>">
                                            <?= $d['jenis'] ?>
                                        </span>
                                    </td>
                                    <td>Rp <?= number_format($d['jumlah']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>

                </div>
            </div>

            <!-- CHART (SAMA POSISI) -->
            <!-- <div class="card">
                <div class="card-body">
                    <h6>Grafik Kas</h6>
                    <canvas id="chartKas" height="100"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script>
        new Chart(document.getElementById('chartKas'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($label) ?>,
                datasets: [{
                    label: 'Total',
                    data: <?= json_encode($data) ?>
                }]
            }
        });
    </script> -->

</body>

</html>