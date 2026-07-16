<?php

if (!function_exists('digiflazz_cfg')) {
    function digiflazz_cfg(): array
    {
        static $cfg = null;

        if ($cfg === null) {
            $cfg = require __DIR__ . '/_digiflazz_config.php';
        }

        return is_array($cfg) ? $cfg : [];
    }
}

if (!function_exists('digiflazz_sign_pricelist')) {
    function digiflazz_sign_pricelist(string $username, string $apiKey): string
    {
        return md5($username . $apiKey . 'pricelist');
    }
}

if (!function_exists('digiflazz_sign_transaction')) {
    function digiflazz_sign_transaction(string $username, string $apiKey, string $refId): string
    {
        return md5($username . $apiKey . $refId);
    }
}

if (!function_exists('digiflazz_server_ip')) {
    function digiflazz_server_ip(): string
    {
        $keys = [
            'SERVER_ADDR',
            'LOCAL_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                return trim((string)$_SERVER[$key]);
            }
        }

        return 'unknown';
    }
}

if (!function_exists('digiflazz_extract_message')) {
    function digiflazz_extract_message(?array $decoded, string $fallback = 'Unknown response'): string
    {
        if (!is_array($decoded)) {
            return $fallback;
        }

        $candidates = [
            $decoded['message'] ?? null,
            $decoded['msg'] ?? null,
            $decoded['error'] ?? null,
            $decoded['data']['message'] ?? null,
            $decoded['data']['msg'] ?? null,
            $decoded['data']['rc'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string)$candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $fallback;
    }
}

if (!function_exists('digiflazz_post')) {
    function digiflazz_post(string $endpoint, array $payload): array
    {
        $cfg = digiflazz_cfg();

        $baseUrl = rtrim((string)($cfg['base_url'] ?? ''), '/');
        $url = $baseUrl . '/' . ltrim($endpoint, '/');
        $serverIp = digiflazz_server_ip();

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return [
                'success'    => false,
                'http_code'  => 0,
                'error'      => 'Gagal encode JSON payload',
                'message'    => json_last_error_msg(),
                'raw'        => null,
                'data'       => null,
                'server_ip'  => $serverIp,
                'endpoint'   => $endpoint,
                'request_to' => $url,
            ];
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($json),
            ],
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_CONNECTTIMEOUT => (int)($cfg['connect_timeout'] ?? 15),
            CURLOPT_TIMEOUT        => (int)($cfg['timeout'] ?? 45),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($body === false) {
            return [
                'success'    => false,
                'http_code'  => $httpCode,
                'error'      => 'cURL error: ' . $curlErr,
                'message'    => 'Koneksi ke Digiflazz gagal',
                'raw'        => null,
                'data'       => null,
                'server_ip'  => $serverIp,
                'endpoint'   => $endpoint,
                'request_to' => $url,
            ];
        }

        $decoded = json_decode($body, true);
        $isSuccess = ($httpCode >= 200 && $httpCode < 300);

        return [
            'success'    => $isSuccess,
            'http_code'  => $httpCode,
            'error'      => $isSuccess ? '' : digiflazz_extract_message(is_array($decoded) ? $decoded : null, 'HTTP request gagal'),
            'message'    => digiflazz_extract_message(is_array($decoded) ? $decoded : null, $isSuccess ? 'OK' : 'Request gagal'),
            'raw'        => $body,
            'data'       => is_array($decoded) ? $decoded : null,
            'server_ip'  => $serverIp,
            'endpoint'   => $endpoint,
            'request_to' => $url,
        ];
    }
}

