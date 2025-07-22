<?php
require "../vendor/autoload.php";

use Firebase\JWT\JWT;

$key = "123##Secret";  // Change this to a strong secret key
$issuedAt = time();
$expirationTime = $issuedAt + 86400; // 24 hours expiry

function generateJWT($user_id, $email)
{
    global $key, $issuedAt, $expirationTime;

    $payload = [
        "iat" => $issuedAt,
        "exp" => $expirationTime,
        "sub" => $user_id,
        "email" => $email
    ];

    return JWT::encode($payload, $key, 'HS256');
}
