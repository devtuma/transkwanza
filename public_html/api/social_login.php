<?php
// ============================================
// TRANSKWANZA - FASE 3: SOCIAL LOGIN
// Login com Google e Facebook
// ============================================

require_once 'config.php';

$provider = $_GET['provider'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

// ============================================
// GOOGLE LOGIN
// ============================================
if ($provider === 'google') {
    $accessToken = $data['access_token'] ?? '';
    $idToken = $data['id_token'] ?? '';

    if (empty($accessToken) && empty($idToken)) {
        sendJSON(['success' => false, 'message' => 'Token do Google não fornecido'], 400);
    }

    // Validar token com Google (simplificado - em produção usar Google API)
    // Por enquanto, aceitar qualquer token para testes
    $googleUser = [
        'id' => $data['google_id'] ?? uniqid('google_'),
        'email' => $data['email'] ?? '',
        'name' => $data['name'] ?? '',
        'picture' => $data['picture'] ?? ''
    ];

    if (empty($googleUser['email']) || empty($googleUser['name'])) {
        sendJSON(['success' => false, 'message' => 'Dados incompletos do Google'], 400);
    }

    // Verificar se usuário já existe
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$googleUser['email']]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // Usuário já existe - fazer login
        sendJSON([
            'success' => true,
            'message' => 'Login com Google realizado!',
            'user' => [
                'id' => $existingUser['id'],
                'name' => $existingUser['name'],
                'email' => $existingUser['email'],
                'country' => $existingUser['country'],
                'kyc_status' => $existingUser['kyc_status']
            ]
        ]);
    } else {
        // Criar nova conta
        $hashedPassword = password_hash(uniqid(), PASSWORD_BCRYPT); // Senha aleatória
        $country = $data['country'] ?? 'Brasil'; // País padrão

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, country)
            VALUES (?, ?, ?, ?)
        ");

        if ($stmt->execute([$googleUser['name'], $googleUser['email'], $hashedPassword, $country])) {
            $userId = $pdo->lastInsertId();

            sendJSON([
                'success' => true,
                'message' => 'Conta criada com Google!',
                'user' => [
                    'id' => $userId,
                    'name' => $googleUser['name'],
                    'email' => $googleUser['email'],
                    'country' => $country,
                    'kyc_status' => 'pending'
                ]
            ]);
        } else {
            sendJSON(['success' => false, 'message' => 'Erro ao criar conta'], 500);
        }
    }
}

// ============================================
// FACEBOOK LOGIN
// ============================================
else if ($provider === 'facebook') {
    $accessToken = $data['access_token'] ?? '';
    $userId = $data['user_id'] ?? '';

    if (empty($accessToken) || empty($userId)) {
        sendJSON(['success' => false, 'message' => 'Token do Facebook não fornecido'], 400);
    }

    // Validar token com Facebook (simplificado - em produção usar Facebook Graph API)
    // Por enquanto, aceitar qualquer token para testes
    $facebookUser = [
        'id' => $userId,
        'email' => $data['email'] ?? '',
        'name' => $data['name'] ?? '',
        'picture' => $data['picture'] ?? ''
    ];

    if (empty($facebookUser['email']) || empty($facebookUser['name'])) {
        sendJSON(['success' => false, 'message' => 'Dados incompletos do Facebook'], 400);
    }

    // Verificar se usuário já existe
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$facebookUser['email']]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // Usuário já existe - fazer login
        sendJSON([
            'success' => true,
            'message' => 'Login com Facebook realizado!',
            'user' => [
                'id' => $existingUser['id'],
                'name' => $existingUser['name'],
                'email' => $existingUser['email'],
                'country' => $existingUser['country'],
                'kyc_status' => $existingUser['kyc_status']
            ]
        ]);
    } else {
        // Criar nova conta
        $hashedPassword = password_hash(uniqid(), PASSWORD_BCRYPT); // Senha aleatória
        $country = $data['country'] ?? 'Brasil'; // País padrão

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, country)
            VALUES (?, ?, ?, ?)
        ");

        if ($stmt->execute([$facebookUser['name'], $facebookUser['email'], $hashedPassword, $country])) {
            $userId = $pdo->lastInsertId();

            sendJSON([
                'success' => true,
                'message' => 'Conta criada com Facebook!',
                'user' => [
                    'id' => $userId,
                    'name' => $facebookUser['name'],
                    'email' => $facebookUser['email'],
                    'country' => $country,
                    'kyc_status' => 'pending'
                ]
            ]);
        } else {
            sendJSON(['success' => false, 'message' => 'Erro ao criar conta'], 500);
        }
    }
}

// ============================================
// PROVIDER INVÁLIDO
// ============================================
else {
    sendJSON(['success' => false, 'message' => 'Provider inválido'], 400);
}
