<?php
/**
 * TRANSKWANZA - API de Upload
 * Handler genérico para uploads de arquivos
 */

require_once 'config.php';

// Requer autenticação
$user = requireAuth();

// Obter tipo de upload
$type = $_GET['type'] ?? '';

// Validar tipo
$validTypes = ['avatar', 'payment_proof'];
if (!in_array($type, $validTypes)) {
    error('Tipo de upload inválido', 400);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Método não permitido', 405);
}

// ============================================
// PROCESSAR UPLOAD
// ============================================

try {
    if (!isset($_FILES['file'])) {
        error('Nenhum arquivo enviado', 400);
    }

    $file = $_FILES['file'];

    // Validar arquivo
    $validation = validateUploadedFile($file);

    if (!$validation['valid']) {
        error($validation['error'], 400);
    }

    $extension = getExtensionFromMime($validation['mime_type']);

    // Definir pasta de destino baseado no tipo
    $subfolder = '';
    switch ($type) {
        case 'avatar':
            $subfolder = 'avatars/';
            break;
        case 'payment_proof':
            $subfolder = 'payment_proofs/';
            break;
    }

    // Gerar nome único
    $filename = generateUniqueFilename($user['id'], $type, $extension);
    $filepath = UPLOAD_BASE_PATH . $subfolder . $filename;
    $relativePath = 'uploads/' . $subfolder . $filename;

    // Mover arquivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        error('Erro ao salvar arquivo', 500);
    }

    $pdo = getDBConnection();

    // Registrar upload
    $stmt = $pdo->prepare("
        INSERT INTO uploads (user_id, type, original_name, stored_name, file_path, file_size, mime_type)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user['id'],
        $type,
        $file['name'],
        $filename,
        $relativePath,
        $file['size'],
        $validation['mime_type']
    ]);

    $uploadId = $pdo->lastInsertId();

    // Se for avatar, atualizar usuário
    if ($type === 'avatar') {
        // Deletar avatar antigo se existir
        if ($user['avatar'] && file_exists(UPLOAD_BASE_PATH . str_replace('uploads/', '', $user['avatar']))) {
            @unlink(UPLOAD_BASE_PATH . str_replace('uploads/', '', $user['avatar']));
        }

        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$relativePath, $user['id']]);
    }

    // Registrar atividade
    logActivity($user['id'], 'upload_file', "Upload de {$type}");

    success([
        'upload_id' => $uploadId,
        'file_path' => $relativePath,
        'file_url' => '/' . $relativePath,
        'filename' => $filename
    ], 'Arquivo enviado com sucesso');

} catch (PDOException $e) {
    // Deletar arquivo em caso de erro
    if (isset($filepath) && file_exists($filepath)) {
        @unlink($filepath);
    }

    error_log("Upload Error: " . $e->getMessage());
    error('Erro ao processar upload', 500);
}
