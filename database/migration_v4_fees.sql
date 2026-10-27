-- =============================================================================
-- STUDENT PORTAL SYSTEM - MIGRATION V4: PAYFAST SCHOOL FEES & BILLING
-- 
-- Adds student fee invoicing (R50.00/month) and PayFast payment audit tables.
-- =============================================================================

USE `university_db`;

-- Drop dependent tables first if re-running
DROP TABLE IF EXISTS `fee_payments`;
DROP TABLE IF EXISTS `student_fees`;

-- -----------------------------------------------------------------------------
-- 1. Table: student_fees
-- Invoices generated for each student per month for the academic year.
-- -----------------------------------------------------------------------------
CREATE TABLE `student_fees` (
  `fee_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `fee_title` varchar(255) NOT NULL,
  `academic_year` int(11) NOT NULL DEFAULT 2026,
  `billing_month` tinyint(2) NOT NULL, -- 1 = Jan, 2 = Feb, ..., 12 = Dec
  `amount` decimal(10,2) NOT NULL DEFAULT 50.00,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_date` date NOT NULL,
  `status` enum('unpaid', 'partially_paid', 'paid', 'waived') NOT NULL DEFAULT 'unpaid',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`fee_id`),
  UNIQUE KEY `unique_student_month_year` (`student_id`, `academic_year`, `billing_month`),
  KEY `fk_fee_student` (`student_id`),
  CONSTRAINT `fk_fee_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table: fee_payments
-- Audit ledger of all checkout transactions (PayFast ITN, Manual, EFT).
-- -----------------------------------------------------------------------------
CREATE TABLE `fee_payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `fee_id` int(11) DEFAULT NULL,
  `transaction_ref` varchar(64) NOT NULL UNIQUE,
  `payfast_pf_payment_id` varchar(64) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'PayFast',
  `payment_status` enum('PENDING', 'COMPLETE', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
  `payfast_signature` varchar(128) DEFAULT NULL,
  `itn_raw_payload` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `fk_payment_student` (`student_id`),
  KEY `fk_payment_fee` (`fee_id`),
  CONSTRAINT `fk_payment_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_fee` FOREIGN KEY (`fee_id`) REFERENCES `student_fees` (`fee_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Seed Monthly Fees (Feb to Nov 2026 - 10 Academic Months @ R50.00)
-- -----------------------------------------------------------------------------
-- Student 1: Avuyile Mthembu (id: 1)
INSERT INTO `student_fees` (`student_id`, `fee_title`, `academic_year`, `billing_month`, `amount`, `amount_paid`, `due_date`, `status`) VALUES
(1, 'School Fee - February 2026', 2026, 2, 50.00, 50.00, '2026-02-28', 'paid'),
(1, 'School Fee - March 2026', 2026, 3, 50.00, 50.00, '2026-03-31', 'paid'),
(1, 'School Fee - April 2026', 2026, 4, 50.00, 0.00, '2026-04-30', 'unpaid'),
(1, 'School Fee - May 2026', 2026, 5, 50.00, 0.00, '2026-05-31', 'unpaid'),
(1, 'School Fee - June 2026', 2026, 6, 50.00, 0.00, '2026-06-30', 'unpaid'),
(1, 'School Fee - July 2026', 2026, 7, 50.00, 0.00, '2026-07-31', 'unpaid'),
(1, 'School Fee - August 2026', 2026, 8, 50.00, 0.00, '2026-08-31', 'unpaid'),
(1, 'School Fee - September 2026', 2026, 9, 50.00, 0.00, '2026-09-30', 'unpaid'),
(1, 'School Fee - October 2026', 2026, 10, 50.00, 0.00, '2026-10-31', 'unpaid'),
(1, 'School Fee - November 2026', 2026, 11, 50.00, 0.00, '2026-11-30', 'unpaid');

-- Record historical payments for student 1's paid Feb & Mar fees
INSERT INTO `fee_payments` (`student_id`, `fee_id`, `transaction_ref`, `payfast_pf_payment_id`, `amount`, `payment_method`, `payment_status`, `completed_at`) VALUES
(1, 1, 'PF-2026-02-10HA23-SEED1', '14029182', 50.00, 'PayFast', 'COMPLETE', '2026-02-25 14:22:10'),
(1, 2, 'PF-2026-03-10HA23-SEED2', '14109483', 50.00, 'PayFast', 'COMPLETE', '2026-03-28 09:15:44');

-- Student 2: Jane Smith (id: 2)
INSERT INTO `student_fees` (`student_id`, `fee_title`, `academic_year`, `billing_month`, `amount`, `amount_paid`, `due_date`, `status`) VALUES
(2, 'School Fee - February 2026', 2026, 2, 50.00, 50.00, '2026-02-28', 'paid'),
(2, 'School Fee - March 2026', 2026, 3, 50.00, 0.00, '2026-03-31', 'unpaid'),
(2, 'School Fee - April 2026', 2026, 4, 50.00, 0.00, '2026-04-30', 'unpaid'),
(2, 'School Fee - May 2026', 2026, 5, 50.00, 0.00, '2026-05-31', 'unpaid'),
(2, 'School Fee - June 2026', 2026, 6, 50.00, 0.00, '2026-06-30', 'unpaid'),
(2, 'School Fee - July 2026', 2026, 7, 50.00, 0.00, '2026-07-31', 'unpaid'),
(2, 'School Fee - August 2026', 2026, 8, 50.00, 0.00, '2026-08-31', 'unpaid'),
(2, 'School Fee - September 2026', 2026, 9, 50.00, 0.00, '2026-09-30', 'unpaid'),
(2, 'School Fee - October 2026', 2026, 10, 50.00, 0.00, '2026-10-31', 'unpaid'),
(2, 'School Fee - November 2026', 2026, 11, 50.00, 0.00, '2026-11-30', 'unpaid');

INSERT INTO `fee_payments` (`student_id`, `fee_id`, `transaction_ref`, `payfast_pf_payment_id`, `amount`, `payment_method`, `payment_status`, `completed_at`) VALUES
(2, 11, 'PF-2026-02-ST1012-SEED1', '14030112', 50.00, 'PayFast', 'COMPLETE', '2026-02-27 16:40:02');

-- Student 3: John Doe (id: 3)
INSERT INTO `student_fees` (`student_id`, `fee_title`, `academic_year`, `billing_month`, `amount`, `amount_paid`, `due_date`, `status`) VALUES
(3, 'School Fee - February 2026', 2026, 2, 50.00, 0.00, '2026-02-28', 'unpaid'),
(3, 'School Fee - March 2026', 2026, 3, 50.00, 0.00, '2026-03-31', 'unpaid'),
(3, 'School Fee - April 2026', 2026, 4, 50.00, 0.00, '2026-04-30', 'unpaid'),
(3, 'School Fee - May 2026', 2026, 5, 50.00, 0.00, '2026-05-31', 'unpaid'),
(3, 'School Fee - June 2026', 2026, 6, 50.00, 0.00, '2026-06-30', 'unpaid'),
(3, 'School Fee - July 2026', 2026, 7, 50.00, 0.00, '2026-07-31', 'unpaid'),
(3, 'School Fee - August 2026', 2026, 8, 50.00, 0.00, '2026-08-31', 'unpaid'),
(3, 'School Fee - September 2026', 2026, 9, 50.00, 0.00, '2026-09-30', 'unpaid'),
(3, 'School Fee - October 2026', 2026, 10, 50.00, 0.00, '2026-10-31', 'unpaid'),
(3, 'School Fee - November 2026', 2026, 11, 50.00, 0.00, '2026-11-30', 'unpaid');

-- Student 4: Alucias Makonko (id: 4)
INSERT INTO `student_fees` (`student_id`, `fee_title`, `academic_year`, `billing_month`, `amount`, `amount_paid`, `due_date`, `status`) VALUES
(4, 'School Fee - February 2026', 2026, 2, 50.00, 50.00, '2026-02-28', 'paid'),
(4, 'School Fee - March 2026', 2026, 3, 50.00, 50.00, '2026-03-31', 'paid'),
(4, 'School Fee - April 2026', 2026, 4, 50.00, 50.00, '2026-04-30', 'paid'),
(4, 'School Fee - May 2026', 2026, 5, 50.00, 0.00, '2026-05-31', 'unpaid'),
(4, 'School Fee - June 2026', 2026, 6, 50.00, 0.00, '2026-06-30', 'unpaid'),
(4, 'School Fee - July 2026', 2026, 7, 50.00, 0.00, '2026-07-31', 'unpaid'),
(4, 'School Fee - August 2026', 2026, 8, 50.00, 0.00, '2026-08-31', 'unpaid'),
(4, 'School Fee - September 2026', 2026, 9, 50.00, 0.00, '2026-09-30', 'unpaid'),
(4, 'School Fee - October 2026', 2026, 10, 50.00, 0.00, '2026-10-31', 'unpaid'),
(4, 'School Fee - November 2026', 2026, 11, 50.00, 0.00, '2026-11-30', 'unpaid');
