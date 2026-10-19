# Student Portal System

System Design, Class Architecture, and Academic Management

Prepared by: Mr Avuyile Mthembu  
Position: Intern: Software Developer  
Directorate: Information Technology Business Solutions (ITBS)  
Unit: ICT Services: Infrastructure and Security Support  
Branch: Corporate Management Services (CMS)  
Department: Department of Forestry, Fisheries and the Environment (DFFE)  
Version: 2.0  

---

## Overview

The Student Portal System is a web-based academic records platform designed to manage student registrations, courses, curriculum modules, teaching allocations, and quarterly academic results.

The system expands on an earlier single-table student directory by introducing a normalized relational database and object-oriented domain classes, while preserving full backward compatibility with existing student records and login credentials.

---

## Core Features

- **Domain Model**: Structured PHP domain classes (`Student`, `Course`, `Module`, `Lecturer`, `Enrollment`, `ModuleResult`, and `RegistrationService`) handle business logic, data validation, and database operations.
- **Single Active Course Rule**: Ensures a student can only be actively enrolled in one qualification at a time, while keeping complete history of past enrollments.
- **Automated Student Numbers**: Automatically generates formatted student numbers (`ST` + 2-digit year + 5-digit sequence) with database uniqueness checks.
- **Flexible Curriculum Mapping**:
  - Courses and modules share a many-to-many relationship, allowing modules to be reused across different qualifications.
  - Modules support multiple lecturers with defined primary and co-lecturer roles.
- **Quarterly Mark Tracking**: Records marks for Q1 through Q4 with range validation (0 to 100) and automatically calculates the overall running average.
- **Role-Based Portals**:
  - **Administrator**: Manage students, qualifications, modules, lecturer assignments, and quarterly mark entries.
  - **Student**: View personal profiles, enrolled modules, lecturers, and academic transcripts.
- **Reporting and Document Generation**: Generates printable registration confirmation slips and academic transcripts with direct PDF export support.
- **Backward Compatibility**: Maintains legacy table columns alongside normalized relational tables so existing queries, scripts, and imports continue to function without interruption.

---

## Setup and Installation

### 1. Requirements
- PHP 7.4 or higher with PDO MySQL extension
- MySQL 5.7+ or MariaDB 10.4+
- Web server (Apache via AMPPS, XAMPP, or similar)

### 2. Database Configuration
1. Import `database/database.sql` into your MySQL/MariaDB server to create and seed the `university_db` database.
2. If upgrading an existing installation from version 1, run `database/migration_v2.sql`.
3. Copy the configuration file template:
   ```bash
   cp config.example.php config.php
   ```
4. Open `config.php` and set your local database credentials (`DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`).

### 3. Default Login Credentials

| Role | Username | Password | Notes |
| :--- | :--- | :--- | :--- |
| Administrator | `admin` | `admin123` | Full administrative access |
| Student (Sample) | `10ha2304014` | `bitcoin` | Seeded demo student account |
| Newly Registered Student | Generated Student Number | `password123` | Default password upon registration |

---

## Architecture and Documentation

Detailed technical documentation, class specifications, and database schemas are available in:
- [System Design and Class Relationships](documentation/STUDENT_PORTAL_SYSTEM_DESIGN.md)
- [Entity Relationship Specification and Data Dictionary](documentation/ENTITY_RELATIONSHIP_SPECIFICATION.md)

---

## Attribution

Developed for the Directorate: Information Technology Business Solutions (ITBS), Department of Forestry, Fisheries and the Environment (DFFE).

