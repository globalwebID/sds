<?php
require '_config.php';

$input = json_decode(file_get_contents("php://input"), true);
$number  = $input['number'] ?? '';
$message = $input['message'] ?? '';

if (!$number || !$message) {
    response(false, 'Data WA tidak lengkap');
}

if ($number[0] === '0') {
    $number = '62'.substr($number,1);
}

$payload = [
    'api_key' => 'ISI_API_KEY_WA',
    'sender'  => '6285855858715',
    'number'  => $number,
    'message' => $message
];

$ch = curl_init('https://srv1.wa-api.my.id/send-message');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$result = curl_exec($ch);
curl_close($ch);

response(true, 'WA terkirim', json_decode($result, true));
