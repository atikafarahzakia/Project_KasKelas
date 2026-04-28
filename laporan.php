<?php
session_start();
include 'config/app.php';

if (!isset($_SESSION['login']))
    header("location:login.php");

// ================= FILTER =================
$search = trim($_GET['search'] ?? '');
$jenis  = $_GET['jenis']  ?? '';
$bulan_filter = (int)($_GET['bulan'] ?? 0);
$tahun_filter = (int)($_GET['tahun'] ?? 0);

$where = "WHERE 1=1";

if ($search) {
    $s = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND keterangan LIKE '%$s%'";
}
if ($jenis === 'masuk' || $jenis === 'keluar') {
    $where .= " AND jenis='$jenis'";
}
if ($bulan_filter >= 1 && $bulan_filter <= 12) {
    $where .= " AND MONTH(tanggal) = $bulan_filter";
}
if ($tahun_filter >= 2000) {
    $where .= " AND YEAR(tanggal) = $tahun_filter";
}

// ================= RINGKASAN GLOBAL =================
$masuk  = (int)query("SELECT IFNULL(SUM(jumlah),0) as total FROM transaksi WHERE jenis='masuk'")[0]['total'];
$keluar = (int)query("SELECT IFNULL(SUM(jumlah),0) as total FROM transaksi WHERE jenis='keluar'")[0]['total'];
$saldo  = $masuk - $keluar;

// ================= PAGINATION KAS MASUK =================
$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$start  = ($page - 1) * $limit;

$masukWhere = "WHERE jenis='masuk'";
if ($search)        $masukWhere .= " AND keterangan LIKE '%" . mysqli_real_escape_string($GLOBALS['db'], $search) . "%'";
if ($bulan_filter)  $masukWhere .= " AND MONTH(tanggal) = $bulan_filter";
if ($tahun_filter)  $masukWhere .= " AND YEAR(tanggal) = $tahun_filter";

$totalData  = (int)query("SELECT COUNT(*) as total FROM transaksi $masukWhere")[0]['total'];
$totalPage  = max(1, (int)ceil($totalData / $limit));
if ($page > $totalPage) $page = $totalPage;
$start = ($page - 1) * $limit;

