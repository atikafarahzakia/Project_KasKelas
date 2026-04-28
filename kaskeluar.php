<?php
session_start();
include 'config/app.php';

if (!isset($_SESSION['login']))
    header("location:login.php");

// ================= AMBIL DATA =================
$ringkasan = ringkasanKasKeluar();

// ================= ENUM KATEGORI =================
$dataEnum    = query("SHOW COLUMNS FROM pengajuan LIKE 'kategori'");
$type        = $dataEnum[0]['Type'];
$type        = str_replace(["enum('", "')"], '', $type);
$kategoriList = explode("','", $type);

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];

// ================= TAMBAH PENGAJUAN =================
if (isset($_POST['simpan'])) {
    $jumlah     = (int) str_replace(['.', ','], '', $_POST['jumlah']);
    $kategori   = trim($_POST['kategori']);
    $keterangan = trim($_POST['keterangan']);
    $saldo_now  = (int)$ringkasan['saldo'];

    $errors = [];
    if ($jumlah <= 0)                              $errors[] = "Jumlah harus lebih dari 0.";
    if ($jumlah > $saldo_now)                      $errors[] = "Jumlah melebihi saldo saat ini (Rp " . number_format($saldo_now) . ").";
    if (!in_array($kategori, $kategoriList))       $errors[] = "Kategori tidak valid.";
    if (empty($keterangan))                        $errors[] = "Keterangan tidak boleh kosong.";

    // Upload bukti
    $bukti = null;
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === 0) {
        $ext     = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        if (!in_array($ext, $allowed)) $errors[] = "Format bukti harus jpg/jpeg/png.";
        if ($_FILES['bukti']['size'] > 2 * 1024 * 1024) $errors[] = "Ukuran bukti maksimal 2MB.";

        if (empty($errors)) {
            $namaBaru = uniqid() . '.' . $ext;
            if (!is_dir('upload')) mkdir('upload', 0777, true);
            move_uploaded_file($_FILES['bukti']['tmp_name'], 'upload/' . $namaBaru);
            $bukti = $namaBaru;
        }
    }

    if (!empty($errors)) {
        $err_msg = urlencode(implode('; ', $errors));
        header("Location: kaskeluar.php?error=validasi&msg=$err_msg");
        exit;
    }

    $bulan = date('n');
    $tahun = date('Y');
    $kategori_safe   = mysqli_real_escape_string($GLOBALS['db'], $kategori);
    $keterangan_safe = mysqli_real_escape_string($GLOBALS['db'], $keterangan);
    $bukti_val       = $bukti ? "'$bukti'" : "NULL";

    $stmt = mysqli_prepare($GLOBALS['db'],
        "INSERT INTO pengajuan (tanggal,bulan,tahun,jumlah,kategori,keterangan,bukti,status)
         VALUES (CURDATE(),?,?,?,?,?,?,'pending')");
    mysqli_stmt_bind_param($stmt, "iiisss", $bulan, $tahun, $jumlah, $kategori, $keterangan, $bukti);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: kaskeluar.php?success=tambah");
    exit;
}

// ================= UPDATE =================
if (isset($_POST['update'])) {
    $id         = (int)$_POST['id_pengajuan'];
    $jumlah     = (int)str_replace(['.', ','], '', $_POST['jumlah']);
    $kategori   = trim($_POST['kategori']);
    $keterangan = trim($_POST['keterangan']);

    $errors = [];
    if ($jumlah <= 0)                        $errors[] = "Jumlah harus lebih dari 0.";
    if (!in_array($kategori, $kategoriList)) $errors[] = "Kategori tidak valid.";
    if (empty($keterangan))                  $errors[] = "Keterangan tidak boleh kosong.";

    // Cek selisih vs saldo
    $dataLama = query("SELECT * FROM pengajuan WHERE id_pengajuan=$id");
    if (empty($dataLama)) { header("Location: kaskeluar.php"); exit; }
    $dataLama = $dataLama[0];

    if ($dataLama['status'] === 'disetujui') {
        $selisih = $jumlah - (int)$dataLama['jumlah'];
        if ($selisih > 0 && $selisih > (int)$ringkasan['saldo']) {
            $errors[] = "Kenaikan jumlah melebihi saldo (Rp " . number_format($ringkasan['saldo']) . ").";
        }
    }

    if (!empty($errors)) {
        $err_msg = urlencode(implode('; ', $errors));
        header("Location: kaskeluar.php?error=validasi&msg=$err_msg");
        exit;
    }

    $kategori_safe   = mysqli_real_escape_string($GLOBALS['db'], $kategori);
    $keterangan_safe = mysqli_real_escape_string($GLOBALS['db'], $keterangan);
    query("UPDATE pengajuan SET jumlah='$jumlah', kategori='$kategori_safe', keterangan='$keterangan_safe' WHERE id_pengajuan=$id");

    header("Location: kaskeluar.php?success=update");
    exit;
}

