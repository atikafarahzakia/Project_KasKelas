<?php
include 'config/app.php';
session_start();

if (!isset($_SESSION['login']))
    header("location:login.php");

// ================= AMBIL DATA =================
$murid    = query("SELECT * FROM murid ORDER BY nama ASC");
$ringkasan = ringkasanKasMasuk();

// ================= FILTER =================
$search       = $_GET['search'] ?? '';
$bulan_filter = $_GET['bulan']  ?? '';

$where = "WHERE t.jenis = 'masuk'";

if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND m.nama LIKE '%$search_safe%'";
}
if (!empty($bulan_filter)) {
    $where .= " AND t.bulan = " . (int)$bulan_filter;
}

// ================= DELETE =================
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        query("DELETE FROM transaksi WHERE id_transaksi = $id");
        header("Location: kasmasuk.php?success=delete");
        exit;
    }
}

// ================= UPDATE =================
if (isset($_POST['update'])) {
    $id     = (int)$_POST['id_transaksi'];
    $nisn   = (int)$_POST['nisn'];
    $jumlah = (int)$_POST['jumlah'];

    $errors_update = [];
    if ($nisn <= 0)   $errors_update[] = "Siswa harus dipilih.";
    if ($jumlah <= 0) $errors_update[] = "Jumlah harus lebih dari 0.";

    if (empty($errors_update)) {
        query("UPDATE transaksi SET nisn='$nisn', jumlah='$jumlah' WHERE id_transaksi=$id");
        header("Location: kasmasuk.php?success=update");
        exit;
    }
}

