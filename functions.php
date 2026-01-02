<?php
// functions.php
require_once __DIR__ . '/config.php';

function fetch_sheet_values() {
    global $GOOGLE_API_KEY, $SPREADSHEET_ID, $SHEET_NAME;

    $sheet = urlencode($SHEET_NAME);

    // URL benar TANPA !A:Z
    $url = "https://sheets.googleapis.com/v4/spreadsheets/$SPREADSHEET_ID/values/$sheet?key=$GOOGLE_API_KEY";

    // echo "<pre>URL: $url</pre>";

    $json = @file_get_contents($url);

    if ($json === false) {
        echo "<pre>file_get_contents ERROR</pre>";
        return null;
    }

    // echo "<pre>RESPONSE RAW:\n$json\n</pre>";

    return json_decode($json, true);
}

function render_table_from_sheet($data) {
    if (!isset($data['values']) || count($data['values']) == 0) {
        echo "<p><strong>Tidak ada data!</strong></p>";
        return;
    }

    $rows = $data['values'];

    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width:100%; text-align:left;'>";

    // Header
    echo "<thead><tr>";
    foreach ($rows[0] as $header) {
        echo "<th style='background:#eee; font-weight:bold;'>$header</th>";
    }
    echo "</tr></thead>";

    // Body
    echo "<tbody>";
    for ($i = 1; $i < count($rows); $i++) {
        echo "<tr>";
        foreach ($rows[$i] as $cell) {
            echo "<td>$cell</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";

    echo "</table>";
}


function toWitaDateFromTimestamp($ts) {
    $dt = normalize_timestamp($ts);
    return $dt; // sudah DateTime WITA
}

function is_late_from_timestamp($ts) {
    $dt = toWitaDateFromTimestamp($ts);
    $deadline = clone $dt;
    $deadline->setTime(DEADLINE_HOUR, 0, 0);
    return ($dt > $deadline);
}

function normalize_timestamp($ts) {
    // Format dari Google Form: m/d/Y H:i:s
    $dt = DateTime::createFromFormat('m/d/Y H:i:s', $ts, new DateTimeZone('Asia/Makassar'));
    if ($dt === false) {
        return null;
    }
    return $dt;
}

// Hitung teguran per orang
function compute_teguran_status($late_dates) {
    $total = count($late_dates);
    $consec = 0;

    if ($total > 0) {
        $last = new DateTime(end($late_dates));
        $consec = 1;
        for ($i = $total - 2; $i >= 0; $i--) {
            $d = new DateTime($late_dates[$i]);
            $diff = (int)$last->diff($d)->format('%a');
            if ($diff == 1) {
                $consec++;
                $last = $d;
            } else break;
        }
    }

    // ===========================
    // ATURAN TEGURAN BARU DISINI
    // ===========================
    $teguran = 0;
    if ($total >= 12)       $teguran = 3;   // SP 3
    elseif ($total >= 9)    $teguran = 2;   // SP 2
    elseif ($total >= 6)    $teguran = 1;   // SP 1
    elseif ($total >= 3)    $teguran = 0.5; // Teguran Lisan
    // kamu bebas ubah nilai 0.5 kalau ingin

    return ['total'=>$total,'consecutive'=>$consec,'teguran'=>$teguran];
}

/*************************
 * CRUD IZIN
 *************************/

# CREATE izin
function izin_create($data, $file){
    global $conn;

    $nama       = mysqli_real_escape_string($conn,$data['nama']);
    $instansi   = mysqli_real_escape_string($conn,$data['instansi']);
    $tanggal    = mysqli_real_escape_string($conn,$data['tanggal']);
    $jenis      = mysqli_real_escape_string($conn,$data['jenis']);
    $keterangan = mysqli_real_escape_string($conn,$data['keterangan']);

    # Upload bukti jika ada
    $buktiPath = null;
    if (!empty($file['name'])) {
        if (!is_dir("uploads")) mkdir("uploads", 0755, true);
        $fn = time()."_".basename($file['name']);
        $dest = "uploads/".$fn;
        move_uploaded_file($file['tmp_name'],$dest);
        $buktiPath = $dest;
    }

    return mysqli_query($conn,
        "INSERT INTO izin (nama,instansi,tanggal,jenis,keterangan,bukti)
        VALUES ('$nama','$instansi','$tanggal','$jenis','$keterangan','$buktiPath')"
    );
}

# READ izin
function izin_get_all(){
    global $conn;
    return mysqli_query($conn,"SELECT * FROM izin ORDER BY tanggal DESC");
}

function izin_get_by_id($id){
    global $conn;
    return mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM izin WHERE id='$id'"));
}

# UPDATE izin
function izin_update($id,$data,$file){
    global $conn;
    $nama       = mysqli_real_escape_string($conn,$data['nama']);
    $instansi   = mysqli_real_escape_string($conn,$data['instansi']);
    $tanggal    = mysqli_real_escape_string($conn,$data['tanggal']);
    $jenis      = mysqli_real_escape_string($conn,$data['jenis']);
    $keterangan = mysqli_real_escape_string($conn,$data['keterangan']);

    $sqlBukti = "";
    if(!empty($file['name'])){
        if (!is_dir("uploads")) mkdir("uploads", 0755, true);
        $fn = time()."_".basename($file['name']);
        $dest = "uploads/".$fn;
        move_uploaded_file($file['tmp_name'],$dest);
        $sqlBukti = ", bukti='$dest'";
    }

    return mysqli_query($conn,
        "UPDATE izin SET nama='$nama',instansi='$instansi',tanggal='$tanggal',
         jenis='$jenis',keterangan='$keterangan' $sqlBukti WHERE id='$id'"
    );
}

# DELETE izin
function izin_delete($id){
    global $conn;
    return mysqli_query($conn,"DELETE FROM izin WHERE id='$id'");
}

// Rekapan
function late_minutes($timestamp){
    $dt = normalize_timestamp($timestamp);
    if(!$dt) return 0;

    // batas jam
    $deadline = clone $dt;
    $deadline->setTime(DEADLINE_HOUR, 0, 0); // DEADLINE_HOUR = 8 di config.php

    if($dt <= $deadline) return 0;

    $diff = $dt->getTimestamp() - $deadline->getTimestamp();
    return floor($diff/60); // hasil dalam menit
}

//izin
function fetch_sheet_values_izin(){
    global $GOOGLE_API_KEY, $SPREADSHEET_ID_IZIN, $SHEET_NAME_IZIN;

    $sheet = urlencode($SHEET_NAME_IZIN);
    $url = "https://sheets.googleapis.com/v4/spreadsheets/$SPREADSHEET_ID_IZIN/values/$sheet?key=$GOOGLE_API_KEY";

    $json = file_get_contents($url);
    $data = json_decode($json,true)['values'];

    $izin = [];
    for($i=1;$i<count($data);$i++){
        $izin[]=[
            "id"=>$i,
            "nama"=>$data[$i][0]??'',
            "tanggal"=>$data[$i][1]??'',
            "alasan"=>$data[$i][2]??'',
            "bukti"=>$data[$i][3]??'',
            "status"=>"pending"
        ];
    }
    return $izin;
}

function get_izin_status(){
    global $conn;
    $data=[];
    $q = mysqli_query($conn,"SELECT * FROM izin_status");
    while($r=mysqli_fetch_assoc($q)){
        $data[$r['nama']][$r['tanggal']] = $r; // akses mudah
    }
    return $data;
}

function getDaftarPegawaiAktif() {
    global $db_host, $db_user, $db_pass, $db_name;

    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT nama FROM pegawai_aktif WHERE status = 'aktif'");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_map('strtolower', $result); // case-insensitive
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        return [];
    }
}


