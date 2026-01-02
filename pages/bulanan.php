<?php
require_once __DIR__ . '/../functions.php';
$data = fetch_sheet_values();
$rows = $data['values'] ?? [];

// === KONEKSI DATABASE LANGSUNG ===
function getIzinFromDB($nama, $tanggal) {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=absensi2_db;charset=utf8mb4',
            'root',  // Ganti jika perlu
            '',      // Ganti jika ada password
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $stmt = $pdo->prepare("SELECT alasan, status, alpha FROM izin_status WHERE nama = ? AND tanggal = ?");
        $stmt->execute([$nama, $tanggal]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Ambil input user (bulan)
$bulan = $_GET['bulan'] ?? '';

// Kumpulkan data per (nama + tanggal) — HANYA untuk absensi "Datang"
$catatanHarian = [];
$tanggalUnik = [];

for ($i = 1; $i < count($rows); $i++) {
    $ts = $rows[$i][0] ?? '';
    $nama = $rows[$i][1] ?? '';
    $keterangan = strtolower(trim($rows[$i][4] ?? '')); // Kolom E: KETERANGAN

    if (!$ts || !$nama) continue;

    // ✅ ABAIKAN JIKA KETERANGAN "PULANG"
    if ($keterangan === 'pulang') continue;

    $dt = toWitaDateFromTimestamp($ts);
    if (!$dt) continue;

    $tanggal = $dt->format('Y-m-d');
    $bln = $dt->format('Y-m');

    if ($bulan && $bln != $bulan) continue;

    if (!in_array($tanggal, $tanggalUnik)) {
        $tanggalUnik[] = $tanggal;
    }

    $key = $nama . '|' . $tanggal;

    if (!isset($catatanHarian[$key])) {
        $catatanHarian[$key] = [
            'nama' => $nama,
            'tanggal' => $tanggal,
            'timestamps' => []
        ];
    }
    $catatanHarian[$key]['timestamps'][] = $ts;
}

// Hitung rekap
$rekap = [];
$totalHadir = 0;
$totalTelat = 0;
$totalIzinDitolak = 0;
$totalAlpha = 0;

foreach ($catatanHarian as $item) {
    $nama = $item['nama'];
    $tanggal = $item['tanggal'];
    $timestamps = $item['timestamps'];

    if (!isset($rekap[$nama])) {
        $rekap[$nama] = [
            'hadir' => 0,
            'telat' => 0,
            'izin_ditolak' => 0,
            'alpha' => 0,
            'alasan_list' => []
        ];
    }

    $izin = getIzinFromDB($nama, $tanggal);

    if ($izin) {
        $alasan = trim($izin['alasan'] ?? '') ?: 'Tanpa alasan';
        $status = $izin['status'] ?? '';

        if ($status === 'approved') {
            $rekap[$nama]['hadir']++;
            $totalHadir++;
            $rekap[$nama]['alasan_list'][] = "$tanggal: [Izin Disetujui] " . htmlspecialchars($alasan);
        } elseif ($status === 'rejected') {
            $rekap[$nama]['izin_ditolak']++;
            $totalIzinDitolak++;
            $rekap[$nama]['alasan_list'][] = "$tanggal: [Izin Ditolak] " . htmlspecialchars($alasan);
        }
    } else {
        $adaHadirTepat = false;
        $adaTerlambat = false;

        foreach ($timestamps as $ts) {
            if (!is_late_from_timestamp($ts)) {
                $adaHadirTepat = true;
            } else {
                $adaTerlambat = true;
            }
        }

        if ($adaHadirTepat) {
            $rekap[$nama]['hadir']++;
            $totalHadir++;
        } elseif ($adaTerlambat) {
            $rekap[$nama]['telat']++;
            $totalTelat++;
        } else {
            $rekap[$nama]['alpha']++;
            $totalAlpha++;
        }
    }
}

// Daftar bulan
$listBulan = [];
for ($i = 1; $i < count($rows); $i++) {
    if (isset($rows[$i][0])) {
        $d = toWitaDateFromTimestamp($rows[$i][0]);
        if ($d) {
            $listBulan[] = $d->format('Y-m');
        }
    }
}
$listBulan = array_unique($listBulan);
sort($listBulan, SORT_STRING);

$totalHari = count($tanggalUnik);
?>

<!-- ✅ CSS Kustom -->
<style>
.stat-card {
    background: white;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 1.2rem;
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}
.stat-card h5 {
    font-size: 14px;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.5rem;
}
.stat-card .stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
}
.text-hadir { color: #198754; }
.text-telat { color: #ffc107; }
.text-ditolak { color: #dc3545; }
.text-alpha { color: #6c757d; }

.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 1.5rem;
    margin-top: 1.5rem;
}
.table-card h4 {
    margin-bottom: 1.2rem;
    font-weight: 600;
    color: #2F44BA;
}
.export-btn {
    padding: 0.45rem 1rem;
    font-size: 14px;
}
</style>

<!-- Header -->
<h2 class="mt-3 mb-4">Rekapan Bulanan</h2>

<!-- Form Filter -->
<form method="GET" class="row g-3 mb-4">
    <input type="hidden" name="page" value="bulanan">
    <div class="col-md-4">
        <select name="bulan" class="form-select">
            <option value="">-- Semua Bulan --</option>
            <?php foreach ($listBulan as $b): ?>
                <option value="<?= htmlspecialchars($b) ?>" <?= ($bulan == $b ? 'selected' : '') ?>>
                    <?= date("F Y", strtotime($b . "-01")) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-8 d-flex gap-2">
        <button type="submit" class="btn btn-primary">Tampilkan</button>
        <a href="?page=bulanan" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<!-- Statistik Ringkasan (Card) -->
<?php if (!empty($rekap)): ?>
<div class="row g-4 mb-4">
    <div class="col-md-2">
        <div class="stat-card text-center">
            <h5>HADIR</h5>
            <p class="stat-number text-hadir"><?= $totalHadir ?></p>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <h5>TERLAMBAT</h5>
            <p class="stat-number text-telat"><?= $totalTelat ?></p>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <h5>IZIN DITOLAK</h5>
            <p class="stat-number text-ditolak"><?= $totalIzinDitolak ?></p>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <h5>ALPHA</h5>
            <p class="stat-number text-alpha"><?= $totalAlpha ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card text-center">
            <h5>TOTAL HARI</h5>
            <p class="stat-number"><?= $totalHari ?></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tabel dalam Card -->
<div class="table-card">
    <div class="d-flex justify-content-between align-items-center">
        <h4>Data Rekap Bulanan</h4>
        <a href="export/export_bulanan.php?bulan=<?= urlencode($bulan) ?>" class="btn btn-success export-btn">
            <i class="fas fa-file-excel"></i> Export ke Excel
        </a>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nama</th>
                    <th>Hadir<br><small>(Termasuk Izin Disetujui)</small></th>
                    <th>Terlambat</th>
                    <th>Izin<br><small class="text-danger">Ditolak</small></th>
                    <th>Alpha<br><small>(Tidak Hadir)</small></th>
                    <th>Total<br>Hari</th>
                    <th>Alasan Izin</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekap)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox me-2"></i>
                            Tidak ada data dalam periode ini
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rekap as $nama => $v): 
                        $total = (int)$v['hadir'] + (int)$v['telat'] + (int)$v['izin_ditolak'] + (int)$v['alpha'];
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($nama) ?></strong></td>
                            <td class="text-success"><?= $v['hadir'] ?></td>
                            <td class="text-warning"><?= $v['telat'] ?></td>
                            <td class="text-danger"><?= $v['izin_ditolak'] ?></td>
                            <td><?= $v['alpha'] ?></td>
                            <td class="fw-bold"><?= $total ?></td>
                            <td>
                                <?php if (!empty($v['alasan_list'])): ?>
                                    <ul class="mb-0 ps-3" style="font-size:12px; color:#6c757d;">
                                        <?php foreach ($v['alasan_list'] as $alasan): ?>
                                            <li><?= $alasan ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted">–</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>