<?php
// Muat konfigurasi dan fungsi
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

$data = fetch_sheet_values();
$rows = $data['values'] ?? [];

// === Fungsi aman: ambil status izin dari database ===
function getIzinStatus($nama, $tanggal) {
    global $db_host, $db_user, $db_pass, $db_name;
    
    try {
        $pdo = new PDO(
            "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        $stmt = $pdo->prepare("SELECT status FROM izin_status WHERE nama = ? AND tanggal = ? LIMIT 1");
        $stmt->execute([$nama, $tanggal]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

// CSS Kustom
?>
<style>
.izin-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 1.5rem;
    margin-top: 1.5rem;
}
.izin-card h2 {
    color: #0d6efd;
    font-weight: 700;
    margin-bottom: 1.5rem;
}
.status-badge {
    padding: 0.4em 0.8em;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 600;
}
.status-badge.pending { background-color: #e9ecef; color: #6c757d; }
.status-badge.approved { background-color: #d4edda; color: #155724; }
.status-badge.rejected { background-color: #f8d7da; color: #721c24; }
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
}
</style>

<div class="izin-card">
    <h2>Daftar Pengajuan Izin</h2>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Instansi</th>
                    <th>Tanggal</th>
                    <th>Catatan Izin</th>
                    <th>Bukti</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $hasData = false;

                for ($i = 1; $i < count($rows); $i++) {
                    $r = $rows[$i];
                    $timestamp = $r[0] ?? '';
                    $nama = trim($r[1] ?? '');
                    $instansi = $r[2] ?? '';
                    $keterangan = trim($r[6] ?? ''); // Kolom G: Catatan Izin
                    $bukti = $r[5] ?? '';

                    if (!$timestamp || $keterangan === '') continue;

                    // Parse tanggal dari timestamp Google Sheets
                    $dt = toWitaDateFromTimestamp($timestamp);
                    if (!$dt) continue;

                    $tgl = $dt->format('Y-m-d');

                    // Ambil status dari database
                    $izin = getIzinStatus($nama, $tgl);
                    $status = $izin ? $izin['status'] : 'pending';

                    // Tentukan badge
                    switch ($status) {
                        case 'approved':
                            $badgeClass = 'approved';
                            $statusText = 'Disetujui';
                            break;
                        case 'rejected':
                            $badgeClass = 'rejected';
                            $statusText = 'Ditolak';
                            break;
                        default:
                            $badgeClass = 'pending';
                            $statusText = 'Pending';
                    }

                    $hasData = true;
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($nama) ?></td>
                        <td><?= htmlspecialchars($instansi) ?></td>
                        <td><?= $tgl ?></td>
                        <td><?= htmlspecialchars(ucfirst($keterangan)) ?></td>
                        <td>
                            <?php if ($bukti): ?>
                                <a href="<?= htmlspecialchars($bukti) ?>" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                            <?php else: ?>
                                <span class="text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?= $badgeClass ?>"><?= $statusText ?></span>
                        </td>
                        <td>
                            <?php if ($status === 'pending'): ?>
                                <a href="index.php?page=izin_approve&nama=<?= urlencode($nama) ?>&tgl=<?= $tgl ?>" 
                                   class="btn btn-success btn-sm me-1">
                                    <i class="fas fa-check"></i> Setujui
                                </a>
                                <a href="index.php?page=izin_reject&nama=<?= urlencode($nama) ?>&tgl=<?= $tgl ?>" 
                                   class="btn btn-danger btn-sm">
                                    <i class="fas fa-times"></i> Tolak
                                </a>
                            <?php else: ?>
                                <span class="text-muted">
                                    <?= $status === 'approved' ? '✔ Disetujui' : '❌ Ditolak' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }

                if (!$hasData): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox me-2"></i>
                            Belum ada data pengajuan izin
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>