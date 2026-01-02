<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../config.php';

$data = fetch_sheet_values();
$rows = $data['values'] ?? [];

// Buat koneksi database global
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// keyword pencarian nama
$keyword = isset($_GET['cari']) ? strtolower(trim($_GET['cari'])) : '';
?>

<style>
.table-custom {
    width: 100%; /*  Perbaiki: jangan 165% agar tidak overflow */
    border-collapse: collapse;
    font-size: 14px;
}
.table-custom thead { background:#0d6efd; color:white; }
.table-custom th, .table-custom td { padding:10px 12px; border:1px solid #dee2e6; }
.table-custom tbody tr:nth-child(even){ background:#f8f9fa; }
.table-custom tbody tr:hover{ background:#ffe5e5; }
.btn-foto{ padding:3px 8px; background:#0d6efd; color:white; border-radius:5px; font-size:12px; text-decoration:none; }
.btn-foto:hover{ opacity:0.8; }
.badge-terlambat{ background:#dc3545; padding:4px 10px; border-radius:10px; color:white; font-size:12px; }
</style>

<?php include __DIR__ . '/pelanggaran.php'; ?>

<h2 class="mt-3 mb-3">Rekapan Terlambat</h2>

<!-- PILIH NAMA -->
<form method="GET" class="d-flex align-items-center gap-2 mb-3">
    <input type="hidden" name="page" value="terlambat">
    <select name="cari" class="form-select" style="max-width:250px;">
        <option value="">-- Pilih Nama Pegawai --</option>
        <?php 
        $listNama = [];
        for($i = 1; $i < count($rows); $i++) {
            $nm = $rows[$i][1] ?? '';
            // Abaikan "Pulang" saat kumpulkan nama
            $ket = strtolower(trim($rows[$i][4] ?? ''));
            if ($nm && $ket !== 'pulang' && !in_array($nm, $listNama)) {
                $listNama[] = $nm;
            }
        }
        sort($listNama);
        foreach($listNama as $nm) {
            $sel = ($keyword == strtolower($nm)) ? "selected" : "";
            echo "<option $sel value='" . strtolower($nm) . "'>$nm</option>";
        }
        ?>
    </select>
    <button class="btn btn-primary">Tampilkan</button>
    <a href="index.php?page=terlambat" class="btn btn-outline-secondary">Reset</a>
</form>

<?php
// ===================== REKAP STATISTIK PER NAMA ===================== //
if ($keyword) {
    $totalAbsen = 0;
    $totalTerlambat = 0;
    $totalMenit = 0;

    for ($i = 1; $i < count($rows); $i++) {
        $r = $rows[$i];
        $timestamp = $r[0] ?? '';
        $nama = $r[1] ?? '';
        $keterangan = strtolower(trim($r[4] ?? ''));

        if (!$timestamp || strtolower($nama) !== $keyword) continue;

        // Abaikan "Pulang"
        if ($keterangan === 'pulang') continue;

        $totalAbsen++;
        if (is_late_from_timestamp($timestamp)) {
            $totalTerlambat++;
            $dt = toWitaDateFromTimestamp($timestamp);
            $deadline = (clone $dt)->setTime(8, 0, 0);
            $totalMenit += round(($dt->getTimestamp() - $deadline->getTimestamp()) / 60);
        }
    }

    $hari = floor($totalMenit / 60);
    echo "
    <div class='alert alert-info mt-3'>
        <h5>Rekapan " . ucfirst($keyword) . "</h5>
        <b>Total Absen:</b> $totalAbsen kali<br>
        <b>Total Terlambat:</b> $totalTerlambat kali<br>
        <b>Total Menit Keterlambatan:</b> $totalMenit menit<br>
        <b>Akumulasi Hari Terlambat:</b> $hari hari<br>
    </div>";
}
?>

<table class="table-custom">
<thead>
    <tr>
        <th>#</th>
        <th>Tanggal</th>
        <th>Nama</th>
        <th>Waktu</th>
        <th>Status</th>
        <th>Foto</th>
    </tr>
</thead>
<tbody>
<?php
$no = 1;

// Ambil seluruh izin dari database
$izinStatus = [];
$q = mysqli_query($conn, "SELECT * FROM izin_status");
while ($d = mysqli_fetch_assoc($q)) {
    $izinStatus[$d['nama']][$d['tanggal']] = $d['status'];
}

for ($i = 1; $i < count($rows); $i++) {
    $r = $rows[$i];
    $timestamp = $r[0] ?? '';
    $nama = $r[1] ?? '';
    $foto = $r[5] ?? '';
    $keterangan = strtolower(trim($r[4] ?? '')); // Kolom E: KETERANGAN

    if (!$timestamp || !$nama) continue;

    // Abaikan "Pulang"
    if ($keterangan === 'pulang') continue;

    if ($keyword && strtolower($nama) !== $keyword) continue;

    $dt = toWitaDateFromTimestamp($timestamp);
    $tgl = $dt->format('Y-m-d');
    $izin = $izinStatus[$nama][$tgl] ?? null;

    // ======= IZIN APPROVED = HADIR ======= //
    if ($izin === "approved") {
        echo "<tr>
            <td>$no</td>
            <td>$tgl</td>
            <td>$nama</td>
            <td>-</td>
            <td><span class='badge bg-success'>Hadir (Izin Disetujui)</span></td>
            <td>-</td>
        </tr>";
        $no++;
        continue;
    }

    // ======= IZIN REJECTED = TERLAMBAT ======= //
    if ($izin === "rejected") {
        echo "<tr>
            <td>$no</td>
            <td>$tgl</td>
            <td>$nama</td>
            <td>-</td>
            <td><span class='badge-terlambat'>Terlambat (Izin Ditolak)</span></td>
            <td>" . ($foto ? "<a class='btn-foto' href='" . htmlspecialchars($foto) . "' target='_blank'>Lihat Foto</a>" : "-") . "</td>
        </tr>";
        $no++;
        continue;
    }

    // ======= Bukan izin → cek terlambat normal ======= //
    if (!is_late_from_timestamp($timestamp)) continue;

    echo "<tr>
        <td>$no</td>
        <td>$tgl</td>
        <td>$nama</td>
        <td>" . $dt->format('H:i:s') . "</td>
        <td><span class='badge-terlambat'>Terlambat</span></td>
        <td>" . ($foto ? "<a class='btn-foto' href='" . htmlspecialchars($foto) . "' target='_blank'>Lihat Foto</a>" : "-") . "</td>
    </tr>";
    $no++;
}

if ($no == 1) {
    echo "<tr><td colspan='6' style='text-align:center;color:#777;padding:15px'>Tidak ada data</td></tr>";
}
?>
</tbody>
</table>