<?php
session_start();
include 'config/app.php';

if (!isset($_SESSION['login']))
    header("location:login.php");

// ================= FILTER =================
$bulan = (int)($_GET['bulan'] ?? 0);
$tahun = (int)($_GET['tahun'] ?? 0);

$dataMasuk  = [];
$dataKeluar = [];
$masuk  = 0;
$keluar = 0;
$saldo  = 0;

// Validasi filter
$valid_filter = ($bulan >= 1 && $bulan <= 12 && $tahun >= 2000 && $tahun <= (int)date('Y') + 1);

if ($bulan && $tahun && $valid_filter) {

    $masuk = (int)query("SELECT IFNULL(SUM(jumlah),0) as total FROM transaksi 
        WHERE jenis='masuk' AND bulan='$bulan' AND tahun='$tahun'")[0]['total'];

    $keluar = (int)query("SELECT IFNULL(SUM(jumlah),0) as total FROM transaksi 
        WHERE jenis='keluar' AND bulan='$bulan' AND tahun='$tahun'")[0]['total'];

    $saldo = $masuk - $keluar;

    $dataMasuk = query("SELECT t.*, m.nama FROM transaksi t
        LEFT JOIN murid m ON m.nisn = t.nisn
        WHERE t.jenis='masuk' AND t.bulan='$bulan' AND t.tahun='$tahun'
        ORDER BY t.tanggal DESC, t.id_transaksi DESC");

    $dataKeluar = query("SELECT * FROM transaksi 
        WHERE jenis='keluar' AND bulan='$bulan' AND tahun='$tahun'
        ORDER BY tanggal DESC, id_transaksi DESC");
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
    <title>Arus Kas — Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style/aruskas.css">
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

                <a href="aruskas.php" class="active"><i class="fas fa-chart-bar"></i> Arus Kas</a>
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
                <h1><i class="fas fa-chart-bar" style="color:var(--brand);font-size:.9rem;margin-right:6px;"></i>Arus Kas</h1>
            </div>

            <!-- FILTER -->
            <div class="filter-card">
                <h2><i class="fas fa-sliders"></i> Filter Periode</h2>
                <form method="GET" id="filterForm" onsubmit="return validateFilter()">
                    <div class="filter-row">
                        <div class="filter-field">
                            <label>Bulan</label>
                            <select name="bulan" id="f_bulan">
                                <option value="">-- Pilih Bulan --</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($bulan == $i ? 'selected' : '') ?>>
                                        <?= $nama_bulan[$i] ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label>Tahun</label>
                            <select name="tahun" id="f_tahun">
                                <option value="">-- Pilih Tahun --</option>
                                <?php for ($t = (int)date('Y'); $t >= (int)date('Y') - 5; $t--): ?>
                                    <option value="<?= $t ?>" <?= ($tahun == $t ? 'selected' : '') ?>>
                                        <?= $t ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="filter-field btn-field" style="flex-direction:row; gap:8px; padding-bottom:1px;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-magnifying-glass"></i> Tampilkan</button>
                            <a href="aruskas.php" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> Reset</a>
                        </div>
                    </div>
                    <div id="err_filter" style="color:#dc2626;font-size:.8rem;margin-top:8px;display:none;">
                        <i class="fas fa-circle-exclamation"></i> Pilih bulan dan tahun terlebih dahulu.
                    </div>
                </form>
            </div>

            <?php if ($bulan && $tahun && $valid_filter): ?>

                <!-- RINGKASAN STAT CARDS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--success-bg);color:var(--success);"><i class="fas fa-arrow-down-to-line"></i></div>
                        <div>
                            <div class="stat-label">Kas Masuk</div>
                            <div class="stat-value saldo-positive">Rp <?= number_format($masuk) ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--danger-bg);color:var(--danger);"><i class="fas fa-arrow-up-from-line"></i></div>
                        <div>
                            <div class="stat-label">Kas Keluar</div>
                            <div class="stat-value saldo-negative">Rp <?= number_format($keluar) ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:<?= $saldo >= 0 ? 'var(--brand-light)' : 'var(--danger-bg)' ?>;color:<?= $saldo >= 0 ? 'var(--brand)' : 'var(--danger)' ?>;"><i class="fas fa-wallet"></i></div>
                        <div>
                            <div class="stat-label">Saldo Bersih</div>
                            <div class="stat-value <?= $saldo > 0 ? 'saldo-positive' : ($saldo < 0 ? 'saldo-negative' : 'saldo-zero') ?>">
                                <?= $saldo < 0 ? '−' : '' ?>Rp <?= number_format(abs($saldo)) ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background:var(--warning-bg);color:var(--warning);"><i class="fas fa-calendar"></i></div>
                        <div>
                            <div class="stat-label">Periode</div>
                            <div class="stat-value" style="font-size:.95rem;color:var(--warning)">
                                <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($masuk == 0 && $keluar == 0): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-triangle-exclamation"></i>
                        Tidak ada transaksi pada periode <strong><?= $nama_bulan[$bulan] ?> <?= $tahun ?></strong>.
                    </div>
                <?php endif; ?>

                <?php if ($saldo < 0): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-circle-exclamation"></i>
                        Perhatian! Kas keluar melebihi kas masuk pada periode ini. Saldo minus Rp <?= number_format(abs($saldo)) ?>.
                    </div>
                <?php endif; ?>

                <!-- CHARTS -->
                <div class="charts-grid">

                    <div class="chart-card">
                        <h2><i class="fas fa-chart-bar" style="color:var(--brand);"></i> Perbandingan Kas</h2>
                        <div class="chart-wrap">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h2><i class="fas fa-chart-pie" style="color:var(--brand);"></i> Komposisi Kas</h2>
                        <div class="chart-wrap">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>

                </div>

                <!-- TABEL DETAIL -->
                <div class="tables-grid">

                    <!-- KAS MASUK -->
                    <div class="table-card">
                        <div class="table-card-header">
                            <h2><span class="dot dot-success"></span> Rincian Kas Masuk</h2>
                            <span class="count"><?= count($dataMasuk) ?> transaksi</span>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama Siswa</th>
                                        <th>Keterangan</th>
                                        <th>Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($dataMasuk)): ?>
                                        <tr class="empty-row">
                                            <td colspan="4">Tidak ada data kas masuk</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($dataMasuk as $d): ?>
                                            <tr>
                                                <td style="white-space:nowrap;"><?= date('d M', strtotime($d['tanggal'])) ?></td>
                                                <td style="font-weight:600;"><?= htmlspecialchars($d['nama'] ?? '—') ?></td>
                                                <td style="color:var(--muted);"><?= htmlspecialchars($d['keterangan']) ?></td>
                                                <td style="font-weight:700;color:var(--success);white-space:nowrap;">+Rp <?= number_format($d['jumlah']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <!-- SUBTOTAL -->
                                        <tr style="background:#f0fdf4;">
                                            <td colspan="3" style="font-weight:700;font-size:.82rem;color:var(--success);padding:10px 14px;">Total Kas Masuk</td>
                                            <td style="font-weight:700;color:var(--success);white-space:nowrap;padding:10px 14px;">Rp <?= number_format($masuk) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- KAS KELUAR -->
                    <div class="table-card">
                        <div class="table-card-header">
                            <h2><span class="dot dot-danger"></span> Rincian Kas Keluar</h2>
                            <span class="count"><?= count($dataKeluar) ?> transaksi</span>
                        </div>
                        <div class="table-wrap">
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
                                    <?php if (empty($dataKeluar)): ?>
                                        <tr class="empty-row">
                                            <td colspan="4">Tidak ada data kas keluar</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($dataKeluar as $d): ?>
                                            <tr>
                                                <td style="white-space:nowrap;"><?= date('d M', strtotime($d['tanggal'])) ?></td>
                                                <td style="color:var(--muted);"><?= htmlspecialchars($d['keterangan']) ?></td>
                                                <td>
                                                    <span style="background:var(--info-bg);color:var(--info);font-size:.72rem;font-weight:600;padding:2px 8px;border-radius:99px;">
                                                        <?= htmlspecialchars($d['kategori'] ?? '—') ?>
                                                    </span>
                                                </td>
                                                <td style="font-weight:700;color:var(--danger);white-space:nowrap;">−Rp <?= number_format($d['jumlah']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <!-- SUBTOTAL -->
                                        <tr style="background:var(--danger-bg);">
                                            <td colspan="3" style="font-weight:700;font-size:.82rem;color:var(--danger);padding:10px 14px;">Total Kas Keluar</td>
                                            <td style="font-weight:700;color:var(--danger);white-space:nowrap;padding:10px 14px;">Rp <?= number_format($keluar) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            <?php elseif (isset($_GET['bulan']) || isset($_GET['tahun'])): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-triangle-exclamation"></i>
                    Filter tidak valid. Pastikan bulan dan tahun dipilih dengan benar.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-circle-info"></i>
                    Pilih <strong>bulan</strong> dan <strong>tahun</strong> untuk menampilkan laporan arus kas.
                </div>
            <?php endif; ?>

        </main>
    </div>

    <?php if ($bulan && $tahun && $valid_filter && ($masuk > 0 || $keluar > 0)): ?>
        <script>
            const masuk = <?= (int)$masuk ?>;
            const keluar = <?= (int)$keluar ?>;

            const chartColors = {
                masuk: {
                    bg: '#22c55e',
                    border: '#16a34a'
                },
                keluar: {
                    bg: '#ef4444',
                    border: '#dc2626'
                },
            };

            // Bar Chart
            new Chart(document.getElementById('barChart'), {
                type: 'bar',
                data: {
                    labels: ['Kas Masuk', 'Kas Keluar'],
                    datasets: [{
                        label: 'Jumlah (Rp)',
                        data: [masuk, keluar],
                        backgroundColor: [chartColors.masuk.bg, chartColors.keluar.bg],
                        borderColor: [chartColors.masuk.border, chartColors.keluar.border],
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                font: {
                                    family: 'Plus Jakarta Sans',
                                    size: 11
                                },
                                callback: v => 'Rp ' + (v / 1000) + 'k'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Plus Jakarta Sans',
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        }
                    }
                }
            });

            // Pie Chart
            new Chart(document.getElementById('pieChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Kas Masuk', 'Kas Keluar'],
                    datasets: [{
                        data: [masuk, keluar],
                        backgroundColor: [chartColors.masuk.bg, chartColors.keluar.bg],
                        borderColor: ['#fff', '#fff'],
                        borderWidth: 3,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
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
        </script>
    <?php endif; ?>

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
            const bulan = document.getElementById('f_bulan').value;
            const tahun = document.getElementById('f_tahun').value;
            const err = document.getElementById('err_filter');

            document.getElementById('f_bulan').classList.remove('is-invalid');
            document.getElementById('f_tahun').classList.remove('is-invalid');
            err.style.display = 'none';

            if (!bulan || !tahun) {
                if (!bulan) document.getElementById('f_bulan').classList.add('is-invalid');
                if (!tahun) document.getElementById('f_tahun').classList.add('is-invalid');
                err.style.display = 'block';
                return false;
            }
            return true;
        }
    </script>
</body>

</html>