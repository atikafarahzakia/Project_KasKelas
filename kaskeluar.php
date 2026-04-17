<?php
session_start();
include 'config/app.php';

// CEK KOLOM KATEGORI
$cekKolom = query("SHOW COLUMNS FROM transaksi LIKE 'id_kategori'");
$hasKategoriColumn = !empty($cekKolom);
$kategori = $hasKategoriColumn ? query("SELECT * FROM kategori") : [];

// TAMBAH
if (isset($_POST['simpan'])) {
    if ($jumlah > 0 && $jumlah <= getSaldo()) {

        $columns = "tanggal, jenis, jumlah, keterangan";
        $values = "NOW(), 'keluar', '$jumlah', 'Kas Keluar'";

        if ($hasKategoriColumn && !empty($_POST['id_kategori'])) {
            $id_kategori = (int)$_POST['id_kategori'];
            $columns .= ", id_kategori";
            $values .= ", '$id_kategori'";
        }

        query("INSERT INTO transaksi ($columns) VALUES ($values)");

        header("Location: kaskeluar.php?success=tambah");
        exit;
    } else {
        header("Location: kaskeluar.php?error=saldo");
        exit;
    }
}

// UPDATE
if (isset($_POST['update'])) {
    $id = (int)$_POST['id_transaksi'];
    $jumlah = (int)$_POST['jumlah'];

    if ($jumlah > 0) {

        $sql = "UPDATE transaksi SET jumlah='$jumlah'";

        if ($hasKategoriColumn && isset($_POST['id_kategori'])) {
            $id_kategori = (int)$_POST['id_kategori'];
            $sql .= ", id_kategori='$id_kategori'";
        }

        $sql .= " WHERE id_transaksi='$id'";
        query($sql);
    }

    header("Location: kaskeluar.php?success=update");
    exit;
}

// DELETE
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    query("DELETE FROM transaksi WHERE id_transaksi='$id'");
    header("Location: kaskeluar.php?success=delete");
    exit;
}

// FILTER
$search = $_GET['search'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';

$where = "t.jenis='keluar'";

if ($search) {
    $s = mysqli_real_escape_string($db, $search);

    if ($hasKategoriColumn) {
        $where .= " AND (
            t.jumlah LIKE '%$s%' 
            OR k.nama LIKE '%$s%'
        )";
    } else {
        $where .= " AND t.jumlah LIKE '%$s%'";
    }
}

if ($tanggal) {
    $where .= " AND DATE(t.tanggal) = '$tanggal'";
}

// DATA (FIX JOIN KATEGORI)
if ($hasKategoriColumn) {
    $kasKeluar = mysqli_query($db, "
        SELECT t.*, k.nama as kategori 
        FROM transaksi t
        LEFT JOIN kategori k ON t.id_kategori = k.id_kategori
        WHERE $where 
        ORDER BY t.tanggal DESC
    ");
} else {
    $kasKeluar = query("
    SELECT t.*, k.nama as kategori 
    FROM transaksi t
    LEFT JOIN kategori k ON t.id_kategori = k.id_kategori
    WHERE $where 
    ORDER BY t.tanggal DESC
");
}

// RINGKASAN
$keluar = ringkasanKasKeluar();
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Kas Keluar</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                    <li><a class="nav-link" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
                    <li><a class="nav-link active" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
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

            <h4 class="mb-4">Kas Keluar</h4>

            <!-- RINGKASAN -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6>Total Kas Keluar</h6>
                                <h4 class="text-danger">
                                    Rp <?= number_format($keluar['totalKeluar']) ?>
                                </h4>
                            </div>
                            <i class="bi bi-arrow-up-circle fs-1 text-danger"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6>Saldo</h6>
                                <h4 class="text-success">
                                    Rp <?= number_format($keluar['saldo']) ?>
                                </h4>
                            </div>
                            <i class="bi bi-wallet2 fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BUTTON -->
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambahKas">
                Tambah Kas Keluar
            </button>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
                    <?php
                    if ($_GET['success'] == 'tambah') echo 'Kas keluar berhasil ditambahkan!';
                    if ($_GET['success'] == 'update') echo 'Kas keluar berhasil diupdate!';
                    if ($_GET['success'] == 'delete') echo 'Kas keluar berhasil dihapus!';
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
                <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                    <?php
                    if ($_GET['error'] == 'saldo') echo 'Saldo tidak cukup untuk melakukan transaksi!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <script>
                    setTimeout(() => {
                        const alert = document.getElementById('errorAlert');
                        if (alert) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 3000);
                </script>
            <?php endif; ?>

            <!-- FILTER -->
            <form method="GET" class="row g-2 mb-2 mt-3">

                <!-- SEARCH -->
                <div class="col-md-4">
                    <input type="text"
                        name="search"
                        class="form-control"
                        placeholder="Cari jumlah / keterangan..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>

                <!-- BULAN -->
                <div class="col-md-3">
                    <input type="date" name="tanggal" class="form-control"
                        value="<?= $_GET['tangal'] ?? '' ?>">
                </div>

                <!-- TAHUN -->
                <div class="col-md-2">
                    <select name="kategori" class="form-control">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategori as $cat): ?>
                            <option value="<?= $cat['id_kategori'] ?>"
                                <?= (($_GET['kategori'] ?? '') == $cat['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- BUTTON -->
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary me-2">Filter</button>
                    <a href="kaskeluar.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <!-- TABEL -->
            <div class="card">
                <div class="card-body">

                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Kategori</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <?php foreach ($kasKeluar as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                <td>Rp <?= number_format($row['jumlah']) ?></td>
                                <td><?= htmlspecialchars($row['kategori'] ?? '-') ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?= $row['id_transaksi'] ?>">
                                        Edit
                                    </button>
                                    <a href="?hapus=<?= $row['id_transaksi'] ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Yakin ingin menghapus data ini?')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="modalTambahKas">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kas Keluar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST">
                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                        </div>

                        <?php if ($hasKategoriColumn): ?>
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="id_kategori" class="form-control" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach ($kategori as $cat): ?>
                                        <option value="<?= $cat['id_kategori']; ?>">
                                            <?= htmlspecialchars($cat['nama']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input type="number" name="jumlah" class="form-control" required>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <?php mysqli_data_seek($kasKeluar, 0); // ulang loop 
    ?>
    <?php while ($row = mysqli_fetch_assoc($kasKeluar)): ?>

        <div class="modal fade" id="edit<?= $row['id_transaksi'] ?>">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content">

                    <input type="hidden" name="id_transaksi" value="<?= $row['id_transaksi'] ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Kas Keluar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <?php if ($hasKategoriColumn): ?>
                            <div class="mb-3">
                                <label>Kategori</label>
                                <select name="id_kategori" class="form-control">
                                    <?php foreach ($kategori as $cat): ?>
                                        <option value="<?= $cat['id_kategori'] ?>"
                                            <?= $row['id_kategori'] == $cat['id_kategori'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

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

    <?php endwhile; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>