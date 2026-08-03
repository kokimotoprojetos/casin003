-- ==================================================== --
-- TABELA: poseidonpay (Gateway PoseidonPay)
-- Execute no banco de dados (Aiven/MySQL)
-- ==================================================== --

CREATE TABLE IF NOT EXISTS `poseidonpay` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'x-public-key',
  `client_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'x-secret-key',
  `ativo` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `poseidonpay` (`id`, `client_id`, `client_secret`, `ativo`)
VALUES (1, '', '', 0)
ON DUPLICATE KEY UPDATE `id` = `id`;
