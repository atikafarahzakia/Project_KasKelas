<?php
session_start();
include 'config/app.php';

if (!isset($_SESSION['login']))
    header("location:login.php");

$target_kas = 240000;

// FILTER
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where = "WHERE 1=1";
if ($search) {
    $search_escaped = mysqli_real_escape_string($GLOBALS['db'], $search);
    $where .= " AND murid.nama LIKE '%$search_escaped%'";
}

// QUERY UTAMA
$q = query("
    SELECT 
        murid.nisn,
        murid.nama,
        IFNULL(SUM(CASE 
            WHEN transaksi.jenis='masuk' 
            THEN transaksi.jumlah 
            ELSE 0 
        END),0) as total
    FROM murid
    LEFT JOIN transaksi 
        ON murid.nisn = transaksi.nisn
        AND transaksi.tahun = YEAR(CURDATE())
    $where
    GROUP BY murid.nisn, murid.nama
    ORDER BY murid.nama ASC
");

// CHART DATA
$dataChart = query("
    SELECT murid.nama, IFNULL(SUM(transaksi.jumlah),0) as total
    FROM murid
    LEFT JOIN transaksi 
        ON murid.nisn = transaksi.nisn
        AND transaksi.jenis='masuk'
    GROUP BY murid.nisn, murid.nama
");

$chartNama = [];
$chartTotal = [];
foreach ($dataChart as $d) {
    $chartNama[]  = $d['nama'];
    $chartTotal[] = (int)$d['total'];
}

$ringkasan = ringkasanStatusBayar($target_kas);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Bayar — Kas Kelas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style/statusbayar.css">
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
                    <a href="pengajuan.php"><i class="fas fa-clock"></i> Pengajuan</a>
                <?php endif; ?>

                <?php if ($_SESSION['role'] == 'bendahara'): ?>
                    <a href="kasmasuk.php"><i class="fas fa-arrow-down"></i> Kas Masuk</a>
                    <a href="kaskeluar.php"><i class="fas fa-arrow-up"></i> Kas Keluar</a>
                <?php endif; ?>

                <a href="aruskas.php"><i class="fas fa-chart-bar"></i> Arus Kas</a>
                <a href="statusbayar.php" class="active"><i class="fas fa-chart-column"></i> Status Bayar</a>
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
                    <h1>Status Bayar</h1>
                    <div class="greeting">Pantau status pembayaran kas seluruh siswa — <?= date('d F Y') ?></div>
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--success-bg); color:var(--success);">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div>
                        <div class="label">Lunas</div>
                        <div class="value" style="color:var(--success);"><?= $ringkasan['lunas'] ?></div>
                        <div class="sub">Siswa</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--warning-bg); color:var(--warning);">
                        <i class="fas fa-circle-half-stroke"></i>
                    </div>
                    <div>
                        <div class="label">Sebagian</div>
                        <div class="value" style="color:var(--warning);"><?= $ringkasan['sebagian'] ?></div>
                        <div class="sub">Siswa</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--danger-bg); color:var(--danger);">
                        <i class="fas fa-circle-xmark"></i>
                    </div>
                    <div>
                        <div class="label">Belum Bayar</div>
                        <div class="value" style="color:var(--danger);"><?= $ringkasan['belum'] ?></div>
                        <div class="sub">Siswa</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--brand-light); color:var(--brand);">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div>
                        <div class="label">Target / Siswa</div>
                        <div class="value" style="color:var(--brand); font-size:1.1rem;">Rp <?= number_format($target_kas) ?></div>
                        <div class="sub">Per tahun</div>
                    </div>
                </div>
            </div>

            <!-- CHART SECTION -->
            <div class="section-card" style="margin-bottom:24px;">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-chart-bar" style="color:var(--brand);"></i>
                        Perbandingan Pembayaran Per Siswa
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="chartSiswa"></canvas>
                    </div>
                </div>
            </div>

            <!-- TABLE SECTION -->
            <div class="section-card">
                <div class="card-header">
                    <div class="card-title">
                        <i class="fas fa-table" style="color:var(--brand);"></i>
                        Daftar Status Pembayaran
                    </div>
                </div>
                <div class="card-body">

                    <!-- FILTER -->
                    <form method="GET" class="filter-bar" style="margin-bottom:20px;">
                        <div class="filter-group" style="flex:1; min-width:180px;">
                            <label>Cari Nama Siswa</label>
                            <input type="text" name="search" class="form-control" placeholder="Ketik nama..."
                                value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="filter-group" style="min-width:160px;">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="lunas" <?= $status_filter == 'lunas'      ? 'selected' : '' ?>>Lunas</option>
                                <option value="sebagian" <?= $status_filter == 'sebagian'   ? 'selected' : '' ?>>Sebagian</option>
                                <option value="belum_bayar" <?= $status_filter == 'belum_bayar' ? 'selected' : '' ?>>Belum Bayar</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label style="opacity:0;">_</label>
                            <div style="display:flex; gap:8px;">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                                <a href="statusbayar.php" class="btn btn-secondary"><i class="fas fa-rotate-left"></i> Reset</a>
                            </div>
                        </div>
                    </form>

                    <!-- TABLE -->
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Siswa</th>
                                    <th>Status</th>
                                    <th>Progress Pembayaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 0;
                                $ada_data = false;
                                foreach ($q as $m):
                                    $total_bayar = (int)$m['total'];
                                    $persen = ($target_kas > 0) ? min(($total_bayar / $target_kas) * 100, 100) : 0;

                                    if ($total_bayar == 0) {
                                        $s = 'Belum Bayar';
                                        $cls = 'danger';
                                        $icon = 'circle-xmark';
                                    } elseif ($total_bayar < $target_kas) {
                                        $s = 'Sebagian';
                                        $cls = 'warning';
                                        $icon = 'circle-half-stroke';
                                    } else {
                                        $s = 'Lunas';
                                        $cls = 'success';
                                        $icon = 'circle-check';
                                    }

                                    if ($status_filter == 'lunas'       && $s != 'Lunas')       continue;
                                    if ($status_filter == 'sebagian'    && $s != 'Sebagian')    continue;
                                    if ($status_filter == 'belum_bayar' && $s != 'Belum Bayar') continue;

                                    $ada_data = true;
                                    $no++;
                                ?>
                                    <tr>
                                        <td style="color:var(--muted); font-size:.78rem;"><?= $no ?></td>
                                        <td>
                                            <span class="student-name"><?= htmlspecialchars($m['nama']) ?></span>
                                            <a href="view-detail.php?nisn=<?= $m['nisn'] ?>" class="detail-link">
                                                <i class="fas fa-eye" style="font-size:.65rem;"></i> Lihat Detail
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $cls ?>">
                                                <i class="fas fa-<?= $icon ?>" style="font-size:.65rem;"></i>
                                                <?= $s ?>
                                            </span>
                                        </td>
                                        <td style="min-width:200px;">
                                            <div class="progress-wrap">
                                                <div class="progress-fill <?= $cls ?>" style="width:<?= $persen ?>%"></div>
                                            </div>
                                            <div class="progress-meta">
                                                Rp <?= number_format($total_bayar) ?> / Rp <?= number_format($target_kas) ?>
                                                &nbsp;·&nbsp; <strong><?= round($persen) ?>%</strong>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (!$ada_data): ?>
                                    <tr>
                                        <td colspan="4" class="empty-table">
                                            <i class="fas fa-inbox" style="font-size:2rem; display:block; margin-bottom:8px; opacity:.4;"></i>
                                            Tidak ada data yang cocok dengan filter
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

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

        // ── Bar Chart ──
        const chartNama = <?= json_encode($chartNama) ?>;
        const chartTotal = <?= json_encode($chartTotal) ?>;
        const targetKas = <?= (int)$target_kas ?>;

        const colors = chartTotal.map(v =>
            v >= targetKas ? '#22c55e' :
            v > 0 ? '#f59e0b' : '#ef4444'
        );

        new Chart(document.getElementById('chartSiswa'), {
            type: 'bar',
            data: {
                labels: chartNama,
                datasets: [{
                    label: 'Total Dibayar (Rp)',
                    data: chartTotal,
                    backgroundColor: colors,
                    borderRadius: 6,
                    borderSkipped: false,
                }, {
                    label: 'Target (Rp)',
                    data: chartNama.map(() => targetKas),
                    type: 'line',
                    borderColor: '#2563eb',
                    borderDash: [6, 4],
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
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
                            label: ctx => ' Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => 'Rp ' + v.toLocaleString('id-ID'),
                            font: {
                                size: 11,
                                family: 'Plus Jakarta Sans'
                            }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11,
                                family: 'Plus Jakarta Sans'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>