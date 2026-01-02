<?php
require_once __DIR__."/../functions.php";

if($_SERVER['REQUEST_METHOD']=='POST'){
    if(izin_create($_POST,$_FILES['bukti'])){
        header("Location:index.php?page=izin_list&success=added");
        exit;
    }
}
?>

<h2>Tambah Izin</h2>
<form method="POST" enctype="multipart/form-data" class="mt-3">

<?php
require_once __DIR__ . '/../functions.php';
$data = fetch_sheet_values();
$rows = $data['values'] ?? [];

// ambil nama-instansi unik
$pegawai = [];
for ($i=1;$i<count($rows);$i++){
    $nm = $rows[$i][1] ?? '';
    $ins = $rows[$i][2] ?? '';
    if($nm && !isset($pegawai[$nm])) $pegawai[$nm] = $ins;
}
?>


  <div class="mb-2">
      <label class="form-label">Pegawai</label>
      <select class="form-select" name="nama" id="pegawaiSelect" required>
          <option value="">-- Pilih Pegawai --</option>
          <?php foreach($pegawai as $nama=>$instansi){ ?>
              <option value="<?= $nama ?>" data-instansi="<?= $instansi ?>">
                  <?= $nama . " - " . $instansi ?>
              </option>
          <?php } ?>
      </select>
  </div>

  <div class="mb-2">
      <label class="form-label">Instansi</label>
      <input class="form-control" name="instansi" id="instansiField" readonly required>
  </div>


    <div class="mb-2">
        <label>Tanggal</label>
        <input type="date" class="form-control" name="tanggal" required>
    </div>

    <div class="mb-2">
        <label>Jenis</label>
        <select class="form-select" name="jenis" required>
            <option>Sakit</option>
            <option>Izin</option>
            <option>Tugas</option>
        </select>
    </div>

    <div class="mb-2">
        <label>Keterangan</label>
        <textarea class="form-control" name="keterangan"></textarea>
    </div>

    <div class="mb-2">
        <label>Bukti</label>
        <input type="file" class="form-control" name="bukti">
    </div>

    <button class="btn btn-primary mt-2">Simpan</button>
</form>
<!-- untuk instansi otomatis terisi -->
<script>
document.getElementById('pegawaiSelect').addEventListener('change', function(){
    let instansi = this.selectedOptions[0].getAttribute('data-instansi');
    document.getElementById('instansiField').value = instansi;
});
</script>

