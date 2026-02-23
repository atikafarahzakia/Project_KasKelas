<?php
session_start();
if (!isset($_SESSION['login']))
    header("location:login.php");
include 'config/app.php';

$kas_wajib = 20000;
$q = query("
    SELECT murid.id_murid, murid.nama,
           IFNULL(SUM(transaksi.jumlah), 0) AS total
    FROM murid
    LEFT JOIN transaksi 
        ON murid.id_murid = transaksi.id_murid
        AND transaksi.jenis = 'Masuk'
        AND MONTH(transaksi.tanggal) = MONTH(CURDATE())
    GROUP BY murid.id_murid
");

$masukBulanIni  = kasMasukBulanIni();
$keluarBulanIni = kasKeluarBulanIni();
$arusBulanIni   = arusKasBulanIni();
$masuk  = ringkasanKasMasuk();
$keluar = ringkasanKasKeluar();
?>

<!doctype html>
<html lang="en">

<head>
    <title>Dashboard</title>
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
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>

                    <li class="nav-item mt-4">
                        <small class="ms-3">Menu Utama</small>
                    </li>
                    <?php if ($_SESSION['role'] == 'Bendahara'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="datamurid.php">Data Murid</a>
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
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="statusbayar.php">Status Bayar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php">Laporan</a>
                    </li>
                    <hr>
                    <li class="nav-item" id="logout">
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
                <h3>Selamat datang, <?= $_SESSION['username']; ?></h3>
                <div class="jumlah-saldo">
                    <div class="card mt-4">
                        <card class="card-body">
                            <h3>Total Saldo Kas</h3>
                            <!-- ambil dari database -->
                            <h5>Rp.<?= number_format(getSaldo()); ?></h5>
                            <hr>
                            <p>
                                Kas Bulan Ini:
                                <strong class="<?= $arusBulanIni < 0 ? 'text-danger' : 'text-success'; ?>">
                                    Rp <?= number_format($arusBulanIni, 0, ',', '.'); ?>
                                </strong>
                            </p>
                        </card>
                    </div>
                </div>

                <!-- ringkasan -->
                <div class="mt-4">
                    <h3>Ringkasan</h3>
                    <div class="row g-4 text-center">
                        <!-- 1 -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body border border-success rounded d-flex flex-column justify-content-center shadow">
                                    <h5>Kas Masuk</h5>
                                    <h6 class="mb-0 fw-bold">
                                        Rp <?= number_format($masukBulanIni, 0, ',', '.'); ?>
                                    </h6>
                                    <small class="text-muted">Bulan ini</small>
                                </div>
                            </div>
                        </div>
                        <!-- 2 -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body border border-danger rounded d-flex flex-column justify-content-center shadow">
                                    <h5>Kas Keluar</h5>
                                    <h6 class="mb-0 fw-bold">
                                        Rp <?= number_format($keluarBulanIni, 0, ',', '.'); ?>
                                    </h6>
                                    <small class="text-muted">Bulan ini</small>

                                </div>
                            </div>
                        </div>
                        <!-- 3 -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body border border-info rounded d-flex flex-column justify-content-center shadow">
                                    <h5>Arus Kas</h5>
                                    <h6 class="mb-0 fw-bold <?= $arusBulanIni < 0 ? 'text-danger' : 'text-primary'; ?>">
                                        Rp <?= number_format($arusBulanIni, 0, ',', '.'); ?>
                                    </h6>
                                    <small class="text-muted">Bulan ini</small>
                                </div>
                            </div>
                        </div>
                        <!-- 4 -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body border border-warning rounded d-flex flex-column justify-content-center shadow">
                                    <h5>Data Murid</h5>
                                    <h6 class="mb-0 text-muted"><?= number_format(dataSiswa()); ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- grafik -->

                <!-- status pembayaran -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5>Status Pembayaran</h5>
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
                                    <th scope="col">Nama Siswa</th>
                                    <th scope="col">Total Bayar</th>
                                    <th scope="col">Status</th>
                                    <?php if ($_SESSION['role'] == 'bendahara'): ?>
                                        <th scope="col">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($m = mysqli_fetch_assoc($q)):
                                    if ($m['total'] == 0) {
                                        $s = "Belum Bayar";
                                        $w = "danger";
                                    } elseif ($m['total'] < $kas_wajib) {
                                        $s = "Sebagian";
                                        $w = "warning";
                                    } else {
                                        $s = "Lunas";
                                        $w = "success";
                                    }
                                ?>
                                    <tr>
                                        <td><?= $m['nama']; ?></td>
                                        <td>Rp <?= number_format($m['total']); ?></td>
                                        <td><span class="badge bg-<?= $w ?>"><?= $s ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
</body>

</html>