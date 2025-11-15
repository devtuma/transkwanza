<?php
/**
 * TRANSKWANZA - API de Login Social
 * OAuth 2.0: Google, Facebook, Instagram, Apple
 */

require_once 'config.php';

// Obter método HTTP e provider
$method = $_SERVER['REQUEST_METHOD'];
$provider = $_GET['provider'] ?? '';

if ($method !== 'POST') {
    error('Método não permitido', 405);
}

// ============================================
// ROTEAMENTO POR PROVIDER
// ============================================

switch ($provider) {
    case 'google':
        handleGoogleLogin();
        break;

    case 'facebook':
        handleFacebookLogin();
        break;

    case 'instagram':
        handleInstagramLogin();
        break;

    case 'apple':
        handleAppleLogin();
        break;

    default:
        error('Provider inválido', 400);
}

// ============================================
// FUNÇÃO: LOGIN COM GOOGLE
// ============================================
function handleGoogleLogin() {
    $data = getJSONInput();

    if (empty($data['access_token'])) {
        error('Token do Google não fornecido', 400);
    }

    $accessToken = $data['access_token'];

    // Validar token com Google API
    $googleUser = validateGoogleToken($accessToken);

    if (!$googleUser) {
        error('Token do Google inválido', 401);
    }

    // Processar login/registro
    processOAuthLogin(
        'google',
        $googleUser['id'],
        $googleUser['email'],
        $googleUser['name'],
        $googleUser['picture'] ?? null
    );
}

// ============================================
// FUNÇÃO: LOGIN COM FACEBOOK
// ============================================
function handleFacebookLogin() {
    $data = getJSONInput();

    if (empty($data['access_token'])) {
        error('Token do Facebook não fornecido', 400);
    }

    $accessToken = $data['access_token'];

    // Validar token com Facebook API
    $fbUser = validateFacebookToken($accessToken);

    if (!$fbUser) {
        error('Token do Facebook inválido', 401);
    }

    // Processar login/registro
    processOAuthLogin(
        'facebook',
        $fbUser['id'],
        $fbUser['email'] ?? null,
        $fbUser['name'],
        $fbUser['picture']['data']['url'] ?? null
    );
}

// ============================================
// FUNÇÃO: LOGIN COM INSTAGRAM
// ============================================
function handleInstagramLogin() {
    $data = getJSONInput();

    if (empty($data['access_token'])) {
        error('Token do Instagram não fornecido', 400);
    }

    $accessToken = $data['access_token'];

    // Instagram usa Facebook Graph API
    $instaUser = validateInstagramToken($accessToken);

    if (!$instaUser) {
        error('Token do Instagram inválido', 401);
    }

    // Processar login/registro
    processOAuthLogin(
        'instagram',
        $instaUser['id'],
        $instaUser['email'] ?? null,
        $instaUser['name'] ?? $instaUser['username'],
        $instaUser['profile_picture_url'] ?? null
    );
}

// ============================================
// FUNÇÃO: LOGIN COM APPLE
// ============================================
function handleAppleLogin() {
    $data = getJSONInput();

    if (empty($data['id_token'])) {
        error('Token da Apple não fornecido', 400);
    }

    $idToken = $data['id_token'];

    // Validar token da Apple
    $appleUser = validateAppleToken($idToken);

    if (!$appleUser) {
        error('Token da Apple inválido', 401);
    }

    // Processar login/registro
    processOAuthLogin(
        'apple',
        $appleUser['sub'],
        $appleUser['email'] ?? null,
        $data['name'] ?? 'Usuário Apple', // Apple não retorna nome no token
        null
    );
}

// ============================================
// VALIDADORES DE TOKEN
// ============================================

/**
 * Valida token do Google
 */
function validateGoogleToken($accessToken) {
    $url = "https://www.googleapis.com/oauth2/v3/tokeninfo?access_token=" . urlencode($accessToken);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return false;
    }

    $tokenInfo = json_decode($response, true);

    if (!isset($tokenInfo['sub']) || !isset($tokenInfo['email'])) {
        return false;
    }

    // Buscar informações do usuário
    $url = "https://www.googleapis.com/oauth2/v2/userinfo?access_token=" . urlencode($accessToken);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    $userInfo = json_decode($response, true);

    return [
        'id' => $userInfo['id'],
        'email' => $userInfo['email'],
        'name' => $userInfo['name'] ?? $userInfo['email'],
        'picture' => $userInfo['picture'] ?? null
    ];
}

