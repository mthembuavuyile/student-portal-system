# Student Portal System
### Object-Oriented Architecture, UML Modeling & Full-Stack Implementation

**Developer:** Mr Avuyile Mthembu  
**Role:** Intern: Software Developer  
**Directorate:** Information Technology Business Solutions (ITBS)  
**Unit:** ICT Services: Infrastructure and Security Support  
**Branch:** Corporate Management Services (CMS)  
**Host Department:** Department of Forestry, Fisheries and the Environment (DFFE)  
**Project Nature:** Internship Practical Assessment & Technical Competency Demonstration  
**Version:** 2.0  

---

## 🌐 Live System Deployment

The system is hosted and actively accessible at:  
👉 **[https://student-system.infinityfree.io/index.php](https://student-system.infinityfree.io/index.php)**

### Quick Access Demo Credentials
- **Admin Portal**: Username `admin` | Password `admin123`
- **Student Portal**: Username `10ha2304014` | Password `bitcoin`

---

## Project Overview & Assessment Aim

This project was developed as a comprehensive **internship technical assessment task** assigned by the development mentor within the Directorate: Information Technology Business Solutions (ITBS). 

The primary objective of this assessment is to evaluate and demonstrate practical software engineering competencies across the full development lifecycle:
1. **Requirements Analysis**: Analyzing legacy requirements and modeling institutional business rules.
2. **Object-Oriented Design (UML)**: Designing formal UML Class diagrams and relationship architectures.
3. **Database Architecture**: Transforming a flat, single-table schema into a normalized 3NF relational database.
4. **Backend Programming**: Implementing clean, reusable OOP domain models and service layers in PHP.
5. **Security & Backward Compatibility**: Ensuring 100% backward compatibility with pre-existing student data, hashes, and session logic.

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
- **Security & Cryptographic Salting**: Secures user passwords using Bcrypt hashing (`PASSWORD_BCRYPT`) with an automatic, cryptographically secure 22-character pseudorandom salt per account. This ensures identical plain-text passwords produce completely distinct hashes, protecting stored credentials against rainbow table lookups and offline dictionary attacks.
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
- [Software Requirements Specification (SRS)](documentation/SOFTWARE_REQUIREMENTS_SPECIFICATION.md)
- [System Design and Class Relationships](documentation/STUDENT_PORTAL_SYSTEM_DESIGN.md)
- [Entity Relationship Specification and Data Dictionary](documentation/ENTITY_RELATIONSHIP_SPECIFICATION.md)

---

## Project Background & Attribution

Developed by **Mr Avuyile Mthembu** as a technical assessment and practical evaluation project during the software development internship program within the Directorate: Information Technology Business Solutions (ITBS), Department of Forestry, Fisheries and the Environment (DFFE). Mentor evaluation task.

