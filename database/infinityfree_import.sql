-- =============================================================================
-- STUDENT PORTAL SYSTEM - INFINITYFREE LIVE DATABASE IMPORT & SYNC SCRIPT
-- 
-- Author:      Mr Avuyile Mthembu
-- Position:    Full Stack Software Developer / Systems Architect
-- Directorate: Information Technology Business Solutions (ITBS)
-- Unit:        ICT Services: Infrastructure & Security Support
-- Branch:      Corporate Management Services (CMS)
-- Department:  Department of Forestry, Fisheries and the Environment (DFFE)
-- 
-- Purpose:
-- Single, complete, self-contained SQL file engineered specifically for InfinityFree
-- MySQL hosting (phpMyAdmin). It prepares the live database so that if an older
-- version already existed (e.g. V1 flat table, V2 without lecturer accounts, or
-- partially migrated schema), it drops obsolete tables safely and re-creates the
-- complete 3NF schema and seed data to be 100% identical to local development (AMPPS).
-- 
-- InfinityFree Deployment Notes:
-- 1. In InfinityFree phpMyAdmin, select your assigned database (e.g. if0_43192402_university_db).
-- 2. Click the "Import" tab at the top.
-- 3. Select this file (infinityfree_import.sql) and click "Go".
--    (Or copy and paste this script directly into the "SQL" tab and run it).
-- 4. No "CREATE DATABASE" or "USE" statements are included, preventing InfinityFree
--    permission error #1044 (Access denied).
-- =============================================================================

-- Temporarily disable foreign key checks to allow clean drops and creations in any order
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. DROP EXISTING TABLES (Safely removes older schema versions if present)
-- Child/junction tables are listed first, followed by parent entity tables.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `module_results`;
DROP TABLE IF EXISTS `enrollments`;
DROP TABLE IF EXISTS `course_modules`;
DROP TABLE IF EXISTS `module_lecturers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `modules`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `lecturers`;

-- -----------------------------------------------------------------------------
-- 2. TABLE STRUCTURE: `students`
-- Includes both new normalized attributes (student_number, first_name, surname, id_number)
-- and legacy columns (full_name, student_id_number, course_of_study, academic_status)
-- to guarantee 100% backward compatibility with all queries and forms.
-- -----------------------------------------------------------------------------
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_id_number` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `course_of_study` varchar(255) NOT NULL,
  `enrollment_date` date NOT NULL,
  `academic_status` varchar(50) DEFAULT 'Active',
  `registration_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id_number` (`student_id_number`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_student_number` (`student_number`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATA: `students`
-- -----------------------------------------------------------------------------
INSERT INTO `students` (`id`, `student_number`, `first_name`, `surname`, `id_number`, `full_name`, `student_id_number`, `email`, `date_of_birth`, `course_of_study`, `enrollment_date`, `academic_status`, `registration_timestamp`) VALUES
(1, '10ha2304014', 'Avuyile', 'Mthembu', '0504205812083', 'Avuyile Mthembu', '10ha2304014', 'avuyile@avuyilemthembu.co.za', '2005-04-20', 'Systems Development', '2023-02-20', 'Active', '2026-10-16 12:00:16'),
(2, 'ST1012', 'Jane', 'Smith', '0208225812081', 'Jane Smith', 'ST1012', 'jane.smith@example.com', '2002-08-22', 'Information Technology', '2024-02-21', 'Active', '2026-10-16 12:00:16'),
(3, 'ST1013', 'John', 'Doe', '0105155812089', 'John Doe', 'ST1013', 'john.doe@example.com', '2001-05-15', 'Computer Science', '2024-01-10', 'Pending', '2026-10-16 12:00:16');

-- -----------------------------------------------------------------------------
-- 3. TABLE STRUCTURE: `courses`
-- Academic qualifications offered by the institution.
-- -----------------------------------------------------------------------------
CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `duration` varchar(50) NOT NULL DEFAULT '3 Years',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`course_id`),
  UNIQUE KEY `course_code` (`course_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATA: `courses`
-- -----------------------------------------------------------------------------
INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `duration`, `created_at`) VALUES
(1, 'SD101', 'Systems Development', '3 Years', '2026-10-16 12:00:16'),
(2, 'IT101', 'Information Technology', '3 Years', '2026-10-16 12:00:16'),
(3, 'CS101', 'Computer Science', '3 Years', '2026-10-16 12:00:16');

-- -----------------------------------------------------------------------------
-- 4. TABLE STRUCTURE: `modules`
-- Curricular modules and credit allocations.
-- -----------------------------------------------------------------------------
CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_code` varchar(50) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `credits` int(11) NOT NULL DEFAULT 15,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`module_id`),
  UNIQUE KEY `module_code` (`module_code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATA: `modules`