// ================= INSERT =================
if (isset($_POST['simpan'])) {
    $nisn       = (int)$_POST['nisn'];
    $tipe       = $_POST['tipe'] ?? '';
    $bulan_list = $_POST['bulan'] ?? [];
    $tahun      = date('Y');
    $berhasil   = false;

    // Validasi server-side
    $errors_insert = [];
    if ($nisn <= 0)                  $errors_insert[] = "Siswa harus dipilih.";
    if (!in_array($tipe, ['bulanan', 'mingguan'])) $errors_insert[] = "Tipe pembayaran tidak valid.";
    if (empty($bulan_list))          $errors_insert[] = "Pilih minimal satu bulan.";

    if (empty($errors_insert)) {

        if ($tipe === 'bulanan') {
            $kas_bulanan = 20000;
            foreach ($bulan_list as $bulan) {
                $bulan = (int)$bulan;
                $cek = query("SELECT IFNULL(SUM(jumlah),0) as total FROM transaksi 
                    WHERE nisn='$nisn' AND jenis='masuk' AND bulan='$bulan' AND tahun='$tahun'");
                if ($cek[0]['total'] < $kas_bulanan) {
                    $tanggal = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-01';
                    query("INSERT INTO transaksi (nisn,tanggal,bulan,tahun,minggu,jenis,jumlah,kategori,keterangan)
                        VALUES ('$nisn','$tanggal','$bulan','$tahun',NULL,'masuk','$kas_bulanan','Kas','Kas Bulanan')");
                    $berhasil = true;
                }
            }
        }

        if ($tipe === 'mingguan') {
            $minggu_list  = $_POST['minggu'] ?? [];
            $bulan        = (int)($bulan_list[0] ?? 0);
            $kas_mingguan = 5000;
            if (empty($minggu_list)) $errors_insert[] = "Pilih minimal satu minggu.";
            if ($bulan <= 0)         $errors_insert[] = "Pilih bulan untuk pembayaran mingguan.";

            if (empty($errors_insert)) {
                foreach ($minggu_list as $minggu) {
                    $minggu = (int)$minggu;
                    $cek = query("SELECT IFNULL(SUM(jumlah),0) as total FROM transaksi 
                        WHERE nisn='$nisn' AND jenis='masuk' AND bulan='$bulan' AND tahun='$tahun' AND minggu='$minggu'");
                    if ($cek[0]['total'] < $kas_mingguan) {
                        $tanggal = $tahun . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-01';
                        query("INSERT INTO transaksi (nisn,tanggal,bulan,tahun,minggu,jenis,jumlah,kategori,keterangan)
                            VALUES ('$nisn','$tanggal','$bulan','$tahun','$minggu','masuk','$kas_mingguan','Kas','Kas Mingguan')");
                        $berhasil = true;
                    }
                }
            }
        }
    }

    if (!empty($errors_insert)) {
        $err_msg = urlencode(implode('; ', $errors_insert));
        header("Location: kasmasuk.php?error=validasi&msg=$err_msg");
    } else {
        header("Location: kasmasuk.php?" . ($berhasil ? "success=tambah" : "error=tidak_ada_tagihan"));
    }
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

$nama_bulan = [
    '',
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
];
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kas Masuk — Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style/kasmasuk.css">
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
                    <a href="kasmasuk.php" class="active"><i class="fas fa-arrow-down"></i> Kas Masuk</a>
                    <a href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a>
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
                <h1><i class="fas fa-arrow-down" style="color:var(--brand);font-size:.9rem;margin-right:6px;"></i>Kas Masuk</h1>
                <button class="btn btn-primary" onclick="openModal('modalTambah')">
                    <i class="fas fa-plus"></i> Tambah Kas Masuk
                </button>
            </div>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-arrow-down-to-line"></i></div>
                    <div>
                        <div class="stat-label">Total Kas Masuk</div>
                        <div class="stat-value" style="color:var(--brand)">Rp <?= number_format($ringkasan['totalKasMasuk']) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-wallet"></i></div>
                    <div>
                        <div class="stat-label">Saldo Saat Ini</div>
                        <div class="stat-value" style="color:var(--success)">Rp <?= number_format($ringkasan['saldo']) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#faf5ff;color:#7c3aed;"><i class="fas fa-list-check"></i></div>
                    <div>
                        <div class="stat-label">Total Transaksi</div>
                        <div class="stat-value" style="color:#7c3aed"><?= count($data) ?> data</div>
                    </div>
                </div>
            </div>

            <!-- ALERTS -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" id="autoAlert">
                    <i class="fas fa-circle-check"></i>
                    <span>
                        <?php
                        if ($_GET['success'] === 'tambah') echo 'Kas masuk berhasil ditambahkan!';
                        if ($_GET['success'] === 'update') echo 'Data kas masuk berhasil diperbarui!';
                        if ($_GET['success'] === 'delete') echo 'Data kas masuk berhasil dihapus!';
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
                        if ($_GET['error'] === 'tidak_ada_tagihan') echo 'Tidak ada tagihan yang bisa diproses atau semua sudah lunas di bulan tersebut.';
                        if ($_GET['error'] === 'validasi') echo htmlspecialchars(urldecode($_GET['msg'] ?? ''));
                        if ($_GET['error'] === 'melebihi_batas') echo 'Jumlah pembayaran melebihi batas tagihan yang tersisa.';
                        ?>
                    </span>
                    <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-xmark"></i></button>
                </div>
            <?php endif; ?>

            <!-- FILTER TOOLBAR -->
            <form method="GET">
                <div class="toolbar">
                    <div class="field search-field">
                        <label>Cari Nama Siswa</label>
                        <input type="text" name="search" placeholder="Ketik nama..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="field">
                        <label>Filter Bulan</label>
                        <select name="bulan">
                            <option value="">Semua Bulan</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= ((int)$bulan_filter === $i) ? 'selected' : '' ?>>
                                    <?= $nama_bulan[$i] ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="field" style="flex-direction:row; gap:8px; padding-bottom:1px;">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                        <a href="kasmasuk.php" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> Reset</a>
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
                                <th>Nama Siswa</th>
                                <th>Bulan</th>
                                <th>Tipe</th>
                                <th>Jumlah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <tr class="empty-row">
                                    <td colspan="7">
                                        <i class="fas fa-inbox" style="font-size:1.5rem;opacity:.4;display:block;margin-bottom:6px;"></i>
                                        Tidak ada data kas masuk
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data as $i => $row): ?>
                                    <tr>
                                        <td style="color:var(--muted);font-size:.8rem;"><?= $i + 1 ?></td>
                                        <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                        <td style="font-weight:600;"><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= $nama_bulan[(int)$row['bulan']] ?? '-' ?></td>
                                        <td>
                                            <?php if (!empty($row['minggu'])): ?>
                                                <span class="badge-type badge-mingguan"><i class="fas fa-calendar-week" style="font-size:.7rem;"></i> Minggu <?= $row['minggu'] ?></span>
                                            <?php else: ?>
                                                <span class="badge-type badge-bulanan"><i class="fas fa-calendar" style="font-size:.7rem;"></i> Bulanan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight:700;color:var(--success);">+Rp <?= number_format($row['jumlah']) ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm"
                                                onclick="openEditModal(<?= $row['id_transaksi'] ?>, <?= $row['nisn'] ?>, <?= $row['jumlah'] ?>)">
                                                <i class="fas fa-pen"></i> Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" style="margin-left:4px;"
                                                onclick="confirmHapus(<?= $row['id_transaksi'] ?>, '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
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

    <!-- ─── MODAL TAMBAH KAS ─── -->
    <div class="modal-backdrop" id="modalTambah" onclick="closeOnBackdrop(event,'modalTambah')">
        <div class="modal-box">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle" style="color:var(--brand);margin-right:6px;"></i>Tambah Kas Masuk</h2>
                <button class="modal-close" onclick="closeModal('modalTambah')"><i class="fas fa-xmark"></i></button>
            </div>
            <form method="POST" id="formTambah" onsubmit="return validateFormTambah()">
                <div class="modal-body">

                    <div class="form-group">
                        <label>Nama Siswa <span class="req">*</span></label>
                        <select name="nisn" id="t_nisn" class="form-control" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($murid as $s): ?>
                                <option value="<?= $s['nisn'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-msg" id="err_nisn">Siswa harus dipilih.</div>
                    </div>

                    <div class="form-group">
                        <label>Tipe Pembayaran <span class="req">*</span></label>
                        <select name="tipe" id="t_tipe" class="form-control" required onchange="onTipeChange()">
                            <option value="">-- Pilih Tipe --</option>
                            <option value="bulanan">Bulanan (Rp 20.000/bulan)</option>
                            <option value="mingguan">Mingguan (Rp 5.000/minggu)</option>
                        </select>
                        <div class="invalid-msg" id="err_tipe">Tipe pembayaran harus dipilih.</div>
                    </div>

                    <div class="form-group">
                        <label>Pilih Bulan <span class="req">*</span></label>
                        <div class="check-grid" id="bulanGrid">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <label class="check-label" id="bl_<?= $i ?>">
                                    <input type="checkbox" name="bulan[]" value="<?= $i ?>" onchange="onBulanChange(this, <?= $i ?>)">
                                    <?= $nama_bulan[$i] ?>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div class="invalid-msg" id="err_bulan">Pilih minimal satu bulan.</div>
                    </div>

                    <div class="form-group" id="mingguGroup" style="display:none;">
                        <label>Pilih Minggu <span class="req">*</span></label>
                        <div class="check-grid" style="grid-template-columns:repeat(2,1fr);">
                            <?php for ($m = 1; $m <= 4; $m++): ?>
                                <label class="check-label" id="mw_<?= $m ?>">
                                    <input type="checkbox" name="minggu[]" value="<?= $m ?>" onchange="onMingguChange(this, <?= $m ?>)">
                                    Minggu <?= $m ?>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div class="invalid-msg" id="err_minggu">Pilih minimal satu minggu.</div>
                    </div>

                    <div class="form-group">
                        <div class="total-preview">
                            <span class="tp-label">Total Bayar</span>
                            <span class="tp-value" id="totalPreview">Rp 0</span>
                        </div>
                        <input type="hidden" name="jumlah" id="t_jumlahHidden">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                    <button type="submit" name="simpan" class="btn btn-success"><i class="fas fa-floppy-disk"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL EDIT ─── -->
    <div class="modal-backdrop" id="modalEdit" onclick="closeOnBackdrop(event,'modalEdit')">
        <div class="modal-box">
            <div class="modal-header">
                <h2><i class="fas fa-pen" style="color:var(--warning);margin-right:6px;"></i>Edit Kas Masuk</h2>
                <button class="modal-close" onclick="closeModal('modalEdit')"><i class="fas fa-xmark"></i></button>
            </div>
            <form method="POST" id="formEdit" onsubmit="return validateFormEdit()">
                <div class="modal-body">
                    <input type="hidden" name="id_transaksi" id="e_id">

                    <div class="form-group">
                        <label>Nama Siswa <span class="req">*</span></label>
                        <select name="nisn" id="e_nisn" class="form-control" required>
                            <?php foreach ($murid as $s): ?>
                                <option value="<?= $s['nisn'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-msg" id="err_e_nisn">Siswa harus dipilih.</div>
                    </div>

                    <div class="form-group">
                        <label>Jumlah (Rp) <span class="req">*</span></label>
                        <input type="number" name="jumlah" id="e_jumlah" class="form-control" min="1000" step="1000" required placeholder="Contoh: 20000">
                        <div class="form-hint">Minimal Rp 1.000, kelipatan Rp 1.000</div>
                        <div class="invalid-msg" id="err_e_jumlah">Jumlah tidak valid (min Rp 1.000).</div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit')">Batal</button>
                    <button type="submit" name="update" class="btn btn-warning"><i class="fas fa-floppy-disk"></i> Perbarui</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL KONFIRMASI HAPUS ─── -->
    <div class="modal-backdrop" id="modalHapus" onclick="closeOnBackdrop(event,'modalHapus')">
        <div class="modal-box" style="max-width:380px;">
            <div class="modal-header">
                <h2 style="color:var(--danger);"><i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>Konfirmasi Hapus</h2>
                <button class="modal-close" onclick="closeModal('modalHapus')"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="modal-body">
                <p style="font-size:.9rem;">Anda yakin ingin menghapus data kas masuk atas nama <strong id="hapusNama"></strong>?</p>
                <p style="font-size:.82rem;color:var(--muted);margin-top:6px;">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modalHapus')">Batal</button>
                <a id="hapusLink" href="#" class="btn btn-danger"><i class="fas fa-trash"></i> Ya, Hapus</a>
            </div>
        </div>
    </div>

    <script>
        // ── Sidebar ──
        function openSidebar() {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('overlay').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('show');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeSidebar();
                closeAllModals();
            }
        });

        // ── Modal helpers ──
        function openModal(id) {
            document.getElementById(id).classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
            document.body.style.overflow = '';
        }

        function closeOnBackdrop(e, id) {
            if (e.target === e.currentTarget) closeModal(id);
        }

        function closeAllModals() {
            document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
            document.body.style.overflow = '';
        }

        // ── Auto-dismiss success alert ──
        const autoAlert = document.getElementById('autoAlert');
        if (autoAlert) setTimeout(() => autoAlert.remove(), 4000);

        // ── Form Tambah Logic ──
        const KAS_BULANAN = 20000;
        const KAS_MINGGUAN = 5000;

        function onTipeChange() {
            const tipe = document.getElementById('t_tipe').value;
            const mingguGroup = document.getElementById('mingguGroup');
            const bulanGrid = document.getElementById('bulanGrid');

            mingguGroup.style.display = (tipe === 'mingguan') ? 'block' : 'none';

            // Untuk mingguan, only allow single bulan selection
            if (tipe === 'mingguan') {
                bulanGrid.querySelectorAll('input').forEach(cb => {
                    cb.checked = false;
                    cb.type = 'radio';
                    cb.name = 'bulan[]';
                });
            } else {
                bulanGrid.querySelectorAll('input').forEach(cb => {
                    cb.checked = false;
                    cb.type = 'checkbox';
                    cb.name = 'bulan[]';
                });
            }
            // clear minggu
            document.querySelectorAll('input[name="minggu[]"]').forEach(cb => {
                cb.checked = false;
                document.getElementById('mw_' + cb.value).classList.remove('checked');
            });
            updateCheckLabelStyles();
            hitungTotal();
        }

        function onBulanChange(cb, i) {
            document.getElementById('bl_' + i).classList.toggle('checked', cb.checked);
            hitungTotal();
        }

        function onMingguChange(cb, i) {
            document.getElementById('mw_' + i).classList.toggle('checked', cb.checked);
            hitungTotal();
        }

        function updateCheckLabelStyles() {
            document.querySelectorAll('#bulanGrid .check-label').forEach(lbl => lbl.classList.remove('checked'));
        }

        function hitungTotal() {
            const tipe = document.getElementById('t_tipe').value;
            let total = 0;
            if (tipe === 'bulanan') {
                document.querySelectorAll('input[name="bulan[]"]:checked').forEach(() => total += KAS_BULANAN);
            } else if (tipe === 'mingguan') {
                document.querySelectorAll('input[name="minggu[]"]:checked').forEach(() => total += KAS_MINGGUAN);
            }
            document.getElementById('totalPreview').textContent = 'Rp ' + total.toLocaleString('id-ID');
            document.getElementById('t_jumlahHidden').value = total;
        }

        function validateFormTambah() {
            let valid = true;
            const nisn = document.getElementById('t_nisn');
            const tipe = document.getElementById('t_tipe');
            const bulan = document.querySelectorAll('input[name="bulan[]"]:checked');
            const minggu = document.querySelectorAll('input[name="minggu[]"]:checked');

            // Reset
            ['t_nisn', 't_tipe'].forEach(id => document.getElementById(id).classList.remove('is-invalid'));
            ['err_nisn', 'err_tipe', 'err_bulan', 'err_minggu'].forEach(id => document.getElementById(id).style.display = 'none');

            if (!nisn.value) {
                nisn.classList.add('is-invalid');
                document.getElementById('err_nisn').style.display = 'block';
                valid = false;
            }
            if (!tipe.value) {
                tipe.classList.add('is-invalid');
                document.getElementById('err_tipe').style.display = 'block';
                valid = false;
            }
            if (bulan.length === 0) {
                document.getElementById('err_bulan').style.display = 'block';
                valid = false;
            }
            if (tipe.value === 'mingguan' && minggu.length === 0) {
                document.getElementById('err_minggu').style.display = 'block';
                valid = false;
            }
            const total = parseInt(document.getElementById('t_jumlahHidden').value || '0');
            if (total <= 0) {
                valid = false;
            }

            return valid;
        }

        // ── Edit Modal ──
        function openEditModal(id, nisn, jumlah) {
            document.getElementById('e_id').value = id;
            document.getElementById('e_nisn').value = nisn;
            document.getElementById('e_jumlah').value = jumlah;
            ['e_nisn', 'e_jumlah'].forEach(i => document.getElementById(i).classList.remove('is-invalid'));
            ['err_e_nisn', 'err_e_jumlah'].forEach(i => document.getElementById(i).style.display = 'none');
            openModal('modalEdit');
        }

        function validateFormEdit() {
            let valid = true;
            const nisn = document.getElementById('e_nisn');
            const jumlah = document.getElementById('e_jumlah');

            nisn.classList.remove('is-invalid');
            jumlah.classList.remove('is-invalid');
            document.getElementById('err_e_nisn').style.display = 'none';
            document.getElementById('err_e_jumlah').style.display = 'none';

            if (!nisn.value) {
                nisn.classList.add('is-invalid');
                document.getElementById('err_e_nisn').style.display = 'block';
                valid = false;
            }
            if (!jumlah.value || parseInt(jumlah.value) < 1000) {
                jumlah.classList.add('is-invalid');
                document.getElementById('err_e_jumlah').style.display = 'block';
                valid = false;
            }
            return valid;
        }

        // ── Hapus konfirmasi ──
        function confirmHapus(id, nama) {
            document.getElementById('hapusNama').textContent = nama;
            document.getElementById('hapusLink').href = '?hapus=' + id;
            openModal('modalHapus');
        }
    </script>
</body>

</html>