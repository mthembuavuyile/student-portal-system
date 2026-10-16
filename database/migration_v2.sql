-- =============================================================================
-- STUDENT PORTAL SYSTEM - DATABASE MIGRATION SCRIPT (VERSION 2.0)
-- 
-- Prepared by: Mr Avuyile Mthembu
-- Position:    Intern: Software Developer
-- Directorate: Information Technology Business Solutions (ITBS)
-- Unit:        ICT Services: Infrastructure & Security Support
-- Branch:      Corporate Management Services (CMS)
-- Department:  Department of Forestry, Fisheries and the Environment (DFFE)
-- 
-- Description:
-- Expands existing database schema with Courses, Modules, Lecturers,
-- Enrollments, and Module Results while maintaining 100% backward compatibility
-- with legacy tables, columns, queries, and reports.
-- =============================================================================

USE `university_db`;

-- -----------------------------------------------------------------------------
-- 1. UPGRADE STUDENTS TABLE (WITH BACKWARD COMPATIBILITY)
-- -----------------------------------------------------------------------------
-- Add new normalized fields (student_number, first_name, surname, id_number)
-- while preserving legacy fields (full_name, student_id_number, course_of_study, academic_status)

SET @dbname = DATABASE();
SET @tablename = "students";

-- Add student_number
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'student_number') > 0,
  "SELECT 1",
  "ALTER TABLE `students` ADD COLUMN `student_number` VARCHAR(50) NULL AFTER `id`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add first_name
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'first_name') > 0,
  "SELECT 1",
  "ALTER TABLE `students` ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `student_number`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add surname
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'surname') > 0,
  "SELECT 1",
  "ALTER TABLE `students` ADD COLUMN `surname` VARCHAR(100) NULL AFTER `first_name`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add id_number
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'id_number') > 0,
  "SELECT 1",
  "ALTER TABLE `students` ADD COLUMN `id_number` VARCHAR(50) NULL AFTER `surname`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Populate new fields for legacy records
UPDATE `students` 
SET 
  `student_number` = IFNULL(`student_number`, `student_id_number`),
  `first_name` = IFNULL(`first_name`, IF(LOCATE(' ', `full_name`) > 0, SUBSTRING_INDEX(`full_name`, ' ', 1), `full_name`)),
  `surname` = IFNULL(`surname`, IF(LOCATE(' ', `full_name`) > 0, SUBSTRING(`full_name`, LOCATE(' ', `full_name`) + 1), 'Student')),
  `id_number` = IFNULL(`id_number`, CONCAT('020', LPAD(FLOOR(RAND() * 9999999999), 10, '0')))
WHERE `student_number` IS NULL OR `first_name` IS NULL OR `surname` IS NULL OR `id_number` IS NULL;