// ================= HAPUS =================
if (isset($_GET['hapus'])) {
    $id   = (int)$_GET['hapus'];
    $cek  = query("SELECT status FROM pengajuan WHERE id_pengajuan=$id");

    if (empty($cek)) { header("Location: kaskeluar.php"); exit; }

    if ($cek[0]['status'] === 'disetujui') {
        header("Location: kaskeluar.php?error=tidak_boleh_hapus");
        exit;
    }

    query("DELETE FROM pengajuan WHERE id_pengajuan=$id");
    header("Location: kaskeluar.php?success=delete");
    exit;
}

// ================= FILTER + DATA =================
$search        = $_GET['search']  ?? '';
$status_filter = $_GET['status']  ?? '';
$bulan_filter  = $_GET['bulan']   ?? '';

$where = "1=1";
if (!empty($search)) {
    $s = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND (kategori LIKE '%$s%' OR keterangan LIKE '%$s%')";
}
if (!empty($status_filter)) {
    $sf = mysqli_real_escape_string($GLOBALS['db'], $status_filter);
    $where .= " AND status = '$sf'";
}
if (!empty($bulan_filter)) {
    $where .= " AND bulan = " . (int)$bulan_filter;
}

$pengajuan = query("SELECT * FROM pengajuan WHERE $where ORDER BY tanggal DESC");
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kas Keluar — Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style/kaskeluar.css">
</head>
<body>
<div class="app">

    <div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

    <!-- SIDEBAR -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="fas fa-coins"></i></div>
            <span>Kas Kelas</span>
        </div>
        <div class="sidebar-divider"></div>
        <div class="sidebar-profile">
            <img src="assets/profile.jpg" alt="Profil"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username']) ?>&background=3b82f6&color=fff&size=80'">
            <div>
                <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="role"><?= htmlspecialchars($_SESSION['role']) ?></div>
            </div>
        </div>
        <div class="sidebar-divider"></div>
        <div class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>

            <?php if ($_SESSION['role'] == 'wali kelas'): ?>
                <a href="datamurid.php"><i class="fas fa-users"></i> Data Murid</a>
                <a href="pengajuan.php"><i class="fas fa-clock"></i> Pengajuan</a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'bendahara'): ?>
                <a href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a>
                <a href="kaskeluar.php" class="active"><i class="fas fa-arrow-up"></i> Kas Keluar</a>
            <?php endif; ?>

            <a href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a>
            <a href="statusbayar.php"><i class="fas fa-chart-column"></i> Status Bayar</a>
            <a href="laporan.php"><i class="fas fa-file"></i> Laporan</a>
            <div class="sidebar-divider" style="margin:12px 0 8px;"></div>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="content">

        <div class="topbar">
            <button class="hamburger" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
            <h1><i class="fas fa-arrow-up" style="color:var(--danger);font-size:.9rem;margin-right:6px;"></i>Kas Keluar</h1>
            <button class="btn btn-danger" onclick="openModal('modalTambah')">
                <i class="fas fa-plus"></i> Tambah Pengajuan
            </button>
        </div>

        <!-- STAT CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--danger-bg);color:var(--danger);"><i class="fas fa-arrow-up-from-line"></i></div>
                <div>
                    <div class="stat-label">Total Kas Keluar</div>
                    <div class="stat-value" style="color:var(--danger)">Rp <?= number_format($ringkasan['totalKasKeluar']) ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--success-bg);color:var(--success);"><i class="fas fa-wallet"></i></div>
                <div>
                    <div class="stat-label">Saldo Saat Ini</div>
                    <div class="stat-value" style="color:var(--success)">Rp <?= number_format($ringkasan['saldo']) ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--warning-bg);color:var(--warning);"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="stat-label">Pending</div>
                    <?php $jml_pending = count(array_filter($pengajuan, fn($r) => $r['status'] === 'pending')); ?>
                    <div class="stat-value" style="color:var(--warning)"><?= $jml_pending ?> pengajuan</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--info-bg);color:var(--info);"><i class="fas fa-list"></i></div>
                <div>
                    <div class="stat-label">Total Pengajuan</div>
                    <div class="stat-value" style="color:var(--info)"><?= count($pengajuan) ?> data</div>
                </div>
            </div>
        </div>

        <!-- ALERTS -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" id="autoAlert">
                <i class="fas fa-circle-check"></i>
                <span>
                    <?php
                    if ($_GET['success'] === 'tambah') echo 'Pengajuan kas keluar berhasil ditambahkan!';
                    if ($_GET['success'] === 'update') echo 'Data kas keluar berhasil diperbarui!';
                    if ($_GET['success'] === 'delete') echo 'Data kas keluar berhasil dihapus!';
                    ?>
                </span>
                <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-circle-exclamation"></i>
                <span>
                    <?php
                    if ($_GET['error'] === 'validasi')         echo htmlspecialchars(urldecode($_GET['msg'] ?? ''));
                    if ($_GET['error'] === 'saldo')            echo 'Jumlah melebihi saldo kas yang tersedia.';
                    if ($_GET['error'] === 'tidak_boleh_hapus') echo 'Pengajuan yang sudah disetujui tidak dapat dihapus.';
                    ?>
                </span>
                <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <!-- FILTER TOOLBAR -->
        <form method="GET">
            <div class="toolbar">
                <div class="field search-field">
                    <label>Cari Keterangan / Kategori</label>
                    <input type="text" name="search" placeholder="Ketik kata kunci..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="field">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="pending"   <?= $status_filter === 'pending'   ? 'selected' : '' ?>>Pending</option>
                        <option value="disetujui" <?= $status_filter === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak"   <?= $status_filter === 'ditolak'   ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
                <div class="field">
                    <label>Bulan</label>
                    <select name="bulan">
                        <option value="">Semua Bulan</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= ((int)$bulan_filter === $i) ? 'selected' : '' ?>>
                                <?= $nama_bulan[$i] ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="field" style="flex-direction:row;gap:8px;padding-bottom:1px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    <a href="kaskeluar.php" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> Reset</a>
                </div>
            </div>
        </form>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Kategori</th>
                            <th>Keterangan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pengajuan)): ?>
                            <tr class="empty-row">
                                <td colspan="7">
                                    <i class="fas fa-inbox" style="font-size:1.5rem;opacity:.4;display:block;margin-bottom:6px;"></i>
                                    Tidak ada data pengajuan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pengajuan as $i => $row): ?>
                                <tr>
                                    <td style="color:var(--muted);font-size:.8rem;"><?= $i + 1 ?></td>
                                    <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td style="font-weight:700;color:var(--danger);">−Rp <?= number_format($row['jumlah']) ?></td>
                                    <td><span class="badge-cat"><i class="fas fa-tag" style="font-size:.7rem;"></i> <?= htmlspecialchars($row['kategori']) ?></span></td>
                                    <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($row['keterangan']) ?>">
                                        <?= htmlspecialchars($row['keterangan']) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $st = $row['status'];
                                        $cls = $st === 'pending' ? 'badge-pending' : ($st === 'disetujui' ? 'badge-disetujui' : 'badge-ditolak');
                                        $ico = $st === 'pending' ? 'fa-clock' : ($st === 'disetujui' ? 'fa-circle-check' : 'fa-circle-xmark');
                                        ?>
                                        <span class="status-badge <?= $cls ?>">
                                            <i class="fas <?= $ico ?>" style="font-size:.7rem;"></i>
                                            <?= ucfirst($st) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group-action">
                                            <button class="btn btn-info btn-sm"
                                                onclick='openDetail(<?= json_encode($row) ?>)'>
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($row['status'] !== 'disetujui'): ?>
                                                <button class="btn btn-warning btn-sm"
                                                    onclick='openEditModal(<?= json_encode($row) ?>)'>
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="btn btn-danger btn-sm"
                                                    onclick="confirmHapus(<?= $row['id_pengajuan'] ?>, '<?= date('d M Y', strtotime($row['tanggal'])) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <span style="font-size:.75rem;color:var(--muted);padding:0 4px;">Terkunci</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- ─── MODAL TAMBAH ─── -->
