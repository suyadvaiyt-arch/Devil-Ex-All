-- =====================================================
-- DEVIL EX Online APK Update Control System
-- database.sql  (MySQL 5.7+ / MariaDB 10.3+, InfinityFree compatible)
-- Import via phpMyAdmin into your existing database.
-- The first admin account is created by install.php (no default password is stored here).
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- admins ----------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `email`         VARCHAR(120) NOT NULL,
  `display_name`  VARCHAR(80)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- applications ----------
CREATE TABLE IF NOT EXISTS `applications` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `app_id`                CHAR(32)     NOT NULL COMMENT 'Public unique Application ID used by the API',
  `name`                  VARCHAR(100) NOT NULL,
  `package_name`          VARCHAR(150) NOT NULL,
  `current_version`       VARCHAR(30)  NOT NULL DEFAULT '1.0.0',
  `latest_version`        VARCHAR(30)  NOT NULL DEFAULT '1.0.0',
  `min_supported_version` VARCHAR(30)  NOT NULL DEFAULT '1.0.0',
  `icon_path`             VARCHAR(255) DEFAULT NULL,
  `download_url`          VARCHAR(500) DEFAULT NULL,
  `description`           TEXT DEFAULT NULL,
  `is_active`             TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_applications_app_id` (`app_id`),
  UNIQUE KEY `uq_applications_package` (`package_name`),
  KEY `idx_applications_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- update_configs (one row per application) ----------
CREATE TABLE IF NOT EXISTS `update_configs` (
  `id`                         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id`             INT UNSIGNED NOT NULL,
  `dialog_enabled`             TINYINT(1) NOT NULL DEFAULT 0,
  `update_type`                ENUM('optional','mandatory') NOT NULL DEFAULT 'optional',
  `latest_version`             VARCHAR(30)  NOT NULL DEFAULT '1.0.0',
  `latest_version_code`        INT UNSIGNED NOT NULL DEFAULT 1,
  `min_supported_version_code` INT UNSIGNED NOT NULL DEFAULT 1,
  `title`                      VARCHAR(150) NOT NULL DEFAULT 'New Update Available',
  `message`                    VARCHAR(500) NOT NULL DEFAULT 'A new version is available.',
  `description`                TEXT DEFAULT NULL,
  `download_url`               VARCHAR(500) DEFAULT NULL,
  `update_button_text`         VARCHAR(40)  NOT NULL DEFAULT 'Update Now',
  `cancel_button_text`         VARCHAR(40)  NOT NULL DEFAULT 'Later',
  `changelog`                  TEXT DEFAULT NULL,
  `theme`                      ENUM('light','dark') NOT NULL DEFAULT 'light',
  `maintenance_mode`           TINYINT(1) NOT NULL DEFAULT 0,
  `maintenance_message`        VARCHAR(500) DEFAULT NULL,
  `updated_by`                 INT UNSIGNED DEFAULT NULL,
  `created_at`                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_update_configs_app` (`application_id`),
  KEY `idx_update_configs_type` (`update_type`),
  CONSTRAINT `fk_uc_app`   FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uc_admin` FOREIGN KEY (`updated_by`)     REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- update_history (every config change, with full snapshot) ----------
CREATE TABLE IF NOT EXISTS `update_history` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id`   INT UNSIGNED NOT NULL,
  `admin_id`         INT UNSIGNED DEFAULT NULL,
  `admin_name`       VARCHAR(80) NOT NULL,
  `previous_version` VARCHAR(30) DEFAULT NULL,
  `new_version`      VARCHAR(30) NOT NULL,
  `update_type`      ENUM('optional','mandatory') NOT NULL,
  `status`           ENUM('enabled','disabled','maintenance','changed') NOT NULL DEFAULT 'changed',
  `config_snapshot`  MEDIUMTEXT NOT NULL COMMENT 'JSON of the full configuration at this point',
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uh_app_date` (`application_id`, `created_at`),
  CONSTRAINT `fk_uh_app`   FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uh_admin` FOREIGN KEY (`admin_id`)       REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- api_logs ----------
CREATE TABLE IF NOT EXISTS `api_logs` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` INT UNSIGNED DEFAULT NULL,
  `endpoint`       VARCHAR(60) NOT NULL,
  `ip_address`     VARCHAR(45) NOT NULL,
  `version_code`   INT DEFAULT NULL,
  `status_code`    SMALLINT UNSIGNED NOT NULL,
  `user_agent`     VARCHAR(255) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_api_logs_app` (`application_id`),
  KEY `idx_api_logs_endpoint_date` (`endpoint`, `created_at`),
  KEY `idx_api_logs_created` (`created_at`),
  CONSTRAINT `fk_al_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- app_events (report_download.php / report_update.php) ----------
CREATE TABLE IF NOT EXISTS `app_events` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id`   INT UNSIGNED NOT NULL,
  `event_type`       ENUM('download','update') NOT NULL,
  `from_version_code` INT DEFAULT NULL,
  `to_version_code`   INT DEFAULT NULL,
  `ip_address`       VARCHAR(45) NOT NULL,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ae_app_type` (`application_id`, `event_type`),
  CONSTRAINT `fk_ae_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- rate_limits (API + login throttling, no external cache needed) ----------
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `bucket`       VARCHAR(40) NOT NULL COMMENT 'e.g. api, login',
  `identifier`   VARCHAR(64) NOT NULL COMMENT 'IP address or hashed key',
  `hits`         INT UNSIGNED NOT NULL DEFAULT 1,
  `window_start` DATETIME NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rl_bucket_ident` (`bucket`, `identifier`),
  KEY `idx_rl_window` (`window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- system_settings ----------
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key`   VARCHAR(60) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------- default settings ----------
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
  ('site_name',              'DEVIL EX'),
  ('site_tagline',           'Online APK Update Control System'),
  ('api_rate_limit',         '60'),
  ('api_rate_window_sec',    '60'),
  ('login_max_attempts',     '5'),
  ('login_lockout_minutes',  '15'),
  ('api_logging_enabled',    '1'),
  ('public_status_enabled',  '1'),
  ('log_retention_days',     '30'),
  ('installed',              '0')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
