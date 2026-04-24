<?php
session_start();
include 'config/app.php';

// ================= RINGKASAN =================
$keluar = ringkasanKasKeluar();
$saldoSekarang = $keluar['saldo'];

// ================= TAMBAH PENGAJUAN =================
if (isset($_POST['simpan'])) {

    $jumlah = (int)$_POST['jumlah'];
    $kategori = $_POST['kategori'];
    $keterangan = $_POST['keterangan'];

    // VALIDASI ENUM
    $allowedKategori = ['ATK', 'Kegiatan', 'Lainnya'];
    if (!in_array($kategori, $allowedKategori)) {
        die("Kategori tidak valid!");
    }

    // ================= UPLOAD BUKTI =================
    $bukti = null;

    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === 0) {

        $namaFile = $_FILES['bukti']['name'];
        $tmp = $_FILES['bukti']['tmp_name'];
        $size = $_FILES['bukti']['size'];

        $ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($ext, $allowed)) {
            die("Format file harus jpg/jpeg/png");
        }

        if ($size > 2000000) {
            die("Ukuran file max 2MB");
        }

        $namaBaru = uniqid() . '.' . $ext;

        if (!is_dir('upload')) {
            mkdir('upload', 0777, true);
        }

        move_uploaded_file($tmp, 'upload/' . $namaBaru);
        $bukti = $namaBaru;
    }

    // ================= SIMPAN =================
    if ($jumlah > 0) {

        if ($jumlah > $saldoSekarang) {
            header("Location: kaskeluar.php?error=saldo");
            exit;
        }

        $bulan = date('n');
        $tahun = date('Y');

        $sql = "INSERT INTO pengajuan 
        (tanggal, bulan, tahun, jumlah, kategori, keterangan, bukti, status)
        VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, 'pending')";

        $stmt = mysqli_prepare($db, $sql);

        if (!$stmt) {
            die("Prepare gagal: " . mysqli_error($db));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iiisss",
            $bulan,
            $tahun,
            $jumlah,
            $kategori,
            $keterangan,
            $bukti
        );

        if (!mysqli_stmt_execute($stmt)) {
            die("Execute gagal: " . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);

        header("Location: kaskeluar.php?success=tambah");
        exit;
    }
}

// ================= UPDATE =================
if (isset($_POST['update'])) {

    $id = (int)$_POST['id_pengajuan'];
    $jumlah = (int)$_POST['jumlah'];
    $kategori = $_POST['kategori'];
    $keterangan = $_POST['keterangan'];

    // ambil data lama
    $dataLama = query("SELECT * FROM pengajuan WHERE id_pengajuan=$id")[0];
    $jumlahLama = $dataLama['jumlah'];

    // hitung selisih (AMAN sekarang)
    $selisih = $jumlah - $jumlahLama;

    $keluar = ringkasanKasKeluar();
    $saldoSekarang = $keluar['saldo'];

    if ($selisih > 0 && $selisih > $saldoSekarang) {
        header("Location: kaskeluar.php?error=saldo");
        exit;
    }

    // update data
    query("UPDATE pengajuan SET
        jumlah='$jumlah',
        kategori='$kategori',
        keterangan='$keterangan'
        WHERE id_pengajuan='$id'
    ");

    header("Location: kaskeluar.php?success=update");
    exit;
}

// ================= HAPUS =================
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    // cek status dulu
    $data = query("SELECT status FROM pengajuan WHERE id_pengajuan=$id");

    if (!$data) {
        header("Location: kaskeluar.php");
        exit;
    }

    if ($data[0]['status'] == 'disetujui') {
        header("Location: kaskeluar.php?error=tidak_boleh_hapus");
        exit;
    }

    query("DELETE FROM pengajuan WHERE id_pengajuan=$id");

    header("Location: kaskeluar.php?success=delete");
    exit;
}

