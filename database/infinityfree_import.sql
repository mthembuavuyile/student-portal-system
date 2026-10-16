-- =============================================================================
-- STUDENT PORTAL SYSTEM - COMPLETE DATABASE SCHEMA & SEED DATA
-- 
-- Prepared by: Mr Avuyile Mthembu
-- Position:    Intern: Software Developer
-- Directorate: Information Technology Business Solutions (ITBS)
-- Unit:        ICT Services: Infrastructure & Security Support
-- Branch:      Corporate Management Services (CMS)
-- Department:  Department of Forestry, Fisheries and the Environment (DFFE)
-- =============================================================================

-- Database is already created and selected in InfinityFree phpMyAdmin

-- Temporarily disable FK checks to allow tables to be dropped in any order
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Table structure for table `students`
-- Includes both new normalized attributes (student_number, first_name, surname, id_number)
-- and legacy columns (full_name, student_id_number, course_of_study, academic_status)
-- to ensure 100% backward compatibility.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `student_id_number` varchar(50) NOT NULL UNIQUE,
  `email` varchar(255) NOT NULL UNIQUE,
  `date_of_birth` date NOT NULL,
  `course_of_study` varchar(255) NOT NULL,
  `enrollment_date` date NOT NULL,
  `academic_status` varchar(50) DEFAULT 'Active',
  `registration_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_number` (`student_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Dumping sample data for table `students`
-- -----------------------------------------------------------------------------
INSERT INTO `students` (`id`, `student_number`, `first_name`, `surname`, `id_number`, `full_name`, `student_id_number`, `email`, `date_of_birth`, `course_of_study`, `enrollment_date`, `academic_status`) VALUES
(1, '10ha2304014', 'Avuyile', 'Mthembu', '0504205812083', 'Avuyile Mthembu', '10ha2304014', 'avuyile@avuyilemthembu.co.za', '2005-04-20', 'Systems Development', '2023-02-20', 'Active'),
(2, 'ST1012', 'Jane', 'Smith', '0208225812081', 'Jane Smith', 'ST1012', 'jane.smith@example.com', '2002-08-22', 'Information Technology', '2024-02-21', 'Active'),
(3, 'ST1013', 'John', 'Doe', '0105155812089', 'John Doe', 'ST1013', 'john.doe@example.com', '2001-05-15', 'Computer Science', '2024-01-10', 'Pending');

-- -----------------------------------------------------------------------------
-- Table structure for table `courses`
-- Course: courseId, courseName, courseCode, duration
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL UNIQUE,
  `course_name` varchar(255) NOT NULL,
  `duration` varchar(50) NOT NULL DEFAULT '3 Years',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `duration`) VALUES
(1, 'SD101', 'Systems Development', '3 Years'),
(2, 'IT101', 'Information Technology', '3 Years'),
(3, 'CS101', 'Computer Science', '3 Years');

-- -----------------------------------------------------------------------------
-- Table structure for table `modules`
-- Module: moduleId, moduleName, moduleCode, credits
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `modules`;
CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_code` varchar(50) NOT NULL UNIQUE,
  `module_name` varchar(255) NOT NULL,
  `credits` int(11) NOT NULL DEFAULT 15,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modules` (`module_id`, `module_code`, `module_name`, `credits`) VALUES
(1, 'DBS201', 'Database Systems & SQL Design', 15),
(2, 'SEP202', 'Software Engineering Principles', 15),
(3, 'WAA203', 'Web Application Architecture', 15),
(4, 'OOP101', 'Object-Oriented Programming', 15),
(5, 'NIS204', 'Network Infrastructure & Security', 15),
(6, 'CSC301', 'Cloud Computing & Virtualization', 15);

-- -----------------------------------------------------------------------------
-- Table structure for table `lecturers`
-- Lecturer: lecturerId, firstName, surname, email
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `lecturers`;
CREATE TABLE `lecturers` (
  `lecturer_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`lecturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lecturers` (`lecturer_id`, `first_name`, `surname`, `email`) VALUES
(1, 'Nkosinathi', 'Sithole', 'n.sithole@dffe.gov.za'),
(2, 'Kaveshan', 'Naidoo', 'k.naidoo@dffe.gov.za'),
(3, 'Zandile', 'Khumalo', 'z.khumalo@dffe.gov.za'),
(4, 'Thabo', 'Baloyi', 't.baloyi@dffe.gov.za');

-- -----------------------------------------------------------------------------
-- Table structure for table `course_modules`
-- Business Rule: Modules may be shared across courses (Many-to-Many)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `course_modules`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `course_modules` (`course_id`, `module_id`, `semester`) VALUES
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

-- -----------------------------------------------------------------------------
-- Table structure for table `module_lecturers`
-- Business Rule: Modules taught by multiple lecturers (Many-to-Many)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `module_lecturers`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `module_lecturers` (`module_id`, `lecturer_id`, `is_primary`) VALUES
(1, 1, 1),
(1, 2, 0),
(2, 2, 1),
(3, 3, 1),
(3, 1, 0),
(4, 4, 1),
(5, 4, 1),
(6, 3, 1);

-- -----------------------------------------------------------------------------
-- Table structure for table `enrollments`
-- Business Rule: A student may have only one active course enrollment at a time.
-- The Enrollment class maintains registration history.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `enrollments`;
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
  CONSTRAINT `fk_enrollment_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enrollment_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `course_id`, `registration_date`, `status`) VALUES
(1, 1, 1, '2023-02-20', 'Active'),
(2, 2, 2, '2024-02-21', 'Active'),
(3, 3, 3, '2024-01-10', 'Pending');

-- -----------------------------------------------------------------------------
-- Table structure for table `module_results`
-- Business Rule: ModuleResult stores four quarterly marks for one student, one module,
-- and one academic year. calculateAverage() calculates average.
-- Mark validation: 0-100 permitted range.
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `module_results`;
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
  CONSTRAINT `fk_result_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_result_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `module_results` (`mark_id`, `student_id`, `module_id`, `academic_year`, `quarter1`, `quarter2`, `quarter3`, `quarter4`, `average`) VALUES
(1, 1, 1, 2026, 82.50, 88.00, 85.50, 90.00, 86.50),
(2, 1, 2, 2026, 78.00, 81.50, 84.00, 86.50, 82.50),
(3, 1, 3, 2026, 85.00, 92.00, 89.50, 94.00, 90.13),
(4, 1, 4, 2026, 74.00, 79.50, 82.00, 88.00, 80.88);

-- -----------------------------------------------------------------------------
-- Table structure for table `users`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','student') NOT NULL DEFAULT 'student',
  `student_id_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id_number` (`student_id_number`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`student_id_number`) REFERENCES `students` (`student_id_number`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `role`, `student_id_number`) VALUES
(1, 'admin', '$2y$10$Ie/I.B5p2Wd9/q5L4p.d.eW.S3Lg2oYk5fQxJzC6mR7b8G9f0a1b2', 'admin', NULL),
(2, '10ha2304014', '$2y$10$jL0n0ZpD3MFxvIJRxbPNzuo32cLKnm5W1ClHwnpvn9b.ASQKnHk2G', 'student', '10ha2304014');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;
