<?php
session_start();
include 'config/app.php';

$search = $_GET['search'] ?? '';
$jenis = $_GET['jenis'] ?? '';

// ================= FILTER =================
$where = "WHERE 1=1";

if ($search) {
    $search_escaped = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND keterangan LIKE '%$search_escaped%'";
}

if ($jenis) {
    $where .= " AND jenis='$jenis'";
}

// ================= QUERY =================
$q = query("SELECT * FROM transaksi $where ORDER BY tanggal DESC");

// ================= PAGINATION KAS MASUK =================
$limit = 10;
$page = (int)($_GET['page'] ?? 1);
$start = ($page - 1) * $limit;

$dataMasukPaging = query("
    SELECT * FROM transaksi
    WHERE jenis='masuk'
    ORDER BY tanggal DESC
    LIMIT $start, $limit
");

// total halaman
$totalData = query("SELECT COUNT(*) as total FROM transaksi WHERE jenis='masuk'")[0]['total'];
$totalPage = ceil($totalData / $limit);

// ================= RINGKASAN =================
$masuk = query("SELECT IFNULL(SUM(jumlah),0) as total FROM transaksi WHERE jenis='masuk'")[0]['total'];
$keluar = query("SELECT IFNULL(SUM(jumlah),0) as total FROM transaksi WHERE jenis='keluar'")[0]['total'];
$saldo = $masuk - $keluar;

// ================= GROUP PER BULAN =================
$groupMasuk = [];
$groupKeluar = [];

foreach ($q as $d) {
    $bulan = date('F Y', strtotime($d['tanggal']));

    if ($d['jenis'] == 'masuk') {
        $groupMasuk[$bulan][] = $d;
    } else {
        $groupKeluar[$bulan][] = $d;
    }
}

?>

<!doctype html>
<html lang="en">

<head>
    <title>Laporan</title>
    <meta charset="utf-8" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
        }

        .card {
            border-radius: 12px;
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
                    <li><a class="nav-link" href="pengajuan.php"><i class="fa-solid fa-clock"></i>Pengajuan</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'bendahara'): ?>
                    <li><a class="nav-link" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
                    <li><a class="nav-link" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
                <?php endif; ?>

                <li><a class="nav-link" href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a></li>
                <li><a class="nav-link" href="statusbayar.php"><i class="fa-solid fa-chart-column"></i> Status Bayar</a></li>
                <li><a class="nav-link active" href="laporan.php"><i class="fas fa-file"></i> Laporan</a></li>

                <hr>
                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>


        <!-- CONTENT -->
        <div class="flex-fill p-4">

            <h4 class="mb-4">Laporan Kas</h4>

            <!-- RINGKASAN -->
            <div class="row mb-4 text-center">
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
            <form method="GET" class="row g-2 mb-2 mt-3">

                <div class="col-md-6">
                    <label for="">Cari Laporan</label>
                    <input type="text" name="search" class="form-control"value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-3">
                    <label for="">Jenis Kas</label>
                    <select name="jenis" class="form-select">
                        <option value="">Semua</option>
                        <option value="masuk">Masuk</option>
                        <option value="keluar">Keluar</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary me-2">Filter</button>
                    <a href="laporan.php" class="btn btn-secondary">Reset</a>
                </div>

            </form>

            <div class="row">

                <!-- ================= KAS MASUK (PAGINATION) ================= -->
                <div class="col-md-6">
                    <div class="card p-3">
                        <h5 class="text-success">Kas Masuk</h5>

                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (!empty($dataMasukPaging)): ?>
                                    <?php foreach ($dataMasukPaging as $d): ?>
                                        <tr>
                                            <td><?= $d['tanggal'] ?></td>
                                            <td><?= $d['keterangan'] ?></td>
                                            <td>Rp <?= number_format($d['jumlah']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <!-- PAGINATION -->
                        <nav>
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $totalPage; $i++): ?>
                                    <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                                        <a class="page-link"
                                            href="?page=<?= $i ?>&search=<?= $search ?>&jenis=<?= $jenis ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>

                    </div>
                </div>

                <!-- ================= KAS KELUAR (GROUP BULAN) ================= -->
                <div class="col-md-6">
                    <div class="card p-3">
                        <h5 class="text-danger">Kas Keluar</h5>

                        <?php if (!empty($groupKeluar)): ?>
                            <?php foreach ($groupKeluar as $bulan => $items): ?>
                                <h6 class="mt-3"><?= $bulan ?></h6>
                                <table class="table table-sm">
                                    <?php
                                    $total = 0;
                                    foreach ($items as $d):
                                        $total += $d['jumlah'];
                                    ?>
                                        <tr>
                                            <td><?= $d['tanggal'] ?></td>
                                            <td><?= $d['keterangan'] ?></td>
                                            <td>Rp <?= number_format($d['jumlah']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="fw-bold">
                                        <td colspan="2">Total</td>
                                        <td>Rp <?= number_format($total) ?></td>
                                    </tr>
                                </table>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada data</p>
                        <?php endif; ?>

                    </div>
                </div>

            </div>

        </div>
    </div>

</body>

</html>