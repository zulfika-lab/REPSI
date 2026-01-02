<?php
require_once __DIR__ . '/../functions.php';

$id = $_GET['id'] ?? null;
$data = izin_get_by_id($id);

if(!$data){
    echo "<h3>Data tidak ditemukan</h3>";
    exit;
}
?>

<h2>Detail Izin</h2>
<table class="table table-bordered" style="max-width:600px;">
<tr><th>Nama</th><td><?= $data['nama'] ?></td></tr>
<tr><th>Instansi</th><td><?= $data['instansi'] ?></td></tr>
<tr><th>Tanggal</th><td><?= $data['tanggal'] ?></td></tr>
<tr><th>Jenis</th><td><?= $data['jenis'] ?></td></tr>
<tr><th>Keterangan</th><td><?= $data['keterangan'] ?></td></tr>
<tr><th>Status</th><td><b><?= $data['status'] ?></b></td></tr>
<tr><th>Bukti</th><td>
    <?php if($data['bukti']){ ?>
        <a href="<?= $data['bukti']?>" target="_blank" class="btn btn-info btn-sm">Lihat Bukti</a>
    <?php } else { echo "-"; } ?>
</td></tr>
</table>

<?php if($data['status']=='Pending'): ?>
<form method="POST" action="index.php?page=izin_action">
    <input type="hidden" name="id" value="<?= $data['id'] ?>">

    <label>Alasan (jika ditolak)</label>
    <textarea name="alasan" class="form-control mb-3"></textarea>

    <button name="approve" class="btn btn-success">Approve</button>
    <button name="reject" class="btn btn-danger">Reject</button>
</form>
<?php else: ?>
<div class="alert alert-info">Status: <b><?= $data['status']?></b></div>
<a href="index.php?page=izin_list" class="btn btn-secondary">Kembali</a>
<?php endif; ?>