<div class="modal-backdrop" id="modalTambah" onclick="closeOnBackdrop(event,'modalTambah')">
    <div class="modal-box">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle" style="color:var(--danger);margin-right:6px;"></i>Tambah Pengajuan Kas Keluar</h2>
            <button class="modal-close" onclick="closeModal('modalTambah')"><i class="fas fa-xmark"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" id="formTambah" onsubmit="return validateTambah()">
            <div class="modal-body">

                <div class="saldo-info">
                    <i class="fas fa-wallet"></i>
                    Saldo tersedia: <strong>Rp <?= number_format($ringkasan['saldo']) ?></strong>
                </div>

                <div class="form-group">
                    <label>Jumlah (Rp) <span class="req">*</span></label>
                    <input type="text" name="jumlah" id="t_jumlah" class="form-control" placeholder="Contoh: 50.000" required oninput="formatInput(this)" inputmode="numeric">
                    <div class="invalid-msg" id="err_t_jumlah">Jumlah tidak valid atau melebihi saldo.</div>
                </div>

                <div class="form-group">
                    <label>Kategori <span class="req">*</span></label>
                    <select name="kategori" id="t_kategori" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategoriList as $k): ?>
                            <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-msg" id="err_t_kategori">Kategori harus dipilih.</div>
                </div>

                <div class="form-group">
                    <label>Keterangan <span class="req">*</span></label>
                    <textarea name="keterangan" id="t_keterangan" class="form-control" placeholder="Jelaskan keperluan pengeluaran..." required></textarea>
                    <div class="invalid-msg" id="err_t_keterangan">Keterangan tidak boleh kosong.</div>
                </div>

                <div class="form-group">
                    <label>Bukti <span style="color:var(--muted);font-weight:400;">(Opsional, maks 2MB)</span></label>
                    <input type="file" name="bukti" id="t_bukti" class="form-control" accept=".jpg,.jpeg,.png">
                    <div class="form-hint">Format: JPG, JPEG, PNG. Ukuran maks 2MB.</div>
                    <div class="invalid-msg" id="err_t_bukti">Format atau ukuran file tidak valid.</div>
                    <div id="previewWrap" style="display:none;margin-top:8px;">
                        <img id="imgPreview" style="max-width:100%;border-radius:var(--radius-sm);border:1px solid var(--border);">
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" name="simpan" class="btn btn-danger"><i class="fas fa-floppy-disk"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── MODAL EDIT ─── -->
<div class="modal-backdrop" id="modalEdit" onclick="closeOnBackdrop(event,'modalEdit')">
    <div class="modal-box">
        <div class="modal-header">
            <h2><i class="fas fa-pen" style="color:var(--warning);margin-right:6px;"></i>Edit Pengajuan</h2>
            <button class="modal-close" onclick="closeModal('modalEdit')"><i class="fas fa-xmark"></i></button>
        </div>
        <form method="POST" id="formEdit" onsubmit="return validateEdit()">
            <div class="modal-body">
                <input type="hidden" name="id_pengajuan" id="e_id">

                <div class="form-group">
                    <label>Jumlah (Rp) <span class="req">*</span></label>
                    <input type="text" name="jumlah" id="e_jumlah" class="form-control" required oninput="formatInput(this)" inputmode="numeric">
                    <div class="invalid-msg" id="err_e_jumlah">Jumlah tidak valid (min Rp 1.000).</div>
                </div>

                <div class="form-group">
                    <label>Kategori <span class="req">*</span></label>
                    <select name="kategori" id="e_kategori" class="form-control" required>
                        <?php foreach ($kategoriList as $k): ?>
                            <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-msg" id="err_e_kategori">Kategori harus dipilih.</div>
                </div>

                <div class="form-group">
                    <label>Keterangan <span class="req">*</span></label>
                    <textarea name="keterangan" id="e_keterangan" class="form-control" required></textarea>
                    <div class="invalid-msg" id="err_e_keterangan">Keterangan tidak boleh kosong.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit')">Batal</button>
                <button type="submit" name="update" class="btn btn-warning"><i class="fas fa-floppy-disk"></i> Perbarui</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── MODAL DETAIL ─── -->
