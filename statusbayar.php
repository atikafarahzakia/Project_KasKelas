<?php
session_start();
include 'config/app.php';

$target_kas = 240000; // semester

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// SEARCH
$where = "";
if ($search) {
    $search_escaped = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where = "WHERE murid.nama LIKE '%$search_escaped%'";
}

// QUERY UTAMA (SEMESTER)
$q = query("
    SELECT murid.nisn, nama, IFNULL(SUM(jumlah),0) as total
    FROM murid
    LEFT JOIN transaksi 
        ON murid.nisn = transaksi.nisn
        AND jenis='Masuk'
    $where
    GROUP BY murid.nisn
    ORDER BY murid.nama ASC
");

// DATA CHART
$dataChart = query("
    SELECT nama, IFNULL(SUM(jumlah),0) as total
    FROM murid
    LEFT JOIN transaksi 
        ON murid.nisn = transaksi.nisn
        AND jenis='Masuk'
    GROUP BY murid.nisn
");

$nama = [];
$total = [];

foreach ($dataChart as $d) {
    $nama[] = $d['nama'];
    $total[] = $d['total'];
}

$ringkasan = ringkasanStatusBayar($target_kas);

?>
<!doctype html>
<html lang="en">

<head>
    <title>Status Pembayaran</title>
    <meta charset="utf-8" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/style/style.css">

    <style>
        body {
            background: #f5f7fb;
        }

        .card {
            border-radius: 12px;
        }

        .progress {
            height: 18px;
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
                <li><a class="nav-link active" href="statusbayar.php"><i class="fas fa-chart-bar"></i> Status Bayar</a></li>
                <li><a class="nav-link" href="laporan.php"><i class="fas fa-file"></i> Laporan</a></li>

                <hr>

                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

            </ul>
        </div>

        <!-- CONTENT -->
        <div class="flex-fill p-4">

            <h4 class="mb-4">Status Pembayaran</h4>

            <div class="row mb-3">

                <div class="col-md-4">
                    <div class="card p-3 text-center">
                        <h6 class="text-success">Lunas</h6>
                        <h3><?= $ringkasan['lunas']; ?></h3>
                        <small>Siswa</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 text-center">
                        <h6 class="text-warning">Sebagian</h6>
                        <h3><?= $ringkasan['sebagian']; ?></h3>
                        <small>Siswa</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 text-center">
                        <h6 class="text-danger">Belum Bayar</h6>
                        <h3><?= $ringkasan['belum']; ?></h3>
                        <small>Siswa</small>
                    </div>
                </div>

            </div>

            <!-- FILTER -->
            <form method="GET" class="row g-2 mb-2 mt-3">
                <div class="row g-2">

                    <div class="col-md-5">
                        <small>Cari nama</small>
                        <input type="text" name="search" class="form-control"
                            value="<?= htmlspecialchars($search); ?>">
                    </div>

                    <div class="col-md-5">
                        <small>Status</small>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <option value="lunas" <?= $status_filter == 'lunas' ? 'selected' : '' ?>>Lunas</option>
                            <option value="sebagian" <?= $status_filter == 'sebagian' ? 'selected' : '' ?>>Sebagian</option>
                            <option value="belum_bayar" <?= $status_filter == 'belum_bayar' ? 'selected' : '' ?>>Belum Bayar</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary me-2">Filter</button>
                        <a href="kaskeluar.php" class="btn btn-secondary">Reset</a>
                    </div>

                </div>
            </form>

            <!-- TABEL -->
            <div class="card mb-4">
                <div class="card-body">

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Progress</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($q as $m): ?>

                                <?php
                                if ($m['total'] == 0) {
                                    $s = "Belum Bayar";
                                    $w = "danger";
                                } elseif ($m['total'] < $target_kas) {
                                    $s = "Sebagian";
                                    $w = "warning";
                                } else {
                                    $s = "Lunas";
                                    $w = "success";
                                }

                                if ($status_filter == 'lunas' && $s != 'Lunas') continue;
                                if ($status_filter == 'sebagian' && $s != 'Sebagian') continue;
                                if ($status_filter == 'belum_bayar' && $s != 'Belum Bayar') continue;

                                $persen = min(100, ($m['total'] / $target_kas) * 100);
                                ?>

                                <tr>
                                    <td><?= $m['nama']; ?>
                                        <br>
                                        <a href=""><small>view</small></a>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $w ?>"><?= $s ?></span>
                                    </td>

                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-<?= $w ?>"
                                                style="width: <?= $persen ?>%">
                                                <?= round($persen) ?>%
                                            </div>
                                        </div>
                                        <small>
                                            Rp <?= number_format($m['total']); ?> /
                                            Rp <?= number_format($target_kas); ?>
                                        </small>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>

                    </table>

                </div>
            </div>
        </div>
    </div>

    <!-- CHART -->
    <script>
        const ctx = document.getElementById('chartKas');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($nama) ?>,
                datasets: [{
                    label: 'Total Pembayaran',
                    data: <?= json_encode($total) ?>
                }]
            }
        });
    </script>

</body>

</html>