$dataMasukPaging = query("SELECT t.*, m.nama FROM transaksi t
    LEFT JOIN murid m ON m.nisn = t.nisn
    $masukWhere ORDER BY t.tanggal DESC LIMIT $start, $limit");

// ================= KAS KELUAR GROUP BULAN =================
$keluarWhere = "WHERE jenis='keluar'";
if ($search)        $keluarWhere .= " AND keterangan LIKE '%" . mysqli_real_escape_string($GLOBALS['db'], $search) . "%'";
if ($bulan_filter)  $keluarWhere .= " AND MONTH(tanggal) = $bulan_filter";
if ($tahun_filter)  $keluarWhere .= " AND YEAR(tanggal) = $tahun_filter";

$qKeluar    = query("SELECT * FROM transaksi $keluarWhere ORDER BY tanggal DESC");
$groupKeluar = [];
foreach ($qKeluar as $d) {
    $key = date('F Y', strtotime($d['tanggal']));
    $groupKeluar[$key][] = $d;
}

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
    <title>Laporan Kas — Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style/laporan.css">
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
                    <a href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a>
                <?php endif; ?>

                <a href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a>
                <a href="statusbayar.php"><i class="fas fa-chart-column"></i> Status Bayar</a>
                <a href="laporan.php" class="active"><i class="fas fa-file"></i> Laporan</a>
                <div class="sidebar-divider" style="margin:12px 0 8px;"></div>
                <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="content">

            <div class="topbar">
                <button class="hamburger" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
                <h1><i class="fas fa-file-lines" style="color:var(--brand);font-size:.9rem;margin-right:6px;"></i>Laporan Kas</h1>
                <a href="#" class="print-btn" onclick="window.print(); return false;">
                    <i class="fas fa-print"></i> Cetak Laporan
                </a>
            </div>

            <!-- STAT CARDS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--success-bg);color:var(--success);"><i class="fas fa-arrow-down-to-line"></i></div>
                    <div>
                        <div class="stat-label">Total Masuk</div>
                        <div class="stat-value" style="color:var(--success)">Rp <?= number_format($masuk) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--danger-bg);color:var(--danger);"><i class="fas fa-arrow-up-from-line"></i></div>
                    <div>
                        <div class="stat-label">Total Keluar</div>
                        <div class="stat-value" style="color:var(--danger)">Rp <?= number_format($keluar) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:<?= $saldo >= 0 ? 'var(--brand-light)' : 'var(--danger-bg)' ?>;color:<?= $saldo >= 0 ? 'var(--brand)' : 'var(--danger)' ?>;"><i class="fas fa-wallet"></i></div>
                    <div>
                        <div class="stat-label">Saldo Bersih</div>
                        <div class="stat-value" style="color:<?= $saldo >= 0 ? 'var(--brand)' : 'var(--danger)' ?>">
                            <?= $saldo < 0 ? '−' : '' ?>Rp <?= number_format(abs($saldo)) ?>
                        </div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--info-bg);color:var(--info);"><i class="fas fa-list"></i></div>
                    <div>
                        <div class="stat-label">Total Transaksi Masuk</div>
                        <div class="stat-value" style="color:var(--info)"><?= $totalData ?> data</div>
                    </div>
                </div>
            </div>

            <!-- FILTER -->
            <div class="filter-card">
                <h2><i class="fas fa-sliders"></i> Filter Laporan</h2>
                <form method="GET" id="filterForm" onsubmit="return validateFilter()">
                    <div class="filter-row">
                        <div class="filter-field grow">
                            <label>Cari Keterangan</label>
                            <input type="text" name="search" placeholder="Ketik kata kunci..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="filter-field">
                            <label>Jenis Kas</label>
                            <select name="jenis">
                                <option value="">Semua</option>
                                <option value="masuk" <?= $jenis === 'masuk'  ? 'selected' : '' ?>>Kas Masuk</option>
                                <option value="keluar" <?= $jenis === 'keluar' ? 'selected' : '' ?>>Kas Keluar</option>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label>Bulan</label>
                            <select name="bulan">
                                <option value="">Semua Bulan</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($bulan_filter === $i) ? 'selected' : '' ?>><?= $nama_bulan[$i] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label>Tahun</label>
                            <select name="tahun">
                                <option value="">Semua Tahun</option>
                                <?php for ($t = (int)date('Y'); $t >= (int)date('Y') - 5; $t--): ?>
                                    <option value="<?= $t ?>" <?= ($tahun_filter === $t) ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="filter-field btn-row" style="flex-direction:row;gap:8px;padding-bottom:1px;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                            <a href="laporan.php" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ACTIVE FILTER PILLS -->
            <?php
            $hasPills = $search || $jenis || $bulan_filter || $tahun_filter;
            if ($hasPills):
                $pillBase = http_build_query(array_filter([
                    'search' => $search,
                    'jenis' => $jenis,
                    'bulan' => $bulan_filter ?: '',
                    'tahun' => $tahun_filter ?: ''
                ]));
            ?>
                <div class="filter-pills">
                    <?php if ($search): ?>
                        <span class="pill"><i class="fas fa-magnifying-glass" style="font-size:.7rem;"></i> "<?= htmlspecialchars($search) ?>"
                            <a href="?<?= http_build_query(array_filter(['jenis' => $jenis, 'bulan' => $bulan_filter, 'tahun' => $tahun_filter])) ?>"><i class="fas fa-xmark"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($jenis): ?>
                        <span class="pill"><i class="fas fa-tag" style="font-size:.7rem;"></i> <?= ucfirst($jenis) ?>
                            <a href="?<?= http_build_query(array_filter(['search' => $search, 'bulan' => $bulan_filter, 'tahun' => $tahun_filter])) ?>"><i class="fas fa-xmark"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($bulan_filter): ?>
                        <span class="pill"><i class="fas fa-calendar" style="font-size:.7rem;"></i> <?= $nama_bulan[$bulan_filter] ?>
                            <a href="?<?= http_build_query(array_filter(['search' => $search, 'jenis' => $jenis, 'tahun' => $tahun_filter])) ?>"><i class="fas fa-xmark"></i></a>
                        </span>
                    <?php endif; ?>
                    <?php if ($tahun_filter): ?>
                        <span class="pill"><i class="fas fa-calendar-days" style="font-size:.7rem;"></i> <?= $tahun_filter ?>
                            <a href="?<?= http_build_query(array_filter(['search' => $search, 'jenis' => $jenis, 'bulan' => $bulan_filter])) ?>"><i class="fas fa-xmark"></i></a>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- REPORTS GRID -->
            <div class="reports-grid">

                <!-- ─── KAS MASUK ─── -->
                <div class="table-card">
                    <div class="tc-header">
                        <h2 style="color:var(--success);">
                            <i class="fas fa-circle" style="font-size:.55rem;color:var(--success);"></i>
                            Kas Masuk
                        </h2>
                        <span class="tc-meta"><?= $totalData ?> total data</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Nama Siswa</th>
                                    <th>Keterangan</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dataMasukPaging)): ?>
                                    <tr class="empty-row">
                                        <td colspan="5">
                                            <i class="fas fa-inbox" style="font-size:1.3rem;opacity:.4;display:block;margin-bottom:5px;"></i>
                                            Tidak ada data kas masuk
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dataMasukPaging as $i => $d): ?>
                                        <tr>
                                            <td style="color:var(--muted);font-size:.78rem;"><?= ($start + $i + 1) ?></td>
                                            <td style="white-space:nowrap;"><?= date('d M Y', strtotime($d['tanggal'])) ?></td>
                                            <td style="font-weight:600;"><?= htmlspecialchars($d['nama'] ?? '—') ?></td>
                                            <td style="color:var(--muted);max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($d['keterangan']) ?>">
                                                <?= htmlspecialchars($d['keterangan']) ?>
                                            </td>
                                            <td style="font-weight:700;color:var(--success);white-space:nowrap;">+Rp <?= number_format($d['jumlah']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <div class="tc-footer">
                        <span class="info">
                            Halaman <?= $page ?> dari <?= $totalPage ?> &nbsp;·&nbsp; <?= $totalData ?> data
                        </span>
                        <div class="pagination">
                            <?php
                            $qStr = http_build_query(array_filter(['search' => $search, 'jenis' => $jenis, 'bulan' => $bulan_filter, 'tahun' => $tahun_filter]));
                            $qStr = $qStr ? $qStr . '&' : '';
                            ?>
                            <a href="?<?= $qStr ?>page=1" class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <i class="fas fa-angles-left" style="font-size:.7rem;"></i>
                            </a>
                            <a href="?<?= $qStr ?>page=<?= max(1, $page - 1) ?>" class="page-btn <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <i class="fas fa-angle-left" style="font-size:.7rem;"></i>
                            </a>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page   = min($totalPage, $page + 2);
                            if ($start_page > 1) echo '<span class="page-btn disabled">…</span>';
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <a href="?<?= $qStr ?>page=<?= $i ?>" class="page-btn <?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
                            <?php
                            endfor;
                            if ($end_page < $totalPage) echo '<span class="page-btn disabled">…</span>';
                            ?>

                            <a href="?<?= $qStr ?>page=<?= min($totalPage, $page + 1) ?>" class="page-btn <?= ($page >= $totalPage) ? 'disabled' : '' ?>">
                                <i class="fas fa-angle-right" style="font-size:.7rem;"></i>
                            </a>
                            <a href="?<?= $qStr ?>page=<?= $totalPage ?>" class="page-btn <?= ($page >= $totalPage) ? 'disabled' : '' ?>">
                                <i class="fas fa-angles-right" style="font-size:.7rem;"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- ─── KAS KELUAR ─── -->
                <div class="table-card">
                    <div class="tc-header">
                        <h2 style="color:var(--danger);">
                            <i class="fas fa-circle" style="font-size:.55rem;color:var(--danger);"></i>
                            Kas Keluar
                        </h2>
                        <span class="tc-meta"><?= count($qKeluar) ?> total data</span>
                    </div>
                    <div class="table-wrap">
                        <?php if (empty($groupKeluar)): ?>
                            <div style="text-align:center;padding:40px;color:var(--muted);font-size:.875rem;">
                                <i class="fas fa-inbox" style="font-size:1.3rem;opacity:.4;display:block;margin-bottom:5px;"></i>
                                Tidak ada data kas keluar
                            </div>
                        <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th>Kategori</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groupKeluar as $bulanLabel => $items):
                                        $totalBulan = array_sum(array_column($items, 'jumlah'));
                                    ?>
                                        <tr>
                                            <td colspan="4" style="padding:0;">
                                                <div class="month-header">
                                                    <span><?= $bulanLabel ?></span>
                                                    <span class="month-total">Rp <?= number_format($totalBulan) ?></span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php foreach ($items as $d): ?>
                                            <tr>
                                                <td style="white-space:nowrap;"><?= date('d M Y', strtotime($d['tanggal'])) ?></td>
                                                <td style="color:var(--muted);max-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($d['keterangan']) ?>">
                                                    <?= htmlspecialchars($d['keterangan']) ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($d['kategori'])): ?>
                                                        <span style="background:var(--info-bg);color:var(--info);font-size:.71rem;font-weight:600;padding:2px 7px;border-radius:99px;">
                                                            <?= htmlspecialchars($d['kategori']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:var(--muted);font-size:.8rem;">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-weight:700;color:var(--danger);white-space:nowrap;">−Rp <?= number_format($d['jumlah']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($groupKeluar)): ?>
                        <div class="tc-footer">
                            <span class="info"><?= count($qKeluar) ?> transaksi keluar</span>
                            <span style="font-size:.82rem;font-weight:700;color:var(--danger);">
                                Total: Rp <?= number_format(array_sum(array_column($qKeluar, 'jumlah'))) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </main>
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
            if (e.key === 'Escape') closeSidebar();
        });

        // ── Filter validation ──
        function validateFilter() {
            // All fields optional — just trim whitespace on search
            return true;
        }
    </script>
</body>

</html>