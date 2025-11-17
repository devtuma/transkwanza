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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir um usuário de teste
INSERT INTO users (name, email, password, country) VALUES
('Usuário Teste', 'teste@transkwanza.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Brasil');
-- Senha: password

SELECT 'Banco de dados criado com sucesso!' as status;
