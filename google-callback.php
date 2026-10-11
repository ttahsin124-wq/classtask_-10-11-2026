<?php
session_start();
require 'db.php';
require 'config.php';

// Verify state to prevent CSRF
if (
    !isset($_GET['code'], $_GET['state']) ||
    !hash_equals($_SESSION['oauth_state'] ?? '', $_GET['state'])
) {
    exit('Invalid request.');
}
unset($_SESSION['oauth_state']);

function curlJson($url, $postData = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }
    if ($token) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    }
    $response = curl_exec($ch);
    if ($response === false) {
        exit('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($response, true);
}

// 1. Exchange the code for an access token
$token = curlJson('https://oauth2.googleapis.com/token', [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (empty($token['access_token'])) {
    exit('Google login failed.');
}

// 2. Get the user's profile
$g = curlJson(
    'https://openidconnect.googleapis.com/v1/userinfo',
    null,
    $token['access_token']
);

if (empty($g['sub']) || empty($g['email']) || empty($g['email_verified'])) {
    exit('Could not verify your Google account.');
}

$googleId = $g['sub'];
$email    = $g['email'];
$name     = $g['name'] ?? $email;

// 3. Find or create the user
$stmt = $pdo->prepare('SELECT * FROM accounts WHERE google_id = ?');
$stmt->execute([$googleId]);
$user = $stmt->fetch();

if (!$user) {
    // Existing account with the same email? Link it.
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $pdo->prepare('UPDATE accounts SET google_id = ? WHERE id = ?')
            ->execute([$googleId, $user['id']]);
    } else {
        // New user: random password hash so password login is impossible
        $randomHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $pdo->prepare(
            'INSERT INTO accounts (name, username, password, email, google_id)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$name, 'g_' . $googleId, $randomHash, $email, $googleId]);

        $user = [
            'id'   => $pdo->lastInsertId(),
            'name' => $name,
        ];
    }
}

// 4. Log in
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['name']    = $user['name'];

header('Location: home.php');
exit;