<?php
// config.php — konfigurasi global

// Google Sheets API
// Buat API key Google Sheets dan masukkan di sini
$GOOGLE_API_KEY = "AIzaSyAC9fYj1SsIHD_aN5GdlaBC-z0znUpqFRQ";
$SPREADSHEET_ID = "1NB92zdXd41yDAZ9uqGJQ0QvM27EkJot8jUcIrilvYD0";
$SHEET_NAME     = "Form Responses 1";

// DB local
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "absensi2_db";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Koneksi DB gagal: " . mysqli_connect_error());
}

// izin
$SPREADSHEET_ID_IZIN = "ID_SPREADSHEET_IZIN_GOOGLE_FORM";
$SHEET_NAME_IZIN = "NamaSheetIzin";


// Umum
date_default_timezone_set("Asia/Makassar"); // WITA
const DEADLINE_HOUR = 8; // 08:00
const UPLOAD_DIR = __DIR__ . "/uploads/";
