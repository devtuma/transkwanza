<?php
/**
 * TRANSKWANZA - Configuração Principal
 * Conexão PDO, JWT, CORS, Funções Auxiliares
 */

// ============================================
// CONFIGURAÇÕES DE ERRO
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0); // Em produção: 0
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// ============================================
// CONFIGURAÇÕES DE TIMEZONE
// ============================================
date_default_timezone_set('America/Sao_Paulo');

// ============================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'u442547792_transkwanza');
define('DB_USER', 'u442547792_admin');
define('DB_PASS', 'Life0852new2580!');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// CONFIGURAÇÕES JWT
// ============================================
define('JWT_SECRET', 'Transkwanza_2024_Secret_Key_9Countries_P2P_Remittance_Platform_Ultra_Secure_Token');
define('JWT_EXPIRATION', 60 * 60 * 24 * 7); // 7 dias

// ============================================
// CONFIGURAÇÕES DE UPLOAD
// ============================================
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);
define('UPLOAD_BASE_PATH', __DIR__ . '/../uploads/');

// ============================================
// CONFIGURAÇÕES DE SEGURANÇA
// ============================================
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('FRAUD_SCORE_LIMIT', 80);

// ============================================
// HEADERS CORS
// ============================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Em produção, especificar o domínio
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 3600');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// CONEXÃO PDO COM MYSQL
// ============================================
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
            exit();
        }
    }

    return $pdo;
}

// ============================================
// FUNÇÕES JWT
// ============================================

/**
 * Gera um token JWT
 */
function generateJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRATION;
    $payload = json_encode($payload);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Valida e decodifica um token JWT
 */
function validateJWT($token) {
    if (empty($token)) {
        return false;
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

    // Verificar assinatura
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignatureCheck = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    if ($base64UrlSignature !== $base64UrlSignatureCheck) {
        return false;
    }

    // Decodificar payload
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);

    // Verificar expiração
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

/**
 * Obtém o usuário autenticado a partir do token JWT
 */
function getAuthenticatedUser() {
    $headers = getallheaders();
    $token = null;

    // Tentar obter token do header Authorization
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }

    // Tentar obter token do header X-Auth-Token (fallback)
    if (!$token && isset($headers['X-Auth-Token'])) {
        $token = $headers['X-Auth-Token'];
    }

    if (!$token) {
        return null;
    }

    $payload = validateJWT($token);
    if (!$payload || !isset($payload['user_id'])) {
        return null;
    }

    // Buscar usuário no banco
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_blocked = 0");
    $stmt->execute([$payload['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

/**
 * Requer autenticação - retorna erro se não autenticado
 */
function requireAuth() {
    $user = getAuthenticatedUser();
    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'Não autorizado'], 401);
        exit();
    }
    return $user;
}

/**
 * Requer permissão de admin
 */
function requireAdmin() {
    $user = requireAuth();
    if (!$user['is_admin']) {
        jsonResponse(['success' => false, 'error' => 'Acesso restrito a administradores'], 403);
        exit();
    }
    return $user;
}

// ============================================
// FUNÇÕES DE RESPOSTA JSON
// ============================================

/**
 * Envia resposta JSON padronizada
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Resposta de sucesso
 */
function success($data = [], $message = null) {
    $response = ['success' => true];
    if ($message) $response['message'] = $message;
    if (!empty($data)) $response['data'] = $data;
    jsonResponse($response);
}

/**
 * Resposta de erro
 */
function error($message, $code = 400) {
    jsonResponse(['success' => false, 'error' => $message], $code);
}

// ============================================
// FUNÇÕES DE VALIDAÇÃO
// ============================================

/**
 * Valida email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida senha
 */
function validatePassword($password) {
    return strlen($password) >= PASSWORD_MIN_LENGTH;
}

/**
 * Sanitiza string
 */
function sanitizeString($string) {
    return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida input obrigatório
 */
function validateRequired($fields, $data) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    return $missing;
}

// ============================================
// FUNÇÕES DE UPLOAD
// ============================================

/**
 * Valida arquivo de upload
 */
function validateUploadedFile($file, $allowedTypes = null) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'error' => 'Parâmetros inválidos'];
    }

    // Verificar erros de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo excede o tamanho máximo permitido',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o tamanho máximo permitido',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado'
        ];
        return ['valid' => false, 'error' => $errors[$file['error']] ?? 'Erro no upload'];
    }

    // Verificar tamanho
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['valid' => false, 'error' => 'Arquivo muito grande. Máximo: 5MB'];
    }

    // Verificar tipo MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedTypes = $allowedTypes ?? UPLOAD_ALLOWED_TYPES;
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'Tipo de arquivo não permitido'];
    }

    return ['valid' => true, 'mime_type' => $mimeType];
}