if (!function_exists('digiflazz_fetch_pricelist')) {
    function digiflazz_fetch_pricelist(array $filters = []): array
    {
        $cfg = digiflazz_cfg();
        $username = trim((string)($cfg['username'] ?? ''));
        $apiKey   = trim((string)($cfg['api_key'] ?? ''));

        if ($username === '' || $apiKey === '') {
            return [
                'success'   => false,
                'message'   => 'Username/API key Digiflazz belum diisi',
                'items'     => [],
                'debug'     => null,
                'server_ip' => digiflazz_server_ip(),
            ];
        }

        $payload = [
            'cmd'      => 'prepaid',
            'username' => $username,
            'sign'     => digiflazz_sign_pricelist($username, $apiKey),
        ];

        foreach (['code', 'category', 'brand', 'type'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $payload[$key] = (string)$filters[$key];
            }
        }

        $res = digiflazz_post('price-list', $payload);

        if (!$res['success']) {
            return [
                'success'   => false,
                'message'   => $res['message'] ?: 'Request pricelist gagal',
                'items'     => [],
                'debug'     => $res,
                'server_ip' => $res['server_ip'] ?? digiflazz_server_ip(),
            ];
        }

        $decoded = $res['data'] ?? [];
        $items = [];

        if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) {
            $items = $decoded['data'];
        }

        return [
            'success'   => true,
            'message'   => $res['message'] ?: 'OK',
            'items'     => $items,
            'debug'     => $res,
            'server_ip' => $res['server_ip'] ?? digiflazz_server_ip(),
        ];
    }
}

if (!function_exists('digiflazz_create_transaction')) {
    function digiflazz_create_transaction(string $buyerSkuCode, string $customerNo, string $refId, ?int $maxPrice = null): array
    {
        $cfg = digiflazz_cfg();
        $username = trim((string)($cfg['username'] ?? ''));
        $apiKey   = trim((string)($cfg['api_key'] ?? ''));
        $callback = trim((string)($cfg['callback_url'] ?? ''));

        if ($username === '' || $apiKey === '') {
            return [
                'success'   => false,
                'message'   => 'Username/API key Digiflazz belum diisi',
                'provider'  => null,
                'debug'     => null,
                'server_ip' => digiflazz_server_ip(),
            ];
        }

        $payload = [
            'username'       => $username,
            'buyer_sku_code' => $buyerSkuCode,
            'customer_no'    => $customerNo,
            'ref_id'         => $refId,
            'sign'           => digiflazz_sign_transaction($username, $apiKey, $refId),
        ];

        if (!empty($cfg['testing'])) {
            $payload['testing'] = true;
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $payload['max_price'] = $maxPrice;
        }

        if ($callback !== '') {
            $payload['cb_url'] = $callback;
        }

        $res = digiflazz_post('transaction', $payload);

        $decoded = $res['data'] ?? [];
        $providerData = null;

        if (is_array($decoded) && isset($decoded['data']) && is_array($decoded['data'])) {
            $providerData = $decoded['data'];
        }

        $message = $res['message'] ?? 'Request transaksi gagal';

        if (is_array($providerData)) {
            $providerMessage = trim((string)($providerData['message'] ?? ''));
            if ($providerMessage !== '') {
                $message = $providerMessage;
            } else {
                $providerRc = trim((string)($providerData['rc'] ?? ''));
                if ($providerRc !== '') {
                    $message = $providerRc;
                }
            }
        }

        return [
            'success'   => (bool)$res['success'],
            'message'   => $message,
            'provider'  => $providerData,
            'debug'     => $res,
            'server_ip' => $res['server_ip'] ?? digiflazz_server_ip(),
        ];
    }
}

if (!function_exists('digiflazz_to_bool')) {
    function digiflazz_to_bool($value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $v = strtolower(trim((string)$value));
        return in_array($v, ['1', 'true', 'yes', 'y'], true) ? 1 : 0;
    }
}

if (!function_exists('digiflazz_normalize_text')) {
    function digiflazz_normalize_text(?string $value): string
    {
        $value = trim((string)$value);
        $value = preg_replace('/\s+/', ' ', $value);
        return (string)($value ?? '');
    }
}

