<?php
/**
 * TRANSKWANZA - API de Autenticação
 * Login/Cadastro tradicional (email/senha)
 */

require_once 'config.php';

// Obter método HTTP e ação
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================
// ROTEAMENTO
// ============================================

switch ($action) {
    case 'register':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleRegister();
        break;

    case 'login':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleLogin();
        break;

    case 'check':
        if ($method !== 'GET') error('Método não permitido', 405);
        handleCheckAuth();
        break;

    case 'logout':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleLogout();
        break;

    default:
        error('Ação inválida', 400);
}

// ============================================
// FUNÇÃO: CADASTRO
// ============================================
function handleRegister() {
    $data = getJSONInput();

    // Validar campos obrigatórios
    $required = ['name', 'email', 'password', 'country'];
    $missing = validateRequired($required, $data);

    if (!empty($missing)) {
        error('Campos obrigatórios faltando: ' . implode(', ', $missing), 400);
    }

    // Sanitizar e validar dados
    $name = sanitizeString($data['name']);
    $email = strtolower(trim($data['email']));
    $password = $data['password'];
    $country = strtoupper(sanitizeString($data['country']));
    $phone = isset($data['phone']) ? sanitizeString($data['phone']) : null;

    // Validações
    if (!validateEmail($email)) {
        error('Email inválido', 400);
    }

    if (!validatePassword($password)) {
        error('Senha deve ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres', 400);
    }

    if (strlen($name) < 3) {
        error('Nome deve ter no mínimo 3 caracteres', 400);
    }

    if (strlen($country) !== 2) {
        error('Código de país inválido', 400);
    }

    $pdo = getDBConnection();

    try {
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            error('Email já cadastrado', 400);
        }

        // Gerar hash da senha
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Inserir usuário
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, country, phone, login_method, last_ip)
            VALUES (?, ?, ?, ?, ?, 'email', ?)
        ");

        $stmt->execute([
            $name,
            $email,
            $passwordHash,
            $country,
            $phone,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);

        $userId = $pdo->lastInsertId();

        // Buscar usuário criado
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        // Registrar atividade
        logActivity($userId, 'register', 'Cadastro via email');

        // Gerar token JWT
        $token = generateJWT([
            'user_id' => $userId,
            'email' => $email
        ]);

        // Sanitizar dados do usuário
        $user = sanitizeUserData($user);

        success([
            'token' => $token,
            'user' => $user,
            'requires_kyc' => true
        ], 'Cadastro realizado com sucesso');

    } catch (PDOException $e) {
        error_log("Register Error: " . $e->getMessage());
        error('Erro ao criar usuário', 500);
    }
}

// ============================================
// FUNÇÃO: LOGIN
// ============================================
function handleLogin() {
    $data = getJSONInput();

    // Validar campos obrigatórios
    $required = ['email', 'password'];
    $missing = validateRequired($required, $data);

    if (!empty($missing)) {
        error('Campos obrigatórios faltando: ' . implode(', ', $missing), 400);
    }

    $email = strtolower(trim($data['email']));
    $password = $data['password'];

    if (!validateEmail($email)) {
        error('Email inválido', 400);
    }

    $pdo = getDBConnection();

    try {
        // Buscar usuário por email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Registrar tentativa falha
            logActivity(null, 'login_failed', 'Email não encontrado: ' . $email);
            error('Email ou senha incorretos', 401);
        }

        // Verificar se conta está bloqueada
        if ($user['is_blocked']) {
            logActivity($user['id'], 'login_blocked', 'Tentativa de login em conta bloqueada');
            error('Conta bloqueada: ' . ($user['blocked_reason'] ?? 'Entre em contato com o suporte'), 403);
        }

        // Verificar senha (apenas se usuário tiver senha - login tradicional)
        if (!$user['password']) {
            error('Use o login social para acessar esta conta', 400);
        }

        if (!password_verify($password, $user['password'])) {
            // Registrar tentativa falha
            logActivity($user['id'], 'login_failed', 'Senha incorreta');
            error('Email ou senha incorretos', 401);
        }

        // Atualizar último login e IP
        $stmt = $pdo->prepare("
            UPDATE users
            SET last_login = NOW(), last_ip = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);

        // Verificar múltiplas contas no mesmo IP (anti-fraude)
        $sameIPCount = checkMultipleAccountsSameIP($user['id']);
        if ($sameIPCount >= 3) {
            createFraudAlert(
                $user['id'],
                'multiple_accounts',
                'medium',
                "Detectadas {$sameIPCount} contas com o mesmo IP"
            );
        }

        // Registrar atividade
        logActivity($user['id'], 'login', 'Login via email');

        // Gerar token JWT
        $token = generateJWT([
            'user_id' => $user['id'],
            'email' => $user['email']
        ]);

        // Sanitizar dados do usuário
        $user = sanitizeUserData($user);

        // Verificar se precisa completar KYC
        $requiresKYC = in_array($user['kyc_status'], ['pending', 'rejected']);

        success([
            'token' => $token,
            'user' => $user,
            'requires_kyc' => $requiresKYC
        ], 'Login realizado com sucesso');

    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        error('Erro ao fazer login', 500);
    }
}

// ============================================
// FUNÇÃO: VERIFICAR AUTENTICAÇÃO
// ============================================
function handleCheckAuth() {
    $user = getAuthenticatedUser();

    if (!$user) {
        error('Não autenticado', 401);
    }

    // Sanitizar dados
    $user = sanitizeUserData($user);

    success([
        'authenticated' => true,
        'user' => $user
    ]);
}

// ============================================
// FUNÇÃO: LOGOUT
// ============================================
function handleLogout() {
    $user = requireAuth();

    // Registrar atividade
    logActivity($user['id'], 'logout', 'Logout');

    success(['message' => 'Logout realizado com sucesso']);
}