-- -----------------------------------------------------------------------------
INSERT INTO `modules` (`module_id`, `module_code`, `module_name`, `credits`, `created_at`) VALUES
(1, 'DBS201', 'Database Systems & SQL Design', 15, '2026-10-16 12:00:16'),
(2, 'SEP202', 'Software Engineering Principles', 15, '2026-10-16 12:00:16'),
(3, 'WAA203', 'Web Application Architecture', 15, '2026-10-16 12:00:16'),
(4, 'OOP101', 'Object-Oriented Programming', 15, '2026-10-16 12:00:16'),
(5, 'NIS204', 'Network Infrastructure & Security', 15, '2026-10-16 12:00:16'),
(6, 'CSC301', 'Cloud Computing & Virtualization', 15, '2026-10-16 12:00:16');

-- -----------------------------------------------------------------------------
-- 5. TABLE STRUCTURE: `lecturers`
-- Academic faculty members and contact profiles.
-- -----------------------------------------------------------------------------
CREATE TABLE `lecturers` (
  `lecturer_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`lecturer_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATA: `lecturers`
-- -----------------------------------------------------------------------------
INSERT INTO `lecturers` (`lecturer_id`, `first_name`, `surname`, `email`, `created_at`) VALUES
(1, 'Nkosinathi', 'Sithole', 'n.sithole@dffe.gov.za', '2026-10-16 12:00:16'),
(2, 'Kaveshan', 'Naidoo', 'k.naidoo@dffe.gov.za', '2026-10-16 12:00:16'),
(3, 'Zandile', 'Khumalo', 'z.khumalo@dffe.gov.za', '2026-10-16 12:00:16'),
(4, 'Thabo', 'Baloyi', 't.baloyi@dffe.gov.za', '2026-10-16 12:00:16');

-- -----------------------------------------------------------------------------
-- 6. TABLE STRUCTURE: `course_modules`
-- Many-to-Many junction mapping modules into courses by semester.
-- -----------------------------------------------------------------------------
CREATE TABLE `course_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `semester` varchar(50) DEFAULT 'Semester 1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_course_module` (`course_id`,`module_id`),
  KEY `fk_cm_module` (`module_id`),
  CONSTRAINT `fk_cm_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cm_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATA: `course_modules`
-- -----------------------------------------------------------------------------
INSERT INTO `course_modules` (`id`, `course_id`, `module_id`, `semester`) VALUES
(1, 1, 1, 'Semester 1'),
(2, 1, 2, 'Semester 1'),
(3, 1, 3, 'Semester 2'),
(4, 1, 4, 'Semester 2'),
(5, 2, 1, 'Semester 1'),
(6, 2, 5, 'Semester 1'),
(7, 2, 6, 'Semester 2'),
(8, 2, 3, 'Semester 2'),
(9, 3, 2, 'Semester 1'),
(10, 3, 4, 'Semester 1'),
(11, 3, 1, 'Semester 2'),
(12, 3, 6, 'Semester 2');

-- -----------------------------------------------------------------------------
-- 7. TABLE STRUCTURE: `module_lecturers`
-- Many-to-Many junction assigning faculty members to modules.
-- -----------------------------------------------------------------------------
CREATE TABLE `module_lecturers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_module_lecturer` (`module_id`,`lecturer_id`),
  KEY `fk_ml_lecturer` (`lecturer_id`),
  CONSTRAINT `fk_ml_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ml_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATA: `module_lecturers`
-- -----------------------------------------------------------------------------
INSERT INTO `module_lecturers` (`id`, `module_id`, `lecturer_id`, `is_primary`) VALUES
(1, 1, 1, 1),
(2, 1, 2, 0),
(3, 2, 2, 1),
(4, 3, 3, 1),
(5, 3, 1, 0),
(6, 4, 4, 1),
(7, 5, 4, 1),
(8, 6, 3, 1);

-- -----------------------------------------------------------------------------
-- 8. TABLE STRUCTURE: `enrollments`
-- Tracks official student course registrations with single active course rule.
-- -----------------------------------------------------------------------------
CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `registration_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`enrollment_id`),
  KEY `fk_enrollment_student` (`student_id`),
  KEY `fk_enrollment_course` (`course_id`),
  CONSTRAINT `fk_enrollment_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enrollment_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATA: `enrollments`
-- -----------------------------------------------------------------------------
INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `course_id`, `registration_date`, `status`, `created_at`) VALUES
(1, 1, 1, '2023-02-20', 'Active', '2026-10-16 12:00:17'),
(2, 2, 2, '2024-02-21', 'Active', '2026-10-16 12:00:17'),
(3, 3, 3, '2024-01-10', 'Pending', '2026-10-16 12:00:17');