/**
 * Gera nome único para arquivo
 */
function generateUniqueFilename($userId, $type, $extension) {
    return $userId . '_' . $type . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
}

/**
 * Obtém extensão a partir do MIME type
 */
function getExtensionFromMime($mimeType) {
    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf'
    ];
    return $mimeMap[$mimeType] ?? 'bin';
}

// ============================================
// FUNÇÕES DE LOG E AUDITORIA
// ============================================

/**
 * Registra atividade do usuário
 */
function logActivity($userId, $action, $description = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Log Activity Error: " . $e->getMessage());
    }
}

/**
 * Registra ação administrativa
 */
function logAdminAction($adminId, $actionType, $targetId = null, $details = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_id, details)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$adminId, $actionType, $targetId, $details]);
    } catch (Exception $e) {
        error_log("Log Admin Action Error: " . $e->getMessage());
    }
}

/**
 * Cria notificação para usuário
 */
function createNotification($userId, $type, $title, $message) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([$userId, $type, $title, $message]);
    } catch (Exception $e) {
        error_log("Create Notification Error: " . $e->getMessage());
    }
}

// ============================================
// FUNÇÕES ANTI-FRAUDE
// ============================================

/**
 * Cria alerta de fraude
 */
function createFraudAlert($userId, $checkType, $riskLevel, $details) {
    try {
        $pdo = getDBConnection();

        // Inserir alerta
        $stmt = $pdo->prepare("
            INSERT INTO fraud_checks (user_id, check_type, risk_level, details)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $checkType, $riskLevel, $details]);

        // Atualizar fraud_score
        $scoreIncrease = [
            'low' => 10,
            'medium' => 20,
            'high' => 30,
            'critical' => 50
        ];

        $increase = $scoreIncrease[$riskLevel] ?? 0;

        $stmt = $pdo->prepare("
            UPDATE users
            SET fraud_score = LEAST(fraud_score + ?, 100)
            WHERE id = ?
        ");
        $stmt->execute([$increase, $userId]);

        // Verificar se deve bloquear usuário
        $stmt = $pdo->prepare("SELECT fraud_score FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && $user['fraud_score'] >= FRAUD_SCORE_LIMIT) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET is_blocked = 1, blocked_reason = 'Pontuação de fraude muito alta'
                WHERE id = ?
            ");
            $stmt->execute([$userId]);

            createNotification($userId, 'kyc_rejected', 'Conta Bloqueada', 'Sua conta foi bloqueada devido a atividade suspeita.');
        }

    } catch (Exception $e) {
        error_log("Create Fraud Alert Error: " . $e->getMessage());
    }
}

/**
 * Verifica documento duplicado
 */
function checkDuplicateDocument($documentNumber, $excludeUserId = null) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT id, name, email
        FROM users
        WHERE document_number = ? AND id != ?
        LIMIT 1
    ");
    $stmt->execute([$documentNumber, $excludeUserId ?? 0]);
    return $stmt->fetch();
}

/**
 * Verifica múltiplas contas mesmo IP
 */
function checkMultipleAccountsSameIP($userId) {
    $pdo = getDBConnection();
    $currentIP = $_SERVER['REMOTE_ADDR'] ?? null;

    if (!$currentIP) return 0;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM users
        WHERE last_ip = ? AND id != ?
    ");
    $stmt->execute([$currentIP, $userId]);
    $result = $stmt->fetch();

    return $result['count'] ?? 0;
}

// ============================================
// FUNÇÃO DE LIMPEZA DE DADOS SENSÍVEIS
// ============================================

/**
 * Remove campos sensíveis do objeto usuário
 */
function sanitizeUserData($user) {
    unset($user['password']);
    unset($user['google_id']);
    unset($user['facebook_id']);
    unset($user['instagram_id']);
    unset($user['apple_id']);
    return $user;
}

// ============================================
// FUNÇÃO GET INPUT JSON
// ============================================

/**
 * Obtém dados JSON do corpo da requisição
 */
function getJSONInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

// ============================================
// INICIALIZAÇÃO
// ============================================

// Conectar ao banco na inicialização para garantir que está funcionando
$GLOBALS['pdo'] = getDBConnection();
