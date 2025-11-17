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

-- ============================================
-- FASE 3: TABELAS DE MOEDAS E PROPOSTAS
-- ============================================

DROP TABLE IF EXISTS currencies;
DROP TABLE IF EXISTS proposals;

-- Tabela de Moedas
CREATE TABLE currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    country VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir as 9 moedas suportadas
INSERT INTO currencies (code, name, symbol, country) VALUES
('BRL', 'Real Brasileiro', 'R$', 'Brasil'),
('AOA', 'Kwanza Angolano', 'Kz', 'Angola'),
('EUR', 'Euro', '€', 'Portugal'),
('USD', 'Dólar Americano', '$', 'Estados Unidos'),
('CUP', 'Peso Cubano', '$', 'Cuba'),
('RUB', 'Rublo Russo', '₽', 'Rússia'),
('ZAR', 'Rand Sul-Africano', 'R', 'África do Sul'),
('NAD', 'Dólar Namibiano', '$', 'Namíbia'),
('MZN', 'Metical Moçambicano', 'MT', 'Moçambique');

-- Tabela de Propostas de Câmbio
CREATE TABLE proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('buy', 'sell') NOT NULL,
    currency_from VARCHAR(3) NOT NULL,
    currency_to VARCHAR(3) NOT NULL,
    amount_from DECIMAL(15,2) NOT NULL,
    exchange_rate DECIMAL(10,6) NOT NULL,
    amount_to DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(100) NOT NULL,
    min_amount DECIMAL(15,2) DEFAULT NULL,
    max_amount DECIMAL(15,2) DEFAULT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_currencies (currency_from, currency_to),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Banco de dados criado com sucesso!' as status;