// ================= DATA =================
$pengajuan = query("SELECT * FROM pengajuan ORDER BY tanggal DESC");

// ================= ENUM =================
$dataEnum = query("SHOW COLUMNS FROM pengajuan LIKE 'kategori'");
$type = $dataEnum[0]['Type'];
$type = str_replace(["enum('", "')"], '', $type);
$kategoriList = explode("','", $type);

// ================= SEARCH =================
$search  = $_GET['search'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$status  = $_GET['status'] ?? '';

$where = "1=1";

if ($search) {
    $s = mysqli_real_escape_string($db, $search);
    $where .= " AND (
        kategori LIKE '%$s%' 
        OR keterangan LIKE '%$s%' 
        OR CAST(jumlah AS CHAR) LIKE '%$s%'
    )";
}

if ($tanggal) {
    $where .= " AND DATE(tanggal) = '$tanggal'";
}

if ($status) {
    $s = mysqli_real_escape_string($db, $status);
    $where .= " AND status = '$s'";
}

$pengajuan = query("SELECT * FROM pengajuan WHERE $where ORDER BY tanggal DESC");
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
                    <li><a class="nav-link active" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
                <?php endif; ?>

                <li><a class="nav-link" href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a></li>
                <li><a class="nav-link" href="statusbayar.php"><i class="fa-solid fa-chart-column"></i> Status Bayar</a></li>
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
                                <h4 class="text-danger">Rp <?= number_format($keluar['totalKeluar']) ?></h4>
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
                                <h4 class="text-success">Rp <?= number_format($keluar['saldo']) ?></h4>
                            </div>
                            <i class="bi bi-wallet2 fs-1 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">
                Tambah Kas Keluar
            </button>

            <!-- ALERT -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
                    <?php
                    if ($_GET['error'] == 'saldo') echo 'Saldo tidak cukup!';
                    if ($_GET['error'] == 'tidak_boleh_hapus') echo 'Data yang sudah disetujui tidak boleh dihapus!';
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
                    <label>Cari Kas Keluar</label>
                    <input type="text" name="search" class="form-control"
                        value="<?= htmlspecialchars($search) ?>">
                </div>

                <!-- TANGGAL -->
                <div class="col-md-3">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" class="form-control"
                        value="<?= $tanggal ?>">
                </div>

                <!-- STATUS -->
                <div class="col-md-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="disetujui" <?= $status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak" <?= $status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
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
                                <th>Status</th>
                                <!-- <th>Bukti</th> -->
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <?php foreach ($pengajuan as $row): ?>
                            <tr>
                                <td><?= $row['tanggal'] ?></td>
                                <td>Rp <?= number_format($row['jumlah']) ?></td>
                                <td><?= $row['kategori'] ?></td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif ($row['status'] == 'disetujui'): ?>
                                        <span class="badge bg-success">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <!-- <td>
                                    <?php if ($row['bukti']): ?>
                                        <img src="upload/<?= $row['bukti'] ?>" width="60">
                                    <?php endif; ?>
                                </td> -->
                                <td>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#detail<?= $row['id_pengajuan'] ?>">
                                        Detail
                                    </button>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#edit<?= $row['id_pengajuan'] ?>">
                                        Edit
                                    </button>
                                    <a href="?hapus=<?= $row['id_pengajuan'] ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Yakin hapus?')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </table>

                </div>
            </div>

        </div>
    </div>

    <!-- ================= MODAL EDIT ================= -->
    <?php foreach ($pengajuan as $row): ?>
        <div class="modal fade" id="edit<?= $row['id_pengajuan'] ?>">
            <div class="modal-dialog">
                <form method="POST" class="modal-content">

                    <input type="hidden" name="id_pengajuan" value="<?= $row['id_pengajuan'] ?>">

                    <div class="modal-header">
                        <h5>Edit Pengajuan</h5>
                    </div>

                    <div class="modal-body">
                        <input type="number" name="jumlah" value="<?= $row['jumlah'] ?>" class="form-control mb-2">

                        <select name="kategori" class="form-control mb-2">
                            <?php foreach ($kategoriList as $k): ?>
                                <option <?= $row['kategori'] == $k ? 'selected' : '' ?>><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>

                        <textarea name="keterangan" class="form-control"><?= $row['keterangan'] ?></textarea>
                    </div>

                    <div class="modal-footer">
                        <button name="update" class="btn btn-warning">Update</button>
                    </div>

                </form>
            </div>
        </div>
    <?php endforeach; ?>


    <!-- ================= MODAL DETAIL ================= -->
    <?php foreach ($pengajuan as $row): ?>
        <div class="modal fade" id="detail<?= $row['id_pengajuan'] ?>">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5>Detail Pengajuan</h5>
                    </div>

                    <div class="modal-body">
                        <p><b>Tanggal:</b> <?= $row['tanggal'] ?></p>
                        <p><b>Jumlah:</b> Rp <?= number_format($row['jumlah']) ?></p>
                        <p><b>Kategori:</b> <?= $row['kategori'] ?></p>
                        <p><b>Status:</b> <?= $row['status'] ?></p>

                        <p><b>Keterangan:</b></p>
                        <div class="border p-2">
                            <?= nl2br($row['keterangan']) ?>
                        </div>

                        <?php if ($row['bukti']): ?>
                            <img src="upload/<?= $row['bukti'] ?>" class="img-fluid mt-2">
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="modalTambah">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">

                <div class="modal-header">
                    <h5>Tambah Pengajuan</h5>
                </div>

                <div class="modal-body">

                    <div class="mb-2">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah" id="jumlah" class="form-control" required>
                    </div>

                    <div class="mb-2">
                        <label>Kategori</label>
                        <select name="kategori" class="form-control" required>
                            <?php foreach ($kategoriList as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control"></textarea>
                    </div>

                    <div class="mb-2">
                        <label>Bukti</label>
                        <input type="file" name="bukti" class="form-control">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                </div>

            </form>
        </div>
    </div>

    <!-- TOAST NOTIFIKASI -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">

        <!-- SUCCESS -->
        <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    ✅ Pengajuan kas keluar berhasil ditambahkan!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>

        <!-- UPDATE -->
        <div id="toastUpdate" class="toast align-items-center text-bg-warning border-0" role="alert" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    ✏️ Data berhasil diupdate!
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>

        <!-- DELETE -->
        <div id="toastDelete" class="toast align-items-center text-bg-danger border-0" role="alert" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    🗑️ Data berhasil dihapus!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>

    </div>

    <!-- TOAST NOTIFIKASI -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">

        <!-- SUCCESS -->
        <div id="toastSuccess" class="toast align-items-center text-bg-success border-0" role="alert" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    ✅ Pengajuan kas keluar berhasil ditambahkan!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>

        <!-- UPDATE -->
        <div id="toastUpdate" class="toast align-items-center text-bg-warning border-0" role="alert" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    ✏️ Data berhasil diupdate!
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>

        <!-- DELETE -->
        <div id="toastDelete" class="toast align-items-center text-bg-danger border-0" role="alert" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    🗑️ Data berhasil dihapus!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>

    </div>


    <?php if (isset($_GET['success'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {

                let type = "<?= $_GET['success'] ?>";
                let toastEl;

                if (type === "tambah") {
                    toastEl = document.getElementById('toastSuccess');
                } else if (type === "update") {
                    toastEl = document.getElementById('toastUpdate');
                } else if (type === "delete") {
                    toastEl = document.getElementById('toastDelete');
                }

                if (toastEl) {
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                }

            });
        </script>
    <?php endif; ?>

    <script>
        const input = document.getElementById('jumlah');

        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, ''); // hapus semua selain angka
            this.value = formatRupiah(value);
        });

        function formatRupiah(angka) {
            return angka.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>