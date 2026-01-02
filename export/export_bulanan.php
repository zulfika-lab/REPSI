<?php
// export_bulanan.php

require_once __DIR__ . '/../functions.php';

// Ambil parameter bulan (jika ada)
$bulan = $_GET['bulan'] ?? '';

// Ambil data dari Google Sheets
$data = fetch_sheet_values();
$rows = $data['values'] ?? [];

// Fungsi koneksi database (langsung di sini)
function getIzinFromDB($nama, $tanggal) {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=absensi2_db;charset=utf8mb4',
            'root',  // GANTI jika user/password berbeda
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        $stmt = $pdo->prepare("SELECT alasan, status, alpha FROM izin_status WHERE nama = ? AND tanggal = ?");
        $stmt->execute([$nama, $tanggal]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Kumpulkan data per (nama + tanggal)
$catatanHarian = [];
for ($i = 1; $i < count($rows); $i++) {
    $ts = $rows[$i][0] ?? '';
    $nama = $rows[$i][1] ?? '';
    if (!$ts || !$nama) continue;

    $dt = toWitaDateFromTimestamp($ts);
    if (!$dt) continue;

    $bln = $dt->format('Y-m');
    if ($bulan && $bln != $bulan) continue;

    $tanggal = $dt->format('Y-m-d');
    $key = $nama . '|' . $tanggal;

    if (!isset($catatanHarian[$key])) {
        $catatanHarian[$key] = ['nama' => $nama, 'tanggal' => $tanggal, 'timestamps' => []];
    }
    $catatanHarian[$key]['timestamps'][] = $ts;
}

// Hitung rekap
$rekap = [];
foreach ($catatanHarian as $item) {
    $nama = $item['nama'];
    $tanggal = $item['tanggal'];
    $timestamps = $item['timestamps'];

    if (!isset($rekap[$nama])) {
        $rekap[$nama] = [
            'hadir' => 0,
            'telat' => 0,
            'izin_disetujui' => 0,
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
            $rekap[$nama]['izin_disetujui']++;
            $rekap[$nama]['alasan_list'][] = "$tanggal: [Disetujui] " . $alasan;
        } elseif ($status === 'rejected') {
            $rekap[$nama]['izin_ditolak']++;
            $rekap[$nama]['alasan_list'][] = "$tanggal: [Ditolak] " . $alasan;
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
        } elseif ($adaTerlambat) {
            $rekap[$nama]['telat']++;
        } else {
            $rekap[$nama]['alpha']++;
        }
    }
}

// === EXPORT KE CSV ===
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="rekap_bulanan_' . ($bulan ?: 'semua') . '.csv"');

$output = fopen('php://output', 'w');

// Header
fputcsv($output, [
    'Nama',
    'Hadir (Tepat Waktu)',
    'Terlambat',
    'Izin Disetujui',
    'Izin Ditolak',
    'Alpha (Tidak Hadir)',
    'Total Hari',
    'Alasan Izin'
]);

// Isi data
foreach ($rekap as $nama => $v) {
    $total = (int)$v['hadir'] + (int)$v['telat'] + (int)$v['izin_disetujui'] + (int)$v['izin_ditolak'] + (int)$v['alpha'];
    $alasanText = !empty($v['alasan_list']) ? implode("\n", $v['alasan_list']) : '';

    fputcsv($output, [
        $nama,
        (int)$v['hadir'],
        (int)$v['telat'],
        (int)$v['izin_disetujui'],
        (int)$v['izin_ditolak'],
        (int)$v['alpha'],
        $total,
        $alasanText
    ]);
}

fclose($output);
exit;