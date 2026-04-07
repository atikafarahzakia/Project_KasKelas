<?php
session_start();
include 'config/app.php';

// CREATE
if (isset($_POST['simpan'])) {
    query("INSERT INTO murid VALUES(
        NULL,
        '$_POST[nama]',
        '$_POST[kelas]',
        'aktif'
    )");

    header("Location: datamurid.php?success=tambah");
    exit;
}

// UPDATE
if (isset($_POST['update'])) {
    query("UPDATE murid SET
        nama='$_POST[nama]',
        kelas='$_POST[kelas]',
        status='$_POST[status]'
        WHERE id_murid='$_POST[id_murid]'
    ");

    header("Location: datamurid.php?success=update");
    exit;
}

// DELETE
if (isset($_GET['hapus'])) {
    query("DELETE FROM murid WHERE id_murid='$_GET[hapus]'");
    header("Location: datamurid.php?success=delete");
    exit;
}

// FILTER
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];

if ($search) {
    $s = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where[] = "nama LIKE '%$s%'";
}

if ($status) {
    $st = mysqli_real_escape_string($GLOBALS['db'], $status);
    $where[] = "status='$st'";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$data = query("SELECT * FROM murid $whereSql ORDER BY id_murid DESC");

$no = 1;

// RINGKASAN
$totalMurid = mysqli_fetch_assoc(query("SELECT COUNT(*) as total FROM murid"))['total'];
$totalAktif = mysqli_fetch_assoc(query("SELECT COUNT(*) as total FROM murid WHERE status='aktif'"))['total'];
$totalNonaktif = mysqli_fetch_assoc(query("SELECT COUNT(*) as total FROM murid WHERE status='Tidak Aktif'"))['total'];
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Data Murid</title>

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

                <?php if ($_SESSION['role'] == 'bendahara'): ?>
                    <li><a class="nav-link active" href="datamurid.php"><i class="fas fa-users"></i> Data Murid</a></li>
                    <li><a class="nav-link" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
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

            <h4 class="mb-4">Data Murid</h4>

            <!-- RINGKASAN -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card p-3 text-center">
                        <h6>Total Murid</h6>
                        <h4><?= $totalMurid ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center">
                        <h6>Murid Aktif</h6>
                        <h4 class="text-success"><?= $totalAktif ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 text-center">
                        <h6>Tidak Aktif</h6>
                        <h4 class="text-danger"><?= $totalNonaktif ?></h4>
                    </div>
                </div>
            </div>

            <!-- BUTTON -->
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
                Tambah Murid
            </button>

            <!-- FILTER -->
            <form method="GET" class="row g-2 mb-2 mt-3 align-items-end">

                <div class="col-md-5">
                    <label>Cari Nama Siswa</label>
                    <input type="text" name="search" class="form-control" value="<?= $search ?>">
                </div>

                <div class="col-md-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua</option>
                        <option value="aktif" <?= ($_GET['status'] ?? '') == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Tidak Aktif" <?= ($_GET['status'] ?? '') == 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary me-2">Filter</button>
                    <a href="datamurid.php" class="btn btn-secondary">Reset</a>
                </div>

            </form>

            <!-- TABLE -->
            <div class="card">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php while ($m = mysqli_fetch_assoc($data)): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= $m['nama'] ?></td>
                                    <td><?= $m['kelas'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= strtolower($m['status']) == 'aktif' ? 'success' : 'danger' ?>">
                                            <?= $m['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?= $m['id_murid'] ?>">
                                            Edit
                                        </button>

                                        <a href="?hapus=<?= $m['id_murid'] ?>" onclick="return confirm('hapus?')" class="btn btn-danger btn-sm">
                                            Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="modalTambah">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Tambah Murid</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label>Nama</label>
                    <input type="text" name="nama" class="form-control mb-2">
                    <label>Kelas</label>
                    <input type="text" name="kelas" class="form-control">
                </div>

                <div class="modal-footer">
                    <button name="simpan" class="btn btn-primary">Simpan</button>
                </div>

            </form>
        </div>
    </div>

    <!-- 🔥 MODAL EDIT FIX -->
    <?php
    $dataModal = query("SELECT * FROM murid ORDER BY id_murid DESC");
    while ($m = mysqli_fetch_assoc($dataModal)):
    ?>
        <div class="modal fade" id="edit<?= $m['id_murid'] ?>">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content">

                    <input type="hidden" name="id_murid" value="<?= $m['id_murid'] ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Murid</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="text" name="nama" value="<?= $m['nama'] ?>" class="form-control mb-2">
                        <input type="text" name="kelas" value="<?= $m['kelas'] ?>" class="form-control mb-2">

                        <select name="status" class="form-control">
                            <option <?= $m['status'] == 'aktif' ? 'selected' : '' ?>>aktif</option>
                            <option <?= $m['status'] == 'Tidak Aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                        </select>
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