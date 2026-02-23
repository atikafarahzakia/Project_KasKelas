<?php
session_start();
if (!isset($_SESSION['login']))
    header("location:login.php");
include 'config/app.php';

$kas_wajib = 20000;
$q = query("
    SELECT murid.id_murid, murid.nama,
           IFNULL(SUM(transaksi.jumlah), 0) AS total
    FROM murid
    LEFT JOIN transaksi 
        ON murid.id_murid = transaksi.id_murid
        AND transaksi.jenis = 'Masuk'
        AND MONTH(transaksi.tanggal) = MONTH(CURDATE())
    GROUP BY murid.id_murid
");
?>

<!doctype html>
<html lang="en">

<head>
    <title>Iuran Khusus</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.2.1 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style/style.css">
</head>

<body>
    <div class="d-flex min-vh-100">
        <!-- SIDEBAR -->
        <div class="collapse show bg-dark text-white shadow" id="sidebar">
            <div class="p-3">

                <h4 class="text-center">Kas Kelas</h4>
                <hr>

                <!-- profile -->
                <div class="d-flex align-items-center px-2 mb-2">
                    <img src="assets/piploy.jpg"
                        class="rounded-circle me-2"
                        style="width:45px;height:45px;object-fit:cover;">
                    <p class="fs-5 mb-0"><?= $_SESSION['role']; ?></p>
                </div>

                <hr>

                <!-- menu -->
                <ul class="nav nav-pills flex-column gap-2">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>

                    <li class="nav-item mt-4">
                        <small class="ms-3">Menu Utama</small>
                    </li>
                    <?php if ($_SESSION['role'] == 'Bendahara'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="datamurid.php">Data Murid</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kasmasuk.php">Kas Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kaskeluar.php">Kas Keluar</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="iuran_khusus.php">Iuran Khusus</a>
                        </li>
                        <li class="nav-item mt-4">
                            <small class="ms-3">Laporan</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="aruskas.php">Arus Kas</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="statusbayar.php">Status Bayar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="laporan.php">Laporan</a>
                    </li>
                    <hr>
                    <li class="nav-item" id="logout">
                        <small class="ms-3">Logout</small>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">Log Out</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="flex-fill d-flex flex-column">
            <!-- TOP NAV -->
            <nav class="navbar navbar-dark bg-dark">
                <div class="container-fluid">
                    <button class="navbar-toggler"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#sidebar">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <span class="navbar-brand ms-2">SMK NEGERI 7 SAMARINDA</span>
                </div>
            </nav>
            <!-- main -->
            <div class="p-4">
                <!-- modal murid -->
                <div class="d-grid gap-2 mt-5">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKas">Tambah Murid</button>
                </div>
                <!-- Modal -->
                <div class="modal fade" id="modalTambahKas" tabindex="-1" aria-labelledby="modalTambahKasLabel" aria-hidden="true">
                    <div class="modal-dialog modal-md modal-dialog-centered">
                        <div class="modal-content">
                            <!-- Header -->
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTambahKasLabel"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <!-- Body -->
                            <form action="" method="POST">
                                <input type="hidden" name="id_murid">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Nama Iuran</label>
                                        <input type="text" name="nama" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Siswa</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="semuasiswa" />
                                            <label class="form-check-label" for=""> Semua Siswa </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="pilihsiswa" />
                                            <label class="form-check-label" for=""> Pilih Siswa </label>
                                        </div>
                                    </div>
                                    <!-- muncul ketika pilih siswa -->
                                    <div class="mb-3">
                                        <label class="form-label">Cari</label>
                                        <input type="search" name="cari" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">nominal</label>
                                        <input type="text" name="nominal" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Batas Pembayaran</label>
                                        <input type="text" name="batas" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Keterangan</label>
                                        <input type="text" name="keterangan" class="form-control" required>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <form action="">
                    <div class="mt-3">
                        <div class="card mt-3">
                            <div class="card-body">
                                <!-- ambil dari nama iuran yg di buat sesuai database -->
                                <div class="header">
                                    <div class="d-flex gap-3">
                                        <h5>Iuran Khusus</h5>
                                        <a href=""><i class="fa-solid fa-pen mt-2"></i></a>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <h6 class="text-muted mb-1">Tanggal dibuat: 10/11/2026</h6>
                                        <h6 class="text-muted mb-1">Deadline: 10/11/2027</h6>
                                        <h6 class="text-muted mb-1">Nominal: Rp. 30.000</h6>
                                        <h6 class="text-muted mb-1">Aktif/Selesai</h6>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col">
                                        <input type="search" class="form-control" placeholder="Search">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="inputState" class="form-select">
                                            <option selected>Status</option>
                                            <!-- statis tar ganti yg dari database -->
                                            <option>Januari</option>
                                            <option>Februari</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- tabel -->
                                <table class="table table-hover table-striped border mt-3">
                                    <thead>
                                        <tr>
                                            <th scope="col">Tanggal</th>
                                            <th scope="col">Nama Siswa</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>10/10/2025</td>
                                            <td>Atika</td>
                                            <td>Belum Lunas</td>
                                            <td>
                                                <a href="" class="btn btn-sm btn-warning"><i class="fa-solid fa-pen-to-square"></i></a>
                                                <a href="" onclick="return confirm('hapus data?')" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <h6 class="text-muted mb-0">Target: Rp. 678.000</h6>
                                        <h6 class="text-muted mb-0">Total Terkumpul: Rp. 30.000</h6>
                                    </div>
                                    <nav aria-label="Page navigation example">
                                        <ul class="pagination mb-0">
                                            <li class="page-item">
                                                <a class="page-link" href="#" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            <li class="page-item"><a class="page-link" href="#">1</a></li>
                                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                                            <li class="page-item">
                                                <a class="page-link" href="#" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <footer class="border-top py-2 text-center text-muted small mt-5">
        © Kas Kelas Atika
    </footer>
    <script
        src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+"
        crossorigin="anonymous"></script>
</body>

</html>