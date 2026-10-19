# STUDENT PORTAL SYSTEM
## Software Requirements Specification (SRS)
### Internship Practical Technical Assessment & Evaluation

---

| **Document Metadata** | **Specification Details** |
| :--- | :--- |
| **Document Title** | Software Requirements Specification (SRS) |
| **System Name** | Student Portal System |
| **Project Nature** | **Internship Technical Assessment / Practical Competency Evaluation Task** |
| **Candidate / Author** | **Mr Avuyile Mthembu** |
| **Position** | **Intern: Software Developer** |
| **Directorate** | **Information Technology Business Solutions (ITBS)** |
| **Unit** | **ICT Services: Infrastructure & Security Support** |
| **Branch** | **Corporate Management Services (CMS)** |
| **Host Department** | **Department of Forestry, Fisheries and the Environment (DFFE)** |
| **Standard Followed** | IEEE Standard 830-1998 (Recommended Practice for Software Requirements Specifications) |
| **Architecture Version** | 2.0 (Relational Normalization with 100% Backward Compatibility) |
| **Live Production Instance** | [https://student-system.infinityfree.io/index.php](https://student-system.infinityfree.io/index.php) |

---

## 1. Introduction

### 1.1 Purpose & Assessment Scope
This **Software Requirements Specification (SRS)** captures the complete functional and non-functional requirements for the **Student Portal System**. The project was assigned as a practical software engineering assessment task by the development mentor within the Directorate: Information Technology Business Solutions (ITBS).

The objective of this assessment is to evaluate the intern's capability to:
1. Conduct software requirements analysis and translate domain rules into formal technical specifications.
2. Produce professional **UML Class Diagrams** and **Entity Relationship Diagrams (ERDs)**.
3. Decompose a flat, single-table legacy database into a normalized **Third Normal Form (3NF)** relational database schema.
4. Implement clean, maintainable **Object-Oriented Programming (OOP)** domain models and services in PHP.
5. Engineer a backward compatibility layer preserving existing student records, queries, authentication sessions, and reporting templates without regressions.
6. Deploy a working, responsive full-stack prototype to a live web hosting environment.

### 1.2 Intended Audience
This document is prepared for:
* **Technical Mentor & Senior Developers (ITBS)**: For evaluating system requirements coverage, architectural decisions, and code quality against standard software development criteria.
* **Academic Supervisors & Assessors**: For reviewing documentation artifacts, UML modeling rigor, and internship task completion.

---

## 2. Overall Description

### 2.1 Product Perspective
The system evolves an existing, flat-file style student records directory into an academic management platform. The application operates in a LAMP/WAMP environment (Apache, MySQL, PHP) with a Material Design user interface.

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
+-------------------------------------------------------------------------------+
                                      | PDO (Prepared Statements)
                                      v
+-------------------------------------------------------------------------------+
|                               DATABASE TIER (MySQL)                           |
|  Normalized Relational Schema (`university_db`) with 100% Backward Compat     |
+-------------------------------------------------------------------------------+
```

### 2.2 User Classes and Characteristics

| User Role | Access Level | Responsibilities & Capabilities |
| :--- | :--- | :--- |
| **Administrator** | Full Administrative Access | Registers students with auto-generated student numbers; manages curriculum qualifications and modules; assigns lecturers; captures and updates quarterly marks; views system-wide student directory with live filters. |
| **Student** | Self-Service Portal Access | Logs in with student number and password; views personal biographical profile; inspects registered modules and assigned academic lecturers; views quarterly academic results and running GPA averages; exports official registration slips and academic transcripts as PDF. |

---

## 3. Specific Functional Requirements (FR)

### Module 1: Authentication & Access Control
* **FR-01: Cryptographic Authentication with Unique Random Salt**  
  The system shall verify user credentials against stored hashes in the database using Bcrypt (`password_verify`). Every password hash is generated using PHP's native `password_hash()` algorithm which automatically produces a cryptographically secure 22-character pseudorandom salt (`$2y$10$[random_salt][hash]`). The random salt ensures that identical passwords yield distinct hashes, completely mitigating rainbow table lookups and pre-computed dictionary attacks.
* **FR-02: Role-Based Redirection**  
  Upon successful authentication, administrators shall be redirected to `admin/dashboard.php` and students shall be redirected to `student/dashboard.php`.
* **FR-03: Session Management & Security**  
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
* **FR-09: Module Management & Shared Modules**  
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
  Administrators shall capture academic performance marks across four quarterly assessment periods (Q1, Q2, Q3, Q4). The system shall reject any mark outside the valid range of `0.00` to `100.00`.
* **FR-14: Real-Time Composite Average Calculation**  
  The system shall automatically compute the running mathematical average across completed quarters using `ModuleResult::calculateAverage()`, rounding the outcome to 2 decimal places.

### Module 6: Reporting & Document Generation
* **FR-15: Registration Confirmation Slip Export**  
  Students and administrators shall be capable of generating a standardized, print-ready Registration Confirmation Slip with client-side PDF export (`jsPDF`).
* **FR-16: Official Academic Transcript Export**  
  The student portal shall generate a comprehensive Academic Transcript detailing enrolled modules, quarterly performance scores, calculated averages, and overall academic status, exportable to PDF.

---

## 4. Use Case Specifications

### Use Case UC-01: Admin Registers a New Student
* **Primary Actor**: Administrator
* **Preconditions**: Administrator is logged into the admin portal.
* **Standard Flow**:
  1. Administrator navigates to `admin/register_student.php`.
  2. System automatically generates the next sequential student number (e.g., `ST2600003`).
  3. Administrator inputs First Name, Surname, National ID, Email, Date of Birth, selects Course, and sets Enrollment Date.
  4. Administrator submits the registration form.
  5. System initiates a database transaction:
     - Inserts record into `students` table (populating both normalized and legacy columns).
     - Inserts initial `'Active'` enrollment into `enrollments` table.
     - Provisions a user login account in `users` table with default hashed password.
  6. System commits transaction and displays confirmation banner with generated credentials.
* **Postconditions**: Student record, active course enrollment, and user login credentials exist in the database.

### Use Case UC-02: Student Accesses Academic Transcript
* **Primary Actor**: Student
* **Preconditions**: Student is authenticated with their student number and password.
* **Standard Flow**:
  1. Student clicks "Academic Results" on `student/dashboard.php`.
  2. System retrieves student ID from session and queries `module_results` joined with `modules` and `lecturers`.
  3. System renders transcript table displaying Module Code, Module Name, Q1–Q4 marks, and calculated final average.
  4. Student clicks "Download Official Transcript (PDF)".
  5. Client-side PDF engine generates and downloads the formatted document.

---

## 5. Non-Functional Requirements (NFR)

### 5.1 Security Requirements (NFR-01)
* **Cryptographic Hashing with Random Salt**: Passwords must be hashed using PHP's native `password_hash()` with the `PASSWORD_BCRYPT` algorithm (cost factor 10). Each generated hash automatically embeds a unique, cryptographically secure 22-character pseudorandom salt (`$2y$10$[22-char-salt][hash]`). This ensures that identical plain-text passwords produce completely distinct hash strings, neutralizing rainbow table and pre-computed hash lookup attacks. Password verification must use timing-attack resilient `password_verify()`.
* **SQL Injection Prevention**: All database interactions must use PDO prepared statements with bound parameters (`bindParam` / `execute([params])`).
* **Cross-Site Request Forgery (CSRF)**: State-changing HTTP POST operations must enforce CSRF token validation via `validate_csrf_token()`.
* **Cross-Site Scripting (XSS)**: All user-supplied output rendered in HTML views must be escaped using `htmlspecialchars($data, ENT_QUOTES, 'UTF-8')`.

### 5.2 Backward Compatibility (NFR-02)
* The relational schema must maintain legacy columns in the `students` table (`full_name`, `student_id_number`, `course_of_study`, `academic_status`).
* Existing legacy queries and scripts written for Version 1.0 must continue to execute without syntax or schema errors.

### 5.3 Data Integrity & Constraints (NFR-03)
* Foreign keys must enforce referential integrity with appropriate `ON DELETE CASCADE` rules on child entities (`course_modules`, `module_lecturers`, `enrollments`, `module_results`).
* Unique constraints must prevent duplicate student numbers, national IDs, and user account usernames.

### 5.4 Performance & Usability (NFR-04)
* The administrative student directory must provide instant client-side multi-attribute search and filtering without triggering full page reloads.
* The system interface must be responsive across desktop and tablet viewports using a Material Design layout system.

---

## 6. Assessment Traceability Matrix

This matrix maps the mentor's practical assessment requirements directly to the implemented classes, database artifacts, and verified endpoints:

| Assessment Requirement | Implemented Architecture / Class | Database Table(s) | Verification Endpoint |
| :--- | :--- | :--- | :--- |
| **UML Modeling** | Formal Class Diagram & Multiplicities | N/A | `documentation/STUDENT_PORTAL_SYSTEM_DESIGN.md` |
| **Entity Relationship (ERD)** | 3NF Relational Data Dictionary | `university_db` (8 tables) | `documentation/ENTITY_RELATIONSHIP_SPECIFICATION.md` |
| **Authentication & RBAC** | `classes/User.php`, `functions.php` | `users` | `index.php` |
| **Student Domain Model** | `classes/Student.php` | `students` | `admin/student_profile.php` |
| **Auto Student Numbering** | `classes/RegistrationService.php` | `students` (`UNIQUE`) | `admin/register_student.php` |
| **Single Active Course Rule** | `classes/Enrollment.php` | `enrollments` | `admin/student_profile.php` |
| **Shared Course Modules** | `classes/Course.php`, `classes/Module.php` | `course_modules` | `admin/courses.php`, `admin/modules.php` |
| **Lecturer Assignments** | `classes/Lecturer.php`, `classes/Module.php` | `module_lecturers` | `admin/lecturers.php` |
| **Mark Capture & Average** | `classes/ModuleResult.php` | `module_results` | `admin/results.php` |
| **PDF Document Generation** | JavaScript `jspdf.min.js` | N/A | `student/results.php`, `student/registration_slip.php` |
| **Live Web Deployment** | InfinityFree Hosting (PHP 8.2 / MySQL) | Production DB | [https://student-system.infinityfree.io](https://student-system.infinityfree.io) |

---

## 7. Submission Sign-off

```
Submitted by:
Mr Avuyile Mthembu
Intern: Software Developer
Directorate: Information Technology Business Solutions (ITBS)
Unit: ICT Services: Infrastructure & Security Support
Branch: Corporate Management Services (CMS)
Host Department: Department of Forestry, Fisheries and the Environment (DFFE)

Task Status: Practical Assessment Complete – UML, Relational Schema, OOP & Full-Stack Application Implemented
```
