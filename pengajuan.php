<?php
session_start();
include 'config/app.php';

// ================= ACC =================
if (isset($_GET['acc'])) {
    $id = $_GET['acc'];

    $data = query("SELECT * FROM pengajuan WHERE id_pengajuan=$id")[0];

    // masukkan ke transaksi
    query("INSERT INTO transaksi 
        (tanggal, jumlah, jenis, kategori, keterangan, id_pengajuan)
        VALUES 
        (NOW(), '{$data['jumlah']}', 'keluar', '{$data['kategori']}', '{$data['keterangan']}', '$id')");

    // update status
    query("UPDATE pengajuan SET status='disetujui' WHERE id_pengajuan=$id");

    header("Location: pengajuan.php");
}

// ================= TOLAK =================
if (isset($_GET['tolak'])) {
    $id = $_GET['tolak'];
    query("UPDATE pengajuan SET status='ditolak' WHERE id_pengajuan=$id");

    header("Location: pengajuan.php");
}

// ================= DATA =================
$pengajuan = query("SELECT * FROM pengajuan ORDER BY tanggal DESC");
?>

<h3>ACC Pengajuan</h3>

<table border="1" cellpadding="10">
    <tr>
        <th>Jumlah</th>
        <th>Kategori</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>

    <?php foreach ($pengajuan as $p): ?>
        <tr>
            <td><?= $p['jumlah'] ?></td>
            <td><?= $p['kategori'] ?></td>
            <td><?= $p['status'] ?></td>
            <td>
                <?php if ($p['status'] == 'pending'): ?>
                    <a href="?acc=<?= $p['id_pengajuan'] ?>">ACC</a> |
                    <a href="?tolak=<?= $p['id_pengajuan'] ?>">Tolak</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>