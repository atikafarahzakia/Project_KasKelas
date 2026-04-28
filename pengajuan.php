<?php
session_start();
include 'config/app.php';

if (!isset($_SESSION['login']))
    header("location:login.php");

// ================= ACC =================
if (isset($_GET['acc'])) {
    $id   = (int)$_GET['acc'];
    $data = query("SELECT * FROM pengajuan WHERE id_pengajuan=$id");

    if ($data) {
        $data = $data[0];
        $cek  = query("SELECT * FROM transaksi WHERE id_pengajuan=$id");

        if (!$cek) {
            query("INSERT INTO transaksi 
                (tanggal, bulan, tahun, jenis, jumlah, kategori, keterangan, id_pengajuan)
                VALUES 
                (NOW(), MONTH(NOW()), YEAR(NOW()), 'keluar', 
                '{$data['jumlah']}', '{$data['kategori']}', '{$data['keterangan']}', '$id')");
        }

        query("UPDATE pengajuan SET status='disetujui' WHERE id_pengajuan=$id");
    }

    header("Location: pengajuan.php?success=acc");
    exit;
}

// ================= TOLAK =================
if (isset($_GET['tolak'])) {
    $id = (int)$_GET['tolak'];
    query("UPDATE pengajuan SET status='ditolak' WHERE id_pengajuan=$id");
    header("Location: pengajuan.php?success=tolak");
    exit;
}

// ================= FILTER =================
$search  = $_GET['search']  ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$status  = $_GET['status']  ?? '';

$where = "1=1";
if ($search) {
    $s = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND keterangan LIKE '%$s%'";
}
if ($tanggal) $where .= " AND DATE(tanggal) = '$tanggal'";
if ($status)  $where .= " AND status = '$status'";

// ================= DATA =================
$pengajuan = query("SELECT * FROM pengajuan WHERE $where ORDER BY id_pengajuan DESC");

$ringkasan = query("
    SELECT 
        COUNT(CASE WHEN status='disetujui' THEN 1 END) as diterima,
        COUNT(CASE WHEN status='pending'   THEN 1 END) as pending,
        COUNT(CASE WHEN status='ditolak'   THEN 1 END) as ditolak
    FROM pengajuan
")[0];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan — Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style/pengajuan.css">
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
            <div class="info">
                <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="role"><?= htmlspecialchars($_SESSION['role']) ?></div>
            </div>
        </div>
        <div class="sidebar-divider"></div>
        <div class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>

            <?php if ($_SESSION['role'] == 'wali kelas'): ?>
                <a href="datamurid.php"><i class="fas fa-users"></i> Data Murid</a>
                <a href="pengajuan.php" class="active"><i class="fas fa-clock"></i> Pengajuan</a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'bendahara'): ?>
                <a href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a>
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

        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <button class="hamburger" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
            </div>
            <div style="flex:1; min-width:120px;">
                <h1>Pengajuan Kas Keluar</h1>
                <div class="greeting">Kelola pengajuan dan persetujuan kas — <?= date('d F Y') ?></div>
            </div>
        </div>

        <!-- ALERTS -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" id="flashAlert">
                <i class="fas fa-check-circle"></i>
                <span>
                    <?php
                    if ($_GET['success'] == 'acc')   echo 'Pengajuan berhasil disetujui dan dicatat sebagai kas keluar.';
                    if ($_GET['success'] == 'tolak') echo 'Pengajuan berhasil ditolak.';
                    ?>
                </span>
                <button class="alert-close" onclick="this.parentElement.remove()"><i class="fas fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <!-- STAT CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--success-bg); color:var(--success);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div>
                    <div class="label">Disetujui</div>
                    <div class="value" style="color:var(--success);"><?= $ringkasan['diterima'] ?? 0 ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--warning-bg); color:var(--warning);">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <div class="label">Pending</div>
                    <div class="value" style="color:var(--warning);"><?= $ringkasan['pending'] ?? 0 ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:var(--danger-bg); color:var(--danger);">
                    <i class="fas fa-circle-xmark"></i>
                </div>
                <div>
                    <div class="label">Ditolak</div>
                    <div class="value" style="color:var(--danger);"><?= $ringkasan['ditolak'] ?? 0 ?></div>
                </div>
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="section-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-list-check" style="color:var(--brand);"></i>
                    Daftar Pengajuan
                </div>
            </div>
            <div class="card-body">

                <!-- FILTER -->
                <form method="GET" class="filter-bar">
                    <div class="filter-group" style="flex:1; min-width:180px;">
                        <label>Cari Keterangan</label>
                        <input type="text" name="search" class="form-control" placeholder="Ketik kata kunci..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="filter-group" style="min-width:160px;">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tanggal) ?>">
                    </div>
                    <div class="filter-group" style="min-width:140px;">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="pending"   <?= $status == 'pending'   ? 'selected' : '' ?>>Pending</option>
                            <option value="disetujui" <?= $status == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                            <option value="ditolak"   <?= $status == 'ditolak'   ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label style="opacity:0;">_</label>
                        <div style="display:flex; gap:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                            <a href="pengajuan.php" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> Reset</a>
                        </div>
                    </div>
                </form>

                <!-- TABLE -->
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pengajuan)): ?>
                                <tr><td colspan="6" class="empty-table">
                                    <i class="fas fa-inbox" style="font-size:2rem; display:block; margin-bottom:8px; opacity:.4;"></i>
                                    Tidak ada pengajuan
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($pengajuan as $i => $p): ?>
                                    <tr>
                                        <td style="color:var(--muted); font-size:.78rem;"><?= $i+1 ?></td>
                                        <td><?= date('d M Y', strtotime($p['tanggal'])) ?></td>
                                        <td style="font-weight:600;"><?= htmlspecialchars($p['kategori']) ?></td>
                                        <td style="font-weight:700; color:var(--danger);">−Rp <?= number_format($p['jumlah']) ?></td>
                                        <td>
                                            <?php if ($p['status'] == 'pending'): ?>
                                                <span class="badge badge-warning"><i class="fas fa-clock" style="font-size:.6rem;"></i> Pending</span>
                                            <?php elseif ($p['status'] == 'disetujui'): ?>
                                                <span class="badge badge-success"><i class="fas fa-check" style="font-size:.6rem;"></i> Disetujui</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger"><i class="fas fa-xmark" style="font-size:.6rem;"></i> Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="td-actions">
                                                <button class="btn btn-info btn-sm"
                                                    onclick='openDetail(<?= json_encode($p) ?>)'>
                                                    <i class="fas fa-eye"></i> Detail
                                                </button>
                                                <?php if ($p['status'] == 'pending'): ?>
                                                    <button class="btn btn-success btn-sm"
                                                        onclick="confirmAcc(<?= $p['id_pengajuan'] ?>, '<?= htmlspecialchars($p['kategori']) ?>', <?= $p['jumlah'] ?>)">
                                                        <i class="fas fa-check"></i> Terima
                                                    </button>
                                                    <button class="btn btn-danger btn-sm"
                                                        onclick="confirmTolak(<?= $p['id_pengajuan'] ?>, '<?= htmlspecialchars($p['kategori']) ?>')">
                                                        <i class="fas fa-xmark"></i> Tolak
                                                    </button>
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
        </div>

    </main>
