# Student Portal System
### Academic Management Platform, Object-Oriented Architecture and Full-Stack Implementation

---

| Project Overview | Details |
| :--- | :--- |
| **Host Department** | **Department of Forestry, Fisheries and the Environment (DFFE)** |
| **Branch & Directorate** | Corporate Management Services (CMS) — Directorate: IT Business Solutions (ITBS) |
| **Unit** | ICT Services: Infrastructure and Security Support |
| **Project Nature** | **Internship Technical Practical Assessment & Competency Evaluation Task** |
| **Development Sprint** | **2 Calendar Weeks** (10 Intensive Working Days) |
| **Total Project Expenditure** | **< R 1,000.00 ZAR** (Utilizing Open-Source FOSS Stack, Free Cloud Tier & Existing Hardware) |
| **Development Team** | • **Mr Avuyile Mthembu**: Full Stack Software Developer (Project Lead & System Architect)<br>• **Ms Nomthandazo Khoza**: Database Administrator (DBA & Relational Modeler)<br>• **Ms Qhama Nodada**: Frontend Developer (UI/UX & Client Logic) |
| **Technical Mentor** | Senior Specialist: Software Architecture & Systems Development (ITBS) |
| **Live Production Instance** | [https://student-system.infinityfree.io/index.php](https://student-system.infinityfree.io/index.php) |
| **Localhost Instance** | [http://localhost/university_system/index.php](http://localhost/university_system/index.php) |

---

## Deployment Environments & Demo Credentials

The system is operational across both cloud production and local workstation environments:
- **Live Cloud Production**: [https://student-system.infinityfree.io/index.php](https://student-system.infinityfree.io/index.php)
- **Localhost Workstation (AMPPS)**: [http://localhost/university_system/index.php](http://localhost/university_system/index.php)

### Quick Access Demo Credentials (Available via UI role switcher tabs)

| Portal View | Username | Password | Role Description |
| :--- | :--- | :--- | :--- |
| **Student Portal** | `10ha2304014` | `bitcoin` | Seeded demo student account with registered modules, results, and AI coach |
| **Lecturer / Educator Portal** | `lecturer` | `password123` | Educator view to review teaching rosters and capture quarterly marks (Q1–Q4) |
| **Administrator Portal** | `admin` | `admin123` | Administrative governance, student registration, and curriculum management |

---

## Project Background & Assessment Mandate

This system was engineered as a practical two-week software engineering assessment assigned by the technical mentorship team within the Directorate: Information Technology Business Solutions (ITBS) at the Department of Forestry, Fisheries and the Environment (DFFE).

The assessment challenge was to take an academic management domain and demonstrate full-lifecycle software engineering practices under realistic delivery constraints:
1. **Requirements Analysis**: Reverse-engineering a legacy flat student directory and formalizing IEEE 830 functional requirements.
2. **Database Normalization (3NF)**: Decomposing a denormalized single table into a normalized relational schema (`university_db`) with cascading referential integrity.
3. **Object-Oriented Design (UML)**: Modeling domain entities and implementing clean PHP 8 domain classes and service layers.
4. **Multi-Role Segregation**: Implementing distinct, secure portals for Administrators, Lecturers/Educators, and Students.
5. **AI Pedagogical Integration**: Engineering an AI Academic Success Coach leveraging the OpenRouter Free API (`google/gemini-2.0-flash-exp:free`) with local heuristic fallback.
6. **Zero-Regressive Backward Compatibility**: Preserving legacy data, session logic, and login credentials without breaking changes.
7. **Resource Optimization**: Delivering the complete system on a sub-R1,000 budget by leveraging open-source technologies (AMPPS, PHP 8, MariaDB) and free cloud tiers.

---

## Sequential Software Engineering Documentation Suite

To eliminate documentation redundancy and present the project in a logical sequence for assessors, the engineering specifications in [`documentation/`](documentation/) are organized into a 5-Step Sequential Pipeline:

```
[ Step 1: PMP ]  ──>  [ Step 2: Analysis ]  ──>  [ Step 3: SRS ]  ──>  [ Step 4: Architecture ]  ──>  [ Step 5: Database ]
Project Charter       TELOS Feasibility          IEEE 830 Specs        UML Class Modeling            3NF Data Dictionary
2-Week Sprint & RACI  DFDs & 3-Tier Arch         FR-01 to FR-18        OOP Domain Models             Schema & Migrations
```

> **Unified Master Specification Available**:  
> For assessing all specifications, diagrams, data dictionaries, and architectural models within a single comprehensive manual, please consult **[MASTER_DOCUMENTATION.md](MASTER_DOCUMENTATION.md)** on the root directory.


1. **[01. Project Management Plan (PMP)](documentation/01_PROJECT_MANAGEMENT_PLAN.md)**  
   *Scope, Time, Cost, and Team Governance.* Features the Project Charter, 2-Week Sprint Schedule (Gantt chart), sub-R1,000 budget model highlighting open-source cost avoidance (~R48,400 saved), RACI matrix across the 3 interns, and risk register.
2. **[02. Systems Analysis & Process Specification](documentation/02_SYSTEMS_ANALYSIS_AND_PROCESS_SPECIFICATION.md)**  
   *Lifecycle Traceability & Process Modeling.* Details the 5-phase fast-track SDLC, TELOS Feasibility Study, As-Is vs. To-Be process models, DFD Level 0 (Context) & Level 1 decomposition, 3-Tier system topology, and the Requirements Traceability Matrix (RTM).
3. **[03. Software Requirements Specification (SRS)](documentation/03_SOFTWARE_REQUIREMENTS_SPECIFICATION.md)**  
   *Formal IEEE 830 Requirements.* Formulates 18 functional requirements (FR-01 to FR-18) including multi-role governance, auto-numbering, quarterly mark capture, OpenRouter AI coaching, and jsPDF document exports, with detailed use cases and non-functional requirements.
4. **[04. Software Architecture & Technical System Design](documentation/04_SOFTWARE_ARCHITECTURE_AND_DESIGN.md)**  
   *Object-Oriented Architecture & UML Modeling.* Includes the formal UML 2.5 Class Diagram, entity multiplicities, domain model descriptions (`Student`, `Course`, `Module`, `Lecturer`, `Enrollment`, `ModuleResult`, `User`, `RegistrationService`, `OpenRouterService`), and backward compatibility facades.
5. **[05. Database Specification & Relational Data Dictionary](documentation/05_DATABASE_SPECIFICATION_AND_DATA_DICTIONARY.md)**  
   *Relational Schema & 3NF Normalization.* Features the complete 3NF normalization proof, ASCII relational map, data dictionary across all 8 tables, foreign key constraints with cascading rules, and SQL migration scripts.

---

## Core System Features

- **Interactive Multi-Role Switcher UI**: Tabbed login interface allowing users to switch between Student, Lecturer, and Admin roles with one-click demo credentials.
- **Dedicated Lecturer / Educator Portal**: Educators can review their teaching allocations, view student rosters, and capture or update quarterly marks (Q1 through Q4) with automated running average calculations.
- **AI Academic Success Advisor (OpenRouter Free API)**:
  - **One-Click Diagnostic**: Analyzes quarterly marks across all subjects, flags at-risk modules (<50%), and generates targeted revision strategies.
  - **Interactive Study Chat**: Bounded to syllabus mastery, time management, and academic improvement.
  - **Fault-Tolerant Architecture**: Supports OpenRouter free models (`google/gemini-2.0-flash-exp:free`) with fallback to internal heuristic rules when offline.
- **Automated Student Numbering**: Generates standardized institutional student IDs (`ST` + 2-digit year + 5-digit sequence) with atomic uniqueness verification.
- **Single Active Course Rule**: Restricts students to exactly one active course enrollment while maintaining permanent transcript history of prior studies.
- **Curriculum & Shared Modules**: Many-to-many relationship mapping between courses and modules, and multi-lecturer teaching assignments.
- **Document Exporting (jsPDF)**: Client-side generation of official Registration Confirmation Slips and Academic Transcripts in PDF format.
- **Cryptographic Security**: Passwords hashed using Bcrypt (`cost => 10`) with cryptographically secure 22-character random salts per account.
- **Dual-Column Backward Compatibility**: Maintains legacy columns alongside normalized tables so Version 1.0 queries and records continue functioning without errors.

---

## Setup and Local Installation

### 1. Prerequisites
- PHP 7.4 or higher (PHP 8.2 recommended) with PDO MySQL and cURL extensions.
- MySQL 5.7+ or MariaDB 10.4+.
- Web server (Apache via AMPPS, XAMPP, or WampServer).

### 2. Database Configuration
1. Import `database/database.sql` into your MySQL/MariaDB server to create and seed the base `university_db` database.
2. Run the incremental migration scripts:
   - `database/migration_v2.sql` (for 3NF relational tables and indexing).
   - `database/migration_v3_lecturer_ai.sql` (for lecturer role separation and AI conversation tables).
3. Create your local configuration file:
   ```bash
   cp config.example.php config.php
   ```
4. Configure your database connection in `config.php`:
   ```php
   define('DB_SERVER', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', 'mysql'); // or '' depending on local setup
   define('DB_NAME', 'university_db');
   ```
5. *(Optional)* Configure your free OpenRouter API key in `config.php` to enable live generative AI models for the student coach:
   ```php
   define('OPENROUTER_API_KEY', 'your_openrouter_api_key_here');
   define('OPENROUTER_MODEL', 'google/gemini-2.0-flash-exp:free');
   ```
   *(Note: If left empty, the built-in offline heuristic fallback handles performance diagnostics automatically.)*

---

## The Internship Development Team

* **Mr Avuyile Mthembu** — *Full Stack Software Developer (Project Lead & System Architect)*  
  OOP domain architecture, authentication, AI integration, business logic, system integration, and cloud deployment.
* **Ms Nomthandazo Khoza** — *Database Administrator (DBA & Relational Modeler)*  
  Relational schema design, 3NF normalization, data dictionary, SQL migration scripts, foreign keys, and integrity constraints.
* **Ms Qhama Nodada** — *Frontend Developer (UI/UX & Client Logic)*  
  Material Design user interface, responsive viewport layouts, multi-role tabbed switcher, dynamic search filtering, and jsPDF export integration.

**Host Department**: Department of Forestry, Fisheries and the Environment (DFFE)  
**Branch**: Corporate Management Services (CMS) | **Directorate**: Information Technology Business Solutions (ITBS)  
**Supervision**: Technical Mentor & Senior Software Specialist (ITBS)
