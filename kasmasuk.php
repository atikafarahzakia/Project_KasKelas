<?php
include 'config/app.php';
session_start();

// ================= AMBIL DATA =================
$murid = query("SELECT * FROM murid");
$ringkasan = ringkasanKasMasuk();

// ================= FILTER =================
$search = $_GET['search'] ?? '';
$bulan_filter = $_GET['bulan'] ?? '';

$where = "WHERE t.jenis = 'masuk'";

// SEARCH NAMA
if (!empty($search)) {
    $search = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND m.nama LIKE '%$search%'";
}

// FILTER BULAN
if (!empty($bulan_filter)) {
    $bulan_filter = (int)$bulan_filter;
    $where .= " AND t.bulan = $bulan_filter";
}

// ================= DELETE =================
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];

    query("DELETE FROM transaksi WHERE id_transaksi = $id");

    header("Location: kasmasuk.php?success=delete");
    exit;
}

// ================= UPDATE =================
if (isset($_POST['update'])) {

    $id     = (int)$_POST['id_transaksi'];
    $nisn   = (int)$_POST['nisn'];
    $jumlah = (int)$_POST['jumlah'];

    // ❌ TIDAK ADA BULAN DI EDIT → JANGAN DIPAKAI

    query("UPDATE transaksi SET 
        nisn = '$nisn',
        jumlah = '$jumlah'
        WHERE id_transaksi = $id
    ");

    header("Location: kasmasuk.php?success=update");
    exit;
}

// ================= INSERT =================
if (isset($_POST['simpan'])) {

    $nisn   = (int)$_POST['nisn'];
    $tipe   = $_POST['tipe'];
    $bulan_list = $_POST['bulan'] ?? [];
    $tahun  = date('Y');

    $berhasil = false;

    // ================= BULANAN =================
    if ($tipe == 'bulanan') {

        $kas_bulanan = 20000;

        foreach ($bulan_list as $bulan) {

            $cek = query("SELECT IFNULL(SUM(jumlah),0) as total 
                FROM transaksi 
                WHERE nisn='$nisn'
                AND jenis='masuk'
                AND bulan='$bulan'
                AND tahun='$tahun'");

            if ($cek[0]['total'] < $kas_bulanan) {

                $tanggal = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-01';

                query("INSERT INTO transaksi 
                (nisn, tanggal, bulan, tahun, minggu, jenis, jumlah, kategori, keterangan)
                VALUES 
                ('$nisn', '$tanggal', '$bulan', '$tahun', NULL, 'masuk', '$kas_bulanan', 'Kas', 'Kas Bulanan')");
            }
        }

        $berhasil = true;
    }

    // ================= MINGGUAN =================
    if ($tipe == 'mingguan') {

        $minggu_list = $_POST['minggu'] ?? [];
        $bulan = $_POST['bulan'][0] ?? 0; // 🔥 WAJIB ADA
        $kas_mingguan = 5000;

        foreach ($minggu_list as $minggu) {

            $cek = query("SELECT IFNULL(SUM(jumlah),0) as total 
                FROM transaksi 
                WHERE nisn='$nisn'
                AND jenis='masuk'
                AND bulan='$bulan'
                AND tahun='$tahun'
                AND minggu='$minggu'");

            if ($cek[0]['total'] < $kas_mingguan) {

                $tanggal = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-01';

                query("INSERT INTO transaksi 
                (nisn, tanggal, bulan, tahun, minggu, jenis, jumlah, kategori, keterangan)
                VALUES 
                ('$nisn', '$tanggal', '$bulan', '$tahun', '$minggu', 'masuk', '$kas_mingguan', 'Kas', 'Kas Mingguan')");

                $berhasil = true;
            }
        }
    }

    header("Location: kasmasuk.php?" . ($berhasil ? "success=tambah" : "error=tidak_ada_tagihan"));
    exit;
}

// ================= DATA TABLE =================
$data = query("
    SELECT t.*, m.nama 
    FROM transaksi t
    JOIN murid m ON m.nisn = t.nisn
    $where
    ORDER BY t.id_transaksi DESC
");
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
                    <li><a class="nav-link" href="pengajuan.php"><i class="fa-solid fa-clock"></i>Pengajuan</a></li>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'bendahara'): ?>

                    <li><a class="nav-link active" href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a></li>
                    <li><a class="nav-link" href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a></li>
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
                    if ($_GET['error'] == 'tidak_ada_tagihan') {
                        echo '<strong>Gagal!</strong> Tidak ada tagihan yang bisa diproses atau sudah lunas di bulan tersebut.';
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
                    <label>Cari Kas Masuk</label>
                    <input type="text" name="search" class="form-control"
                        value="<?= $_GET['search'] ?? '' ?>">
                </div>

                <div class="col-md-3">
                    <label>Bulan</label>
                    <select name="bulan" class="form-control">
                        <option value="">-- Semua Bulan --</option>
                        <option value="1" <?= ($bulan_filter == 1 ? 'selected' : '') ?>>Januari</option>
                        <option value="2" <?= ($bulan_filter == 2 ? 'selected' : '') ?>>Februari</option>
                        <option value="3" <?= ($bulan_filter == 3 ? 'selected' : '') ?>>Maret</option>
                        <option value="4" <?= ($bulan_filter == 4 ? 'selected' : '') ?>>April</option>
                        <option value="5" <?= ($bulan_filter == 5 ? 'selected' : '') ?>>Mei</option>
                        <option value="6" <?= ($bulan_filter == 6 ? 'selected' : '') ?>>Juni</option>
                        <option value="7" <?= ($bulan_filter == 7 ? 'selected' : '') ?>>Juli</option>
                        <option value="8" <?= ($bulan_filter == 8 ? 'selected' : '') ?>>Agustun</option>
                        <option value="9" <?= ($bulan_filter == 9 ? 'selected' : '') ?>>September</option>
                        <option value="10" <?= ($bulan_filter == 10 ? 'selected' : '') ?>>Oktober</option>
                        <option value="11" <?= ($bulan_filter == 11 ? 'selected' : '') ?>>November</option>
                        <option value="12" <?= ($bulan_filter == 12 ? 'selected' : '') ?>>Desember</option>
                    </select>
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
                                <td>
                                    <?= date('d-m-Y', strtotime($row['tanggal'])) ?>
                                    (Bulan <?= $row['bulan'] ?>)
                                </td>
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

                    <!-- NAMA -->
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

                    <!-- Tipe pembayaran -->
                    <div class="mb-3">
                        <label>Tipe Pembayaran</label>
                        <select name="tipe" id="tipe" class="form-control" required>
                            <option selected>Pilih Pembayaran</option>
                            <option value="bulanan">Bulanan</option>
                            <option value="mingguan">Mingguan</option>
                        </select>
                    </div>

                    <!-- BULAN -->
                    <div class="mb-3">
                        <label>Pilih Bulan</label><br>

                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <label style="margin-right:10px;">
                                <input type="checkbox" name="bulan[]" value="<?= $i ?>">
                                <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                            </label>
                        <?php endfor; ?>
                    </div>

                    <!-- MINGGU -->
                    <div class="mb-3" id="mingguBox" style="display:none;">
                        <label>Pilih Minggu</label><br>

                        <label><input type="checkbox" name="minggu[]" value="1"> Minggu 1</label><br>
                        <label><input type="checkbox" name="minggu[]" value="2"> Minggu 2</label><br>
                        <label><input type="checkbox" name="minggu[]" value="3"> Minggu 3</label><br>
                        <label><input type="checkbox" name="minggu[]" value="4"> Minggu 4</label>
                    </div>

                    <!-- JUMLAH -->
                    <div class="mb-3">
                        <label>Total Bayar</label>
                        <input type="text" id="jumlah" class="form-control" readonly>
                        <input type="hidden" name="jumlah" id="jumlahHidden">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                </div>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const modal = document.getElementById('modalTambahKas');

            modal.addEventListener('shown.bs.modal', function() {

                const tipe = document.getElementById('tipe');
                const mingguBox = document.getElementById('mingguBox');
                const jumlah = document.getElementById('jumlah');
                const jumlahHidden = document.getElementById('jumlahHidden');

                const kasBulanan = 20000;
                const kasMingguan = 5000;

                const mingguCheckbox = document.querySelectorAll('input[name="minggu[]"]');
                const bulanCheckbox = document.querySelectorAll('input[name="bulan[]"]');

                function hitungMinggu() {
                    let total = 0;
                    mingguCheckbox.forEach(cb => {
                        if (cb.checked) total += kasMingguan;
                    });
                    return total;
                }

                function hitungBulan() {
                    let total = 0;
                    bulanCheckbox.forEach(cb => {
                        if (cb.checked) total += kasBulanan;
                    });
                    return total;
                }

                function formatRupiah(angka) {
                    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }

                function updateJumlah() {
                    let total = 0;

                    if (tipe.value === 'bulanan') {
                        total = hitungBulan();
                    } else {
                        total = hitungMinggu();
                    }

                    jumlah.value = formatRupiah(total);
                    jumlahHidden.value = total;
                }

                function toggleMinggu() {
                    if (tipe.value === 'mingguan') {
                        mingguBox.style.display = 'block';
                    } else {
                        mingguBox.style.display = 'none';
                        mingguCheckbox.forEach(cb => cb.checked = false);
                    }
                    updateJumlah();
                }

                tipe.onchange = toggleMinggu;
                mingguCheckbox.forEach(cb => cb.onchange = updateJumlah);
                bulanCheckbox.forEach(cb => cb.onchange = updateJumlah);

                toggleMinggu();
            });

        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>