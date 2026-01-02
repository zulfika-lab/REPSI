<?php
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../config.php'; // untuk koneksi database

$data = fetch_sheet_values();
$rows = $data['values'] ?? [];

// === Ambil semua izin dari database ===
$izinList = [];
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if ($conn) {
    $q = mysqli_query($conn, "SELECT nama, tanggal, status FROM izin_status");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $izinList[ $r['nama'] ][ $r['tanggal'] ] = $r['status'];
        }
    }
}

$today = (new DateTime('now', new DateTimeZone('Asia/Makassar')))->format('Y-m-d');
$totalToday = 0;
$lateToday = 0;
$names = [];

// Hitung rekap
for ($i = 1; $i < count($rows); $i++) {
    $r = $rows[$i];
    $timestamp = $r[0] ?? '';
    $nama = $r[1] ?? '';
    $keterangan = strtoupper(trim($r[4] ?? '')); // Kolom E

    if (!$timestamp || !$nama) continue;
    if ($keterangan === 'PULANG') continue; // ✅ Abaikan "Pulang"

    $dt = toWitaDateFromTimestamp($timestamp);
    if (!$dt) continue;

    $dateOnly = $dt->format('Y-m-d');

    // ✅ Jika hari ini ada izin, skip dari perhitungan absen/terlambat
    if (isset($izinList[$nama][$dateOnly])) {
        // Tapi tetap catat nama untuk daftar pegawai
        if (!isset($names[$nama])) {
            $names[$nama] = [];
        }
        continue;
    }

    if ($dateOnly === $today) {
        $totalToday++;
        if (is_late_from_timestamp($timestamp)) {
            $lateToday++;
        }
    }

    if (!isset($names[$nama])) {
        $names[$nama] = [];
    }
    $names[$nama][] = $dateOnly;
}

// === Tambahkan izin rejected sebagai terlambat ===
$izinRejectedToday = 0;
if ($conn) {
    $q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM izin_status WHERE status = 'rejected' AND tanggal = '$today'");
    if ($q) {
        $row = mysqli_fetch_assoc($q);
        $izinRejectedToday = (int)$row['cnt'];
    }
}
$lateToday += $izinRejectedToday; // Tambahkan ke total terlambat hari ini

// Tutup koneksi (opsional)
// mysqli_close($conn);
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 style="color:#AAAAAA;font-weight:bold;">Dashboard Absensi</h1>
    <small class="text-muted"><?= $today ?></small>
</div>

