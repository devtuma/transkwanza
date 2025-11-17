<?php
// ============================================
// TRANSKWANZA - FASE 2: API KYC
// Upload e verificação de documentos
// ============================================

require_once 'config.php';

$action = $_GET['action'] ?? '';

// ============================================
// VERIFICAR STATUS DO KYC
// ============================================
if ($action === 'status') {
    // Pegar user_id do POST (por enquanto, sem JWT)
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? 0;

    if (!$userId) {
        sendJSON(['success' => false, 'message' => 'ID do usuário não fornecido'], 400);
    }

    $stmt = $pdo->prepare("SELECT kyc_status, document_type, document_number, kyc_submitted_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        sendJSON(['success' => false, 'message' => 'Usuário não encontrado'], 404);
    }

    sendJSON([
        'success' => true,
        'kyc_status' => $user['kyc_status'],
        'document_type' => $user['document_type'],
        'document_number' => $user['document_number'],
        'submitted_at' => $user['kyc_submitted_at']
    ]);
}

// ============================================
// UPLOAD DE DOCUMENTOS
// ============================================
else if ($action === 'upload') {
    // Validar que é um POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJSON(['success' => false, 'message' => 'Método não permitido'], 405);
    }

    // Pegar dados do formulário
    $userId = $_POST['user_id'] ?? 0;
    $documentType = $_POST['document_type'] ?? '';
    $documentNumber = $_POST['document_number'] ?? '';

    // Validações básicas
    if (!$userId || !$documentType || !$documentNumber) {
        sendJSON(['success' => false, 'message' => 'Dados incompletos'], 400);
    }

    // Validar arquivos
    if (!isset($_FILES['document_front']) || !isset($_FILES['document_selfie'])) {
        sendJSON(['success' => false, 'message' => 'É necessário enviar foto da frente do documento e selfie'], 400);
    }

    // Função para criar estrutura de pastas do usuário
    function createUserDirectories($userId) {
        $baseDir = __DIR__ . '/../uploads/users/' . $userId;

        $directories = [
            $baseDir,
            $baseDir . '/profile',
            $baseDir . '/kyc',
            $baseDir . '/transactions',
            $baseDir . '/other'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    // Função para validar e fazer upload de arquivo
    function uploadFile($file, $userId, $type, $subfolder = 'kyc') {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        // Validar tipo
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo de arquivo não permitido. Use JPG, PNG ou PDF'];
        }

        // Validar tamanho
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Arquivo muito grande. Máximo: 5MB'];
        }

        // Criar estrutura de pastas do usuário se não existir
        if (!createUserDirectories($userId)) {
            return ['success' => false, 'message' => 'Erro ao criar diretórios do usuário'];
        }

        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . time() . '_' . uniqid() . '.' . $extension;

        // Caminho organizado: uploads/users/{user_id}/kyc/{filename}
        $relativePath = 'users/' . $userId . '/' . $subfolder . '/' . $filename;
        $uploadPath = __DIR__ . '/../uploads/' . $relativePath;

        // Fazer upload
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => true, 'filename' => $relativePath];
        }

        return ['success' => false, 'message' => 'Erro ao fazer upload'];
    }

    // Upload dos arquivos
    $frontResult = uploadFile($_FILES['document_front'], $userId, 'front');
    if (!$frontResult['success']) {
        sendJSON($frontResult, 400);
    }

    $selfieResult = uploadFile($_FILES['document_selfie'], $userId, 'selfie');
    if (!$selfieResult['success']) {
        sendJSON($selfieResult, 400);
    }

    // Upload do verso (opcional)
    $backFilename = null;
    if (isset($_FILES['document_back']) && $_FILES['document_back']['size'] > 0) {
        $backResult = uploadFile($_FILES['document_back'], $userId, 'back');
        if (!$backResult['success']) {
            sendJSON($backResult, 400);
        }
        $backFilename = $backResult['filename'];
    }

    // Atualizar banco de dados
    $stmt = $pdo->prepare("
        UPDATE users
        SET document_type = ?,
            document_number = ?,
            document_front = ?,
            document_back = ?,
            document_selfie = ?,
            kyc_status = 'approved',
            kyc_submitted_at = NOW(),
            kyc_reviewed_at = NOW()
        WHERE id = ?
    ");

    $success = $stmt->execute([
        $documentType,
        $documentNumber,
        $frontResult['filename'],
        $backFilename,
        $selfieResult['filename'],
        $userId
    ]);

    if ($success) {
        sendJSON([
            'success' => true,
            'message' => 'Documentos enviados e aprovados automaticamente!',
            'kyc_status' => 'approved'
        ]);
    } else {
        sendJSON(['success' => false, 'message' => 'Erro ao salvar no banco de dados'], 500);
    }
}

// ============================================
// AÇÃO INVÁLIDA
// ============================================
else {
    sendJSON(['success' => false, 'message' => 'Ação inválida'], 400);
}
