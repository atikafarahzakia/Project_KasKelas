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
        WHERE jenis='Masuk'
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
        WHERE jenis='Keluar'
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

    $bulanIni = date('m');
    $tahunIni = date('Y');

    // Total kas masuk
    $totalKasMasuk = mysqli_fetch_assoc(
        mysqli_query($db, "SELECT SUM(jumlah) as total 
                           FROM transaksi 
                           WHERE jenis='Masuk'")
    )['total'] ?? 0;

    // Kas masuk bulan ini
    $kasBulanIni = mysqli_fetch_assoc(
        mysqli_query($db, "SELECT SUM(jumlah) as total 
                           FROM transaksi 
                           WHERE jenis='Masuk' 
                           AND MONTH(tanggal)='$bulanIni' 
                           AND YEAR(tanggal)='$tahunIni'")
    )['total'] ?? 0;

    // Murid sudah bayar bulan ini
    $sudahBayar = mysqli_fetch_assoc(
        mysqli_query($db, "SELECT COUNT(DISTINCT id_murid) as total 
                           FROM transaksi 
                           WHERE jenis='Masuk' 
                           AND MONTH(tanggal)='$bulanIni' 
                           AND YEAR(tanggal)='$tahunIni'")
    )['total'] ?? 0;

    // Total murid
    $totalMurid = mysqli_fetch_assoc(
        mysqli_query($db, "SELECT COUNT(*) as total FROM murid")
    )['total'] ?? 0;

    $belumBayar = $totalMurid - $sudahBayar;

    return [
        'totalKasMasuk' => $totalKasMasuk ?? 0,
        'kasBulanIni'   => $kasBulanIni ?? 0,
        'sudahBayar'    => $sudahBayar ?? 0,
        'belumBayar'    => $belumBayar ?? 0
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
        WHERE jenis='Keluar'
    "))['total'] ?? 0;

    // Total kas keluar bulan ini
    $keluarBulanIni = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='Keluar' 
        AND MONTH(tanggal)='$bulanIni' 
        AND YEAR(tanggal)='$tahunIni'
    "))['total'] ?? 0;

    // Jumlah transaksi keluar
    $jumlahTransaksi = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT COUNT(*) as total 
        FROM transaksi 
        WHERE jenis='Keluar'
    "))['total'] ?? 0;

    // Total kas masuk
    $totalMasuk = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='Masuk'
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
        SELECT murid.id_murid,
               IFNULL(SUM(transaksi.jumlah),0) as total
        FROM murid
        LEFT JOIN transaksi 
            ON murid.id_murid = transaksi.id_murid
            AND transaksi.jenis='Masuk'
            AND MONTH(transaksi.tanggal)='$bulan'
            AND YEAR(transaksi.tanggal)='$tahun'
        GROUP BY murid.id_murid
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
