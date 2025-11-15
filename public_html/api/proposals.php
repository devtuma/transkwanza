<?php
/**
 * TRANSKWANZA - API de Propostas
 * CRUD de propostas de câmbio P2P
 */

require_once 'config.php';

// Obter método HTTP e ação
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================
// ROTEAMENTO
// ============================================

switch ($action) {
    case 'search':
        if ($method !== 'GET') error('Método não permitido', 405);
        handleSearch();
        break;

    case 'create':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleCreate();
        break;

    case 'my_proposals':
        if ($method !== 'GET') error('Método não permitido', 405);
        handleMyProposals();
        break;

    case 'update':
        if ($method !== 'PUT' && $method !== 'POST') error('Método não permitido', 405);
        handleUpdate();
        break;

    case 'delete':
        if ($method !== 'DELETE' && $method !== 'POST') error('Método não permitido', 405);
        handleDelete();
        break;

    case 'accept':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleAccept();
        break;

    default:
        error('Ação inválida', 400);
}

// ============================================
// FUNÇÃO: BUSCAR PROPOSTAS
// ============================================
function handleSearch() {
    $pdo = getDBConnection();

    // Parâmetros de busca (opcionais)
    $currencyFrom = isset($_GET['currency_from']) ? strtoupper($_GET['currency_from']) : null;
    $currencyTo = isset($_GET['currency_to']) ? strtoupper($_GET['currency_to']) : null;
    $minAmount = isset($_GET['min_amount']) ? (float)$_GET['min_amount'] : null;
    $maxAmount = isset($_GET['max_amount']) ? (float)$_GET['max_amount'] : null;

    try {
        $sql = "
            SELECT
                p.*,
                u.name as user_name,
                u.reputation,
                u.total_transactions
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

        if ($minAmount) {
            $sql .= " AND p.amount_from >= ?";
            $params[] = $minAmount;
        }

        if ($maxAmount) {
            $sql .= " AND p.amount_from <= ?";
            $params[] = $maxAmount;
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $proposals = $stmt->fetchAll();

        success(['proposals' => $proposals]);

    } catch (PDOException $e) {
        error_log("Search Proposals Error: " . $e->getMessage());
        error('Erro ao buscar propostas', 500);
    }
}

// ============================================
// FUNÇÃO: CRIAR PROPOSTA
// ============================================
function handleCreate() {
    $user = requireAuth();

    // Verificar se KYC foi aprovado
    if ($user['kyc_status'] !== 'approved') {
        error('Você precisa completar o KYC para criar propostas', 403);
    }

    $data = getJSONInput();

    // Validar campos obrigatórios
    $required = ['type', 'currency_from', 'currency_to', 'amount_from', 'exchange_rate'];
    $missing = validateRequired($required, $data);

    if (!empty($missing)) {
        error('Campos obrigatórios faltando: ' . implode(', ', $missing), 400);
    }

    $type = sanitizeString($data['type']);
    $currencyFrom = strtoupper(sanitizeString($data['currency_from']));
    $currencyTo = strtoupper(sanitizeString($data['currency_to']));
    $amountFrom = (float)$data['amount_from'];
    $exchangeRate = (float)$data['exchange_rate'];
    $paymentMethod = isset($data['payment_method']) ? sanitizeString($data['payment_method']) : null;
    $minAmount = isset($data['min_amount']) ? (float)$data['min_amount'] : 0;
    $maxAmount = isset($data['max_amount']) ? (float)$data['max_amount'] : 0;

    // Validações
    if (!in_array($type, ['buy', 'sell'])) {
        error('Tipo inválido. Use "buy" ou "sell"', 400);
    }

    if ($currencyFrom === $currencyTo) {
        error('Moedas de origem e destino não podem ser iguais', 400);
    }

    if ($amountFrom <= 0 || $exchangeRate <= 0) {
        error('Valores devem ser maiores que zero', 400);
    }

    $pdo = getDBConnection();

    try {
        // Verificar se moedas estão habilitadas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM currencies
            WHERE code IN (?, ?) AND is_enabled = 1
        ");
        $stmt->execute([$currencyFrom, $currencyTo]);
        $result = $stmt->fetch();

        if ($result['count'] < 2) {
            error('Uma ou ambas as moedas não estão disponíveis', 400);
        }

        // Calcular amount_to
        $amountTo = $amountFrom * $exchangeRate;

        // Inserir proposta
        $stmt = $pdo->prepare("
            INSERT INTO proposals (
                user_id, type, currency_from, currency_to,
                amount_from, amount_to, exchange_rate,
                min_amount, max_amount, payment_method,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");

        $stmt->execute([
            $user['id'],
            $type,
            $currencyFrom,
            $currencyTo,
            $amountFrom,
            $amountTo,
            $exchangeRate,
            $minAmount,
            $maxAmount,
            $paymentMethod
        ]);

        $proposalId = $pdo->lastInsertId();

        // Registrar atividade
        logActivity($user['id'], 'create_proposal', "Criou proposta {$type} {$amountFrom} {$currencyFrom} → {$currencyTo}");

        success([
            'proposal_id' => $proposalId,
            'message' => 'Proposta criada com sucesso'
        ], 'Proposta criada');

    } catch (PDOException $e) {
        error_log("Create Proposal Error: " . $e->getMessage());
        error('Erro ao criar proposta', 500);
    }
}

// ============================================
// FUNÇÃO: MINHAS PROPOSTAS
// ============================================
function handleMyProposals() {
    $user = requireAuth();
    $pdo = getDBConnection();

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM proposals
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user['id']]);

        $proposals = $stmt->fetchAll();

        success(['proposals' => $proposals]);

    } catch (PDOException $e) {
        error_log("My Proposals Error: " . $e->getMessage());
        error('Erro ao buscar suas propostas', 500);
    }
}