/**
 * Valida token do Facebook
 */
function validateFacebookToken($accessToken) {
    $url = "https://graph.facebook.com/me?fields=id,name,email,picture&access_token=" . urlencode($accessToken);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return false;
    }

    $userInfo = json_decode($response, true);

    if (!isset($userInfo['id'])) {
        return false;
    }

    return $userInfo;
}

/**
 * Valida token do Instagram (usa Facebook Graph API)
 */
function validateInstagramToken($accessToken) {
    $url = "https://graph.facebook.com/me?fields=id,name,username,profile_picture_url&access_token=" . urlencode($accessToken);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return false;
    }

    $userInfo = json_decode($response, true);

    if (!isset($userInfo['id'])) {
        return false;
    }

    return $userInfo;
}

/**
 * Valida token da Apple (ID Token JWT)
 */
function validateAppleToken($idToken) {
    // Decodificar JWT sem verificar assinatura (simplificado)
    // Em produção, deve verificar a assinatura com as chaves públicas da Apple
    $parts = explode('.', $idToken);

    if (count($parts) !== 3) {
        return false;
    }

    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

    if (!isset($payload['sub'])) {
        return false;
    }

    // Verificar se token não expirou
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

// ============================================
// PROCESSAMENTO UNIFICADO DE LOGIN OAUTH
// ============================================
function processOAuthLogin($provider, $providerId, $email, $name, $picture = null) {
    $pdo = getDBConnection();

    try {
        // Definir campo de ID conforme provider
        $idField = $provider . '_id';

        // 1. Buscar usuário por provider_id
        $stmt = $pdo->prepare("SELECT * FROM users WHERE {$idField} = ?");
        $stmt->execute([$providerId]);
        $user = $stmt->fetch();

        // 2. Se não encontrou, tentar por email (vincular conta existente)
        if (!$user && $email) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Vincular provider_id à conta existente
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET {$idField} = ?, login_method = ?, last_login = NOW(), last_ip = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $providerId,
                    $provider,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $user['id']
                ]);

                logActivity($user['id'], 'social_link', "Vinculou conta com {$provider}");
            }
        }

        // 3. Se ainda não encontrou, criar novo usuário
        if (!$user) {
            // Gerar email temporário se não fornecido
            if (!$email) {
                $email = $provider . '_' . $providerId . '@transkwanza.temp';
            }

            // Detectar país pelo timezone ou definir padrão
            $country = 'BR'; // Padrão: Brasil

            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, {$idField}, login_method, country, avatar, last_ip)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $email,
                $providerId,
                $provider,
                $country,
                $picture,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            $userId = $pdo->lastInsertId();

            // Buscar usuário criado
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            logActivity($userId, 'register', "Cadastro via {$provider}");
        } else {
            // Atualizar último login
            $stmt = $pdo->prepare("
                UPDATE users
                SET last_login = NOW(), last_ip = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);

            logActivity($user['id'], 'login', "Login via {$provider}");
        }

        // Verificar se conta está bloqueada
        if ($user['is_blocked']) {
            error('Conta bloqueada: ' . ($user['blocked_reason'] ?? 'Entre em contato com o suporte'), 403);
        }

        // Verificar anti-fraude
        $sameIPCount = checkMultipleAccountsSameIP($user['id']);
        if ($sameIPCount >= 3) {
            createFraudAlert(
                $user['id'],
                'multiple_accounts',
                'medium',
                "Detectadas {$sameIPCount} contas com o mesmo IP (login {$provider})"
            );
        }

        // Gerar token JWT
        $token = generateJWT([
            'user_id' => $user['id'],
            'email' => $user['email']
        ]);

        // Sanitizar dados
        $user = sanitizeUserData($user);

        // Verificar se precisa completar KYC
        $requiresKYC = in_array($user['kyc_status'], ['pending', 'rejected']);

        success([
            'token' => $token,
            'user' => $user,
            'requires_kyc' => $requiresKYC
        ], 'Login realizado com sucesso');

    } catch (PDOException $e) {
        error_log("OAuth Login Error ({$provider}): " . $e->getMessage());
        error('Erro ao fazer login social', 500);
    }
}
