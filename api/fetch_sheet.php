<?php
$spreadsheetId = "1NB92zdXd41yDAZ9uqGJQ0QvM27EkJot8jUcIrilvYD0";
$apiKey = "AIzaSyAC9fYj1SsIHD_aN5GdlaBC-z0znUpqFRQ";
$sheetName = "Form Responses 1";

// Encode sheet name supaya aman
$sheetName = urlencode($sheetName);

// URL Google Sheet API
$url = "https://sheets.googleapis.com/v4/spreadsheets/$spreadsheetId/values/$sheetName?key=$apiKey";

// Inisialisasi cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// fix SSL error di XAMPP (WAJIB)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// Jalankan request
$response = curl_exec($ch);

// Error handling
if (curl_errno($ch)) {
    echo "cURL ERROR: " . curl_error($ch);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Kirim output JSON ke browser
header("Content-Type: application/json");
echo $response;