-- -----------------------------------------------------------------------------
-- 2. COURSES TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `course_id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_code` VARCHAR(50) NOT NULL UNIQUE,
  `course_name` VARCHAR(255) NOT NULL,
  `duration` VARCHAR(50) NOT NULL DEFAULT '3 Years',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. MODULES TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `modules` (
  `module_id` INT AUTO_INCREMENT PRIMARY KEY,
  `module_code` VARCHAR(50) NOT NULL UNIQUE,
  `module_name` VARCHAR(255) NOT NULL,
  `credits` INT NOT NULL DEFAULT 15,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. LECTURERS TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lecturers` (
  `lecturer_id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `surname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. COURSE_MODULES TABLE (MANY-TO-MANY: Modules shared across courses)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_modules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `module_id` INT NOT NULL,
  `semester` VARCHAR(50) DEFAULT 'Semester 1',
  UNIQUE KEY `unique_course_module` (`course_id`, `module_id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
  FOREIGN KEY (`module_id`) REFERENCES `modules`(`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. MODULE_LECTURERS TABLE (MANY-TO-MANY: Modules taught by multiple lecturers)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `module_lecturers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `module_id` INT NOT NULL,
  `lecturer_id` INT NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 1,
  UNIQUE KEY `unique_module_lecturer` (`module_id`, `lecturer_id`),
  FOREIGN KEY (`module_id`) REFERENCES `modules`(`module_id`) ON DELETE CASCADE,
  FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers`(`lecturer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. ENROLLMENTS TABLE (One active enrollment per student, preserves history)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `enrollments` (
  `enrollment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `course_id` INT NOT NULL,
  `registration_date` DATE NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. MODULE_RESULTS TABLE (Quarterly Marks & Average Calculation)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `module_results` (
  `mark_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `module_id` INT NOT NULL,
  `academic_year` INT NOT NULL,
  `quarter1` DECIMAL(5,2) NULL,
  `quarter2` DECIMAL(5,2) NULL,
  `quarter3` DECIMAL(5,2) NULL,
  `quarter4` DECIMAL(5,2) NULL,
  `average` DECIMAL(5,2) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_student_module_year` (`student_id`, `module_id`, `academic_year`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`module_id`) REFERENCES `modules`(`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. SEED CORE DATA
-- -----------------------------------------------------------------------------
INSERT INTO `courses` (`course_code`, `course_name`, `duration`) VALUES
('SD101', 'Systems Development', '3 Years'),
('IT101', 'Information Technology', '3 Years'),
('CS101', 'Computer Science', '3 Years')
ON DUPLICATE KEY UPDATE `course_name` = VALUES(`course_name`);

INSERT INTO `modules` (`module_code`, `module_name`, `credits`) VALUES
('DBS201', 'Database Systems & SQL Design', 15),
('SEP202', 'Software Engineering Principles', 15),
('WAA203', 'Web Application Architecture', 15),
('OOP101', 'Object-Oriented Programming', 15),
('NIS204', 'Network Infrastructure & Security', 15),
('CSC301', 'Cloud Computing & Virtualization', 15)
ON DUPLICATE KEY UPDATE `module_name` = VALUES(`module_name`);

INSERT INTO `lecturers` (`first_name`, `surname`, `email`) VALUES
('Nkosinathi', 'Sithole', 'n.sithole@dffe.gov.za'),
('Kaveshan', 'Naidoo', 'k.naidoo@dffe.gov.za'),
('Zandile', 'Khumalo', 'z.khumalo@dffe.gov.za'),
('Thabo', 'Baloyi', 't.baloyi@dffe.gov.za')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT IGNORE INTO `course_modules` (`course_id`, `module_id`, `semester`) VALUES
(1, 1, 'Semester 1'),
(1, 2, 'Semester 1'),
(1, 3, 'Semester 2'),
(1, 4, 'Semester 2'),
(2, 1, 'Semester 1'),
(2, 5, 'Semester 1'),
(2, 6, 'Semester 2'),
(2, 3, 'Semester 2'),
(3, 2, 'Semester 1'),
(3, 4, 'Semester 1'),
(3, 1, 'Semester 2'),
(3, 6, 'Semester 2');

INSERT IGNORE INTO `module_lecturers` (`module_id`, `lecturer_id`, `is_primary`) VALUES
(1, 1, 1),
(1, 2, 0),
(2, 2, 1),
(3, 3, 1),
(3, 1, 0),
(4, 4, 1),
(5, 4, 1),
(6, 3, 1);

-- Back-populate enrollments for existing students matching their current course_of_study
INSERT INTO `enrollments` (`student_id`, `course_id`, `registration_date`, `status`)
SELECT s.id, c.course_id, s.enrollment_date, s.academic_status
FROM `students` s
JOIN `courses` c ON c.course_name = s.course_of_study
WHERE NOT EXISTS (
  SELECT 1 FROM `enrollments` e WHERE e.student_id = s.id AND e.course_id = c.course_id
);

-- Seed sample quarterly marks for student 1 (Avuyile Mthembu)
INSERT IGNORE INTO `module_results` (`student_id`, `module_id`, `academic_year`, `quarter1`, `quarter2`, `quarter3`, `quarter4`, `average`) VALUES
(1, 1, 2026, 82.50, 88.00, 85.50, 90.00, 86.50),
(1, 2, 2026, 78.00, 81.50, 84.00, 86.50, 82.50),
(1, 3, 2026, 85.00, 92.00, 89.50, 94.00, 90.13),
(1, 4, 2026, 74.00, 79.50, 82.00, 88.00, 80.88);
