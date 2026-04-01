<?php
session_start();
if (!isset($_SESSION['login']))
    header("location:login.php");
include 'config/app.php';

$kas_wajib = 20000;

// Hitungan ringkasan (hindari undefined variable)
$masukBulanIni = kasMasukBulanIni();
$keluarBulanIni = kasKeluarBulanIni();
$arusBulanIni = arusKasBulanIni();

// Cek apakah kolom kategori ada
$hasKategoriColumn = mysqli_num_rows(query("SHOW COLUMNS FROM transaksi LIKE 'id_kategori'")) > 0;

// Logika untuk search dan filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

if ($search) {
    $q = searchMurid($search);
} else {
    $q = query("
        SELECT murid.id_murid, murid.nama,
               IFNULL(SUM(transaksi.jumlah), 0) AS total
        FROM murid
        LEFT JOIN transaksi
            ON murid.id_murid = transaksi.id_murid
            AND transaksi.jenis = 'Masuk'
            AND MONTH(transaksi.tanggal) = MONTH(CURDATE())
        GROUP BY murid.id_murid
        ORDER BY murid.id_murid DESC
    ");
}

// Hitung kategori kas jika kolom kategori ada
$kategoriKasMasuk = 0;
$kategoriKasKeluar = 0;
if ($hasKategoriColumn) {
    $kategoriData = query("
        SELECT jenis, COUNT(DISTINCT id_kategori) as jumlah
        FROM transaksi
        WHERE id_kategori IS NOT NULL AND id_kategori != 0
        GROUP BY jenis
    ");
} else {
    $kategoriData = null;
}

// Filter berdasarkan status jika ada
$filtered_data = [];
if ($status_filter) {
    while ($m = mysqli_fetch_assoc($q)) {
        $total = $m['total'];
        $status = '';
        if ($total == 0) {
            $status = 'belum_bayar';
        } elseif ($total < $kas_wajib) {
            $status = 'sebagian';
        } else {
            $status = 'lunas';
        }

        if ($status === $status_filter) {
            $filtered_data[] = $m;
        }
    }
    $q = $filtered_data;
} else {
    // Jika tidak ada filter, ambil semua data
    $all_data = [];
    mysqli_data_seek($q, 0);
    while ($m = mysqli_fetch_assoc($q)) {
        $all_data[] = $m;
    }
    $q = $all_data;
}

// Hitung kategori kas
if ($kategoriData) {
    while ($row = mysqli_fetch_assoc($kategoriData)) {
        if ($row['jenis'] === 'masuk') $kategoriKasMasuk = $row['jumlah'];
        if ($row['jenis'] === 'keluar') $kategoriKasKeluar = $row['jumlah'];
    }
}
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
                            <hr>
                            <!-- ambil dari database -->
                            <h5>Rp.<?= number_format(getSaldo()); ?></h5>
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
                                    <h6 class="mb-0 fw-bold mb-3">
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
                                    <h6 class="mb-0 fw-bold mb-3">
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
                                    <h6 class="mb-0 fw-bold mb-3 <?= $arusBulanIni < 0 ? 'text-danger' : 'text-primary'; ?>">
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
                                    <small class="text-muted">Total Keseluruhan</small>
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
                            <div class="col-12">
                                <form method="GET" id="filterForm">
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <small class="text-muted d-block mb-1">Cari berdasarkan nama siswa</small>
                                            <input type="text" name="search" class="form-control" placeholder="Cari nama siswa..." id="searchInput" value="<?= htmlspecialchars($search ?? ''); ?>">
                                        </div>
                                        <div class="col-md-5">
                                            <small class="text-muted d-block mb-1">Filter status pembayaran</small>
                                            <select name="status" class="form-select" id="statusFilter">
                                                <option value="">Semua Status</option>
                                                <option value="lunas" <?= $status_filter == 'lunas' ? 'selected' : '' ?>>Lunas</option>
                                                <option value="sebagian" <?= $status_filter == 'sebagian' ? 'selected' : '' ?>>Sebagian</option>
                                                <option value="belum_bayar" <?= $status_filter == 'belum_bayar' ? 'selected' : '' ?>>Belum Bayar</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
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
                                    <th scope="col">Nama Siswa</th>
                                    <th scope="col">Total Bayar</th>
                                    <th scope="col">Status</th>
                                    <?php if ($_SESSION['role'] == 'bendahara'): ?>
                                        <th scope="col">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $jumlahData = count($q);

                                if ($jumlahData == 0) {
                                    $colspan = $_SESSION['role'] == 'bendahara' ? '4' : '3';
                                    echo '<tr><td colspan="' . $colspan . '" class="text-center text-muted py-4">Tidak ada data</td></tr>';
                                } else {
                                    foreach ($q as $m):
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
                                            <?php if ($_SESSION['role'] == 'bendahara'): ?>
                                                <td>
                                                    <a href="statusbayar.php?edit=<?= $m['id_murid']; ?>" class="btn btn-sm btn-warning me-1">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </a>
                                                    <a href="statusbayar.php?hapus=<?= $m['id_murid']; ?>" onclick="return confirm('Hapus status pembayaran?')" class="btn btn-sm btn-danger">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                <?php
                                    endforeach;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- kategori kas -->
                <!-- <div class="row g-4 mt-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body rounded shadow">
                                <h5 class="card-title">Kategori Kas Masuk</h5>
                                <p class="card-text mb-2">Total kategori aktif:</p>
                                <h3><?= number_format($kategoriKasMasuk); ?></h3>
                                <small class="text-muted">Berdasarkan kategori dari transaksi masuk</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body rounded shadow">
                                <h5 class="card-title">Kategori Kas Keluar</h5>
                                <p class="card-text mb-2">Total kategori aktif:</p>
                                <h3><?= number_format($kategoriKasKeluar); ?></h3>
                                <small class="text-muted">Berdasarkan kategori dari transaksi keluar</small>
                            </div>
                        </div>
                    </div>
                </div> -->
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

    <script>
        const searchInput = document.getElementById('searchInput');

        // Reset search only ketika input dikosongkan, status tidak mengirim otomatis
        searchInput.addEventListener('input', function() {
            if (this.value === '') {
                const url = new URL(window.location);
                url.searchParams.delete('search');
                window.location.href = url.toString();
            }
        });
    </script>
</body>

</html>