# STUDENT PORTAL SYSTEM
## System Design, Class Relationships & Technical Architecture

---

| **Metadata** | **Details** |
| :--- | :--- |
| **Document Title** | Student Portal System - System Design and Class Relationships |
| **Project Nature** | **Internship Technical Practical Assessment / Competency Evaluation** |
| **Prepared by** | **Mr Avuyile Mthembu** |
| **Position** | **Intern: Software Developer** |
| **Directorate** | **Information Technology Business Solutions (ITBS)** |
| **Unit** | **ICT Services: Infrastructure & Security Support** |
| **Branch** | **Corporate Management Services (CMS)** |
| **Host Department** | **Department of Forestry, Fisheries and the Environment (DFFE)** |
| **Version** | 2.0 (With 100% Backward Compatibility) |
| **Status** | Completed, Tested & Implemented |
| **Live Production URL** | [https://student-system.infinityfree.io/index.php](https://student-system.infinityfree.io/index.php) |

---

## 1. System Overview & Assessment Aim

The **Student Portal System** (Live demo: [https://student-system.infinityfree.io/index.php](https://student-system.infinityfree.io/index.php)) is an academic records management system developed as a **practical assessment task** assigned by the technical mentor in the Directorate: Information Technology Business Solutions (ITBS). 

The goal of the assignment is to demonstrate practical software engineering competencies:
- **System Analysis & Modeling**: Translating academic management requirements into formal UML diagrams.
- **Relational Architecture**: Decomposing a legacy flat student table into a normalized 3NF relational database.
- **Object-Oriented Programming (OOP)**: Building encapsulated PHP domain models (`Student`, `Course`, `Module`, `Lecturer`, `Enrollment`, `ModuleResult`) and transactional services (`RegistrationService`).
- **Zero-Regressive Backward Compatibility**: Ensuring existing queries, session logic, and login credentials continue functioning without interruption.

### Key Architectural Tenets
1. **Separation of Concerns**: Domain models represent distinct institutional entities (`Student`, `Course`, `Module`, `Lecturer`, `Enrollment`, `ModuleResult`), coordinated by dedicated services (`RegistrationService`).
2. **Data Normalization with Zero Downtime Migration**: New normalized attributes and junction tables support many-to-many relationships while preserving legacy column access.
3. **100% Backward Compatibility**: All legacy queries, reporting templates, session keys, and database tables (`students`, `users`) continue functioning without breaking existing integrations.
4. **Institutional Security & Integrity**: Role-based access control (RBAC), prepared statements (PDO), cryptographic password hashing (bcrypt), and transactional consistency.

---

## 2. UML Class Diagram

The UML Class Diagram illustrates the structural design of the Student Portal System, detailing main classes, attributes, visibility, methods, multiplicities, and associative relationships.

```mermaid
classDiagram
    direction TB

    class Student {
        -int id
        -string studentNumber
        -string firstName
        -string surname
        -string idNumber
        -string email
        -date dateOfBirth
        -string fullName
        -string studentIdNumber
        -string courseOfStudy
        -date enrollmentDate
        -string academicStatus
        +__construct(data: array)
        +getId(): int
        +getStudentNumber(): string
        +setStudentNumber(num: string): void
        +getFirstName(): string
        +setFirstName(fn: string): void
        +getSurname(): string
        +setSurname(sn: string): void
        +getFullName(): string
        +getIdNumber(): string
        +setIdNumber(idNum: string): void
        +getEmail(): string
        +setEmail(email: string): void
        +getDateOfBirth(): date
        +setDateOfBirth(dob: date): void
        +getActiveEnrollment(pdo: PDO): array
        +getEnrollmentHistory(pdo: PDO): array
        +getModuleResults(pdo: PDO, year: int): array
        +toArray(): array
        +static findById(pdo: PDO, id: int): Student
        +static findByStudentNumber(pdo: PDO, num: string): Student
    }

    class Course {
        -int courseId
        -string courseCode
        -string courseName
        -string duration
        +__construct(data: array)
        +getCourseId(): int
        +getCourseCode(): string
        +setCourseCode(code: string): void
        +getCourseName(): string
        +setCourseName(name: string): void
        +getDuration(): string
        +setDuration(duration: string): void
        +getModules(pdo: PDO): array
        +getStudents(pdo: PDO, status: string): array
        +addModule(pdo: PDO, moduleId: int, semester: string): bool
        +static getAll(pdo: PDO): array
        +static findById(pdo: PDO, id: int): Course
        +static findByCode(pdo: PDO, code: string): Course
    }

    class Module {
        -int moduleId
        -string moduleCode
        -string moduleName
        -int credits
        +__construct(data: array)
        +getModuleId(): int
        +getModuleCode(): string
        +setModuleCode(code: string): void
        +getModuleName(): string
        +setModuleName(name: string): void
        +getCredits(): int
        +setCredits(credits: int): void
        +getLecturers(pdo: PDO): array
        +getCourses(pdo: PDO): array
        +assignLecturer(pdo: PDO, lecturerId: int, isPrimary: int): bool
        +static getAll(pdo: PDO): array
        +static findById(pdo: PDO, id: int): Module
        +static findByCode(pdo: PDO, code: string): Module
    }

    class Lecturer {
        -int lecturerId
        -string firstName
        -string surname
        -string email
        +__construct(data: array)
        +getLecturerId(): int
        +getFirstName(): string
        +setFirstName(fn: string): void
        +getSurname(): string
        +setSurname(sn: string): void
        +getFullName(): string
        +getEmail(): string
        +setEmail(email: string): void
        +getModules(pdo: PDO): array
        +static getAll(pdo: PDO): array
        +static findById(pdo: PDO, id: int): Lecturer
    }

    class Enrollment {
        -int enrollmentId
        -int studentId
        -int courseId
        -date registrationDate
        -string status
        +__construct(data: array)
        +getEnrollmentId(): int
        +getStudentId(): int
        +getCourseId(): int
        +getRegistrationDate(): date
        +getStatus(): string
        +setStatus(status: string): void
        +validateSingleActive(pdo: PDO): bool
        +save(pdo: PDO): bool
        +static getActiveForStudent(pdo: PDO, studentId: int): array
        +static getHistoryForStudent(pdo: PDO, studentId: int): array
        +static findById(pdo: PDO, id: int): Enrollment
    }

    class ModuleResult {
        -int markId
        -int studentId
        -int moduleId
        -int academicYear
        -float quarter1
        -float quarter2
        -float quarter3
        -float quarter4
        -float average
        +__construct(data: array)
        +getMarkId(): int
        +getQuarter1(): float
        +setQuarter1(mark: float): void
        +getQuarter2(): float
        +setQuarter2(mark: float): void
        +getQuarter3(): float
        +setQuarter3(mark: float): void
        +getQuarter4(): float
        +setQuarter4(mark: float): void
        +getAverage(): float
        +calculateAverage(): float
        +getGradeStatus(): array
        +save(pdo: PDO): bool
        +static validateMark(mark: float, field: string): void
        +static getResultsForStudent(pdo: PDO, studentId: int, year: int): array
    }

    class RegistrationService {
        +static generateStudentNumber(pdo: PDO, prefix: string): string
        +static registerStudent(pdo: PDO, data: array): array
    }

    class User {
        -int id
        -string username
        -string password
        -string role
        -string studentIdNumber
        +verifyPassword(plainPassword: string): bool
        +updatePassword(pdo: PDO, newPassword: string): bool
        +static findById(pdo: PDO, id: int): User
        +static findByUsername(pdo: PDO, username: string): User
        +static findByStudentNumber(pdo: PDO, num: string): User
        +static changePassword(pdo: PDO, userId: int, curr: string, new: string, conf: string): array
        +static adminResetPassword(pdo: PDO, studentNumber: string, new: string): array
    }

    Student "1" --> "1..*" Enrollment : has history
    Course "1" --> "0..*" Enrollment : admits
    Course "1..*" <--> "1..*" Module : shares (CourseModule)
    Module "1..*" <--> "1..*" Lecturer : taught by (ModuleLecturer)
    Student "1" --> "0..*" ModuleResult : obtains
    Module "1" --> "0..*" ModuleResult : evaluates
    Student "1" --> "0..1" User : authenticates as
    RegistrationService ..> Student : registers & generates ID
    RegistrationService ..> Enrollment : creates initial active
    RegistrationService ..> User : provisions credentials
```

---

## 3. Main Classes and Attributes

### 3.1 `Student`
Represents an enrolled scholar within the institution.
*   **Attributes**:
    *   `studentNumber` *(string, unique)*: Auto-generated institutional student identifier.
    *   `firstName` *(string)*: Student's legal first name.
    *   `surname` *(string)*: Student's legal family name.
    *   `idNumber` *(string, unique)*: South African National Identification number.
    *   `email` *(string, unique)*: Official contact email address.
    *   `dateOfBirth` *(date)*: Date of birth (ISO YYYY-MM-DD).
*   **Backward-Compatible Facade Attributes**:
    *   `fullName`: Computed dynamically as `firstName . ' ' . surname` or mapped to legacy column `full_name`.
    *   `studentIdNumber`: Direct alias for `studentNumber`.
    *   `courseOfStudy`: Reflects the currently active enrolled course name.
    *   `academicStatus`: Reflects current enrollment/academic status (`Active`, `Pending`, `Graduated`, `Dropped`).

### 3.2 `Course`
Represents an academic qualification / degree programme offered by the department.
*   **Attributes**:
    *   `courseId` *(int, Primary Key)*: Internal identifier.
    *   `courseCode` *(string, unique)*: Official programme code (e.g., `SD101`, `IT101`, `CS101`).
    *   `courseName` *(string)*: Formal qualification title (e.g., *Systems Development*).
    *   `duration` *(string)*: Prescribed time for completion (e.g., `3 Years`).

### 3.3 `Module`
Represents a subject / unit of learning within the institutional curriculum.
*   **Attributes**:
    *   `moduleId` *(int, Primary Key)*: Internal identifier.
    *   `moduleCode` *(string, unique)*: Unique subject code (e.g., `DBS201`, `SEP202`, `WAA203`).
    *   `moduleName` *(string)*: Full descriptive subject title.
    *   `credits` *(int)*: National Qualifications Framework (NQF) credit weight (default: 15).

### 3.4 `Lecturer`
Represents academic staff responsible for course instructional delivery and assessments.
*   **Attributes**:
    *   `lecturerId` *(int, Primary Key)*: Staff identifier.
    *   `firstName` *(string)*: Lecturer's first name.
    *   `surname` *(string)*: Lecturer's family name.
    *   `email` *(string, unique)*: Official institutional departmental email address.

### 3.5 `Enrollment`
Tracks academic course registrations, maintaining active status and registration history.
*   **Attributes**:
    *   `enrollmentId` *(int, Primary Key)*: Unique registration identifier.
    *   `studentId` *(int, Foreign Key)*: Linked to `students.id`.
    *   `courseId` *(int, Foreign Key)*: Linked to `courses.course_id`.
    *   `registrationDate` *(date)*: Date the registration was formalized.
    *   `status` *(string)*: Current state (`Active`, `Completed`, `Suspended`, `Withdrawn`).

### 3.6 `ModuleResult`
Stores quarterly assessments and calculated overall academic outcomes.
*   **Attributes**:
    *   `markId` *(int, Primary Key)*: Unique assessment result identifier.
    *   `studentId` *(int, Foreign Key)*: Student evaluated.
    *   `moduleId` *(int, Foreign Key)*: Module evaluated.
    *   `academicYear` *(int)*: Academic year (e.g., `2026`).
    *   `quarter1` *(decimal 5,2)*: Quarter 1 assessment score (0 - 100%).
    *   `quarter2` *(decimal 5,2)*: Quarter 2 assessment score (0 - 100%).
    *   `quarter3` *(decimal 5,2)*: Quarter 3 assessment score (0 - 100%).
    *   `quarter4` *(decimal 5,2)*: Quarter 4 assessment score (0 - 100%).
    *   `average` *(decimal 5,2)*: Calculated composite average of quarterly marks.

### 3.7 `User`
Manages system authentication credentials, role-based authorization, and secure password updates.
*   **Attributes**:
    *   `id` *(int, Primary Key)*: Unique account identifier.
    *   `username` *(string, unique)*: Login identifier (admin name or student number).
    *   `password` *(string)*: Secure Bcrypt cryptographic hash containing an automatic, cryptographically secure 22-character pseudorandom salt (`$2y$10$[random_salt][hash]`). Even identical plain-text passwords produce different stored hashes, protecting against rainbow table attacks.
    *   `role` *(string)*: Institutional role (`admin`, `student`).
    *   `studentIdNumber` *(string, Foreign Key, nullable)*: Associated student number for student accounts.
*   **Methods**:
    *   `verifyPassword(string $plainPassword): bool`: Validates plain-text credential against the stored bcrypt hash using PHP's timing-attack safe `password_verify()`, extracting the embedded salt automatically.
    *   `updatePassword(PDO $pdo, string $newPassword): bool`: Hashes new password with a fresh random salt (`PASSWORD_DEFAULT` / `PASSWORD_BCRYPT`) and updates the database.
    *   `static changePassword(PDO $pdo, int $userId, ...): array`: Self-service password modification.
    *   `static adminResetPassword(PDO $pdo, string $studentNumber, ...): array`: Administrative reset mechanism.

---

## 4. Business Rules and System Logic Verification

### Rule 1: Student Enrollment Rule
> **Rule**: *"A student may have only one active course enrollment at a time. The Enrollment class maintains registration history."*
*   **Implementation & Verification**:
    *   The `Enrollment::validateSingleActive(PDO $pdo)` method performs an atomic query prior to any write. If a record already exists with `status = 'Active'` for that `student_id`, an exception is thrown:
        ```text
        "Business Rule Violation: Student ID X already has an active course enrollment. A student may have only one active course enrollment at a time."
        ```
    *   Prior course registrations transition to `'Completed'`, `'Suspended'`, or `'Withdrawn'`, thereby preserving the student's complete historical transcript.

### Rule 2: Automatic Student Number Generation
> **Rule**: *"RegistrationService generates a unique student number upon successful registration. The database must enforce uniqueness."*
*   **Implementation & Verification**:
    *   `RegistrationService::generateStudentNumber(PDO $pdo, $prefix = 'ST')` calculates the current 2-digit academic year (`date('y')`), inspects existing records, increments the sequence, and runs a collision-detection verification loop against the database.
    *   The database enforces uniqueness through `UNIQUE KEY` constraints on both `student_number` and `student_id_number`.

### Rule 3: Shared Modules and Multiple Lecturers (Many-to-Many)
> **Rule**: *"Modules may be shared across courses and taught by multiple lecturers, supporting many-to-many relationships."*
*   **Implementation & Verification**:
    *   **Course $\leftrightarrow$ Module**: Modeled via `course_modules` junction table (`course_id`, `module_id`, `semester`). Example: `DBS201` is shared between *Systems Development* and *Information Technology*.
    *   **Module $\leftrightarrow$ Lecturer**: Modeled via `module_lecturers` junction table (`module_id`, `lecturer_id`, `is_primary`). Example: `DBS201` has Dr N. Sithole (Primary) and Prof K. Naidoo (Co-Lecturer).

### Rule 4: Quarterly Marks & Average Calculation
> **Rule**: *"ModuleResult stores four quarterly marks for one student, one module, and one academic year. The calculateAverage() method calculates the average of the four marks."*
*   **Implementation & Verification**:
    *   `ModuleResult::calculateAverage()` aggregates the submitted non-null quarterly marks:
        $$\text{Average} = \frac{\sum_{i=1}^{n} \text{Quarter}_i}{n}$$
    *   Invoked automatically prior to persistence in `save()`, ensuring the database always stores exact mathematical outcomes rounded to 2 decimal places.

### Rule 5: Mark Validation Range
> **Rule**: *"Each quarterly mark must fall within the institution's permitted range, typically 0-100."*
*   **Implementation & Verification**:
    *   `ModuleResult::validateMark($mark)` throws an `InvalidArgumentException` if a value $< 0.00$ or $> 100.00$ is provided.
    *   Client-side HTML5 input constraints (`min="0" max="100" step="0.01"`) provide immediate user guidance.

---

## 5. Backward Compatibility Strategy Matrix

To protect existing data and avoid breaking legacy code or reports, the following compatibility layer was engineered:

| Component | Legacy State | Version 2.0 Design | Backward Compatibility Solution |
| :--- | :--- | :--- | :--- |
| **`students` Table** | `full_name`, `student_id_number`, `course_of_study`, `academic_status` | Added `student_number`, `first_name`, `surname`, `id_number` | Preserved all original columns. SQL update script synchronized existing records (`first_name = SUBSTRING_INDEX(full_name, ' ', 1)`). Code writes to both columns. |
| **Authentication (`users`)** | Logged in with `username` & `student_id_number` | Normalized authentication referencing `student_number` | Foreign key references `student_id_number` unchanged; usernames match `student_number`. |
| **Registration Form** | Single "Full Name" and manual "Student ID" input | First Name, Surname, National ID, and Auto-generation toggle | Default toggle runs `RegistrationService`; unchecking allows manual entry for legacy ID migration. |
| **Student List & Search** | Searched only Name and ID | Live search table | Table filters simultaneously across Full Name, Student Number, National ID, and Course. |
| **PDF Slips & Reports** | Relied on `student_id_number` and `full_name` | Enhanced with duration and national ID | Kept exact function signatures; enhanced report cards with official department branding. |

---

## 6. Database Physical Schema (Entity Relationship)

```text
+-----------------------------------------------------------------------------------+
|                                  students                                         |
+-----------------------------------------------------------------------------------+
| PK  id                     INT(11) AUTO_INCREMENT                                 |
|     student_number         VARCHAR(50) NULL                                       |
|     first_name             VARCHAR(100) NULL                                      |
|     surname                VARCHAR(100) NULL                                      |
|     id_number              VARCHAR(50) NULL                                       |
|     full_name              VARCHAR(255) NOT NULL                                  |
| UK  student_id_number      VARCHAR(50) NOT NULL                                   |
| UK  email                  VARCHAR(255) NOT NULL                                  |
|     date_of_birth          DATE NOT NULL                                          |
|     course_of_study        VARCHAR(255) NOT NULL                                  |
|     enrollment_date        DATE NOT NULL                                          |
|     academic_status        VARCHAR(50) DEFAULT 'Active'                           |
|     registration_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP                    |
+-----------------------------------------------------------------------------------+
         |                                           |
         | 1:N                                       | 1:N
         v                                           v
+--------------------------+               +----------------------------------------+
|       enrollments        |               |             module_results             |
+--------------------------+               +----------------------------------------+
| PK  enrollment_id (INT)  |               | PK  mark_id (INT)                      |
| FK  student_id (INT)     |               | FK  student_id (INT)                   |
| FK  course_id (INT)      |               | FK  module_id (INT)                    |
|     registration_date    |               |     academic_year (INT)                |
|     status (Active/...)  |               |     quarter1, quarter2, quarter3, q4   |
+--------------------------+               |     average (DECIMAL 5,2)              |
         ^                                 +----------------------------------------+
         | N:1                                       ^
+--------------------------+                         | N:1
|         courses          |                         |
+--------------------------+                         |
| PK  course_id (INT)      |                         |
| UK  course_code (VARCHAR)|                         |
|     course_name (VARCHAR)|                         |
|     duration (VARCHAR)   |                         |
+--------------------------+                         |
         | 1:N                                       |
         v                                           |
+--------------------------+               +----------------------------------------+
|      course_modules      |               |                modules                 |
+--------------------------+               +----------------------------------------+
| PK  id (INT)             |               | PK  module_id (INT)                    |
| FK  course_id (INT)      |-------------->| UK  module_code (VARCHAR)              |
| FK  module_id (INT)      |               |     module_name (VARCHAR)              |
|     semester (VARCHAR)   |               |     credits (INT)                      |
+--------------------------+               +----------------------------------------+
                                                     | 1:N
                                                     v
                                           +----------------------------------------+
                                           |            module_lecturers            |
                                           +----------------------------------------+
                                           | PK  id (INT)                           |
                                           | FK  module_id (INT)                    |
                                           | FK  lecturer_id (INT)                  |
                                           |     is_primary (TINYINT)               |
                                           +----------------------------------------+
                                                     ^
                                                     | N:1
                                           +----------------------------------------+
                                           |               lecturers                |
                                           +----------------------------------------+
                                           | PK  lecturer_id (INT)                  |
                                           |     first_name (VARCHAR)               |
                                           |     surname (VARCHAR)                  |
                                           | UK  email (VARCHAR)                    |
                                           +----------------------------------------+
```

---

## 7. Submission & Assessment Verification

This technical design artifact, UML diagrams, and corresponding codebase have been implemented and submitted for internship technical evaluation, demonstrating full-stack competency, relational integrity, regression resilience, and mathematical accuracy.

```
Submitted by:
Mr Avuyile Mthembu
Intern: Software Developer
Directorate: Information Technology Business Solutions (ITBS)
Unit: ICT Services: Infrastructure & Security Support
Branch: Corporate Management Services (CMS)
Host Department: Department of Forestry, Fisheries and the Environment (DFFE)

Assessment Task: Mentor Evaluation – UML Design, Relational Normalization & OOP Implementation
```
