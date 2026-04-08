<?php include 'config/app.php';
session_start();

// Ambil data murid dan kategori
$murid = query("SELECT * FROM murid");
$hasKategoriColumn = mysqli_num_rows(query("SHOW COLUMNS FROM transaksi LIKE 'id_kategori'")) > 0;

$kategori = [];

if ($hasKategoriColumn) {
    $kategori = query("SELECT id_kategori, nama FROM kategori");
}

// ================= TAMBAH =================
if (isset($_POST['simpan'])) {

    $id_murid = (int)$_POST['id_murid'];
    $jumlah   = (int)$_POST['jumlah'];

    $columns = 'id_murid, tanggal, jenis, jumlah, keterangan';
    $values  = "'$id_murid', NOW(), 'masuk', '$jumlah', 'Kas Masuk'";

    if ($hasKategoriColumn) {
        $kategoriValue = !empty($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : NULL;
        $columns .= ', id_kategori';
        $values  .= is_null($kategoriValue) ? ", NULL" : ", '$kategoriValue'";
    }

    query("INSERT INTO transaksi ($columns) VALUES ($values)");

    header("Location: kasmasuk.php?success=tambah");
    exit;
}

// ================= UPDATE =================
if (isset($_POST['update'])) {

    $id        = (int)$_POST['id_transaksi'];
    $id_murid  = (int)$_POST['id_murid'];
    $tgl       = $_POST['tanggal'];
    $jml       = (int)$_POST['jumlah'];

    $kategori_update = '';
    if ($hasKategoriColumn) {
        $kategoriValue = !empty($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : NULL;
        $kategori_update = is_null($kategoriValue)
            ? ", id_kategori = NULL"
            : ", id_kategori = '$kategoriValue'";
    }

    query("UPDATE transaksi SET
        id_murid = '$id_murid',
        tanggal = '$tgl',
        jumlah  = '$jml'
        $kategori_update
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
$bulan  = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$tahun  = isset($_GET['tahun']) ? $_GET['tahun'] : '';

$where = "transaksi.jenis = 'masuk'";

if ($search) {
    $search = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND murid.nama LIKE '%$search%'";
}

if ($bulan && $tahun) {
    $where .= " AND MONTH(transaksi.tanggal) = $bulan AND YEAR(transaksi.tanggal) = $tahun";
} elseif ($tahun) {
    $where .= " AND YEAR(transaksi.tanggal) = $tahun";
} elseif ($bulan) {
    $where .= " AND MONTH(transaksi.tanggal) = $bulan";
}

// ================= DATA =================
$data = query("SELECT transaksi.*, murid.nama 
    FROM transaksi 
    JOIN murid ON transaksi.id_murid = murid.id_murid 
    WHERE $where 
    ORDER BY transaksi.tanggal DESC");

// ================= RINGKASAN =================
$ringkasan = ringkasanKasMasuk();
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Kas Masuk</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
                        <h6>Total Kas Masuk</h6>
                        <h4 class="text-primary">Rp <?= number_format($ringkasan['totalKasMasuk']) ?></h4>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3">
                        <h6>Saldo</h6>
                        <h4 class="text-success">Rp <?= number_format($ringkasan['saldo']) ?></h4>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambahKas">
                Tambah Kas Masuk
            </button>

            <!-- FILTER -->
            <form method="GET" class="row g-2 mb-2 mt-3">

                <div class="col-md-4">
                    <label>Cari Nama Siswa</label>
                    <input type="text" name="search" class="form-control"
                        value="<?= $_GET['search'] ?? '' ?>">
                </div>

                <div class="col-md-3">
                    <label>Bulan</label>
                    <input type="month" name="bulan" class="form-control"
                        value="<?= $_GET['bulan'] ?? '' ?>">
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

                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Kas Masuk</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">

                                        <div class="mb-3">
                                            <label>Nama Siswa</label>
                                            <select name="id_murid" class="form-control" disabled>
                                                <?php foreach ($murid as $s): ?>
                                                    <option value="<?= $s['id_murid'] ?>"
                                                        <?= ($row['id_murid'] == $s['id_murid']) ? 'selected' : '' ?>>
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
                        <select name="id_murid" class="form-control" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($murid as $s): ?>
                                <option value="<?= $s['id_murid'] ?>">
                                    <?= htmlspecialchars($s['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" required>
                    </div>

                    <?php if ($hasKategoriColumn): ?>
                        <div class="mb-3">
                            <label>Kategori</label>
                            <select name="id_kategori" class="form-control">

                                <?php if (!empty($kategori)): ?>
                                    <option value="">-- Pilih Kategori --</option>

                                    <?php foreach ($kategori as $k): ?>
                                        <option value="<?= $k['id_kategori'] ?>">
                                            <?= htmlspecialchars($k['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <option disabled selected>Data kategori tidak ada</option>
                                <?php endif; ?>

                            </select>
                        </div>
                    <?php endif; ?>

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