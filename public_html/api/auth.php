<?php
// ============================================
// TRANSKWANZA - FASE 1: AUTENTICAÇÃO
// Cadastro e Login básicos
// ============================================

require_once 'config.php';

$action = $_GET['action'] ?? '';

// ============================================
// CADASTRO
// ============================================
if ($action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $country = trim($data['country'] ?? '');

    // Validações básicas
    if (empty($name) || empty($email) || empty($password) || empty($country)) {
        sendJSON(['success' => false, 'message' => 'Todos os campos são obrigatórios'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJSON(['success' => false, 'message' => 'Email inválido'], 400);
    }

    if (strlen($password) < 6) {
        sendJSON(['success' => false, 'message' => 'Senha deve ter no mínimo 6 caracteres'], 400);
    }

    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        sendJSON(['success' => false, 'message' => 'Este email já está cadastrado'], 400);
    }

    // Hash da senha
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Inserir usuário
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, country)
        VALUES (?, ?, ?, ?)
    ");

    if ($stmt->execute([$name, $email, $hashedPassword, $country])) {
        $userId = $pdo->lastInsertId();

        sendJSON([
            'success' => true,
            'message' => 'Cadastro realizado com sucesso!',
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'country' => $country
            ]
        ]);
    } else {
        sendJSON(['success' => false, 'message' => 'Erro ao cadastrar usuário'], 500);
    }
}

// ============================================
// LOGIN
// ============================================
else if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    // Validações
    if (empty($email) || empty($password)) {
        sendJSON(['success' => false, 'message' => 'Email e senha são obrigatórios'], 400);
    }

    // Buscar usuário
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        sendJSON(['success' => false, 'message' => 'Email ou senha incorretos'], 401);
    }

    // Verificar senha
    if (!password_verify($password, $user['password'])) {
        sendJSON(['success' => false, 'message' => 'Email ou senha incorretos'], 401);
    }

    // Login bem-sucedido
    sendJSON([
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'country' => $user['country']
        ]
    ]);
}

// ============================================
// VERIFICAR SESSÃO (futuro)
// ============================================
else if ($action === 'check') {
    // Por enquanto, retornar false
    // Depois implementaremos JWT
    sendJSON(['success' => false, 'message' => 'Não autenticado'], 401);
}

// ============================================
// AÇÃO INVÁLIDA
// ============================================
else {
    sendJSON(['success' => false, 'message' => 'Ação inválida'], 400);
}
