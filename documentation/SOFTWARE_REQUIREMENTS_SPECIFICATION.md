# STUDENT PORTAL SYSTEM
## Software Requirements Specification (SRS)
### Internship Practical Technical Assessment & Evaluation

---

> **Sequential Documentation Pipeline**  
> ← Previous: [02. Systems Analysis & SDLC Specification](SYSTEMS_ANALYSIS_AND_DESIGN_SDLC.md) | **Document 3 of 5** | Next: [04. System Design & Class Architecture](STUDENT_PORTAL_SYSTEM_DESIGN.md) →

---

| **Document Metadata** | **Specification Details** |
| :--- | :--- |
| **Document Title** | Software Requirements Specification (SRS) |
| **System Name** | Student Portal System |
| **Project Nature** | **Internship Technical Assessment / Practical Competency Evaluation Task** |
| **Project Sprint Duration** | **2 Calendar Weeks** (10 Intensive Working Days) |
| **Project Team** | • **Avuyile Mthembu**: Full Stack Software Developer (Project Lead & System Architect)<br>• **Nomthandazo Khoza**: Database Administrator (DBA & Relational Modeler)<br>• **Qhama Nodada**: Frontend Developer (UI/UX & Client Logic) |
| **Host Department** | **Department of Forestry, Fisheries and the Environment (DFFE)** |
| **Branch & Directorate** | Corporate Management Services (CMS) — Directorate: IT Business Solutions (ITBS) |
| **Unit** | ICT Services: Infrastructure & Security Support |
| **Standard Followed** | IEEE Standard 830-1998 (Recommended Practice for Software Requirements Specifications) |
| **Architecture Version** | Version 3.0 (Role Segregation, 3NF Normalization, AI Coach & 100% Backward Compatibility) |
| **Live Production Instance** | [https://student-system.infinityfree.io/index.php](https://student-system.infinityfree.io/index.php) |

---

## 1. Introduction

### 1.1 Purpose & Assessment Scope
This **Software Requirements Specification (SRS)** captures the complete functional and non-functional requirements for the **Student Portal System**. The project was assigned as an intensive, practical two-week software engineering assessment by the technical mentorship team within the **Directorate: Information Technology Business Solutions (ITBS)** at DFFE.

The primary objective of this assessment is to evaluate the intern team's capability to:
1. Conduct software requirements analysis and translate institutional business rules into formal technical specifications.
2. Produce professional **UML Class Diagrams** and **Entity Relationship Diagrams (ERDs)**.
3. Decompose a flat, single-table legacy database into a normalized **Third Normal Form (3NF)** relational database schema.
4. Implement clean, maintainable **Object-Oriented Programming (OOP)** domain models and services in PHP.
5. Deliver intuitive, role-segregated user interfaces for **Administrators**, **Educators/Lecturers**, and **Students**.
6. Integrate generative AI pedagogical assistance via the OpenRouter Free API with offline heuristic fallback.
7. Engineer a backward compatibility layer preserving existing student records, queries, authentication sessions, and reporting templates without regressions.
8. Deploy a working, responsive full-stack prototype to a live web hosting environment under strict budget (< R1,000) and time constraints (2 weeks).

### 1.2 Intended Audience
This document is prepared for:
* **Technical Mentors & Senior Developers (DFFE ITBS)**: For evaluating requirements completeness, architectural soundness, and code quality.
* **Internship Assessment Committee**: For reviewing technical artifacts, team role execution, and practical task delivery.

---

## 2. Overall Description

### 2.1 Product Perspective
The system evolves a legacy single-table student directory into an enterprise-grade academic management platform operating on a standard LAMP/WAMP architecture (Apache, MySQL/MariaDB, PHP) with a modern Material Design user interface.

```
+-------------------------------------------------------------------------------+
|                             CLIENT TIER (Browser)                             |
|         Responsive Web UI (Materialize CSS, JavaScript, HTML5, jsPDF)         |
+-------------------------------------------------------------------------------+
                                      | HTTP(S) Requests
                                      v
+-------------------------------------------------------------------------------+
|                            APPLICATION / LOGIC TIER                           |
|  - Role-based Access & Session Auth (functions.php, index.php)                |
|  - OOP Domain Model: Student, Course, Module, Lecturer, Enrollment, Result    |
|  - Transaction Services: RegistrationService, Mark Calculation Engine         |
|  - OpenRouter AI Service: Diagnostic Engine & Chat Relay                      |
+-------------------------------------------------------------------------------+
                                      | PDO (Prepared Statements)
                                      v
+-------------------------------------------------------------------------------+
|                               DATABASE TIER (MySQL)                           |
|  Normalized Relational Schema (`university_db`) with 100% Backward Compat     |
+-------------------------------------------------------------------------------+
```

### 2.2 User Classes & System Roles

| User Role | Access Level | Responsibilities & Capabilities |
| :--- | :--- | :--- |
| **Administrator** | Full Administrative Governance | Registers students with auto-generated student numbers; manages curriculum qualifications and modules; assigns lecturers; oversees global academic performance; views system-wide student directory with live multi-attribute filters. |
| **Lecturer / Educator**| Academic Grading & Teaching | Views assigned teaching modules and class rosters; enters and edits quarterly marks (Q1 through Q4) for enrolled students; audits module performance. |
| **Student** | Self-Service Portal Access | Logs in with student number and password; views personal biographical profile; inspects registered modules and assigned lecturers; views quarterly academic results and running GPA averages; exports official registration slips and academic transcripts as PDF; consults the AI Academic Success Coach. |

---

## 3. Specific Functional Requirements (FR)

### Module 1: Authentication & Access Control
* **FR-01: Cryptographic Authentication with Unique Random Salt**  
  The system shall verify user credentials against stored hashes in the database using Bcrypt (`password_verify`). Every password hash is generated using PHP's native `password_hash()` algorithm which automatically produces a cryptographically secure 22-character pseudorandom salt (`$2y$10$[random_salt][hash]`). The random salt ensures that identical passwords yield distinct hashes, completely mitigating rainbow table lookups and pre-computed dictionary attacks.
* **FR-02: Role-Based Redirection**  
  Upon successful authentication, administrators shall be redirected to `admin/dashboard.php`, lecturers to `lecturer/dashboard.php`, and students to `student/dashboard.php`.
* **FR-03: Session Management & Fixation Protection**  
  The system shall regenerate session IDs upon login (`session_regenerate_id(true)`) to mitigate session fixation attacks. Unauthenticated requests to protected endpoints shall redirect to `index.php`.
* **FR-04: Password Change Self-Service**  
  Users shall be capable of changing their password by providing their current password and entering a new password meeting minimum length validation (minimum 6 characters).

### Module 2: Student Lifecycle & Registration
* **FR-05: Automated Student Number Generation**  
  The system shall automatically generate unique institutional student numbers in the format `ST` + 2-digit year + 5-digit sequence (e.g., `ST2600001`) via `RegistrationService::generateStudentNumber()`.
* **FR-06: Manual Student ID Ingestion (Backward Compatibility)**  
  The registration interface shall include a toggle permitting manual student ID entry to support onboarding legacy student identifiers without numbering collisions.
* **FR-07: Normalized Profile Management**  
  The system shall capture and validate first name, surname, South African National ID number, date of birth, contact email, and enrollment date, while automatically synchronizing legacy `full_name` and `student_id_number` columns.

### Module 3: Curriculum & Academic Allocation
* **FR-08: Course & Qualification Management**  
  Administrators shall be able to create, update, and manage academic qualifications specifying course code, qualification title, and duration.
* **FR-09: Shared Course-Module Junction**  
  The system shall support many-to-many associations between courses and modules (`course_modules`), permitting modules to be shared across multiple qualifications.
* **FR-10: Multi-Lecturer Allocation**  
  The system shall support allocating multiple lecturers to a single module (`module_lecturers`), with designation of primary lecturer and co-lecturer roles.

### Module 4: Enrollment & Academic Business Rules
* **FR-11: Single Active Course Rule**  
  The system shall strictly enforce that a student may only hold one `'Active'` course enrollment at any given point in time (`Enrollment::validateSingleActive()`).
* **FR-12: Enrollment History Retention**  
  Prior enrollments that transition to `'Completed'`, `'Suspended'`, or `'Withdrawn'` shall be permanently retained to preserve the student's historical transcript.

### Module 5: Performance Evaluation & Mark Capture
* **FR-13: Quarterly Mark Entry & Range Validation**  
  Lecturers and administrators shall capture academic performance marks across four quarterly assessment periods (Q1, Q2, Q3, Q4). The system shall reject any mark outside the valid range of `0.00` to `100.00`.
* **FR-14: Real-Time Composite Average Calculation**  
  The system shall automatically compute the running mathematical average across completed quarters using `ModuleResult::calculateAverage()`, rounding the outcome to 2 decimal places.

### Module 6: Artificial Intelligence & Academic Advisory
* **FR-15: One-Click Academic Performance Diagnostics**  
  The student portal shall provide a one-click performance analysis button that scans quarterly marks across all enrolled modules, flags subjects below 50%, and generates actionable study recommendations using the OpenRouter Free API (`google/gemini-2.0-flash-exp:free`).
* **FR-16: Resilient Offline Heuristic Fallback**  
  If the OpenRouter API endpoint is unreachable or an API key is not configured, the system shall seamlessly fall back to an internal heuristic rule engine, ensuring students always receive instant diagnostic feedback without errors.
* **FR-17: Interactive Academic Coach Chat**  
  Students shall be able to chat interactively with the AI Success Coach, with system prompts strictly bounding responses to course concepts, time management, revision timetables, and academic recovery strategies.

### Module 7: Reporting & Document Generation
* **FR-18: Client-Side PDF Document Generation (jsPDF)**  
  Students and administrators shall be capable of generating and downloading standardized, print-ready Registration Confirmation Slips and Official Academic Transcripts directly in PDF format using `jsPDF`.

---

## 4. Use Case Specifications

### Use Case UC-01: Admin Registers a New Student
* **Primary Actor**: Administrator
* **Preconditions**: Administrator is authenticated.
* **Standard Flow**:
  1. Administrator navigates to `admin/register_student.php`.
  2. System automatically generates the next sequential student number (e.g., `ST2600003`).
  3. Administrator inputs biographical details, selects qualification, and sets enrollment date.
  4. System executes an atomic database transaction:
     - Inserts record into `students` table (populating both normalized and legacy columns).
     - Inserts initial `'Active'` enrollment into `enrollments` table.
     - Provisions a user login account in `users` table with default hashed password.
  5. System commits transaction and displays confirmation banner with credentials.

### Use Case UC-02: Lecturer Captures Quarterly Marks
* **Primary Actor**: Lecturer / Educator
* **Preconditions**: Lecturer is authenticated.
* **Standard Flow**:
  1. Lecturer navigates to `lecturer/results.php` and selects an assigned module.
  2. System displays enrolled student roster with quarterly input fields (Q1 through Q4).
  3. Lecturer enters marks and submits the form.
  4. System validates that marks fall within `0.00` to `100.00`, computes running average, and persists to `module_results`.

### Use Case UC-03: Student Runs AI Performance Diagnostics & Exports Transcript
* **Primary Actor**: Student
* **Preconditions**: Student is authenticated.
* **Standard Flow**:
  1. Student navigates to `student/results.php`.
  2. System renders module grades, quarterly marks, and running composite average.
  3. Student clicks "Analyze Performance with AI Coach":
     - System compiles grade payload and requests diagnostic summary via `OpenRouterService`.
     - AI diagnoses strengths, highlights weak modules, and presents targeted study advice.
  4. Student clicks "Download Official Transcript (PDF)":
     - Client-side `jsPDF` engine generates formatted, official academic transcript.

---

## 5. Non-Functional Requirements (NFR)

### 5.1 Security Requirements (NFR-01)
* **Cryptographic Salting**: Passwords hashed using Bcrypt (`cost => 10`) with unique 22-character random salts per account.
* **SQL Injection Prevention**: 100% PDO prepared statements with bound parameters (`$stmt->bindParam` / `$stmt->execute`).
* **CSRF Protection**: Form submissions validated against session tokens.
* **XSS Immunization**: All rendered user inputs escaped with `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.

### 5.2 Backward Compatibility (NFR-02)
* Legacy columns (`full_name`, `student_id_number`, `course_of_study`, `academic_status`) maintained with automated synchronization.
* Legacy SQL queries and login credentials from Version 1.0 execute without syntax or schema errors.

### 5.3 Data Integrity & Constraints (NFR-03)
* Foreign keys with `ON DELETE CASCADE` prevent orphaned records in junction tables.
* Database-level `UNIQUE` constraints enforce unique student numbers, ID numbers, and usernames.

### 5.4 Performance & Usability (NFR-04)
* Instant client-side search and filtering across the student directory without full-page reloads.
* Responsive layouts across desktop and mobile viewports using Materialize CSS.
* Average page render time under 250ms.

---

## 6. Assessment Traceability Matrix

| Requirement | Implemented Architecture / Class | Database Table(s) | Primary Team Member | Verification Endpoint |
| :--- | :--- | :--- | :--- | :--- |
| **UML Modeling** | Formal Class Diagram & Multiplicities | N/A | Avuyile Mthembu | `documentation/STUDENT_PORTAL_SYSTEM_DESIGN.md` |
| **Relational ERD** | 3NF Relational Data Dictionary | `university_db` (8 tables) | Nomthandazo Khoza | `documentation/ENTITY_RELATIONSHIP_SPECIFICATION.md` |
| **Authentication & RBAC** | `classes/User.php`, `functions.php` | `users` | Avuyile Mthembu | `index.php` |
| **Auto Student Numbering** | `classes/RegistrationService.php` | `students` (`UNIQUE`) | Avuyile Mthembu | `admin/register_student.php` |
| **Lecturer Portal** | `lecturer/*.php`, `module_lecturers` | `lecturers`, `module_lecturers` | Avuyile & Qhama | `lecturer/dashboard.php` |
| **Quarterly Mark Capture** | `classes/ModuleResult.php` | `module_results` | Nomthandazo & Avuyile| `lecturer/results.php` |
| **AI Academic Success Coach** | `classes/OpenRouterService.php` | `module_results` | Avuyile Mthembu | `student/results.php` |
| **Frontend Layout & Switcher**| Materialize CSS, custom styling | N/A | Qhama Nodada | `index.php`, all portal views |
| **PDF Document Generation** | JavaScript `jspdf.min.js` | N/A | Qhama Nodada | `student/results.php` |
| **Live Web Deployment** | InfinityFree Hosting (PHP 8.2 / MySQL) | Production DB | Team Collaboration | [https://student-system.infinityfree.io](https://student-system.infinityfree.io) |

---

## 7. Submission Endorsement

```
================================================================================
                    SRS SUBMISSION & ASSESSMENT ENDORSEMENT
================================================================================

Submitted by:
• Mr Avuyile Mthembu (Full Stack Software Developer & Project Lead)
• Ms Nomthandazo Khoza (Database Administrator - DBA)
• Mr/Ms Qhama Nodada (Frontend Developer)

Directorate: Information Technology Business Solutions (ITBS)
Host Department: Department of Forestry, Fisheries and the Environment (DFFE)

Status: Complete - All 18 Functional Requirements Implemented & Verified in Production
================================================================================
```

---

> **Sequential Documentation Pipeline**  
> ← Previous: [02. Systems Analysis & SDLC Specification](SYSTEMS_ANALYSIS_AND_DESIGN_SDLC.md) | **Document 3 of 5** | Next: [04. System Design & Class Architecture](STUDENT_PORTAL_SYSTEM_DESIGN.md) →
