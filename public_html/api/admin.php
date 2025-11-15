<?php
/**
 * TRANSKWANZA - API Administrativa
 * Painel de controle para administradores
 */

require_once 'config.php';

// Verificar se usuário é admin
$admin = requireAdmin();

// Obter método HTTP e ação
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================
// ROTEAMENTO
// ============================================

switch ($action) {
    // Estatísticas
    case 'stats':
        if ($method !== 'GET') error('Método não permitido', 405);
        handleStats();
        break;

    // Gerenciar KYC
    case 'pending_kyc':
        if ($method !== 'GET') error('Método não permitido', 405);
        handlePendingKYC();
        break;

    case 'approve_kyc':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleApproveKYC();
        break;

    case 'reject_kyc':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleRejectKYC();
        break;

    // Gerenciar Transações
    case 'pending_transactions':
        if ($method !== 'GET') error('Método não permitido', 405);
        handlePendingTransactions();
        break;

    case 'approve_transaction':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleApproveTransaction();
        break;

    case 'reject_transaction':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleRejectTransaction();
        break;

    // Gerenciar Moedas
    case 'currencies':
        if ($method !== 'GET') error('Método não permitido', 405);
        handleGetCurrencies();
        break;

    case 'toggle_currency':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleToggleCurrency();
        break;

    // Usuários
    case 'block_user':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleBlockUser();
        break;

    case 'unblock_user':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleUnblockUser();
        break;

    // Fraude
    case 'fraud_alerts':
        if ($method !== 'GET') error('Método não permitido', 405);
        handleFraudAlerts();
        break;

    case 'resolve_fraud':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleResolveFraud();
        break;

    // Ações especiais
    case 'connect_offer':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleConnectOffer();
        break;

    case 'make_payment':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleMakePayment();
        break;

    default:
        error('Ação inválida', 400);
}

