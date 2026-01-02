<?php
require_once __DIR__ . '/../functions.php';

// ambil data dari Google Sheets
$data = fetch_sheet_values();
$rows = $data['values'] ?? [];

if(!$rows){
    die("Gagal mengambil data Spreadsheet!");
}

// header agar browser download otomatis
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=rekapan_spreadsheet.xls");
header("Pragma: no-cache");
header("Expires: 0");

// generate table excel
echo "<table border='1'>";

// generate header
echo "<tr>";
foreach($rows[0] as $head){
    echo "<th style='background:#ccc;font-weight:bold;'>$head</th>";
}
echo "</tr>";

// generate body
for($i=1;$i<count($rows);$i++){
    echo "<tr>";
    foreach($rows[$i] as $cell){
        echo "<td>".htmlspecialchars($cell)."</td>";
    }
    echo "</tr>";
}

echo "</table>";
exit;
?>
