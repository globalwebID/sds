<?php
require '_config.php';

// Endpoint lama ini memungkinkan saldo ditambah tanpa pembayaran terverifikasi.
// Top-up hanya boleh dilakukan melalui topup_create.php dan callback penyedia bayar.
http_response_code(410);
response(false, 'Endpoint penambahan saldo langsung sudah dinonaktifkan');