if (!function_exists('digiflazz_is_game_product')) {
    function digiflazz_is_game_product(array $item): bool
    {
        $cfg = digiflazz_cfg();

        $brand    = digiflazz_normalize_text((string)($item['brand'] ?? ''));
        $category = digiflazz_normalize_text((string)($item['category'] ?? ''));
        $type     = digiflazz_normalize_text((string)($item['type'] ?? ''));
        $name     = digiflazz_normalize_text((string)($item['product_name'] ?? ''));
        $desc     = digiflazz_normalize_text((string)($item['desc'] ?? ''));

        $allowedBrands = $cfg['allowed_brands'] ?? [];
        if (is_array($allowedBrands) && count($allowedBrands) > 0) {
            foreach ($allowedBrands as $allowed) {
                if (strcasecmp($brand, (string)$allowed) === 0) {
                    return true;
                }
            }
            return false;
        }

        $haystack = strtolower($brand . ' ' . $category . ' ' . $type . ' ' . $name . ' ' . $desc);

        $keywords = [
            'game', 'games',
            'mobile legends', 'free fire', 'pubg', 'valorant',
            'roblox', 'steam', 'diamond', 'uc', 'voucher game',
            'honor of kings', 'genshin', 'point blank'
        ];

        foreach ($keywords as $kw) {
            if (strpos($haystack, strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('digiflazz_get_margin')) {
    function digiflazz_get_margin(string $brand, int $buyPrice): int
    {
        $cfg = digiflazz_cfg();
        $brand = digiflazz_normalize_text($brand);

        $rules = $cfg['margin_rules'] ?? [];
        if (is_array($rules)) {
            foreach ($rules as $ruleBrand => $margin) {
                if (strcasecmp($brand, (string)$ruleBrand) === 0) {
                    return max(0, (int)$margin);
                }
            }
        }

        $default = (int)($cfg['margin_default'] ?? 2000);

        if ($buyPrice <= 10000) return max($default, 1000);
        if ($buyPrice <= 50000) return max($default, 2000);
        if ($buyPrice <= 100000) return max($default, 3000);
        return max($default, 5000);
    }
}

if (!function_exists('digiflazz_make_sell_price')) {
    function digiflazz_make_sell_price(string $brand, int $buyPrice): array
    {
        $profit = digiflazz_get_margin($brand, $buyPrice);
        $sell = $buyPrice + $profit;

        return [
            'price_buy'  => $buyPrice,
            'profit'     => $profit,
            'price_sell' => $sell,
        ];
    }
}

if (!function_exists('digiflazz_brand_need_zone_id')) {
    function digiflazz_brand_need_zone_id(string $brand): int
    {
        $cfg = digiflazz_cfg();
        $brand = digiflazz_normalize_text($brand);

        $need = $cfg['brands_need_zone_id'] ?? [];
        if (!is_array($need)) {
            return 0;
        }

        foreach ($need as $b) {
            if (strcasecmp($brand, (string)$b) === 0) {
                return 1;
            }
        }

        return 0;
    }
}

if (!function_exists('digiflazz_map_status')) {
    function digiflazz_map_status(?string $status): string
    {
        $status = strtolower(trim((string)$status));

        if ($status === 'sukses' || $status === 'success') {
            return 'SUCCESS';
        }

        if ($status === 'pending' || $status === 'process' || $status === 'processing') {
            return 'PROCESSING';
        }

        if ($status === 'gagal' || $status === 'failed' || $status === 'failure') {
            return 'FAILED';
        }

        return 'CREATED';
    }
}

if (!function_exists('digiflazz_make_ref_id')) {
    function digiflazz_make_ref_id(int $idSiswa): string
    {
        return 'GAME-' . $idSiswa . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
    }
}

if (!function_exists('digiflazz_customer_no')) {
    function digiflazz_customer_no(string $userIdGame, string $zoneId = ''): string
    {
        $userIdGame = trim($userIdGame);
        $zoneId = trim($zoneId);

        return $zoneId !== '' ? ($userIdGame . $zoneId) : $userIdGame;
    }
}
