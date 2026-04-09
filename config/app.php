<?php
include 'conn.php';
function query($q)
{
    global $db;
    return mysqli_query($db, $q);
}

// ================== TAMPILKAN SALDO ==================
function getSaldo()
{
    global $db;
    $m = mysqli_fetch_assoc(mysqli_query($db, "SELECT SUM(jumlah) t FROM transaksi WHERE jenis='masuk'"))['t'];
    $k = mysqli_fetch_assoc(mysqli_query($db, "SELECT SUM(jumlah) t FROM transaksi WHERE jenis='keluar'"))['t'];
    return ($m ?? 0) - ($k ?? 0);
}

// ================== TAMPILKAN DATA SISWA ==================
function dataSiswa()
{
    $data = query("SELECT * FROM murid");
    return mysqli_num_rows($data);
}

// ================== KAS MASUK BULAN INI ==================
function kasMasukBulanIni()
{
    global $db;

    $bulan = date('m');
    $tahun = date('Y');

    $result = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(jumlah) as total
        FROM transaksi
        WHERE jenis='masuk'
        AND MONTH(tanggal)='$bulan'
        AND YEAR(tanggal)='$tahun'
    "));

    return $result['total'] ?? 0;
}

// ================== KAS KELUAR BULAN INI ==================
function kasKeluarBulanIni()
{
    global $db;

    $bulan = date('m');
    $tahun = date('Y');

    $result = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(jumlah) as total
        FROM transaksi
        WHERE jenis='keluar'
        AND MONTH(tanggal)='$bulan'
        AND YEAR(tanggal)='$tahun'
    "));

    return $result['total'] ?? 0;
}

// ================== ARUS KAS BULAN INI ==================
function arusKasBulanIni()
{
    $masuk  = kasMasukBulanIni();
    $keluar = kasKeluarBulanIni();

    return $masuk - $keluar;
}

// ================== RINGKASAN KAS MASUK ==================
function ringkasanKasMasuk()
{
    global $db;

    // total kas masuk
    $masuk = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(jumlah) as total FROM transaksi WHERE jenis='masuk'
    "));

    // total kas keluar
    $keluar = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(jumlah) as total FROM transaksi WHERE jenis='keluar'
    "));

    return [
        'totalKasMasuk' => $masuk['total'] ?? 0,
        'saldo' => ($masuk['total'] ?? 0) - ($keluar['total'] ?? 0)
    ];
}

// ================== RINGKASAN KAS KELUAR ==================
function ringkasanKasKeluar()
{
    global $db; // ⬅️ PERBAIKI DI SINI

    $bulanIni = date('m');
    $tahunIni = date('Y');

    // Total kas keluar semua waktu
    $totalKeluar = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='keluar'
    "))['total'] ?? 0;

    // Total kas keluar bulan ini
    $keluarBulanIni = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='keluar' 
        AND MONTH(tanggal)='$bulanIni' 
        AND YEAR(tanggal)='$tahunIni'
    "))['total'] ?? 0;

    // Jumlah transaksi keluar
    $jumlahTransaksi = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT COUNT(*) as total 
        FROM transaksi 
        WHERE jenis='keluar'
    "))['total'] ?? 0;

    // Total kas masuk
    $totalMasuk = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='masuk'
    "))['total'] ?? 0;

    $saldo = $totalMasuk - $totalKeluar;

    return [
        "totalKeluar"     => $totalKeluar,
        "keluarBulanIni"  => $keluarBulanIni,
        "jumlahTransaksi" => $jumlahTransaksi,
        "saldo"           => $saldo
    ];
}

// ================== HITUNG SISWA LUNAS, BELUM LUNAS, DAN SEBAGIAN BAYAR ==================
function ringkasanStatusBayar($kas_wajib)
{
    global $db;

    $bulan = date('m');
    $tahun = date('Y');

    $result = mysqli_query($db, "
        SELECT murid.nisn,
               IFNULL(SUM(transaksi.jumlah),0) as total
        FROM murid
        LEFT JOIN transaksi 
            ON murid.nisn = transaksi.nisn
            AND transaksi.jenis='masuk'
            AND MONTH(transaksi.tanggal)='$bulan'
            AND YEAR(transaksi.tanggal)='$tahun'
        GROUP BY murid.nisn
    ");

    $lunas = 0;
    $belum = 0;
    $sebagian = 0;

    while ($row = mysqli_fetch_assoc($result)) {

        if ($row['total'] == 0) {
            $belum++;
        } elseif ($row['total'] < $kas_wajib) {
            $sebagian++;
        } else {
            $lunas++;
        }
    }

    return [
        'lunas' => $lunas,
        'belum' => $belum,
        'sebagian' => $sebagian
    ];
}

// ================== SEARCH MURID BERDASARKAN NAMA ==================
function searchMurid($search = '')
{
    global $db;
    
    $search = mysqli_real_escape_string($db, $search);
    
    $q = "
        SELECT murid.nisn, murid.nama,
               IFNULL(SUM(transaksi.jumlah), 0) AS total
        FROM murid
        LEFT JOIN transaksi 
            ON murid.nisn = transaksi.nisn
            AND transaksi.jenis = 'masuk'
            AND MONTH(transaksi.tanggal) = MONTH(CURDATE())
        WHERE murid.nama LIKE '%$search%'
        GROUP BY murid.nisn
        ORDER BY murid.nama ASC
    ";
    
    return query($q);
}