<style>
  body {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .stat-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.2s;
  }

  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
  }

  .stat-card h5 {
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 0.75rem;
  }

  .stat-card h2 {
    font-size: 2.25rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
  }

  .table-responsive {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .table th {
    background-color: #f1f3f5;
    font-weight: 600;
    color: #495057;
  }

  .table td, .table th {
    padding: 0.75rem;
    vertical-align: middle;
  }

  .badge-teguran {
    padding: 0.35em 0.65em;
    font-size: 0.85em;
    font-weight: 600;
    border-radius: 6px;
  }

  .badge-teguran.lisan { background-color: #fff3cd; color: #856404; }
  .badge-teguran.sp1 { background-color: #ffeaa7; color: #553c00; }
  .badge-teguran.sp2 { background-color: #fab1a0; color: #b71c1c; }
  .badge-teguran.sp3 { background-color: #e84393; color: white; }
  .badge-teguran.default { background-color: #e9ecef; color: #6c757d; }/* === GLOBAL RESPONSIVE === */
.content{
    margin-left:240px;
    padding:25px;
    width: calc(100% - 240px);
}

/* Small laptop/Tablet */
@media(max-width: 992px){
    .sidebar{
        width:200px;
    }
    .content{
        margin-left:200px;
        width: calc(100% - 200px);
    }
}

/* HP mode */
@media(max-width: 768px){
    .sidebar{
        position:fixed;
        width: 100%;
        height:auto;
        text-align:center;
        padding:15px;
    }
    .sidebar a{
        display:inline-block;
        margin:4px;
        padding:8px 14px;
    }

    .content{
        margin:0;
        margin-top:160px;
        width:100%;
        padding:15px;
    }

    table{
        font-size:12px;
    }
    th,td{
        padding:6px;
        white-space:nowrap;
    }

    /* enable scroll tabel saat layar kecil */
    .table-responsive{
        overflow-x:auto;
    }
}


</style>

<div class="table-responsive">
<table class="table-custom">
<thead>
<tbody>

<!-- Statistik Kartu -->
<div class="row g-4 mb-5">
  <div class="col-md-4">
    <div class="stat-card">
      <h5>Total Absen Hari Ini</h5>
      <h2 class="text-success"><?= $totalToday ?></h2>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <h5>Terlambat Hari Ini</h5>
      <h2 class="text-warning"><?= $lateToday ?></h2>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card">
      <h5>Total Magang</h5>
      <h2 class="text-info"><?= count($names) ?></h2>
    </div>
  </div>
</div>

<!-- Ringkasan Keterlambatan -->
<hr class="my-4">
<h4 class="mb-3 text-secondary">Ringkasan Keterlambatan Magang</h4>

<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th scope="col">Nama</th>
        <th scope="col">Total Terlambat</th>
        <th scope="col">Teguran</th>
      </tr>
    </thead>
    <tbody>
    
      <?php
      $allLateDates = [];

      // Kumpulkan semua terlambat dari absensi (bukan izin)
      foreach ($names as $nm => $dates) {
          $late_dates = [];
          for ($i = 1; $i < count($rows); $i++) {
              $r = $rows[$i];
              $t = $r[0] ?? '';
              $n = $r[1] ?? '';
              $ket = strtoupper(trim($r[4] ?? ''));

              if ($n !== $nm || !$t) continue;
              if ($ket === 'PULANG') continue;

              $dt = toWitaDateFromTimestamp($t);
              if (!$dt) continue;
              $tgl = $dt->format('Y-m-d');

              // Abaikan jika hari ini ada izin
              if (isset($izinList[$n][$tgl])) continue;

              if (is_late_from_timestamp($t)) {
                  $late_dates[] = $tgl;
              }
          }
          $allLateDates[$nm] = $late_dates;
      }

      // Tambahkan izin rejected sebagai terlambat
      if ($conn) {
          $q = mysqli_query($conn, "SELECT nama, tanggal FROM izin_status WHERE status = 'rejected'");
          if ($q) {
              while ($r = mysqli_fetch_assoc($q)) {
                  $nm = $r['nama'];
                  $tgl = $r['tanggal'];
                  if (!isset($allLateDates[$nm])) {
                      $allLateDates[$nm] = [];
                  }
                  $allLateDates[$nm][] = $tgl; // anggap 1 hari terlambat
              }
          }
      }

      // Tampilkan data
      foreach ($allLateDates as $nm => $late_dates) {
          $stats = compute_teguran_status($late_dates);
          $teg = $stats['teguran'];

          if ($teg == 0) {
              $teg_class = 'default';
              $teg_label = '-';
          } elseif ($teg == 0.5) {
              $teg_class = 'lisan';
              $teg_label = 'Teguran Lisan';
          } elseif ($teg == 1) {
              $teg_class = 'sp1';
              $teg_label = 'SP 1';
          } elseif ($teg == 2) {
              $teg_class = 'sp2';
              $teg_label = 'SP 2';
          } else {
              $teg_class = 'sp3';
              $teg_label = 'SP 3';
          }

          echo "<tr>
                  <td><strong>" . htmlspecialchars($nm) . "</strong></td>
                  <td>" . count($late_dates) . "</td>
                  <td><span class='badge badge-teguran {$teg_class}'>{$teg_label}</span></td>
                </tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<!-- Data Lengkap -->
<hr class="my-4">
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="text-secondary">Data Lengkap Absensi</h4>
  <a href="export/export_spreadsheet.php" class="btn btn-success btn-sm">
    <i class="fas fa-file-excel"></i> Export ke Spreadsheet
  </a>
</div>

<!-- DATA LENGKAP -->
<?php render_table_from_sheet($data); ?>
</tbody>
</table>
</div>