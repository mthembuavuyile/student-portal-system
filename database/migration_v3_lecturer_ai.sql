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

-- 4. Seed default lecturer login account (password: Lecturer@dffe)
-- Hash for 'Lecturer@dffe': $2y$10$dpkOtSAl7113W8toFnN49.Zxl29r1ECZ3mhEDrKXxCWZ5ejGy7Mbq or generate via PHP
INSERT IGNORE INTO `users` (`username`, `password`, `role`, `lecturer_id`)
VALUES ('lecturer', '$2y$10$dpkOtSAl7113W8toFnN49.Zxl29r1ECZ3mhEDrKXxCWZ5ejGy7Mbq', 'lecturer', 1);

COMMIT;