-- -----------------------------------------------------------------------------
-- 9. TABLE STRUCTURE: `module_results`
-- Quarterly marks (Q1, Q2, Q3, Q4) and automated cumulative weighted averages.
-- -----------------------------------------------------------------------------
CREATE TABLE `module_results` (
  `mark_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `academic_year` int(11) NOT NULL,
  `quarter1` decimal(5,2) DEFAULT NULL,
  `quarter2` decimal(5,2) DEFAULT NULL,
  `quarter3` decimal(5,2) DEFAULT NULL,
  `quarter4` decimal(5,2) DEFAULT NULL,
  `average` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`mark_id`),
  UNIQUE KEY `unique_student_module_year` (`student_id`,`module_id`,`academic_year`),
  KEY `fk_result_module` (`module_id`),
  CONSTRAINT `fk_result_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_result_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATA: `module_results`
-- -----------------------------------------------------------------------------
INSERT INTO `module_results` (`mark_id`, `student_id`, `module_id`, `academic_year`, `quarter1`, `quarter2`, `quarter3`, `quarter4`, `average`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2026, 82.50, 88.00, 85.50, 90.00, 86.50, '2026-10-16 12:00:17', '2026-10-16 12:00:17'),
(2, 1, 2, 2026, 78.00, 81.50, 84.00, 86.50, 82.50, '2026-10-16 12:00:17', '2026-10-16 12:00:17'),
(3, 1, 3, 2026, 85.00, 92.00, 89.50, 94.00, 90.13, '2026-10-16 12:00:17', '2026-10-16 12:00:17'),
(4, 1, 4, 2026, 74.00, 79.50, 82.00, 88.00, 80.88, '2026-10-16 12:00:17', '2026-10-16 12:00:17');

-- -----------------------------------------------------------------------------
-- 10. TABLE STRUCTURE: `users`
-- Secure multi-role authentication supporting Admin, Lecturer, and Student roles.
-- Features foreign keys to both students (student_id_number) and lecturers (lecturer_id).
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','lecturer','student') NOT NULL DEFAULT 'student',
  `student_id_number` varchar(50) DEFAULT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `student_id_number` (`student_id_number`),
  KEY `fk_user_lecturer` (`lecturer_id`),
  CONSTRAINT `fk_user_lecturer` FOREIGN KEY (`lecturer_id`) REFERENCES `lecturers` (`lecturer_id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`student_id_number`) REFERENCES `students` (`student_id_number`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DATA: `users`
-- 
-- Credentials Summary:
-- 1. Administrator:   username 'admin'       | password 'TheAdmin@dffe'
-- 2. Demo Student:    username '10ha2304014' | password 'Student@dffe'
-- 3. Lead Lecturer:   username 'lecturer'    | password 'Lecturer@dffe' (Lecturer ID: 1, Nkosinathi Sithole)
-- 4. Faculty Member:  username 'k.naidoo'    | password 'Lecturer@dffe' (Lecturer ID: 2, Kaveshan Naidoo)
-- 5. Faculty Member:  username 'z.khumalo'   | password 'Lecturer@dffe' (Lecturer ID: 3, Zandile Khumalo)
-- 6. Faculty Member:  username 't.baloyi'    | password 'Lecturer@dffe' (Lecturer ID: 4, Thabo Baloyi)
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `password`, `role`, `student_id_number`, `lecturer_id`) VALUES
(1, 'admin', '$2y$10$b6Mh80HB9bAwZnbyUfhiEexxRJzz5K7M26EWopY3jL9gyq1V/JEuC', 'admin', NULL, NULL),
(2, '10ha2304014', '$2y$10$Td2eB3enx.9MKzHF/fuXrOAurKrH6/CVm/UMlTC28EgQYFl8sdbWa', 'student', '10ha2304014', NULL),
(3, 'lecturer', '$2y$10$dpkOtSAl7113W8toFnN49.Zxl29r1ECZ3mhEDrKXxCWZ5ejGy7Mbq', 'lecturer', NULL, 1),
(4, 'k.naidoo', '$2y$10$dpkOtSAl7113W8toFnN49.Zxl29r1ECZ3mhEDrKXxCWZ5ejGy7Mbq', 'lecturer', NULL, 2),
(5, 'z.khumalo', '$2y$10$dpkOtSAl7113W8toFnN49.Zxl29r1ECZ3mhEDrKXxCWZ5ejGy7Mbq', 'lecturer', NULL, 3),
(6, 't.baloyi', '$2y$10$dpkOtSAl7113W8toFnN49.Zxl29r1ECZ3mhEDrKXxCWZ5ejGy7Mbq', 'lecturer', NULL, 4);

-- -----------------------------------------------------------------------------
-- Re-enable foreign key checks and commit transaction
-- -----------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;