// ============================================
// FUNÇÃO: ATUALIZAR PROPOSTA
// ============================================
function handleUpdate() {
    $user = requireAuth();
    $data = getJSONInput();

    if (empty($data['proposal_id'])) {
        error('ID da proposta não fornecido', 400);
    }

    $proposalId = (int)$data['proposal_id'];
    $pdo = getDBConnection();

    try {
        // Verificar se proposta pertence ao usuário
        $stmt = $pdo->prepare("SELECT * FROM proposals WHERE id = ? AND user_id = ?");
        $stmt->execute([$proposalId, $user['id']]);
        $proposal = $stmt->fetch();

        if (!$proposal) {
            error('Proposta não encontrada ou você não tem permissão', 404);
        }

        // Construir SQL de update dinamicamente
        $updates = [];
        $params = [];

        $allowedFields = ['status', 'amount_from', 'amount_to', 'exchange_rate', 'payment_method', 'min_amount', 'max_amount'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            error('Nenhum campo para atualizar', 400);
        }

        $params[] = $proposalId;
        $sql = "UPDATE proposals SET " . implode(', ', $updates) . " WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        success(['message' => 'Proposta atualizada']);

    } catch (PDOException $e) {
        error_log("Update Proposal Error: " . $e->getMessage());
        error('Erro ao atualizar proposta', 500);
    }
}

// ============================================
// FUNÇÃO: DELETAR PROPOSTA
// ============================================
function handleDelete() {
    $user = requireAuth();
    $data = getJSONInput();

    if (empty($data['proposal_id'])) {
        error('ID da proposta não fornecido', 400);
    }

    $proposalId = (int)$data['proposal_id'];
    $pdo = getDBConnection();

    try {
        // Verificar se proposta pertence ao usuário
        $stmt = $pdo->prepare("DELETE FROM proposals WHERE id = ? AND user_id = ?");
        $stmt->execute([$proposalId, $user['id']]);

        if ($stmt->rowCount() === 0) {
            error('Proposta não encontrada ou você não tem permissão', 404);
        }

        success(['message' => 'Proposta deletada']);

    } catch (PDOException $e) {
        error_log("Delete Proposal Error: " . $e->getMessage());
        error('Erro ao deletar proposta', 500);
    }
}

// ============================================
// FUNÇÃO: ACEITAR PROPOSTA
// ============================================
function handleAccept() {
    $user = requireAuth();

    // Verificar se KYC foi aprovado
    if ($user['kyc_status'] !== 'approved') {
        error('Você precisa completar o KYC para aceitar propostas', 403);
    }

    $data = getJSONInput();

    if (empty($data['proposal_id'])) {
        error('ID da proposta não fornecido', 400);
    }

    $proposalId = (int)$data['proposal_id'];
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Buscar proposta
        $stmt = $pdo->prepare("SELECT * FROM proposals WHERE id = ? AND status = 'active'");
        $stmt->execute([$proposalId]);
        $proposal = $stmt->fetch();

        if (!$proposal) {
            $pdo->rollBack();
            error('Proposta não encontrada ou não está mais ativa', 404);
        }

        // Não pode aceitar própria proposta
        if ($proposal['user_id'] == $user['id']) {
            $pdo->rollBack();
            error('Você não pode aceitar sua própria proposta', 400);
        }

        // Calcular taxa (3%)
        $feePercentage = 3.00;
        $feeAmount = ($proposal['amount_from'] * $feePercentage) / 100;

        // Criar transação
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                proposal_id, sender_id, receiver_id,
                sender_currency, receiver_currency,
                sender_amount, receiver_amount,
                exchange_rate, fee_percentage, fee_amount,
                admin_status, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
        ");

        $stmt->execute([
            $proposalId,
            $proposal['user_id'],
            $user['id'],
            $proposal['currency_from'],
            $proposal['currency_to'],
            $proposal['amount_from'],
            $proposal['amount_to'],
            $proposal['exchange_rate'],
            $feePercentage,
            $feeAmount
        ]);

        $transactionId = $pdo->lastInsertId();

        // Atualizar proposta
        $stmt = $pdo->prepare("UPDATE proposals SET status = 'completed' WHERE id = ?");
        $stmt->execute([$proposalId]);

        // Criar notificações
        createNotification(
            $proposal['user_id'],
            'proposal_matched',
            'Proposta Aceita',
            "Sua proposta foi aceita por {$user['name']}."
        );

        createNotification(
            $user['id'],
            'transaction_received',
            'Proposta Aceita',
            'Você aceitou uma proposta. Aguarde instruções de pagamento.'
        );

        // Registrar atividade
        logActivity($user['id'], 'accept_proposal', "Aceitou proposta #{$proposalId}");

        $pdo->commit();

        success([
            'transaction_id' => $transactionId,
            'message' => 'Proposta aceita. Transação criada.'
        ], 'Proposta aceita');

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Accept Proposal Error: " . $e->getMessage());
        error('Erro ao aceitar proposta', 500);
    }
}