// ============================================
// ESTATÍSTICAS
// ============================================
function handleStats() {
    global $admin;
    $pdo = getDBConnection();

    try {
        // Estatísticas de usuários
        $stmt = $pdo->query("
            SELECT
                COUNT(*) as total_users,
                SUM(verified = 1) as verified_users,
                SUM(kyc_status = 'approved') as kyc_approved,
                SUM(kyc_status = 'under_review') as kyc_pending,
                SUM(kyc_status = 'rejected') as kyc_rejected,
                SUM(is_blocked = 1) as blocked_users
            FROM users
        ");
        $userStats = $stmt->fetch();

        // Estatísticas de transações
        $stmt = $pdo->query("
            SELECT
                COUNT(*) as total_transactions,
                SUM(admin_status = 'pending') as pending_approval,
                SUM(admin_status = 'approved') as approved,
                SUM(admin_status = 'rejected') as rejected,
                SUM(status = 'completed') as completed,
                SUM(fee_amount) as total_fees,
                SUM(sender_amount) as total_volume
            FROM transactions
        ");
        $transactionStats = $stmt->fetch();

        // Estatísticas de propostas
        $stmt = $pdo->query("
            SELECT
                COUNT(*) as total_proposals,
                SUM(status = 'active') as active,
                SUM(status = 'completed') as completed
            FROM proposals
        ");
        $proposalStats = $stmt->fetch();

        // Alertas de fraude não resolvidos
        $stmt = $pdo->query("
            SELECT COUNT(*) as unresolved_alerts
            FROM fraud_checks
            WHERE is_resolved = 0
        ");
        $fraudStats = $stmt->fetch();

        success([
            'users' => $userStats,
            'transactions' => $transactionStats,
            'proposals' => $proposalStats,
            'fraud' => $fraudStats
        ]);

    } catch (PDOException $e) {
        error_log("Stats Error: " . $e->getMessage());
        error('Erro ao buscar estatísticas', 500);
    }
}

// ============================================
// GERENCIAR KYC
// ============================================

function handlePendingKYC() {
    global $admin;
    $pdo = getDBConnection();

    try {
        $stmt = $pdo->query("
            SELECT
                id, name, email, country, phone,
                document_type, document_number,
                document_front, document_back, document_selfie,
                kyc_status, created_at
            FROM users
            WHERE kyc_status = 'under_review'
            ORDER BY created_at ASC
        ");

        $users = $stmt->fetchAll();

        success(['users' => $users]);

    } catch (PDOException $e) {
        error_log("Pending KYC Error: " . $e->getMessage());
        error('Erro ao buscar KYC pendentes', 500);
    }
}

function handleApproveKYC() {
    global $admin;
    $data = getJSONInput();

    if (empty($data['user_id'])) {
        error('ID do usuário não fornecido', 400);
    }

    $userId = (int)$data['user_id'];
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Atualizar status do KYC
        $stmt = $pdo->prepare("
            UPDATE users
            SET kyc_status = 'approved',
                verified = 1,
                kyc_reviewed_at = NOW(),
                kyc_reviewed_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$admin['id'], $userId]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            error('Usuário não encontrado', 404);
        }

        // Criar notificação
        createNotification(
            $userId,
            'kyc_approved',
            'KYC Aprovado!',
            'Seus documentos foram aprovados. Agora você pode fazer transações.'
        );

        // Registrar ação admin
        logAdminAction($admin['id'], 'approve_kyc', $userId, 'Aprovou KYC');

        $pdo->commit();

        success(['message' => 'KYC aprovado com sucesso']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Approve KYC Error: " . $e->getMessage());
        error('Erro ao aprovar KYC', 500);
    }
}

function handleRejectKYC() {
    global $admin;
    $data = getJSONInput();

    if (empty($data['user_id']) || empty($data['reason'])) {
        error('ID do usuário e motivo são obrigatórios', 400);
    }

    $userId = (int)$data['user_id'];
    $reason = sanitizeString($data['reason']);

    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Atualizar status do KYC
        $stmt = $pdo->prepare("
            UPDATE users
            SET kyc_status = 'rejected',
                kyc_reviewed_at = NOW(),
                kyc_reviewed_by = ?,
                kyc_rejection_reason = ?
            WHERE id = ?
        ");
        $stmt->execute([$admin['id'], $reason, $userId]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            error('Usuário não encontrado', 404);
        }

        // Criar notificação
        createNotification(
            $userId,
            'kyc_rejected',
            'KYC Rejeitado',
            "Seus documentos foram rejeitados. Motivo: {$reason}. Por favor, envie novamente."
        );

        // Registrar ação admin
        logAdminAction($admin['id'], 'reject_kyc', $userId, "Rejeitou KYC: {$reason}");

        $pdo->commit();

        success(['message' => 'KYC rejeitado']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Reject KYC Error: " . $e->getMessage());
        error('Erro ao rejeitar KYC', 500);
    }
}

// ============================================
// GERENCIAR TRANSAÇÕES
// ============================================

function handlePendingTransactions() {
    global $admin;
    $pdo = getDBConnection();

    try {
        $stmt = $pdo->query("
            SELECT
                t.*,
                sender.name as sender_name,
                sender.email as sender_email,
                receiver.name as receiver_name,
                receiver.email as receiver_email
            FROM transactions t
            JOIN users sender ON t.sender_id = sender.id
            JOIN users receiver ON t.receiver_id = receiver.id
            WHERE t.admin_status = 'pending'
            ORDER BY t.created_at ASC
        ");

        $transactions = $stmt->fetchAll();

        success(['transactions' => $transactions]);

    } catch (PDOException $e) {
        error_log("Pending Transactions Error: " . $e->getMessage());
        error('Erro ao buscar transações pendentes', 500);
    }
}

function handleApproveTransaction() {
    global $admin;
    $data = getJSONInput();

    if (empty($data['transaction_id'])) {
        error('ID da transação não fornecido', 400);
    }

    $transactionId = (int)$data['transaction_id'];
    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Buscar transação
        $stmt = $pdo->prepare("
            SELECT * FROM transactions WHERE id = ?
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            $pdo->rollBack();
            error('Transação não encontrada', 404);
        }

        // Atualizar status da transação
        $stmt = $pdo->prepare("
            UPDATE transactions
            SET admin_status = 'approved',
                admin_reviewed_by = ?,
                admin_reviewed_at = NOW(),
                status = 'completed',
                completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$admin['id'], $transactionId]);

        // Incrementar total_transactions dos usuários
        $stmt = $pdo->prepare("
            UPDATE users
            SET total_transactions = total_transactions + 1
            WHERE id IN (?, ?)
        ");
        $stmt->execute([$transaction['sender_id'], $transaction['receiver_id']]);

        // Criar notificações
        createNotification(
            $transaction['sender_id'],
            'transaction_completed',
            'Transação Aprovada',
            'Sua transação foi aprovada e concluída.'
        );

        createNotification(
            $transaction['receiver_id'],
            'transaction_completed',
            'Transação Concluída',
            'A transação foi concluída com sucesso.'
        );

        // Registrar ação admin
        logAdminAction($admin['id'], 'approve_transaction', $transactionId, 'Aprovou transação');

        $pdo->commit();

        success(['message' => 'Transação aprovada com sucesso']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Approve Transaction Error: " . $e->getMessage());
        error('Erro ao aprovar transação', 500);
    }
}

function handleRejectTransaction() {
    global $admin;
    $data = getJSONInput();

    if (empty($data['transaction_id']) || empty($data['reason'])) {
        error('ID da transação e motivo são obrigatórios', 400);
    }

    $transactionId = (int)$data['transaction_id'];
    $reason = sanitizeString($data['reason']);

    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Buscar transação
        $stmt = $pdo->prepare("
            SELECT * FROM transactions WHERE id = ?
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            $pdo->rollBack();
            error('Transação não encontrada', 404);
        }

        // Atualizar status da transação
        $stmt = $pdo->prepare("
            UPDATE transactions
            SET admin_status = 'rejected',
                admin_reviewed_by = ?,
                admin_reviewed_at = NOW(),
                admin_rejection_reason = ?,
                status = 'cancelled'
            WHERE id = ?
        ");
        $stmt->execute([$admin['id'], $reason, $transactionId]);

        // Criar notificações
        createNotification(
            $transaction['sender_id'],
            'transaction_completed',
            'Transação Rejeitada',
            "Sua transação foi rejeitada. Motivo: {$reason}"
        );

        createNotification(
            $transaction['receiver_id'],
            'transaction_completed',
            'Transação Rejeitada',
            "A transação foi rejeitada. Motivo: {$reason}"
        );

        // Registrar ação admin
        logAdminAction($admin['id'], 'reject_transaction', $transactionId, "Rejeitou transação: {$reason}");

        $pdo->commit();

        success(['message' => 'Transação rejeitada']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Reject Transaction Error: " . $e->getMessage());
        error('Erro ao rejeitar transação', 500);
    }
}

// ============================================
// GERENCIAR MOEDAS
// ============================================

function handleGetCurrencies() {
    global $admin;
    $pdo = getDBConnection();

    try {
        $stmt = $pdo->query("
            SELECT * FROM currencies
            ORDER BY is_enabled DESC, code ASC
        ");

        $currencies = $stmt->fetchAll();

        success(['currencies' => $currencies]);

    } catch (PDOException $e) {
        error_log("Get Currencies Error: " . $e->getMessage());
        error('Erro ao buscar moedas', 500);
    }
}

function handleToggleCurrency() {
    global $admin;
    $data = getJSONInput();

    if (empty($data['code'])) {
        error('Código da moeda não fornecido', 400);
    }

    $code = strtoupper(sanitizeString($data['code']));
    $enable = isset($data['enable']) ? (bool)$data['enable'] : null;

    if ($enable === null) {
        error('Parâmetro "enable" não fornecido', 400);
    }

    $pdo = getDBConnection();

    try {
        $stmt = $pdo->prepare("
            UPDATE currencies
            SET is_enabled = ?,
                enabled_by = ?,
                enabled_at = NOW()
            WHERE code = ?
        ");
        $stmt->execute([$enable ? 1 : 0, $admin['id'], $code]);

        if ($stmt->rowCount() === 0) {
            error('Moeda não encontrada', 404);
        }

        $action = $enable ? 'habilitou' : 'desabilitou';
        logAdminAction($admin['id'], 'toggle_currency', null, "{$action} moeda {$code}");

        success(['message' => "Moeda {$code} " . ($enable ? 'habilitada' : 'desabilitada')]);

    } catch (PDOException $e) {
        error_log("Toggle Currency Error: " . $e->getMessage());
        error('Erro ao atualizar moeda', 500);
    }
}

// ============================================
// GERENCIAR USUÁRIOS
// ============================================

function handleBlockUser() {
    global $admin;
    $data = getJSONInput();

    if (empty($data['user_id']) || empty($data['reason'])) {
        error('ID do usuário e motivo são obrigatórios', 400);
    }

    $userId = (int)$data['user_id'];
    $reason = sanitizeString($data['reason']);

    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Bloquear usuário
        $stmt = $pdo->prepare("
            UPDATE users
            SET is_blocked = 1,
                blocked_reason = ?
            WHERE id = ?
        ");
        $stmt->execute([$reason, $userId]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            error('Usuário não encontrado', 404);
        }

        // Cancelar propostas ativas
        $stmt = $pdo->prepare("
            UPDATE proposals
            SET status = 'cancelled'
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);

        // Criar notificação
        createNotification(
            $userId,
            'kyc_rejected',
            'Conta Bloqueada',
            "Sua conta foi bloqueada. Motivo: {$reason}. Entre em contato com o suporte."
        );

        // Registrar ação admin
        logAdminAction($admin['id'], 'block_user', $userId, "Bloqueou usuário: {$reason}");

        $pdo->commit();

        success(['message' => 'Usuário bloqueado com sucesso']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Block User Error: " . $e->getMessage());
        error('Erro ao bloquear usuário', 500);
    }
}

function handleUnblockUser() {
    global $admin;
    $data = getJSONInput();

    if (empty($data['user_id'])) {
        error('ID do usuário não fornecido', 400);
    }

    $userId = (int)$data['user_id'];
    $pdo = getDBConnection();

    try {
        $stmt = $pdo->prepare("
            UPDATE users
            SET is_blocked = 0,
                blocked_reason = NULL
            WHERE id = ?
        ");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() === 0) {
            error('Usuário não encontrado', 404);
        }

        // Criar notificação
        createNotification(
            $userId,
            'kyc_approved',
            'Conta Desbloqueada',
            'Sua conta foi desbloqueada. Você já pode acessar o sistema.'
        );

        // Registrar ação admin
        logAdminAction($admin['id'], 'unblock_user', $userId, 'Desbloqueou usuário');

        success(['message' => 'Usuário desbloqueado com sucesso']);

    } catch (PDOException $e) {
        error_log("Unblock User Error: " . $e->getMessage());
        error('Erro ao desbloquear usuário', 500);
    }
}

// ============================================
// GERENCIAR FRAUDE
// ============================================

function handleFraudAlerts() {
    global $admin;
    $pdo = getDBConnection();

    try {
        $stmt = $pdo->query("
            SELECT
                f.*,
                u.name as user_name,
                u.email as user_email,
                u.fraud_score
            FROM fraud_checks f
            JOIN users u ON f.user_id = u.id
            WHERE f.is_resolved = 0
            ORDER BY f.risk_level DESC, f.created_at DESC
        ");

        $alerts = $stmt->fetchAll();

        success(['alerts' => $alerts]);

    } catch (PDOException $e) {
        error_log("Fraud Alerts Error: " . $e->getMessage());
        error('Erro ao buscar alertas de fraude', 500);
    }
}

function handleResolveFraud() {
    global $admin;
    $data = getJSONInput();

    if (empty($data['fraud_id'])) {
        error('ID do alerta não fornecido', 400);
    }

    $fraudId = (int)$data['fraud_id'];
    $pdo = getDBConnection();

    try {
        $stmt = $pdo->prepare("
            UPDATE fraud_checks
            SET is_resolved = 1,
                resolved_by = ?,
                resolved_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$admin['id'], $fraudId]);

        if ($stmt->rowCount() === 0) {
            error('Alerta não encontrado', 404);
        }

        // Registrar ação admin
        logAdminAction($admin['id'], 'resolve_fraud', $fraudId, 'Resolveu alerta de fraude');

        success(['message' => 'Alerta resolvido']);

    } catch (PDOException $e) {
        error_log("Resolve Fraud Error: " . $e->getMessage());
        error('Erro ao resolver alerta', 500);
    }
}

// ============================================
// AÇÕES ESPECIAIS
// ============================================

function handleConnectOffer() {
    global $admin;
    $data = getJSONInput();

    if (empty($data['proposal_id_1']) || empty($data['proposal_id_2'])) {
        error('IDs das propostas são obrigatórios', 400);
    }

    $proposalId1 = (int)$data['proposal_id_1'];
    $proposalId2 = (int)$data['proposal_id_2'];

    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Buscar propostas
        $stmt = $pdo->prepare("SELECT * FROM proposals WHERE id IN (?, ?)");
        $stmt->execute([$proposalId1, $proposalId2]);
        $proposals = $stmt->fetchAll(PDO::FETCH_UNIQUE);

        if (count($proposals) !== 2) {
            $pdo->rollBack();
            error('Uma ou mais propostas não encontradas', 404);
        }

        $proposal1 = $proposals[$proposalId1];
        $proposal2 = $proposals[$proposalId2];

        // Criar transação
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                proposal_id, sender_id, receiver_id,
                sender_currency, receiver_currency,
                sender_amount, receiver_amount,
                exchange_rate, fee_percentage, fee_amount,
                admin_status, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'approved', 'pending')
        ");

        $stmt->execute([
            $proposalId1,
            $proposal1['user_id'],
            $proposal2['user_id'],
            $proposal1['currency_from'],
            $proposal1['currency_to'],
            $proposal1['amount_from'],
            $proposal1['amount_to'],
            $proposal1['exchange_rate']
        ]);

        // Marcar propostas como completed
        $stmt = $pdo->prepare("UPDATE proposals SET status = 'completed' WHERE id IN (?, ?)");
        $stmt->execute([$proposalId1, $proposalId2]);

        // Registrar ação admin
        logAdminAction($admin['id'], 'connect_offer', null, "Conectou propostas {$proposalId1} e {$proposalId2}");

        $pdo->commit();

        success(['message' => 'Propostas conectadas com sucesso']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Connect Offer Error: " . $e->getMessage());
        error('Erro ao conectar propostas', 500);
    }
}

function handleMakePayment() {
    global $admin;
    $data = getJSONInput();

    $required = ['sender_id', 'receiver_id', 'amount', 'currency'];
    $missing = validateRequired($required, $data);

    if (!empty($missing)) {
        error('Campos obrigatórios faltando: ' . implode(', ', $missing), 400);
    }

    $senderId = (int)$data['sender_id'];
    $receiverId = (int)$data['receiver_id'];
    $amount = (float)$data['amount'];
    $currency = strtoupper(sanitizeString($data['currency']));

    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        // Criar transação direta
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                sender_id, receiver_id,
                sender_currency, receiver_currency,
                sender_amount, receiver_amount,
                exchange_rate, fee_percentage, fee_amount,
                admin_status, status
            ) VALUES (?, ?, ?, ?, ?, ?, 1, 0, 0, 'approved', 'completed')
        ");

        $stmt->execute([
            $senderId,
            $receiverId,
            $currency,
            $currency,
            $amount,
            $amount
        ]);

        $transactionId = $pdo->lastInsertId();

        // Criar notificações
        createNotification($senderId, 'transaction_completed', 'Pagamento Realizado', "Pagamento de {$amount} {$currency} enviado.");
        createNotification($receiverId, 'transaction_received', 'Pagamento Recebido', "Você recebeu {$amount} {$currency}.");

        // Registrar ação admin
        logAdminAction($admin['id'], 'make_payment', $transactionId, "Pagamento direto: {$amount} {$currency}");

        $pdo->commit();

        success(['message' => 'Pagamento realizado com sucesso', 'transaction_id' => $transactionId]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Make Payment Error: " . $e->getMessage());
        error('Erro ao fazer pagamento', 500);
    }
}
