<?php
session_start();
include 'config/app.php';

if (!isset($_SESSION['login']))
    header("location:login.php");

// ================= AMBIL NISN =================
$nisn = (int)($_GET['nisn'] ?? 0);

// ================= DATA SISWA =================
$siswa = query("SELECT * FROM murid WHERE nisn='$nisn'");
$siswa = $siswa[0] ?? null;

if (!$siswa) {
    header("location:statusbayar.php");
    exit;
}

// ================= SETTING =================
$kas_per_bulan = 20000;
$total_bulan   = 12;
$target        = $kas_per_bulan * $total_bulan;

// ================= TOTAL =================
$totalBayar = query("
    SELECT IFNULL(SUM(jumlah),0) as total 
    FROM transaksi 
    WHERE nisn='$nisn' AND jenis='masuk'
")[0]['total'] ?? 0;

$progress = ($target > 0) ? min(($totalBayar / $target) * 100, 100) : 0;

// ================= DATA PER BULAN =================
$nama_bulan_id = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$dataBulan = [];

for ($i = 1; $i <= 12; $i++) {
    $total = query("
        SELECT IFNULL(SUM(jumlah),0) as total 
        FROM transaksi 
        WHERE nisn='$nisn' AND jenis='masuk' AND MONTH(tanggal)='$i'
    ")[0]['total'] ?? 0;

    if ($total == 0) {
        $status = 'Belum';
        $cls = 'danger';
    } elseif ($total < $kas_per_bulan) {
        $status = 'Sebagian';
        $cls = 'warning';
    } else {
        $status = 'Lunas';
        $cls = 'success';
    }

    $dataBulan[] = [
        'bulan'  => $nama_bulan_id[$i],
        'total'  => (int)$total,
        'status' => $status,
        'cls'    => $cls,
    ];
}

// Ringkasan bulan
$lunas_count   = count(array_filter($dataBulan, fn($b) => $b['status'] == 'Lunas'));
$sebagian_count = count(array_filter($dataBulan, fn($b) => $b['status'] == 'Sebagian'));
$belum_count   = count(array_filter($dataBulan, fn($b) => $b['status'] == 'Belum'));
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Siswa — <?= htmlspecialchars($siswa['nama']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style/view-detail.css">
    <style>
        
    </style>
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
                    <h1>Detail Pembayaran</h1>
                    <div class="greeting">Riwayat kas per bulan — <?= date('d F Y') ?></div>
                </div>
                <a href="statusbayar.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <!-- PROFILE CARD -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="profile-info">
                    <div class="name"><?= htmlspecialchars($siswa['nama']) ?></div>
                    <div class="nisn"><i class="fas fa-id-card" style="font-size:.7rem; margin-right:4px;"></i> NISN: <?= htmlspecialchars($siswa['nisn']) ?></div>
                </div>
                <div class="profile-stats">
                    <div class="profile-stat">
                        <div class="ps-value"><?= $lunas_count ?></div>
                        <div class="ps-label">Bulan Lunas</div>
                    </div>
                    <div class="profile-stat">
                        <div class="ps-value"><?= $sebagian_count ?></div>
                        <div class="ps-label">Sebagian</div>
                    </div>
                    <div class="profile-stat">
                        <div class="ps-value"><?= $belum_count ?></div>
                        <div class="ps-label">Belum Bayar</div>
                    </div>
                </div>
            </div>

            <!-- PROGRESS TOTAL -->
            <div class="progress-card">
                <div class="pc-header">
                    <div class="pc-title">
                        <i class="fas fa-bullseye" style="color:var(--success); margin-right:6px;"></i>
                        Progress Pembayaran Tahunan
                    </div>
                    <div class="pc-target">Target: Rp <?= number_format($target) ?></div>
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill <?= $progress >= 100 ? 'success' : ($progress > 0 ? 'warning' : 'danger') ?>"
                        id="progressFill" style="width:0%"></div>
                </div>
                <div class="progress-meta">
                    <span>Terkumpul: Rp <?= number_format($totalBayar) ?></span>
                    <span id="progressLabel">0%</span>
                </div>
            </div>

            <!-- MINI STATS -->
            <div class="mini-stats">
                <div class="mini-stat">
                    <div class="mini-icon" style="background:var(--success-bg); color:var(--success);">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div>
                        <div class="ms-label">Lunas</div>
                        <div class="ms-value" style="color:var(--success);"><?= $lunas_count ?> Bulan</div>
                    </div>
                </div>
                <div class="mini-stat">
                    <div class="mini-icon" style="background:var(--warning-bg); color:var(--warning);">
                        <i class="fas fa-circle-half-stroke"></i>
                    </div>
                    <div>
                        <div class="ms-label">Sebagian</div>
                        <div class="ms-value" style="color:var(--warning);"><?= $sebagian_count ?> Bulan</div>
                    </div>
                </div>
                <div class="mini-stat">
                    <div class="mini-icon" style="background:var(--danger-bg); color:var(--danger);">
                        <i class="fas fa-circle-xmark"></i>
                    </div>
                    <div>
                        <div class="ms-label">Belum Bayar</div>
                        <div class="ms-value" style="color:var(--danger);"><?= $belum_count ?> Bulan</div>
                    </div>
                </div>
            </div>

            <!-- MONTH GRID -->
            <div class="section-card">
                <div class="card-header-bar">
                    <div class="card-title">
                        <i class="fas fa-calendar-days" style="color:var(--brand);"></i>
                        Rincian Per Bulan
                    </div>
                    <span style="font-size:.78rem; color:var(--muted);">Target per bulan: Rp <?= number_format($kas_per_bulan) ?></span>
                </div>
                <div class="card-body">
                    <div class="month-grid">
                        <?php foreach ($dataBulan as $b): ?>
                            <div class="month-card <?= $b['cls'] ?>">
                                <div class="mc-check">
                                    <i class="fas fa-<?= $b['status'] == 'Lunas' ? 'check-circle' : ($b['status'] == 'Sebagian' ? 'minus-circle' : 'times-circle') ?>"></i>
                                </div>
                                <div class="mc-name"><?= $b['bulan'] ?></div>
                                <div class="mc-amount">Rp <?= number_format($b['total']) ?></div>
                                <div class="mc-target">/ Rp <?= number_format($kas_per_bulan) ?></div>
                                <span class="badge badge-<?= $b['cls'] ?>">
                                    <i class="fas fa-<?= $b['status'] == 'Lunas' ? 'check' : ($b['status'] == 'Sebagian' ? 'minus' : 'xmark') ?>" style="font-size:.6rem;"></i>
                                    <?= $b['status'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
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

        window.addEventListener('load', () => {
            const fill = document.getElementById('progressFill');
            const label = document.getElementById('progressLabel');
            const pct = <?= round($progress, 2) ?>;
            setTimeout(() => {
                fill.style.width = pct + '%';
                label.textContent = pct.toFixed(1) + '%';
            }, 300);
        });
    </script>
</body>

</html>