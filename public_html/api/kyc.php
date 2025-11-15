<?php
/**
 * TRANSKWANZA - API de KYC
 * Upload e validação de documentos (Know Your Customer)
 */

require_once 'config.php';

// Obter método HTTP e ação
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'upload';

// ============================================
// ROTEAMENTO
// ============================================

switch ($action) {
    case 'upload':
        if ($method !== 'POST') error('Método não permitido', 405);
        handleKYCUpload();
        break;

    case 'status':
        if ($method !== 'GET') error('Método não permitido', 405);
        handleKYCStatus();
        break;

    default:
        error('Ação inválida', 400);
}

// ============================================
// FUNÇÃO: UPLOAD DE DOCUMENTOS KYC
// ============================================
function handleKYCUpload() {
    $user = requireAuth();

    // Validar campos obrigatórios
    if (empty($_POST['document_type']) || empty($_POST['document_number'])) {
        error('Tipo e número do documento são obrigatórios', 400);
    }

    if (!isset($_FILES['document_front'])) {
        error('Foto da frente do documento é obrigatória', 400);
    }

    if (!isset($_FILES['document_selfie'])) {
        error('Selfie com documento é obrigatória', 400);
    }

    $documentType = sanitizeString($_POST['document_type']);
    $documentNumber = sanitizeString($_POST['document_number']);

    // Validar tipo de documento
    $validTypes = ['rg', 'cpf', 'cnh', 'passport'];
    if (!in_array(strtolower($documentType), $validTypes)) {
        error('Tipo de documento inválido', 400);
    }

    // Validar número do documento
    if (strlen($documentNumber) < 5) {
        error('Número do documento inválido', 400);
    }

    $pdo = getDBConnection();

    try {
        // ============================================
        // ANTI-FRAUDE: Verificar documento duplicado
        // ============================================
        $duplicateUser = checkDuplicateDocument($documentNumber, $user['id']);

        if ($duplicateUser) {
            createFraudAlert(
                $user['id'],
                'duplicate_document',
                'high',
                "Documento {$documentNumber} já cadastrado pelo usuário {$duplicateUser['name']} (ID: {$duplicateUser['id']})"
            );

            error('Este documento já está cadastrado em outra conta. Nossa equipe irá analisar.', 400);
        }

        // ============================================
        // PROCESSAR UPLOAD DOS ARQUIVOS
        // ============================================
        $uploadedFiles = [];

        // 1. Foto da frente
        $frontFile = $_FILES['document_front'];
        $frontValidation = validateUploadedFile($frontFile);

        if (!$frontValidation['valid']) {
            error('Foto da frente: ' . $frontValidation['error'], 400);
        }

        $frontExtension = getExtensionFromMime($frontValidation['mime_type']);
        $frontFilename = generateUniqueFilename($user['id'], 'front', $frontExtension);
        $frontPath = UPLOAD_BASE_PATH . 'documents/' . $frontFilename;

        if (!move_uploaded_file($frontFile['tmp_name'], $frontPath)) {
            error('Erro ao salvar foto da frente', 500);
        }

        $uploadedFiles['front'] = 'uploads/documents/' . $frontFilename;

        // Registrar upload
        registerUpload(
            $user['id'],
            'document',
            $frontFile['name'],
            $frontFilename,
            $uploadedFiles['front'],
            $frontFile['size'],
            $frontValidation['mime_type']
        );

        // 2. Foto da selfie
        $selfieFile = $_FILES['document_selfie'];
        $selfieValidation = validateUploadedFile($selfieFile);

        if (!$selfieValidation['valid']) {
            // Deletar arquivo já enviado
            @unlink($frontPath);
            error('Selfie: ' . $selfieValidation['error'], 400);
        }

        $selfieExtension = getExtensionFromMime($selfieValidation['mime_type']);
        $selfieFilename = generateUniqueFilename($user['id'], 'selfie', $selfieExtension);
        $selfiePath = UPLOAD_BASE_PATH . 'documents/' . $selfieFilename;

        if (!move_uploaded_file($selfieFile['tmp_name'], $selfiePath)) {
            // Deletar arquivo já enviado
            @unlink($frontPath);
            error('Erro ao salvar selfie', 500);
        }

        $uploadedFiles['selfie'] = 'uploads/documents/' . $selfieFilename;

        // Registrar upload
        registerUpload(
            $user['id'],
            'document',
            $selfieFile['name'],
            $selfieFilename,
            $uploadedFiles['selfie'],
            $selfieFile['size'],
            $selfieValidation['mime_type']
        );

        // 3. Foto do verso (opcional)
        $uploadedFiles['back'] = null;

        if (isset($_FILES['document_back']) && $_FILES['document_back']['error'] !== UPLOAD_ERR_NO_FILE) {
            $backFile = $_FILES['document_back'];
            $backValidation = validateUploadedFile($backFile);

            if ($backValidation['valid']) {
                $backExtension = getExtensionFromMime($backValidation['mime_type']);
                $backFilename = generateUniqueFilename($user['id'], 'back', $backExtension);
                $backPath = UPLOAD_BASE_PATH . 'documents/' . $backFilename;

                if (move_uploaded_file($backFile['tmp_name'], $backPath)) {
                    $uploadedFiles['back'] = 'uploads/documents/' . $backFilename;

                    registerUpload(
                        $user['id'],
                        'document',
                        $backFile['name'],
                        $backFilename,
                        $uploadedFiles['back'],
                        $backFile['size'],
                        $backValidation['mime_type']
                    );
                }
            }
        }

        // ============================================
        // ATUALIZAR DADOS DO USUÁRIO
        // ============================================
        $stmt = $pdo->prepare("
            UPDATE users
            SET document_type = ?,
                document_number = ?,
                document_front = ?,
                document_back = ?,
                document_selfie = ?,
                kyc_status = 'under_review',
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $documentType,
            $documentNumber,
            $uploadedFiles['front'],
            $uploadedFiles['back'],
            $uploadedFiles['selfie'],
            $user['id']
        ]);

        // ============================================
        // CRIAR NOTIFICAÇÃO PARA ADMINS
        // ============================================
        // Buscar todos os admins
        $stmt = $pdo->prepare("SELECT id FROM users WHERE is_admin = 1");
        $stmt->execute();
        $admins = $stmt->fetchAll();

        foreach ($admins as $admin) {
            createNotification(
                $admin['id'],
                'kyc_approved', // type genérico
                'Novo KYC Pendente',
                "Usuário {$user['name']} enviou documentos para análise."
            );
        }

        // ============================================
        // REGISTRAR ATIVIDADE
        // ============================================
        logActivity($user['id'], 'upload_document', "Enviou documentos KYC: {$documentType}");

        success([
            'kyc_status' => 'under_review',
            'message' => 'Documentos enviados com sucesso! Aguarde a análise.'
        ], 'Documentos enviados para análise');

    } catch (PDOException $e) {
        error_log("KYC Upload Error: " . $e->getMessage());

        // Deletar arquivos enviados em caso de erro
        if (isset($uploadedFiles['front'])) @unlink(UPLOAD_BASE_PATH . str_replace('uploads/', '', $uploadedFiles['front']));
        if (isset($uploadedFiles['selfie'])) @unlink(UPLOAD_BASE_PATH . str_replace('uploads/', '', $uploadedFiles['selfie']));
        if (isset($uploadedFiles['back']) && $uploadedFiles['back']) @unlink(UPLOAD_BASE_PATH . str_replace('uploads/', '', $uploadedFiles['back']));

        error('Erro ao processar documentos', 500);
    }
}

// ============================================
// FUNÇÃO: VERIFICAR STATUS DO KYC
// ============================================
function handleKYCStatus() {
    $user = requireAuth();

    $data = [
        'kyc_status' => $user['kyc_status'],
        'document_type' => $user['document_type'],
        'document_number' => $user['document_number'],
        'kyc_reviewed_at' => $user['kyc_reviewed_at'],
        'kyc_rejection_reason' => $user['kyc_rejection_reason']
    ];

    success($data);
}

// ============================================
// FUNÇÃO AUXILIAR: REGISTRAR UPLOAD
// ============================================
function registerUpload($userId, $type, $originalName, $storedName, $filePath, $fileSize, $mimeType) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO uploads (user_id, type, original_name, stored_name, file_path, file_size, mime_type)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $type,
            $originalName,
            $storedName,
            $filePath,
            $fileSize,
            $mimeType
        ]);
    } catch (PDOException $e) {
        error_log("Register Upload Error: " . $e->getMessage());
    }
}
