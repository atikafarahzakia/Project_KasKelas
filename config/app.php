<?php
include 'conn.php';
function query($q)
{
    global $db;
    return mysqli_query($db, $q);
}
function getSaldo()
{
    global $db;
    $m = mysqli_fetch_assoc(mysqli_query($db, "SELECT SUM(jumlah) t FROM transaksi WHERE jenis='masuk'"))['t'];
    $k = mysqli_fetch_assoc(mysqli_query($db, "SELECT SUM(jumlah) t FROM transaksi WHERE jenis='keluar'"))['t'];
    return ($m ?? 0) - ($k ?? 0);
}

function dataSiswa()
{
    $data = query("SELECT * FROM murid");
    return mysqli_num_rows($data);
}
