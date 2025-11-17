<?php
// ============================================
// TRANSKWANZA - FASE 3: API DE PROPOSTAS
// Criar, listar e aceitar propostas de câmbio
// ============================================

require_once 'config.php';

$action = $_GET['action'] ?? '';

// ============================================
// LISTAR MOEDAS
// ============================================
if ($action === 'currencies') {
    $stmt = $pdo->prepare("SELECT code, name, symbol, country FROM currencies WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $currencies = $stmt->fetchAll();

    sendJSON([
        'success' => true,
        'currencies' => $currencies
    ]);
}

// ============================================
// CALCULAR CONVERSÃO
// ============================================
else if ($action === 'calculate') {
    $data = json_decode(file_get_contents('php://input'), true);

    $from = $data['from'] ?? '';
    $to = $data['to'] ?? '';
    $amount = floatval($data['amount'] ?? 0);

    if (empty($from) || empty($to) || $amount <= 0) {
        sendJSON(['success' => false, 'message' => 'Dados inválidos'], 400);
    }

    // Taxas de câmbio simplificadas (em produção, usar API externa)
    $rates = [
        'BRL' => ['AOA' => 150.25, 'EUR' => 0.18, 'USD' => 0.20, 'CUP' => 4.80, 'RUB' => 18.50, 'ZAR' => 3.75, 'NAD' => 3.75, 'MZN' => 12.80],
        'AOA' => ['BRL' => 0.0067, 'EUR' => 0.0012, 'USD' => 0.0013, 'CUP' => 0.032, 'RUB' => 0.123, 'ZAR' => 0.025, 'NAD' => 0.025, 'MZN' => 0.085],
        'EUR' => ['BRL' => 5.50, 'AOA' => 833.33, 'USD' => 1.10, 'CUP' => 26.50, 'RUB' => 102.50, 'ZAR' => 20.75, 'NAD' => 20.75, 'MZN' => 70.85],
        'USD' => ['BRL' => 5.00, 'AOA' => 757.58, 'EUR' => 0.91, 'CUP' => 24.00, 'RUB' => 93.00, 'ZAR' => 18.85, 'NAD' => 18.85, 'MZN' => 64.38],
        'CUP' => ['BRL' => 0.21, 'AOA' => 31.25, 'EUR' => 0.038, 'USD' => 0.042, 'RUB' => 3.85, 'ZAR' => 0.78, 'NAD' => 0.78, 'MZN' => 2.67],
        'RUB' => ['BRL' => 0.054, 'AOA' => 8.13, 'EUR' => 0.0098, 'USD' => 0.011, 'CUP' => 0.26, 'ZAR' => 0.203, 'NAD' => 0.203, 'MZN' => 0.693],
        'ZAR' => ['BRL' => 0.267, 'AOA' => 40.00, 'EUR' => 0.048, 'USD' => 0.053, 'CUP' => 1.28, 'RUB' => 4.93, 'NAD' => 1.00, 'MZN' => 3.42],
        'NAD' => ['BRL' => 0.267, 'AOA' => 40.00, 'EUR' => 0.048, 'USD' => 0.053, 'CUP' => 1.28, 'RUB' => 4.93, 'ZAR' => 1.00, 'MZN' => 3.42],
        'MZN' => ['BRL' => 0.078, 'AOA' => 11.76, 'EUR' => 0.014, 'USD' => 0.016, 'CUP' => 0.375, 'RUB' => 1.44, 'ZAR' => 0.292, 'NAD' => 0.292]
    ];

    if (!isset($rates[$from][$to])) {
        sendJSON(['success' => false, 'message' => 'Par de moedas não suportado'], 400);
    }

    $rate = $rates[$from][$to];
    $convertedAmount = $amount * $rate;
    $fee = $convertedAmount * 0.03; // 3% de taxa
    $finalAmount = $convertedAmount - $fee;

    sendJSON([
        'success' => true,
        'from' => $from,
        'to' => $to,
        'amount' => $amount,
        'rate' => $rate,
        'converted_amount' => round($convertedAmount, 2),
        'fee' => round($fee, 2),
        'final_amount' => round($finalAmount, 2)
    ]);
}

// ============================================
// CRIAR PROPOSTA (EXIGE KYC APROVADO!)
// ============================================
else if ($action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);

    $userId = $data['user_id'] ?? 0;
    $type = $data['type'] ?? '';
    $currencyFrom = $data['currency_from'] ?? '';
    $currencyTo = $data['currency_to'] ?? '';
    $amountFrom = floatval($data['amount_from'] ?? 0);
    $exchangeRate = floatval($data['exchange_rate'] ?? 0);
    $paymentMethod = trim($data['payment_method'] ?? '');

    // Validações
    if (!$userId || !in_array($type, ['buy', 'sell'])) {
        sendJSON(['success' => false, 'message' => 'Dados inválidos'], 400);
    }

    if (empty($currencyFrom) || empty($currencyTo) || $amountFrom <= 0 || $exchangeRate <= 0) {
        sendJSON(['success' => false, 'message' => 'Todos os campos são obrigatórios'], 400);
    }

    if (empty($paymentMethod)) {
        sendJSON(['success' => false, 'message' => 'Método de pagamento é obrigatório'], 400);
    }

    // ✅ VERIFICAR KYC ANTES DE CRIAR PROPOSTA
    $stmt = $pdo->prepare("SELECT kyc_status FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        sendJSON(['success' => false, 'message' => 'Usuário não encontrado'], 404);
    }

    if ($user['kyc_status'] !== 'approved') {
        sendJSON([
            'success' => false,
            'message' => 'Você precisa completar a verificação KYC antes de criar propostas',
            'kyc_required' => true,
            'kyc_status' => $user['kyc_status']
        ], 403);
    }

    // Calcular amount_to
    $amountTo = $amountFrom * $exchangeRate;

    // Inserir proposta
    $stmt = $pdo->prepare("
        INSERT INTO proposals (user_id, type, currency_from, currency_to, amount_from, exchange_rate, amount_to, payment_method)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if ($stmt->execute([$userId, $type, $currencyFrom, $currencyTo, $amountFrom, $exchangeRate, $amountTo, $paymentMethod])) {
        $proposalId = $pdo->lastInsertId();

        sendJSON([
            'success' => true,
            'message' => 'Proposta criada com sucesso!',
            'proposal_id' => $proposalId
        ]);
    } else {
        sendJSON(['success' => false, 'message' => 'Erro ao criar proposta'], 500);
    }
}

// ============================================
// LISTAR PROPOSTAS
// ============================================
else if ($action === 'list') {
    $currencyFrom = $_GET['currency_from'] ?? '';
    $currencyTo = $_GET['currency_to'] ?? '';
    $type = $_GET['type'] ?? '';

    $sql = "
        SELECT p.*, u.name as user_name, u.country
        FROM proposals p
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'active'
    ";

    $params = [];

    if ($currencyFrom) {
        $sql .= " AND p.currency_from = ?";
        $params[] = $currencyFrom;
    }

    if ($currencyTo) {
        $sql .= " AND p.currency_to = ?";
        $params[] = $currencyTo;
    }

    if ($type) {
        $sql .= " AND p.type = ?";
        $params[] = $type;
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $proposals = $stmt->fetchAll();

    sendJSON([
        'success' => true,
        'proposals' => $proposals
    ]);
}

// ============================================
// OBTER PROPOSTA ESPECÍFICA
// ============================================
else if ($action === 'get') {
    $proposalId = $_GET['id'] ?? 0;

    if (!$proposalId) {
        sendJSON(['success' => false, 'message' => 'ID da proposta não fornecido'], 400);
    }

    $stmt = $pdo->prepare("
        SELECT p.*, u.name as user_name, u.email as user_email, u.country
        FROM proposals p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$proposalId]);
    $proposal = $stmt->fetch();

    if (!$proposal) {
        sendJSON(['success' => false, 'message' => 'Proposta não encontrada'], 404);
    }

    sendJSON([
        'success' => true,
        'proposal' => $proposal
    ]);
}

// ============================================
// AÇÃO INVÁLIDA
// ============================================
else {
    sendJSON(['success' => false, 'message' => 'Ação inválida'], 400);
}
