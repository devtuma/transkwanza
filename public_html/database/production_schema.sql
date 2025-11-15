-- ============================================
-- TRANSKWANZA - Database Schema
-- Sistema P2P de Remessas Internacionais
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Database: u442547792_transkwanza
-- Charset: utf8mb4_unicode_ci

-- ============================================
-- 1. TABELA: users (Usuários do sistema)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,

  -- Informações Básicas
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) DEFAULT NULL COMMENT 'Hash bcrypt - NULL para login social',
  `country` VARCHAR(2) NOT NULL COMMENT 'Código ISO do país (BR, AO, PT, etc)',
  `phone` VARCHAR(20) DEFAULT NULL,

  -- Login Social
  `google_id` VARCHAR(255) DEFAULT NULL,
  `facebook_id` VARCHAR(255) DEFAULT NULL,
  `instagram_id` VARCHAR(255) DEFAULT NULL,
  `apple_id` VARCHAR(255) DEFAULT NULL,
  `login_method` ENUM('email', 'google', 'facebook', 'instagram', 'apple') DEFAULT 'email',

  -- KYC (Know Your Customer)
  `document_type` VARCHAR(50) DEFAULT NULL COMMENT 'RG, CPF, CNH, Passport',
  `document_number` VARCHAR(100) DEFAULT NULL,
  `document_front` VARCHAR(255) DEFAULT NULL COMMENT 'Caminho da foto frente',
  `document_back` VARCHAR(255) DEFAULT NULL COMMENT 'Caminho da foto verso',
  `document_selfie` VARCHAR(255) DEFAULT NULL COMMENT 'Caminho da selfie',
  `kyc_status` ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
  `kyc_reviewed_at` DATETIME DEFAULT NULL,
  `kyc_reviewed_by` INT(11) DEFAULT NULL COMMENT 'ID do admin que aprovou/rejeitou',
  `kyc_rejection_reason` TEXT DEFAULT NULL,

  -- Segurança e Permissões
  `is_admin` TINYINT(1) DEFAULT 0 COMMENT '1 = admin, 0 = usuário comum',
  `verified` TINYINT(1) DEFAULT 0 COMMENT 'Email verificado',
  `is_blocked` TINYINT(1) DEFAULT 0 COMMENT 'Conta bloqueada',
  `blocked_reason` TEXT DEFAULT NULL,
  `fraud_score` INT(11) DEFAULT 0 COMMENT 'Pontuação de risco (0-100)',

  -- Perfil
  `avatar` VARCHAR(255) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `reputation` DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Reputação (0.00 a 5.00)',
  `total_transactions` INT(11) DEFAULT 0,

  -- Auditoria
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` DATETIME DEFAULT NULL,
  `last_ip` VARCHAR(45) DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`),
  UNIQUE KEY `facebook_id` (`facebook_id`),
  UNIQUE KEY `instagram_id` (`instagram_id`),
  UNIQUE KEY `apple_id` (`apple_id`),
  KEY `kyc_status` (`kyc_status`),
  KEY `is_admin` (`is_admin`),
  KEY `kyc_reviewed_by` (`kyc_reviewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. TABELA: currencies (Moedas suportadas)
-- ============================================
CREATE TABLE IF NOT EXISTS `currencies` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(3) NOT NULL COMMENT 'BRL, AOA, EUR, USD, etc',
  `name` VARCHAR(100) NOT NULL,
  `symbol` VARCHAR(10) NOT NULL COMMENT 'R$, Kz, €, $, ₽',
  `country` VARCHAR(2) NOT NULL COMMENT 'Código ISO do país',
  `is_enabled` TINYINT(1) DEFAULT 1,
  `enabled_by` INT(11) DEFAULT NULL COMMENT 'ID do admin que habilitou/desabilitou',
  `enabled_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `is_enabled` (`is_enabled`),
  KEY `enabled_by` (`enabled_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. TABELA: proposals (Propostas de câmbio P2P)
-- ============================================
CREATE TABLE IF NOT EXISTS `proposals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type` ENUM('buy', 'sell') NOT NULL COMMENT 'Comprar ou vender moeda',
  `currency_from` VARCHAR(3) NOT NULL,
  `currency_to` VARCHAR(3) NOT NULL,
  `amount_from` DECIMAL(15,2) NOT NULL,
  `amount_to` DECIMAL(15,2) NOT NULL,
  `exchange_rate` DECIMAL(10,6) NOT NULL,
  `min_amount` DECIMAL(15,2) DEFAULT 0.00,
  `max_amount` DECIMAL(15,2) DEFAULT 0.00,
  `payment_method` VARCHAR(100) DEFAULT NULL COMMENT 'PIX, TED, Mpesa, etc',
  `status` ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `currency_from` (`currency_from`),
  KEY `currency_to` (`currency_to`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. TABELA: transactions (Transações P2P)
-- ============================================
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` INT(11) DEFAULT NULL,
  `sender_id` INT(11) NOT NULL,
  `receiver_id` INT(11) NOT NULL,
  `sender_currency` VARCHAR(3) NOT NULL,
  `receiver_currency` VARCHAR(3) NOT NULL,
  `sender_amount` DECIMAL(15,2) NOT NULL,
  `receiver_amount` DECIMAL(15,2) NOT NULL,
  `exchange_rate` DECIMAL(10,6) NOT NULL,
  `fee_percentage` DECIMAL(5,2) DEFAULT 3.00 COMMENT 'Taxa da plataforma (%)',
  `fee_amount` DECIMAL(15,2) DEFAULT 0.00,
  `payment_proof` VARCHAR(255) DEFAULT NULL,

  -- Aprovação Admin
  `admin_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `admin_reviewed_by` INT(11) DEFAULT NULL,
  `admin_reviewed_at` DATETIME DEFAULT NULL,
  `admin_rejection_reason` TEXT DEFAULT NULL,

  `status` ENUM('pending', 'paid', 'confirmed', 'completed', 'cancelled', 'dispute') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `proposal_id` (`proposal_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `status` (`status`),
  KEY `admin_status` (`admin_status`),
  KEY `admin_reviewed_by` (`admin_reviewed_by`),
  FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. TABELA: uploads (Registro de arquivos)
-- ============================================
CREATE TABLE IF NOT EXISTS `uploads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type` ENUM('document', 'avatar', 'payment_proof') NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT(11) NOT NULL COMMENT 'Tamanho em bytes',
  `mime_type` VARCHAR(100) NOT NULL,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. TABELA: fraud_checks (Alertas de fraude)
-- ============================================
CREATE TABLE IF NOT EXISTS `fraud_checks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `check_type` ENUM('duplicate_document', 'multiple_accounts', 'suspicious_activity', 'high_velocity') NOT NULL,
  `risk_level` ENUM('low', 'medium', 'high', 'critical') NOT NULL,
  `details` TEXT NOT NULL,
  `is_resolved` TINYINT(1) DEFAULT 0,
  `resolved_by` INT(11) DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_resolved` (`is_resolved`),
  KEY `risk_level` (`risk_level`),
  KEY `resolved_by` (`resolved_by`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. TABELA: messages (Chat entre usuários)
-- ============================================
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` INT(11) NOT NULL,
  `sender_id` INT(11) NOT NULL,
  `receiver_id` INT(11) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. TABELA: ratings (Avaliações)
-- ============================================
CREATE TABLE IF NOT EXISTS `ratings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` INT(11) NOT NULL,
  `rater_id` INT(11) NOT NULL COMMENT 'Quem avalia',
  `rated_id` INT(11) NOT NULL COMMENT 'Quem é avaliado',
  `rating` TINYINT(1) NOT NULL COMMENT '1 a 5 estrelas',
  `comment` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `rater_id` (`rater_id`),
  KEY `rated_id` (`rated_id`),
  FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`rater_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`rated_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. TABELA: notifications (Notificações)
-- ============================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type` ENUM('kyc_approved', 'kyc_rejected', 'transaction_received', 'transaction_completed', 'transaction_paid', 'proposal_matched', 'message_received', 'rating_received') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. TABELA: activity_log (Log de atividades)
-- ============================================
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'login, logout, upload_document, create_proposal, etc',
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. TABELA: admin_actions (Ações administrativas)
-- ============================================
CREATE TABLE IF NOT EXISTS `admin_actions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) NOT NULL,
  `action_type` ENUM('approve_kyc', 'reject_kyc', 'approve_transaction', 'reject_transaction', 'toggle_currency', 'block_user', 'unblock_user', 'resolve_fraud', 'connect_offer', 'make_payment') NOT NULL,
  `target_id` INT(11) DEFAULT NULL COMMENT 'ID do alvo (user_id, transaction_id, etc)',
  `details` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `action_type` (`action_type`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DADOS INICIAIS: 9 Moedas
-- ============================================
INSERT INTO `currencies` (`code`, `name`, `symbol`, `country`, `is_enabled`) VALUES
('BRL', 'Real Brasileiro', 'R$', 'BR', 1),
('AOA', 'Kwanza Angolano', 'Kz', 'AO', 1),
('EUR', 'Euro', '€', 'PT', 1),
('USD', 'Dólar Americano', '$', 'US', 1),
('CUP', 'Peso Cubano', '$', 'CU', 1),
('RUB', 'Rublo Russo', '₽', 'RU', 1),
('ZAR', 'Rand Sul-Africano', 'R', 'ZA', 1),
('NAD', 'Dólar Namíbio', '$', 'NA', 1),
('MZN', 'Metical Moçambicano', 'MT', 'MZ', 1);

-- ============================================
-- USUÁRIO ADMIN INICIAL
-- Senha: admin123 (hash bcrypt)
-- ============================================
INSERT INTO `users` (`name`, `email`, `password`, `country`, `is_admin`, `verified`, `kyc_status`) VALUES
('Administrador', 'admin@transkwanza.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BR', 1, 1, 'approved');

-- ============================================
-- TRIGGER: Atualizar reputação ao receber avaliação
-- ============================================
DELIMITER $$
CREATE TRIGGER update_reputation_after_rating
AFTER INSERT ON ratings
FOR EACH ROW
BEGIN
  UPDATE users SET reputation = (
    SELECT AVG(rating) FROM ratings WHERE rated_id = NEW.rated_id
  ) WHERE id = NEW.rated_id;
END$$
DELIMITER ;

COMMIT;