</div>

<!-- ═══════ MODAL DETAIL ═══════ -->
<div class="modal-backdrop" id="modalDetail">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-file-lines" style="color:var(--brand); margin-right:6px;"></i>Detail Pengajuan</div>
            <button class="modal-close" onclick="closeModal('modalDetail')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="detailBody">
            <!-- filled by JS -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modalDetail')">Tutup</button>
        </div>
    </div>
</div>

<!-- ═══════ MODAL KONFIRMASI ACC ═══════ -->
<div class="modal-backdrop" id="modalAcc">
    <div class="modal-box" style="max-width:400px; text-align:center;">
        <div class="modal-header" style="justify-content:center; border:none; padding-bottom:0;">
            <button class="modal-close" style="position:absolute; top:14px; right:14px;" onclick="closeModal('modalAcc')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body" style="padding-top:24px;">
            <div class="confirm-icon" style="background:var(--success-bg); color:var(--success);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div style="font-size:1rem; font-weight:700; margin-bottom:6px;">Setujui Pengajuan?</div>
            <div id="accDesc" style="font-size:.85rem; color:var(--muted); margin-bottom:4px;"></div>
            <div style="font-size:.78rem; color:var(--muted);">Pengajuan akan dicatat sebagai kas keluar.</div>
        </div>
        <div class="modal-footer" style="justify-content:center; border:none;">
            <button class="btn btn-secondary" onclick="closeModal('modalAcc')">Batal</button>
            <a id="accLink" href="#" class="btn btn-success"><i class="fas fa-check"></i> Setujui</a>
        </div>
    </div>
</div>

