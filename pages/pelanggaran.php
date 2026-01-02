<?php
// pelanggaran.php — HANYA TAMPILAN, JANGAN TUTUP KONEKSI!

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../config.php';

$data = fetch_sheet_values();
$rows = $data['values'] ?? [];

// === LANGKAH 1: Ambil semua izin (approved & rejected) ===
$izinList = [];
if (isset($conn) && $conn) {
    $q = mysqli_query($conn, "SELECT nama, tanggal, status FROM izin_status");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $izinList[ $r['nama'] ][ $r['tanggal'] ] = $r['status'];
        }
    }
}

// === LANGKAH 2: Hitung keterlambatan dari absensi ===
$summary = [];

for ($i = 1; $i < count($rows); $i++) {
    $r = $rows[$i];
    $nama = $r[1] ?? '';
    $ts = $r[0] ?? '';
    $keterangan = strtolower(trim($r[4] ?? '')); // Kolom E

    if (!$nama || !$ts) continue;
    if ($keterangan === 'pulang') continue; // Abaikan "Pulang"

    $dt = toWitaDateFromTimestamp($ts);
    if (!$dt) continue;
    $tanggal = $dt->format('Y-m-d');

    // ✅ Jika hari ini ada izin (approve/reject), abaikan data absensi
    if (isset($izinList[$nama][$tanggal])) {
        continue;
    }

    $menit = late_minutes($ts);
    if ($menit > 0) {
        if (!isset($summary[$nama])) {
            $summary[$nama] = 0;
        }
        $summary[$nama] += $menit;
    }
}

// === LANGKAH 3: Tambahkan 60 menit untuk setiap izin REJECTED ===
if (isset($conn) && $conn) {
    $q = mysqli_query($conn, "SELECT nama FROM izin_status WHERE status = 'rejected'");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $nama = $r['nama'];
            if (!isset($summary[$nama])) {
                $summary[$nama] = 0;
            }
            $summary[$nama] += 60; // 1 hari = 60 menit
        }
    }
}
?>

<style>
.table-center { text-align:center; }
.table-center td:first-child, .table-center th:first-child { text-align:left; }
.table-pelanggaran { width:100%; }
.badge { font-size: 0.9em; padding: 0.4em 0.7em; }
</style>

<h2 class="mt-3 text-center">Detail Pelanggaran Disiplin Kehadiran</h2>

<?php if (empty($summary)): ?>
    <div class="alert alert-info text-center">Tidak ada pelanggaran keterlambatan.</div>
<?php else: ?>
    <table class="table table-bordered mb-4 table-center">
        <thead class="table-danger">
            <tr>
                <th>Nama</th>
                <th>Total Menit Terlambat</th>
                <th>Akumulasi Hari (60 menit = 1 hari)</th>
                <th>Status Teguran</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($summary as $nama => $menit): 
                $hari = floor($menit / 60);
                if ($hari >= 12) $teguran = "<span class='badge bg-dark'>SP 3</span>"; 
                elseif ($hari >= 9) $teguran = "<span class='badge bg-danger'>SP 2</span>";
                elseif ($hari >= 6) $teguran = "<span class='badge bg-warning text-dark'>SP 1</span>";
                elseif ($hari >= 3) $teguran = "<span class='badge bg-info text-dark'>Teguran Lisan</span>";
                else $teguran = "<span class='badge bg-secondary'>-</span>";
            ?>
            <tr>
                <td><?= htmlspecialchars($nama) ?></td>
                <td><?= $menit ?> menit</td>
                <td><?= $hari ?> hari</td>
                <td><?= $teguran ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>