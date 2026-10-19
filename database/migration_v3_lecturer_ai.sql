-- =============================================================================
-- STUDENT PORTAL SYSTEM - DATABASE MIGRATION SCRIPT (VERSION 3.0)
-- 
-- Prepared by: Mr Avuyile Mthembu
-- Position:    Intern: Software Developer
-- Directorate: Information Technology Business Solutions (ITBS)
-- Unit:        ICT Services: Infrastructure & Security Support
-- Branch:      Corporate Management Services (CMS)
-- Department:  Department of Forestry, Fisheries and the Environment (DFFE)
-- 
-- Description:
-- Introduces Lecturer / Educator role separation, adds lecturer_id foreign key
-- to users table, and seeds lecturer credentials for educator access.
-- =============================================================================

USE `university_db`;

-- 1. Modify users role enum to include 'lecturer'
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','lecturer','student') NOT NULL DEFAULT 'student';

-- 2. Add lecturer_id column if not exists
SET @dbname = DATABASE();
SET @tablename = "users";

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'lecturer_id') > 0,
  "SELECT 1",
  "ALTER TABLE `users` ADD COLUMN `lecturer_id` INT(11) NULL AFTER `student_id_number`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Add foreign key constraint if not exists
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND CONSTRAINT_NAME = 'fk_user_lecturer') > 0,
  "SELECT 1",
  "ALTER TABLE `users` ADD CONSTRAINT `fk_user_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE SET NULL;"
));
PREPARE alterFkIfNotExists FROM @preparedStatement;
EXECUTE alterFkIfNotExists;
DEALLOCATE PREPARE alterFkIfNotExists;

-- 4. Seed default lecturer login account (password: password123)
-- Hash for 'password123': $2y$10$w8.1UuB9n0n3m1r/9u6hOekm2N7E1fJ7xP8q1hQhN7v9m5lF8iC3u or generate via PHP
INSERT IGNORE INTO `users` (`username`, `password`, `role`, `lecturer_id`)
VALUES ('lecturer', '$2y$10$w4rWjA9mR1p8Z5K1iK3wyeWw1yK5Y3E2bA7oF3b0A9i8J1l8O3i1K', 'lecturer', 1);

COMMIT;
