<?php
session_start();
include 'config/app.php';

// Search logic
$search = isset($_GET['search']) ? $_GET['search'] : '';
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '';

// Cek apakah kolom kategori ada
$hasKategoriColumn = mysqli_num_rows(query("SHOW COLUMNS FROM transaksi LIKE 'id_kategori'")) > 0;

// Ambil data ringkasan dari function di app.php
$masuk = ringkasanKasMasuk();
$keluar = ringkasanKasKeluar();
$totalSiswa = dataSiswa();

$totalMasuk = $masuk['totalKasMasuk'];
$totalKeluar = $keluar['totalKeluar'];
$saldoAwal = 0;
$saldoAkhir = $keluar['saldo'];
?>


<!doctype html>
<html lang="en">

<head>
    <title>Laporan</title>
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
    <!-- sidebar -->
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
                    <?php if ($_SESSION['role'] == 'bendahara'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="datamurid.php">Data Murid</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kasmasuk.php">Kas Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kaskeluar.php">Kas Keluar</a>
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
                        <a class="nav-link active" href="laporan.php">Laporan</a>
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
            <div class="p-4 flex-grow-1">
                <!-- ringkasan -->
                <div class="mb-4">
                    <h3>Ringkasan</h3>
                    <div class="row g-4 text-center">
                        <!-- 1 -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body border border-success rounded d-flex flex-column justify-content-center shadow">
                                    <h6 class="text-muted mb-1">Total Kas Masuk</h6>
                                    <h5 class="fw-bold mb-0">Rp <?= number_format($totalMasuk, 0, ',', '.'); ?></h5>
                                </div>
                            </div>
                        </div>
                        <!-- 2 -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body border border-danger rounded d-flex flex-column justify-content-center shadow">
                                    <h6 class="text-muted mb-1">Total Kas Keluar</h6>
                                    <h5 class="fw-bold mb-0">Rp <?= number_format($totalKeluar, 0, ',', '.'); ?></h5>
                                </div>
                            </div>
                        </div>
                        <!-- 3 -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body border border-info rounded d-flex flex-column justify-content-center shadow">
                                    <h6 class="text-muted mb-1">Saldo Akhir</h6>
                                    <h5 class="fw-bold mb-0">Rp <?= number_format($saldoAkhir, 0, ',', '.'); ?></h5>
                                </div>
                            </div>
                        </div>
                        <!-- 4 -->
                        <div class="col-md-3">
                            <div class="card h-100">
                                <div class="card-body border border-warning rounded d-flex flex-column justify-content-center shadow">
                                    <h6 class="text-muted mb-1">Siswa Aktif</h6>
                                    <h5 class="fw-bold mb-0"><?= $totalSiswa; ?> Siswa</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- grafik -->

                <!-- status pembayaran -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5>Report</h5>
                        <hr>
                        <div class="row">
                            <!-- cari -->
                            <div class="col">
                                <form method="GET" id="searchForm">
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <small class="text-muted d-block mb-2">Cari berdasarkan data</small>
                                            <input type="text" name="search" class="form-control" placeholder="Cari..." id="searchInput" value="<?= htmlspecialchars($search ?? ''); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted d-block mb-2">Filter berdasarkan jenis</small>
                                            <select name="jenis" class="form-select">
                                                <option value="">Semua Jenis</option>
                                                <option value="masuk" <?= $jenis == 'masuk' ? 'selected' : '' ?>>Kas Masuk</option>
                                                <option value="keluar" <?= $jenis == 'keluar' ? 'selected' : '' ?>>Kas Keluar</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted d-block mb-2">Bulan</small>
                                            <select name="bulan" class="form-select">
                                                <option value="">Semua</option>
                                                <option value="1" <?= $bulan == 1 ? 'selected' : '' ?>>Januari</option>
                                                <option value="2" <?= $bulan == 2 ? 'selected' : '' ?>>Februari</option>
                                                <option value="3" <?= $bulan == 3 ? 'selected' : '' ?>>Maret</option>
                                                <option value="4" <?= $bulan == 4 ? 'selected' : '' ?>>April</option>
                                                <option value="5" <?= $bulan == 5 ? 'selected' : '' ?>>Mei</option>
                                                <option value="6" <?= $bulan == 6 ? 'selected' : '' ?>>Juni</option>
                                                <option value="7" <?= $bulan == 7 ? 'selected' : '' ?>>Juli</option>
                                                <option value="8" <?= $bulan == 8 ? 'selected' : '' ?>>Agustus</option>
                                                <option value="9" <?= $bulan == 9 ? 'selected' : '' ?>>September</option>
                                                <option value="10" <?= $bulan == 10 ? 'selected' : '' ?>>Oktober</option>
                                                <option value="11" <?= $bulan == 11 ? 'selected' : '' ?>>November</option>
                                                <option value="12" <?= $bulan == 12 ? 'selected' : '' ?>>Desember</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted d-block mb-2">Tahun</small>
                                            <input type="number" name="tahun" class="form-control" placeholder="Tahun" value="<?= htmlspecialchars($tahun ?? ''); ?>" min="2000">
                                        </div>
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary w-100">Cari</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- tabel -->
                        <table class="table table-hover table-striped border mt-3">
                            <thead>
                                <tr>
                                    <th scope="col">Tanggal</th>
                                    <th scope="col">Jenis</th>
                                    <?php if ($hasKategoriColumn): ?>
                                        <th scope="col">Kategori</th>
                                    <?php endif; ?>
                                    <th scope="col">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $where = "1=1";

                                if ($jenis) {
                                    $where .= " AND transaksi.jenis = '$jenis'";
                                }

                                if ($bulan && $tahun) {
                                    $where .= " AND MONTH(transaksi.tanggal) = $bulan AND YEAR(transaksi.tanggal) = $tahun";
                                } elseif ($tahun) {
                                    $where .= " AND YEAR(transaksi.tanggal) = $tahun";
                                } elseif ($bulan) {
                                    $where .= " AND MONTH(transaksi.tanggal) = $bulan";
                                }

                                if ($hasKategoriColumn) {
                                    $query = mysqli_query($db, "
                                        SELECT transaksi.tanggal, transaksi.jenis, transaksi.jumlah, kategori.nama AS kategori
                                        FROM transaksi
                                        LEFT JOIN kategori ON transaksi.id_kategori = kategori.id_kategori
                                        WHERE $where
                                        ORDER BY transaksi.tanggal DESC
                                    ");
                                } else {
                                    $query = mysqli_query($db, "
                                        SELECT tanggal, jenis, jumlah
                                        FROM transaksi
                                        WHERE $where
                                        ORDER BY tanggal DESC
                                    ");
                                }

                                $jumlahData = mysqli_num_rows($query);
                                $colspan = $hasKategoriColumn ? '4' : '3';
                                if ($jumlahData == 0) {
                                    echo '<tr><td colspan="' . $colspan . '" class="text-center text-muted py-4">Tidak ada data</td></tr>';
                                } else {
                                    while ($row = mysqli_fetch_assoc($query)) {
                                ?>
                                        <tr>
                                            <td><?= $row['tanggal']; ?></td>
                                            <td>
                                                <span class="badge <?= ($row['jenis'] == 'masuk') ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= ucfirst($row['jenis']); ?>
                                                </span>
                                            </td>
                                            <?php if ($hasKategoriColumn): ?>
                                                <td><?= $row['kategori'] ?? '-'; ?></td>
                                            <?php endif; ?>
                                            <td><?= number_format($row['jumlah']); ?></td>
                                        </tr>
                                <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <footer class="border-top py-2 text-center text-muted small">
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
        // Auto-reset search ketika input kosong
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                if (this.value === '') {
                    window.location.href = 'laporan.php';
                }
            });
        }
    </script>
</body>

</html>