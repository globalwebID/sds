<?php
// Ganti sesuai data Anda
$apiKey = 'c2luZ3N0cmVldDI1QGdtYWlsLmNvbQ:z2cS6NekuAzI79Mv_8wxS';
$avatarUrl = 'bot_idle.png';

$input = json_decode(file_get_contents('php://input'), true);
$text = $input['text'] ?? 'Halo dari Asisten Virtual.';

$data = [
    "script" => [
        "type" => "text",
        "input" => $text,
        "provider" => [
            "type" => "microsoft",
            "voice_id" => "id-ID-GadisNeural"
        ],
        "lang" => "id"
    ],
    "source_url" => $avatarUrl
];

$ch = curl_init("https://api.d-id.com/talks");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: $apiKey"
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$video_url = $result['result_url'] ?? null;

echo json_encode(["video_url" => $video_url]);
