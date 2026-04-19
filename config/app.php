<?php
include 'conn.php';

// ================== QUERY (FIX UTAMA) ==================
function query($q)
{
    global $db;

    $result = mysqli_query($db, $q);

    // kalau SELECT → ubah jadi array
    if (is_object($result)) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // kalau INSERT / UPDATE / DELETE
    return $result;
}

// ================== TAMPILKAN SALDO ==================
function getSaldo()
{
    $m = query("SELECT SUM(jumlah) t FROM transaksi WHERE jenis='masuk'");
    $k = query("SELECT SUM(jumlah) t FROM transaksi WHERE jenis='keluar'");

    return ($m[0]['t'] ?? 0) - ($k[0]['t'] ?? 0);
}

// ================== TAMPILKAN DATA SISWA ==================
function dataSiswa()
{
    $data = query("SELECT * FROM murid");
    return count($data);
}

// ================== KAS MASUK BULAN INI ==================
function kasMasukBulanIni()
{
    $bulan = date('m');
    $tahun = date('Y');

    $result = query("
        SELECT SUM(jumlah) as total
        FROM transaksi
        WHERE jenis='masuk'
        AND MONTH(tanggal)='$bulan'
        AND YEAR(tanggal)='$tahun'
    ");

    return $result[0]['total'] ?? 0;
}

// ================== KAS KELUAR BULAN INI ==================
function kasKeluarBulanIni()
{
    $bulan = date('m');
    $tahun = date('Y');

    $result = query("
        SELECT SUM(jumlah) as total
        FROM transaksi
        WHERE jenis='keluar'
        AND MONTH(tanggal)='$bulan'
        AND YEAR(tanggal)='$tahun'
    ");

    return $result[0]['total'] ?? 0;
}

// ================== TOTAL SALDO ==================
function totalsaldo()
{
    $masuk  = kasMasukBulanIni();
    $keluar = kasKeluarBulanIni();

    return $masuk - $keluar;
}

// ================== RINGKASAN KAS MASUK ==================
function ringkasanKasMasuk()
{
    $masuk = query("SELECT SUM(jumlah) as total FROM transaksi WHERE jenis='masuk'");
    $keluar = query("SELECT SUM(jumlah) as total FROM transaksi WHERE jenis='keluar'");

    $totalMasuk = $masuk[0]['total'] ?? 0;
    $totalKeluar = $keluar[0]['total'] ?? 0;

    return [
        'totalKasMasuk' => $totalMasuk,
        'saldo' => $totalMasuk - $totalKeluar
    ];
}

// ================== RINGKASAN KAS KELUAR ==================
function ringkasanKasKeluar()
{
    $bulanIni = date('m');
    $tahunIni = date('Y');

    $totalKeluar = query("
        SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='keluar'
    ");

    $keluarBulanIni = query("
        SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='keluar' 
        AND MONTH(tanggal)='$bulanIni' 
        AND YEAR(tanggal)='$tahunIni'
    ");

    $jumlahTransaksi = query("
        SELECT COUNT(*) as total 
        FROM transaksi 
        WHERE jenis='keluar'
    ");

    $totalMasuk = query("
        SELECT SUM(jumlah) as total 
        FROM transaksi 
        WHERE jenis='masuk'
    ");

    $saldo = ($totalMasuk[0]['total'] ?? 0) - ($totalKeluar[0]['total'] ?? 0);

    return [
        "totalKeluar"     => $totalKeluar[0]['total'] ?? 0,
        "keluarBulanIni"  => $keluarBulanIni[0]['total'] ?? 0,
        "jumlahTransaksi" => $jumlahTransaksi[0]['total'] ?? 0,
        "saldo"           => $saldo
    ];
}

// ================== STATUS BAYAR ==================
function ringkasanStatusBayar($kas_wajib)
{
    $bulan = date('m');
    $tahun = date('Y');

    $result = query("
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

    foreach ($result as $row) {
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

// ================== SEARCH MURID ==================
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