<div class="modal-backdrop" id="modalDetail" onclick="closeOnBackdrop(event,'modalDetail')">
    <div class="modal-box">
        <div class="modal-header">
            <h2><i class="fas fa-circle-info" style="color:var(--info);margin-right:6px;"></i>Detail Pengajuan</h2>
            <button class="modal-close" onclick="closeModal('modalDetail')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="detail-row"><span class="detail-label">Tanggal</span>   <span class="detail-value" id="d_tanggal"></span></div>
            <div class="detail-row"><span class="detail-label">Jumlah</span>    <span class="detail-value" id="d_jumlah" style="color:var(--danger);"></span></div>
            <div class="detail-row"><span class="detail-label">Kategori</span>  <span class="detail-value" id="d_kategori"></span></div>
            <div class="detail-row"><span class="detail-label">Status</span>    <span class="detail-value" id="d_status"></span></div>
            <div class="detail-row" style="flex-direction:column;gap:4px;">
                <span class="detail-label">Keterangan</span>
                <span id="d_keterangan" style="font-size:.875rem;line-height:1.6;"></span>
            </div>
            <div id="d_bukti_wrap" style="display:none;margin-top:12px;">
                <div class="detail-label" style="margin-bottom:6px;">Bukti</div>
                <img id="d_bukti" style="max-width:100%;border-radius:var(--radius-sm);border:1px solid var(--border);">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modalDetail')">Tutup</button>
        </div>
    </div>
