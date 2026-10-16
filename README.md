# Student Portal System (SMS)

**System Design, Class Relationships & Academic Management Architecture**

> **Prepared by:** Mr Avuyile Mthembu  
> **Position:** Intern: Software Developer  
> **Directorate:** Information Technology Business Solutions (ITBS)  
> **Unit:** ICT Services: Infrastructure & Security Support  
> **Branch:** Corporate Management Services (CMS)  
> **Department:** Department of Forestry, Fisheries and the Environment (DFFE)  
> **Version:** 2.0 (With 100% Backward Compatibility)  

---

## 📌 System Overview

The **Student Portal System** is an institutional web-based academic records and management platform engineered for managing student profiles, course registrations, curriculum modules, academic staff, and quarterly results.

### 🌟 Key Features
- **Object-Oriented Domain Layer**: Clean classes (`Student`, `Course`, `Module`, `Lecturer`, `Enrollment`, `ModuleResult`, `RegistrationService`).
- **Single Active Course Rule**: Strict institutional business rule enforcement allowing only one active course per student while maintaining complete registration history.
- **Automated Unique Student Number**: Automated institutional student number generator (`ST` + 2-digit Year + 5 digits) with database uniqueness constraints.
- **Many-to-Many Academic Relations**:
  - Modules shared across multiple courses (`course_modules`).
  - Modules co-taught by multiple lecturers (`module_lecturers`) with primary and co-lecturer roles.
- **Quarterly Marks & Automatic Average**: Captures Q1, Q2, Q3, and Q4 marks (validated 0–100%) and computes running averages via `calculateAverage()`.
- **Dual Role Dashboards**: Dedicated interfaces for Administrators (curriculum, faculty, results) and Students (profile, enrolled modules, transcripts).
- **Official Export & Reports**: Registration Confirmation slips and Academic Transcripts printable and exportable to PDF using `jsPDF`.
- **100% Backward Compatibility**: Synchronized dual-column support for legacy database schemas.

---

## 🚀 Quick Setup & Installation

### 1. Database Configuration
1. Import `database/database.sql` into your MySQL/MariaDB server (creates database `university_db`).
2. Alternatively, if upgrading an existing installation, execute `database/migration_v2.sql`.
3. Copy the configuration template:
   ```bash
   cp config.example.php config.php
   ```
4. Edit `config.php` with your local database credentials (e.g. `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`).

### 2. Default Credentials
| Role | Username | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` |
| **Student** | `10ha2304014` | `bitcoin` |
| **New Students** | Auto-generated Student Number | `password123` |

---

## 🏛️ System Architecture & UML

Comprehensive technical architecture, class diagrams, and entity-relationship models can be found in:
- [`documentation/STUDENT_PORTAL_SYSTEM_DESIGN.md`](documentation/STUDENT_PORTAL_SYSTEM_DESIGN.md)

---

## 📄 License & Attribution

Developed under the Directorate: Information Technology Business Solutions (ITBS), Department of Forestry, Fisheries and the Environment (DFFE).