<!-- ═══════ MODAL KONFIRMASI TOLAK ═══════ -->
<div class="modal-backdrop" id="modalTolak">
    <div class="modal-box" style="max-width:400px; text-align:center;">
        <div class="modal-header" style="justify-content:center; border:none; padding-bottom:0;">
            <button class="modal-close" style="position:absolute; top:14px; right:14px;" onclick="closeModal('modalTolak')"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body" style="padding-top:24px;">
            <div class="confirm-icon" style="background:var(--danger-bg); color:var(--danger);">
                <i class="fas fa-circle-xmark"></i>
            </div>
            <div style="font-size:1rem; font-weight:700; margin-bottom:6px;">Tolak Pengajuan?</div>
            <div id="tolakDesc" style="font-size:.85rem; color:var(--muted); margin-bottom:4px;"></div>
            <div style="font-size:.78rem; color:var(--muted);">Status akan diubah menjadi ditolak.</div>
        </div>
        <div class="modal-footer" style="justify-content:center; border:none;">
            <button class="btn btn-secondary" onclick="closeModal('modalTolak')">Batal</button>
            <a id="tolakLink" href="#" class="btn btn-danger"><i class="fas fa-xmark"></i> Tolak</a>
        </div>
    </div>
</div>

<script>
    // ── Sidebar ──
    function openSidebar()  { document.getElementById('sidebar').classList.add('open'); document.getElementById('overlay').classList.add('show'); document.body.style.overflow='hidden'; }
    function closeSidebar() { document.getElementById('sidebar').classList.remove('open'); document.getElementById('overlay').classList.remove('show'); document.body.style.overflow=''; }

    // ── Modals ──
    function openModal(id)  { document.getElementById(id).classList.add('show'); document.body.style.overflow='hidden'; }
    function closeModal(id) { document.getElementById(id).classList.remove('show'); document.body.style.overflow=''; }
    document.querySelectorAll('.modal-backdrop').forEach(b => b.addEventListener('click', e => { if(e.target===b) closeModal(b.id); }));
    document.addEventListener('keydown', e => { if(e.key==='Escape') { closeSidebar(); document.querySelectorAll('.modal-backdrop.show').forEach(b => closeModal(b.id)); } });

    // ── Auto dismiss flash ──
    window.addEventListener('load', () => {
        const a = document.getElementById('flashAlert');
        if(a) { setTimeout(() => a.style.transition='opacity .5s', 2800); setTimeout(() => a.style.opacity='0', 3000); setTimeout(() => a.remove(), 3600); }
    });

    // ── Detail Modal ──
    function openDetail(p) {
        const statusMap = {
            pending:   '<span class="badge badge-warning"><i class="fas fa-clock" style="font-size:.6rem;"></i> Pending</span>',
            disetujui: '<span class="badge badge-success"><i class="fas fa-check" style="font-size:.6rem;"></i> Disetujui</span>',
            ditolak:   '<span class="badge badge-danger"><i class="fas fa-xmark" style="font-size:.6rem;"></i> Ditolak</span>',
        };
        const fmt = n => 'Rp ' + parseInt(n).toLocaleString('id-ID');
        const fmtDate = s => { const d = new Date(s); return d.toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}); };

        let html = `
            <div class="detail-row"><div class="dr-label">Tanggal</div><div class="dr-value">${fmtDate(p.tanggal)}</div></div>
            <div class="detail-row"><div class="dr-label">Kategori</div><div class="dr-value" style="font-weight:600;">${p.kategori}</div></div>
            <div class="detail-row"><div class="dr-label">Jumlah</div><div class="dr-value" style="font-weight:700; color:var(--danger);">−${fmt(p.jumlah)}</div></div>
            <div class="detail-row"><div class="dr-label">Status</div><div class="dr-value">${statusMap[p.status] || p.status}</div></div>
            <div class="detail-row"><div class="dr-label">Keterangan</div><div class="dr-value">${p.keterangan || '-'}</div></div>
        `;

        if (p.bukti) {
            html += `<div style="margin-top:16px;"><div style="font-size:.78rem; font-weight:600; color:var(--muted); text-transform:uppercase; margin-bottom:8px;">Bukti</div>
                <img src="upload/${p.bukti}" style="width:100%; border-radius:var(--radius-sm); border:1px solid var(--border);" alt="bukti"></div>`;
        }

        document.getElementById('detailBody').innerHTML = html;
        openModal('modalDetail');
    }

    // ── Confirm ACC ──
    function confirmAcc(id, kategori, jumlah) {
        document.getElementById('accDesc').textContent = kategori + ' — Rp ' + parseInt(jumlah).toLocaleString('id-ID');
        document.getElementById('accLink').href = '?acc=' + id;
        openModal('modalAcc');
    }

    // ── Confirm TOLAK ──
    function confirmTolak(id, kategori) {
        document.getElementById('tolakDesc').textContent = kategori;
        document.getElementById('tolakLink').href = '?tolak=' + id;
        openModal('modalTolak');
    }
</script>
</body>
</html>