</div>

<!-- ─── MODAL KONFIRMASI HAPUS ─── -->
<div class="modal-backdrop" id="modalHapus" onclick="closeOnBackdrop(event,'modalHapus')">
    <div class="modal-box sm">
        <div class="modal-header">
            <h2 style="color:var(--danger);"><i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>Konfirmasi Hapus</h2>
            <button class="modal-close" onclick="closeModal('modalHapus')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:.9rem;">Hapus pengajuan tanggal <strong id="hapusTanggal"></strong>?</p>
            <p style="font-size:.82rem;color:var(--muted);margin-top:6px;">Pengajuan yang sudah disetujui tidak dapat dihapus. Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modalHapus')">Batal</button>
            <a id="hapusLink" href="#" class="btn btn-danger"><i class="fas fa-trash"></i> Ya, Hapus</a>
        </div>
    </div>
</div>

<script>
    const SALDO = <?= (int)$ringkasan['saldo'] ?>;

    // ── Sidebar ──
    function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('show'); document.body.style.overflow='hidden'; }
    function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('show'); document.body.style.overflow=''; }
    document.addEventListener('keydown', e => { if (e.key==='Escape') { closeSidebar(); closeAllModals(); } });

    // ── Modal ──
    function openModal(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
    function closeAllModals() { document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open')); document.body.style.overflow=''; }
    function closeOnBackdrop(e, id) { if (e.target === e.currentTarget) closeModal(id); }

    // ── Auto dismiss ──
    const autoAlert = document.getElementById('autoAlert');
    if (autoAlert) setTimeout(() => autoAlert.remove(), 4000);

    // ── Rupiah format ──
    function formatInput(el) {
        let raw = el.value.replace(/\D/g, '');
        el.value = raw ? parseInt(raw, 10).toLocaleString('id-ID') : '';
    }
    function parseRupiah(str) {
        return parseInt(str.replace(/\./g, '').replace(/,/g, ''), 10) || 0;
    }

    // ── Image preview ──
    document.getElementById('t_bukti').addEventListener('change', function() {
        const file = this.files[0];
        const wrap = document.getElementById('previewWrap');
        const img  = document.getElementById('imgPreview');
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; wrap.style.display = 'block'; };
            reader.readAsDataURL(file);
        } else { wrap.style.display = 'none'; }
    });

    // ── Validate Tambah ──
    function validateTambah() {
        let ok = true;
        const jumlah    = document.getElementById('t_jumlah');
        const kategori  = document.getElementById('t_kategori');
        const ket       = document.getElementById('t_keterangan');
        const bukti     = document.getElementById('t_bukti');

        [jumlah, kategori, ket].forEach(el => el.classList.remove('is-invalid'));
        ['err_t_jumlah','err_t_kategori','err_t_keterangan','err_t_bukti'].forEach(id => document.getElementById(id).style.display='none');

        const jml = parseRupiah(jumlah.value);
        if (jml < 1000) {
            jumlah.classList.add('is-invalid');
            document.getElementById('err_t_jumlah').style.display = 'block';
            document.getElementById('err_t_jumlah').textContent = 'Jumlah minimal Rp 1.000.';
            ok = false;
        } else if (jml > SALDO) {
            jumlah.classList.add('is-invalid');
            document.getElementById('err_t_jumlah').style.display = 'block';
            document.getElementById('err_t_jumlah').textContent = 'Jumlah melebihi saldo tersedia (Rp ' + SALDO.toLocaleString('id-ID') + ').';
            ok = false;
        }
        if (!kategori.value) { kategori.classList.add('is-invalid'); document.getElementById('err_t_kategori').style.display='block'; ok=false; }
        if (!ket.value.trim()) { ket.classList.add('is-invalid'); document.getElementById('err_t_keterangan').style.display='block'; ok=false; }

        if (bukti.files.length > 0) {
            const file = bukti.files[0];
            const ext  = file.name.split('.').pop().toLowerCase();
            if (!['jpg','jpeg','png'].includes(ext) || file.size > 2 * 1024 * 1024) {
                document.getElementById('err_t_bukti').style.display = 'block';
                ok = false;
            }
        }
        return ok;
    }

    // ── Edit Modal ──
    function openEditModal(row) {
        document.getElementById('e_id').value = row.id_pengajuan;
        const jEl = document.getElementById('e_jumlah');
        jEl.value = parseInt(row.jumlah).toLocaleString('id-ID');
        document.getElementById('e_kategori').value  = row.kategori;
        document.getElementById('e_keterangan').value = row.keterangan;

        ['e_jumlah','e_kategori','e_keterangan'].forEach(id => document.getElementById(id).classList.remove('is-invalid'));
        ['err_e_jumlah','err_e_kategori','err_e_keterangan'].forEach(id => document.getElementById(id).style.display='none');

        openModal('modalEdit');
    }

    function validateEdit() {
        let ok = true;
        const jumlah   = document.getElementById('e_jumlah');
        const kategori = document.getElementById('e_kategori');
        const ket      = document.getElementById('e_keterangan');

        [jumlah, kategori, ket].forEach(el => el.classList.remove('is-invalid'));
        ['err_e_jumlah','err_e_kategori','err_e_keterangan'].forEach(id => document.getElementById(id).style.display='none');

        if (parseRupiah(jumlah.value) < 1000) { jumlah.classList.add('is-invalid'); document.getElementById('err_e_jumlah').style.display='block'; ok=false; }
        if (!kategori.value) { kategori.classList.add('is-invalid'); document.getElementById('err_e_kategori').style.display='block'; ok=false; }
        if (!ket.value.trim()) { ket.classList.add('is-invalid'); document.getElementById('err_e_keterangan').style.display='block'; ok=false; }
        return ok;
    }

    // ── Detail Modal ──
    function openDetail(row) {
        const stMap = { pending:'⏳ Pending', disetujui:'✅ Disetujui', ditolak:'❌ Ditolak' };
        document.getElementById('d_tanggal').textContent   = new Date(row.tanggal).toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'});
        document.getElementById('d_jumlah').textContent    = '−Rp ' + parseInt(row.jumlah).toLocaleString('id-ID');
        document.getElementById('d_kategori').textContent  = row.kategori;
        document.getElementById('d_status').textContent    = stMap[row.status] ?? row.status;
        document.getElementById('d_keterangan').textContent = row.keterangan;

        const buktiWrap = document.getElementById('d_bukti_wrap');
        const buktiImg  = document.getElementById('d_bukti');
        if (row.bukti) {
            buktiImg.src = 'upload/' + row.bukti;
            buktiWrap.style.display = 'block';
        } else {
            buktiWrap.style.display = 'none';
        }
        openModal('modalDetail');
    }

    // ── Konfirmasi Hapus ──
    function confirmHapus(id, tanggal) {
        document.getElementById('hapusTanggal').textContent = tanggal;
        document.getElementById('hapusLink').href = '?hapus=' + id;
        openModal('modalHapus');
    }
</script>
</body>
</html>