<?php
session_start();
if (!isset($_SESSION['login']))
    header("location:login.php");

include 'config/app.php';

// Ringkasan
$ringkasan = ringkasanKasMasuk();
$masukBulanIni = kasMasukBulanIni();
$keluarBulanIni = kasKeluarBulanIni();
$totalsaldo = totalsaldo();

// QUERY AKTIVITAS TERBARU
$historiMasuk = query("SELECT t.*, m.nama FROM transaksi t 
                       JOIN murid m ON t.nisn = m.nisn 
                       WHERE t.jenis='masuk' 
                       ORDER BY t.tanggal DESC LIMIT 5");

$historiKeluar = query("SELECT * FROM transaksi 
                        WHERE jenis='keluar' 
                        ORDER BY tanggal DESC LIMIT 5");

$target_kas = 7920000;
$kas_wajib = 20000;
$statusBayar = ringkasanStatusBayar($kas_wajib);

$lunas   = $statusBayar['lunas'];
$sebagian = $statusBayar['sebagian'];
$belum   = $statusBayar['belum'];

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where = "";
if ($search) {
    $search_escaped = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where = "WHERE murid.nama LIKE '%$search_escaped%'";
}

$q = query("
    SELECT murid.nisn, nama, IFNULL(SUM(jumlah),0) as total
    FROM murid
    LEFT JOIN transaksi 
        ON murid.nisn = transaksi.nisn
        AND jenis='masuk'
        AND MONTH(tanggal) = MONTH(CURDATE())
        AND YEAR(tanggal) = YEAR(CURDATE())
    $where
    GROUP BY murid.nisn
    ORDER BY murid.nama ASC
");

$persen = ($target_kas > 0) ? min(($totalsaldo / $target_kas) * 100, 100) : 0;
$totalSiswa = dataSiswa();

// Validasi data
$errors = [];
if ($totalsaldo < 0) $errors[] = "Saldo kas minus! Segera periksa transaksi.";
if ($belum > 0 && $belum >= $totalSiswa * 0.5) $errors[] = "Lebih dari 50% siswa belum membayar kas bulan ini.";
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style/dashboard.css">
</head>

<body>
    <div class="app">

        <!-- SIDEBAR OVERLAY -->
        <div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

        <!-- SIDEBAR -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon"><i class="fas fa-coins"></i></div>
                <span>Kas Kelas</span>
            </div>
            <div class="sidebar-divider"></div>
            <div class="sidebar-profile">
                <img src="assets/profile.jpg" alt="Foto Profil"
                    onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username']) ?>&background=3b82f6&color=fff&size=80'">
                <div class="info">
                    <div class="name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                    <div class="role"><?= htmlspecialchars($_SESSION['role']) ?></div>
                </div>
            </div>
            <div class="sidebar-divider"></div>
            <div class="sidebar-nav">
                <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>

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
                    <button class="hamburger" onclick="openSidebar()" aria-label="Buka menu">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div style="flex:1; min-width:120px;">
                    <h1>Dashboard</h1>
                    <div class="greeting">
                        Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                        — <?= date('l, d F Y') ?>
                    </div>
                </div>
            </div>

            <!-- VALIDASI / ALERTS -->
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle" style="margin-top:2px;"></i>
                        <span><?= htmlspecialchars($err) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- STAT CARDS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f0fdf4; color:#16a34a;">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <div class="label">Saldo Kas</div>
                        <div class="value" style="color:var(--success)">
                            Rp <?= number_format($totalsaldo) ?>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">
                        <i class="fas fa-arrow-down-to-line"></i>
                    </div>
                    <div>
                        <div class="label">Kas Masuk Bulan Ini</div>
                        <div class="value" style="color:var(--brand)">
                            Rp <?= number_format($masukBulanIni) ?>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef2f2; color:#dc2626;">
                        <i class="fas fa-arrow-up-from-line"></i>
                    </div>
                    <div>
                        <div class="label">Kas Keluar Bulan Ini</div>
                        <div class="value" style="color:var(--danger)">
                            Rp <?= number_format($keluarBulanIni) ?>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background:#ecfeff; color:#0891b2;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="label">Total Siswa</div>
                        <div class="value" style="color:var(--info)">
                            <?= $totalSiswa ?> Siswa
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROGRESS TARGET KAS -->
            <div class="progress-card">
                <div class="header">
                    <h2><i class="fas fa-bullseye" style="color:var(--success);margin-right:6px;"></i>Progress Target Kas Semester</h2>
                    <span class="target">Target: Rp <?= number_format($target_kas) ?></span>
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" id="progressFill" style="width:0%"></div>
                </div>
                <div class="meta">
                    <span>Terkumpul: Rp <?= number_format($totalsaldo) ?></span>
                    <span id="progressLabel">0%</span>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-chart-donut" style="color:var(--brand);"></i>
                            Kas Masuk vs Keluar
                        </div>
                        <a href="aruskas.php" class="btn-link">Lihat Detail →</a>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="chartKas"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-chart-pie" style="color:var(--brand);"></i>
                            Status Bayar Siswa
                        </div>
                        <a href="statusbayar.php" class="btn-link">Lihat Detail →</a>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="chartStatus"></canvas>
                    </div>
                    <div class="status-badges">
                        <span class="badge badge-success"><i class="fas fa-check" style="font-size:.7rem;"></i> Lunas: <?= $lunas ?></span>
                        <span class="badge badge-warning"><i class="fas fa-minus" style="font-size:.7rem;"></i> Sebagian: <?= $sebagian ?></span>
                        <span class="badge badge-danger"><i class="fas fa-xmark" style="font-size:.7rem;"></i> Belum: <?= $belum ?></span>
                    </div>
                </div>
            </div>

            <!-- HISTORY TABLES -->
            <div class="history-grid">
                <div class="history-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-clock-rotate-left text-success"></i>
                            Riwayat Kas Masuk
                        </div>
                        <a href="kasmasuk.php" class="btn-link">Semua →</a>
                    </div>
                    <ul class="history-list">
                        <?php if (empty($historiMasuk)): ?>
                            <li><span class="empty-state" style="width:100%">Belum ada data</span></li>
                        <?php else: ?>
                            <?php foreach ($historiMasuk as $hm): ?>
                                <li>
                                    <div>
                                        <div class="hi-name"><?= htmlspecialchars($hm['nama']) ?></div>
                                        <div class="hi-date"><?= date('d M Y', strtotime($hm['tanggal'])) ?></div>
                                    </div>
                                    <div class="hi-amount text-success">+Rp <?= number_format($hm['jumlah']) ?></div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="history-card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-clock-rotate-left text-danger"></i>
                            Riwayat Kas Keluar
                        </div>
                        <a href="kaskeluar.php" class="btn-link">Semua →</a>
                    </div>
                    <ul class="history-list">
                        <?php if (empty($historiKeluar)): ?>
                            <li><span class="empty-state" style="width:100%">Belum ada data</span></li>
                        <?php else: ?>
                            <?php foreach ($historiKeluar as $hk): ?>
                                <li>
                                    <div>
                                        <div class="hi-name"><?= htmlspecialchars($hk['keterangan']) ?></div>
                                        <div class="hi-date"><?= date('d M Y', strtotime($hk['tanggal'])) ?></div>
                                    </div>
                                    <div class="hi-amount text-danger">−Rp <?= number_format($hk['jumlah']) ?></div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </main>
    </div>

    <script>
        // ── Sidebar toggle ──
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

        // ── Progress bar animate on load ──
        window.addEventListener('load', () => {
            const fill = document.getElementById('progressFill');
            const label = document.getElementById('progressLabel');
            const persen = <?= round($persen, 2) ?>;
            setTimeout(() => {
                fill.style.width = persen + '%';
                label.textContent = persen.toFixed(1) + '%';
            }, 300);
        });

        // ── Chart.js theme colours ──
        const masuk = <?= (int)$masukBulanIni ?>;
        const keluar = <?= (int)$keluarBulanIni ?>;
        const lunas = <?= (int)$lunas ?>;
        const sebagian = <?= (int)$sebagian ?>;
        const belum = <?= (int)$belum ?>;

        // Chart 1: Kas Masuk vs Keluar
        new Chart(document.getElementById('chartKas'), {
            type: 'doughnut',
            data: {
                labels: ['Kas Masuk', 'Kas Keluar'],
                datasets: [{
                    data: [masuk || 0, keluar || 0],
                    backgroundColor: ['#22c55e', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12,
                                family: 'Plus Jakarta Sans'
                            },
                            padding: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' Rp ' + ctx.parsed.toLocaleString('id-ID')
                        }
                    }
                }
            }
        });

        // Chart 2: Status Bayar
        new Chart(document.getElementById('chartStatus'), {
            type: 'pie',
            data: {
                labels: ['Lunas', 'Sebagian', 'Belum'],
                datasets: [{
                    data: [lunas || 0, sebagian || 0, belum || 0],
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12,
                                family: 'Plus Jakarta Sans'
                            },
                            padding: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.parsed + ' siswa'
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>