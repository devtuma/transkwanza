-- ============================================
-- TRANSKWANZA - FASE 1: AUTENTICAÇÃO BÁSICA
-- Apenas 1 tabela: users
-- ============================================

DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    country VARCHAR(50) NOT NULL,

    -- FASE 2: Campos KYC (Documentos Universais)
    -- Tipos aceitos: national_id, drivers_license, passport, residence_permit
    document_type VARCHAR(30) DEFAULT NULL,
    document_number VARCHAR(50) DEFAULT NULL,
    -- Caminhos: uploads/users/{user_id}/kyc/{filename}
    document_front VARCHAR(255) DEFAULT NULL,
    document_back VARCHAR(255) DEFAULT NULL,
    document_selfie VARCHAR(255) DEFAULT NULL,
    kyc_status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
    kyc_submitted_at TIMESTAMP NULL DEFAULT NULL,
    kyc_reviewed_at TIMESTAMP NULL DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_kyc_status (kyc_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir um usuário de teste
INSERT INTO users (name, email, password, country) VALUES
('Usuário Teste', 'teste@transkwanza.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Brasil');
-- Senha: password

SELECT 'Banco de dados criado com sucesso!' as status;
