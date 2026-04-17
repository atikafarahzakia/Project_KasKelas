<?php include 'config/app.php';
session_start();

// Ambil data murid dan kategori
$murid = query("SELECT * FROM murid");
$cekKolom = query("SHOW COLUMNS FROM transaksi LIKE 'id_kategori'");
$hasKategoriColumn = !empty($cekKolom);
$kategori = [];
$ringkasan = ringkasanKasMasuk();

if ($hasKategoriColumn) {
    $kategori = query("SELECT id_kategori, nama FROM kategori");
}

// ================= TAMBAH =================
if (isset($_POST['simpan'])) {
    $nisn = (int)$_POST['nisn'];
    $jumlah_baru = (int)$_POST['jumlah'];
    $kas_wajib = 20000;

    $bulan = date('m');
    $tahun = date('Y');

    $cekBayar = query("SELECT SUM(jumlah) as total FROM transaksi 
                       WHERE nisn = '$nisn' 
                       AND jenis = 'masuk' 
                       AND MONTH(tanggal) = '$bulan' 
                       AND YEAR(tanggal) = '$tahun'");

    $totalBayar = $cekBayar[0]['total'] ?? 0;

    // VALIDASI
    if ($totalBayar >= $kas_wajib) {
        header("Location: kasmasuk.php?error=sudah_lunas");
        exit;
    }

    if (($totalBayar + $jumlah_baru) > $kas_wajib) {
        $sisa = $kas_wajib - $totalBayar;
        header("Location: kasmasuk.php?error=melebihi_batas&sisa=$sisa");
        exit;
    }

    // ✅ INI YANG KAMU LUPA
    query("INSERT INTO transaksi (nisn, tanggal, jenis, jumlah, keterangan, id_kategori)
           VALUES ('$nisn', NOW(), 'masuk', '$jumlah_baru', 'Kas Masuk', '2')");

    header("Location: kasmasuk.php?success=tambah");
    exit;
}

// ================= UPDATE =================
if (isset($_POST['update'])) {

    $id        = (int)$_POST['id_transaksi'];
    $nisn  = (int)$_POST['nisn'];
    $jml       = (int)$_POST['jumlah'];

    query("UPDATE transaksi SET
    nisn = '$nisn',
    jumlah  = '$jml'
    WHERE id_transaksi = '$id'
");

    header("Location: kasmasuk.php?success=update");
    exit;
}

// ================= DELETE =================
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    query("DELETE FROM transaksi WHERE id_transaksi='$id'");
    header("Location: kasmasuk.php?success=delete");
    exit;
}

// ================= FILTER =================
$search = isset($_GET['search']) ? $_GET['search'] : '';
$tanggal = $_GET['tanggal'] ?? '';

$bulan = date('m');
$tahun = date('Y');

$where = "transaksi.jenis = 'masuk' 
          AND MONTH(transaksi.tanggal) = '$bulan'
          AND YEAR(transaksi.tanggal) = '$tahun'";

if ($search) {
    $search = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND murid.nama LIKE '%$search%'";
}

// kalau pilih tanggal manual → override
if ($tanggal) {
    $where = "transaksi.jenis = 'masuk' 
              AND DATE(transaksi.tanggal) = '$tanggal'";
}

// ================= DATA =================
$data = query("SELECT transaksi.*, murid.nama 
    FROM transaksi 
    JOIN murid ON transaksi.nisn = murid.nisn
    WHERE $where 
    ORDER BY transaksi.tanggal DESC");
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Kas Masuk</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <li><a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

                <?php if ($_SESSION['role'] == 'wali kelas'): ?>
                    <li><a class="nav-link" href="datamurid.php"><i class="fas fa-users"></i> Data Murid</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'bendahara'): ?>

                    <li><a class="nav-link active" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
                    <li><a class="nav-link" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
                <?php endif; ?>

                <li><a class="nav-link" href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a></li>
                <li><a class="nav-link" href="statusbayar.php"><i class="fas fa-chart-bar"></i> Status Bayar</a></li>
                <li><a class="nav-link" href="laporan.php"><i class="fas fa-file"></i> Laporan</a></li>

                <hr>
                <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- CONTENT -->
        <div class="flex-fill p-4">
            <h4 class="mb-4">Kas Masuk</h4>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6>Total Kas Masuk</h6>
                                <h4 class="text-primary">
                                    Rp <?= number_format($ringkasan['totalKasMasuk']) ?>
                                </h4>
                            </div>
                            <i class="bi bi-arrow-down-circle fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6>Saldo</h6>
                                <h4 class="text-success">
                                    Rp <?= number_format($ringkasan['saldo']) ?>
                                </h4>
                            </div>
                            <i class="bi bi-wallet2 fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambahKas">
                Tambah Kas Masuk
            </button>

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

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php
                    if ($_GET['error'] == 'sudah_lunas') {
                        echo '<strong>Gagal!</strong> Siswa tersebut sudah melunasi kas untuk semester ini.';
                    }
                    if ($_GET['error'] == 'melebihi_batas') {
                        $sisa = number_format($_GET['sisa']);
                        echo "<strong>Gagal!</strong> Jumlah pembayaran melebihi batas. Sisa tagihan semester ini hanya Rp $sisa.";
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- FILTER -->
            <form method="GET" class="row g-2 mb-2 mt-3">

                <div class="col-md-4">
                    <label>Cari Nama Siswa</label>
                    <input type="text" name="search" class="form-control"
                        value="<?= $_GET['search'] ?? '' ?>">
                </div>

                <div class="col-md-3">
                    <label>Bulan</label>
                    <input type="date" name="tanggal" class="form-control"
                        value="<?= $_GET['tanggal'] ?? '' ?>">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary me-2">Filter</button>
                    <a href="kasmasuk.php" class="btn btn-secondary">Reset</a>
                </div>

            </form>
            <!-- TABLE -->
            <div class="card">
                <div class="card-body">

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?= $row['tanggal'] ?></td>
                                <td><?= $row['nama'] ?></td>
                                <td>Rp <?= number_format($row['jumlah']) ?></td>
                                <td>
                                    <!-- BUTTON EDIT -->
                                    <button class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEdit<?= $row['id_transaksi'] ?>">
                                        Edit
                                    </button>

                                    <!-- BUTTON HAPUS -->
                                    <a href="?hapus=<?= $row['id_transaksi'] ?>"
                                        onclick="return confirm('Hapus data?')"
                                        class="btn btn-danger btn-sm">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </table>
                    <?php foreach ($data as $row): ?>
                        <div class="modal fade" id="modalEdit<?= $row['id_transaksi'] ?>">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" class="modal-content">

                                    <input type="hidden" name="id_transaksi" value="<?= $row['id_transaksi'] ?>">
                                    <!-- <input type="hidden" name="nisn" value="<?= $row['nisn'] ?>"> -->

                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Kas Masuk</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">

                                        <div class="mb-3">
                                            <label>Nama Siswa</label>
                                            <select class="form-control" name="nisn">
                                                <?php foreach ($murid as $s): ?>
                                                    <option value="<?= $s['nisn'] ?>"
                                                        <?= ($row['nisn'] == $s['nisn']) ? 'selected' : '' ?>>
                                                        <?= $s['nama'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label>Jumlah</label>
                                            <input type="number" name="jumlah" value="<?= $row['jumlah'] ?>" class="form-control">
                                        </div>

                                    </div>

                                    <div class="modal-footer">
                                        <button name="update" class="btn btn-warning">Update</button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="modalTambahKas">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kas Masuk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label>Nama Siswa</label>
                        <select name="nisn" class="form-control" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($murid as $s): ?>
                                <option value="<?= $s['nisn'] ?>">
                                    <?= htmlspecialchars($s['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                </div>

            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>