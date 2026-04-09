<?php
session_start();
include 'config/app.php';

// CREATE
if (isset($_POST['simpan'])) {
    query("INSERT INTO murid VALUES(
        '$_POST[nisn]',
        '$_POST[nama]'  
    )");

    header("Location: datamurid.php?success=tambah");
    exit;
}

// UPDATE
if (isset($_POST['update'])) {
    query("UPDATE murid SET
        nama='$_POST[nama]'
        WHERE nisn='$_POST[nisn]'
    ");

    header("Location: datamurid.php?success=update");
    exit;
}

// DELETE
if (isset($_GET['hapus'])) {
    query("DELETE FROM murid WHERE nisn='$_GET[hapus]'");
    header("Location: datamurid.php?success=delete");
    exit;
}

// FILTER
$search = $_GET['search'] ?? '';

$where = [];

if ($search) {
    $s = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where[] = "nama LIKE '%$s%'";
}


$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$data = query("SELECT * FROM murid $whereSql ORDER BY nisn ASC");

$no = 1;

// RINGKASAN
$totalMurid = mysqli_fetch_assoc(query("SELECT COUNT(*) as total FROM murid"))['total'];
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
                
                <?php if ($_SESSION['role'] == 'wali kelas'): ?>
                    <li><a class="nav-link" href="datamurid.php"><i class="fas fa-users"></i> Data Murid</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'bendahara'): ?>
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
                                <th>Nisn</th>
                                <th>Nama Murid</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php while ($m = mysqli_fetch_assoc($data)): ?>
                                <tr>
                                    <td><?= $m['nisn'] ?></td>
                                    <td><?= $m['nama'] ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?= $m['nisn'] ?>">
                                            Edit
                                        </button>

                                        <a href="?hapus=<?= $m['nisn'] ?>" onclick="return confirm('hapus?')" class="btn btn-danger btn-sm">
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
                    <label>Nisn</label>
                    <input type="text" name="nisn" class="form-control mb-2">
                    <label>Nama</label>
                    <input type="text" name="nama" class="form-control">
                </div>

                <div class="modal-footer">
                    <button name="simpan" class="btn btn-primary">Simpan</button>
                </div>

            </form>
        </div>
    </div>

    <!-- 🔥 MODAL EDIT FIX -->
    <?php
    $dataModal = query("SELECT * FROM murid ORDER BY nisn DESC");
    while ($m = mysqli_fetch_assoc($dataModal)):
    ?>
        <div class="modal fade" id="edit<?= $m['nisn'] ?>">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content">

                    <input type="hidden" name="nisn" value="<?= $m['nisn'] ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Murid</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="text" name="nisn" value="<?= $m['nisn'] ?>" class="form-control mb-2">
                        <input type="text" name="nama" value="<?= $m['nama'] ?>" class="form-control mb-2">
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