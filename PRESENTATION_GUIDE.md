# Student Portal System: Team Presentation & Project Guide
### Comprehensive Showcase & Demonstration Manual for Assessment Panels and Stakeholders

---

> **Presentation Overview**  
> This guide details the Student Portal System practical assessment delivered at the Department of Forestry, Fisheries and the Environment (DFFE) under IT Business Solutions (ITBS). It covers project background, problem analysis, the 4-pillar project plan, systems architecture, speaker notes for the 3-person team, and the live demonstration workflow.

---

## Quick Presentation Summary Card

| Item | What to say to the panel |
| :--- | :--- |
| **System Name** | **Student Portal System** |
| **What is it?** | A web-based university portal where students check marks and get study advice, lecturers enter term marks, and administrators register students. |
| **Why was it built?** | As a 2-week practical internship assessment task at the Department of Forestry, Fisheries and the Environment (DFFE) under the IT Business Solutions (ITBS) team. |
| **The Team (3 Interns)** | • **Avuyile Mthembu** — Full Stack Developer & Team Lead (PHP Code, Login & AI)<br>• **Nomthandazo Khoza** — Database Administrator (Database Tables, ERD & Rules)<br>• **Qhama Nodada** — Frontend Developer (Screens, Buttons, Search & PDF Downloads) |
| **What did it cost?** | **Under R 1,000** (Actual cash spent: R 750 on mobile data and backups). All software used was 100% free and open source. |
| **Where does it run?** | • **On the web**: [https://student-system.infinityfree.io/index.php](https://student-system.infinityfree.io/index.php)<br>• **On local laptops**: [http://localhost/university_system/index.php](http://localhost/university_system/index.php) |

---

## Table of Contents

1. [What is this Project and Why Did We Build It?](#1-what-is-this-project-and-why-did-we-build-it)
2. [What Real Problem Does It Solve? (Before vs. After)](#2-what-real-problem-does-it-solve-before-vs-after)
3. [The Project Plan: Scope, Time, Cost, People](#3-the-project-plan-scope-time-cost-people)
   - [3.1 Scope (What we built vs. What we left out)](#31-scope-what-we-built-vs-what-we-left-out)
   - [3.2 Time (Sprint Timeline: Week 1 & Week 2)](#32-time-sprint-timeline-week-1--week-2)
   - [3.3 Cost (How we kept it under R 1,000)](#33-cost-how-we-kept-it-under-r-1000)
   - [3.4 People (Who did what)](#34-people-who-did-what)
4. [Methodology: Systems Analysis & Design (The College Basics)](#4-methodology-systems-analysis--design-the-college-basics)
   - [Step 1: Planning & Feasibility](#step-1-planning--feasibility)
   - [Step 2: Analysis (How Data Flows)](#step-2-analysis-how-data-flows)
   - [Step 3: Database Design & Normalization (1NF, 2NF, 3NF)](#step-3-database-design--normalization-1nf-2nf-3nf)
   - [Step 4: System Architecture & UML Class Diagram](#step-4-system-architecture--uml-class-diagram)
   - [Step 5: Testing & Going Live](#step-5-testing--going-live)
5. [System Diagrams Overview](#5-system-diagrams-overview)
   - [5.1 The Data Flow Diagram (DFD)](#51-the-data-flow-diagram-dfd)
   - [5.2 The Entity Relationship Diagram (ERD)](#52-the-entity-relationship-diagram-erd)
   - [5.3 The UML Class Diagram](#53-the-uml-class-diagram)
6. [Speaker Scripts for the 3 Team Members](#6-speaker-scripts-for-the-3-team-members)
   - [Speaker 1: Avuyile Mthembu (Team Lead & Full Stack Developer)](#speaker-1-avuyile-mthembu-team-lead--full-stack-developer)
   - [Speaker 2: Nomthandazo Khoza (Database Administrator - DBA)](#speaker-2-nomthandazo-khoza-database-administrator---dba)
   - [Speaker 3: Qhama Nodada (Frontend Developer & UI/UX)](#speaker-3-qhama-nodada-frontend-developer--uiux)
7. [Step-by-Step 5-Minute Live Demo Walkthrough](#7-step-by-step-5-minute-live-demo-walkthrough)
8. [Common Questions the Panel Might Ask (And Safe Answers)](#8-common-questions-the-panel-might-ask-and-safe-answers)

---

# 1. What is this Project and Why Did We Build It?

### What is it?
The **Student Portal System** is an easy-to-use website for managing university students, courses, subjects (called modules), and quarterly marks. 

It has three separate areas (portals):
1. **For the Student**: Log in, see your personal details, see your subjects, check marks for Quarters 1 through 4, get automated study advice if a mark is low, and click a button to download your official academic transcript as a PDF.
2. **For the Lecturer**: Log in, see the subjects you have been assigned to teach, see the list of students in your class, and type in their marks for each quarter.
3. **For the Administrator**: Log in, register new students (the system gives them a clean student number automatically, like `ST2600001`), add new courses, and manage lecturers.

### Why did we build it?
Our technical mentor at **DFFE (Directorate: IT Business Solutions)** gave us a practical challenge:
> *"Take a basic, messy student records system, treat it like a real workplace project, and build a clean, modern, secure system in two weeks using proper college Systems Analysis and Design methods."*

We wanted to prove four things:
1. We can design a clean relational database using proper rules (Third Normal Form).
2. We can write clean, organized Object-Oriented code in PHP.
3. We can build an attractive, responsive website that works on laptops and phones.
4. We can deliver a working project on time without spending unnecessary money.

---

# 2. What Real Problem Does It Solve? (Before vs. After)

Before our project, many colleges and small training units manage student records using one huge spreadsheet or one giant database table. 

Here is what was wrong with the old way, and how our new system fixes it:

| Problem in Old / Legacy System | Why It Caused Pain | How Our New System Solves It |
| :--- | :--- | :--- |
| **1. One Huge Flat Table** | The course name, lecturer name, and module name were typed again and again for every student. If a lecturer changed their email, someone had to edit 100 rows! | **8 Clean, Connected Tables (3NF)**: Lecturer details live in one table. Course details live in another. They connect using ID numbers. Update once, and it updates everywhere. |
| **2. Admin Did All the Work** | Lecturers emailed marks to the admin on pieces of paper or Excel. Admin had to re-type everything. Mistakes happened constantly. | **Separate Portals**: Lecturers log in directly and type their own marks. Admin focuses only on official registrations. |
| **3. Students Waiting in Long Queues** | To get a proof of registration or an academic transcript, students had to stand in line at the campus counter to get a stamped printout. | **One-Click PDF Export**: Students click a button on their phone or laptop and instantly download their official PDF transcript and registration slip. |
| **4. Students Failing Silently** | Students often didn't realize they were failing until the end of the year when it was too late to recover. | **AI Academic Success Coach**: Students click "Analyze My Performance." The system looks at their marks, flags any subject under 50%, and gives them a revision timetable. |
| **5. Easy-to-Guess Passwords** | Old systems often stored passwords in plain text or simple scrambled text that anyone could read if they saw the database. | **Bcrypt Security**: Every password is encrypted with a random secret salt. Even if someone steals the database, they cannot read the passwords. |

---

# 3. The Project Plan: Scope, Time, Cost, People

In college project management, every successful project balances four pillars: **Scope**, **Time**, **Cost**, and **People**.

```
                        +----------------------+
                        |     PROJECT PLAN     |
                        +----------------------+
                                   |
         +-----------------+-------+-------+-----------------+
         |                 |               |                 |
         v                 v               v                 v
     [ SCOPE ]          [ TIME ]        [ COST ]         [ PEOPLE ]
   What we built      2-Week Sprint   Under R1,000      3 Interns &
   & what we left     (10 Work Days)  (R750 spent,      Mentor (ITBS)
     for later                        FOSS tools)
```

---

### 3.1 Scope (What we built vs. What we left out)

To finish in two weeks, we had to be very disciplined about what to include and what to leave out.

#### What was IN Scope (We Built This):
* **Secure Login**: Login page with role-switcher tabs (Admin, Lecturer, Student).
* **Admin Tools**: Register students with auto-generated student IDs (`ST2600001`), manage courses, and assign modules.
* **Lecturer Tools**: Roster views and mark entry fields for Quarter 1, Quarter 2, Quarter 3, and Quarter 4.
* **Student Tools**: View marks, see running averages, view enrolled modules, and download PDF transcripts.
* **AI Study Helper**: Free OpenRouter AI connection with a backup heuristic rule engine if internet drops.
* **Clean 3NF Database**: 8 normalized relational tables with foreign keys and automatic cleanup (cascading deletes).
* **Backward Compatibility**: Old student numbers and old login accounts still work without breaking.

#### What was OUT of Scope (Left out on purpose):
* **Online Bank Payments**: We did not connect credit card gateways like PayFast or Visa because real payments require banking contracts.
* **Native Mobile Apps (Apple App Store / Google Play Store)**: Instead of spending months building separate apps, we made the website responsive so it looks and works great on any mobile browser.
* **Fingerprint / Biometric Turnstiles**: Hardware turnstiles were not part of this software challenge.

---

### 3.2 Time (Sprint Timeline: Week 1 & Week 2)

We had **10 working days** (2 calendar weeks). We divided the work into two clear halves:
* **Week 1 (Days 1 to 5)**: Requirements, database modeling, and backend core.
* **Week 2 (Days 6 to 10)**: Portals, screens, AI helper, testing, and putting it on the internet.

```
WEEK 1: FOUNDATIONS
Day 1: Kickoff, look at the old messy database, define what to build.
Day 2: Write functional requirements (FR-01 to FR-18) and use cases.
Day 3: Draw the ERD database diagram and UML class diagram.
Day 4: Create the 8 tables in MySQL and write migration scripts.
Day 5: Write the PHP classes (Student, Course, Module, Lecturer, User).

WEEK 2: BUILDING & GOING LIVE
Day 6: Build the Admin Student Registration form with auto-numbering.
Day 7: Build the Lecturer Portal and mark capture screen (Q1 to Q4).
Day 8: Build the Student Portal, live search filter, and PDF downloads.
Day 9: Connect the OpenRouter AI Coach and test security (passwords & forms).
Day 10: Put the system live on free cloud hosting (InfinityFree) and practice presentation.
```

---

### 3.3 Cost (How we kept it under R 1,000)

Our mentor asked us to be resourceful and prove that good software engineering does not need tens of thousands of Rands in licensing fees.

**Total Actual Cash Spent: R 750.00 ZAR** (Well below the R 1,000 budget!)

| What we spent money on | How much | Why we needed it |
| :--- | :---: | :--- |
| **Mobile Data Bundles** | R 350.00 | 20GB data for team sync, research, and uploading code to cloud hosting. |
| **Testing & Domain Setup** | R 250.00 | Local testing resources and team collaboration tools. |
| **Encrypted Backup USB Drive** | R 150.00 | Storing daily code and database backups safely off-site. |
| **Total Cash Spent** | **R 750.00** | **Total out-of-pocket cost for the entire project.** |

#### How we saved ~R 48,400.00 by using Free and Open-Source Software (FOSS):
* Instead of paying R 12,000 for Microsoft Windows Server / IIS $\rightarrow$ We used **Apache 2.4 via AMPPS (Free)**.
* Instead of paying R 18,500 for Microsoft SQL Server or Oracle $\rightarrow$ We used **MySQL / MariaDB (Free)**.
* Instead of paying R 6,000 for enterprise code tools $\rightarrow$ We used **PHP 8.2 and VS Code (Free)**.
* Instead of paying R 2,400 for expensive cloud hosting $\rightarrow$ We used **InfinityFree Cloud (Free Tier)**.
* Instead of paying R 3,500 for a paid OpenAI subscription $\rightarrow$ We used **OpenRouter Free Tier (`gemini-2.0-flash-exp:free`)**.
* Instead of paying R 4,200 for a commercial PDF software license $\rightarrow$ We used open-source **jsPDF (Free)**.

---

### 3.4 People (Who did what)

To work like a real professional team, we divided responsibilities based on our strengths:

```
+-------------------------------------------------------------------------------+
|                       THE 3-PERSON INTERNSHIP TEAM                            |
+-------------------------------------------------------------------------------+
|                                                                               |
|  1. Mr Avuyile Mthembu       2. Ms Nomthandazo Khoza    3. Ms Qhama Nodada    |
|     Full Stack Developer        Database Administrator     Frontend Developer |
|     & Team Lead                 (DBA & Modeler)            (UI/UX & Client)   |
|                                                                               |
|  • System Architecture       • 3NF Database Design      • Material Design UI  |
|  • PHP 8 Domain Classes      • ERD Diagram              • Login Role Switcher |
|  • Bcrypt Auth & Security    • SQL Migration Scripts    • Live Search Filter  |
|  • AI Coach Integration      • Foreign Keys & Rules     • jsPDF Downloads     |
|  • Cloud Deployment          • Data Integrity           • Mobile Viewports    |
+-------------------------------------------------------------------------------+
```

---

# 4. Methodology: Systems Analysis & Design (The College Basics)

When colleges teach **Systems Analysis and Design (SAD)**, they teach the **Software Development Life Cycle (SDLC)**. We followed the standard 5 stages:

```
[ 1. Planning ] ──> [ 2. Analysis ] ──> [ 3. Design ] ──> [ 4. Implementation ] ──> [ 5. Testing & Deploy ]
Feasibility & Scope   As-Is vs To-Be     ERD & UML Class      PHP, MySQL, HTML/CSS      Bcrypt QA, Cloud Live
```

### Step 1: Planning & Feasibility
* **Is it technically possible?** Yes, PHP and MySQL can handle thousands of students easily.
* **Can we afford it?** Yes, we used free tools and spent only R 750.
* **Is it legal under POPIA?** Yes, South Africa's POPIA act requires protecting personal info. We hash passwords, protect student ID numbers, and stop unauthorized users from seeing other people's marks.

### Step 2: Analysis (How Data Flows)
* We audited the old flat database.
* We mapped out the **As-Is Process** (admin does everything manually on paper and Excel) versus the **To-Be Process** (lecturers capture marks online, students download their own transcripts).

### Step 3: Database Design & Normalization (1NF, 2NF, 3NF)
This was one of the most important parts of the assessment.

* **What is Normalization?** It is simply organizing a database so you don't repeat data and don't make mistakes when you add, change, or delete information.
* **1NF (First Normal Form)**: *"No repeating columns."*  
  In the old system, one row had columns like `module_1`, `module_2`, `module_3`. If a student took 5 subjects, the table ran out of columns! We took modules out of the row and gave each module its own separate record.
* **2NF (Second Normal Form)**: *"Everything must describe the whole key, not just half of it."*  
  Course duration (e.g., "3 Years") only belongs to the course, not to the student. So we moved courses into their own `courses` table.
* **3NF (Third Normal Form)**: *"No stepping stones (transitive dependencies)."*  
  A lecturer's email belongs to the lecturer, not to the module. So we made a separate `lecturers` table. Now if a lecturer gets a new email, we update it in **one place**, not across 50 different student records.

### Step 4: System Architecture & UML Class Diagram
In Object-Oriented Programming (OOP), we turn real-world things into code blueprints called **Classes**:
* `Student`: Knows its name, ID number, and can find its enrolled courses.
* `Course`: Knows its course code, name, and what subjects it offers.
* `Module`: Knows its credit value and which lecturers teach it.
* `Lecturer`: Knows teacher contact details and assigned subjects.
* `ModuleResult`: Holds Quarter 1, 2, 3, and 4 marks, checks that marks are between 0 and 100, and calculates the running average.
* `RegistrationService`: Handles creating new student numbers like `ST2600001` so two students never get the same number.
* `OpenRouterService`: Talks to the AI helper so the student gets helpful study advice.

### Step 5: Testing & Going Live
* We tested entering marks over 100% $\rightarrow$ The system stopped us.
* We tested logging in as a student and trying to open the Admin page $\rightarrow$ The system blocked access and redirected to the student dashboard.
* We deployed the code and database to **InfinityFree Cloud** so the panel can test it live on the internet.

---

# 5. System Diagrams Overview

### 5.1 The Data Flow Diagram (DFD)

A **Data Flow Diagram** shows who sends data, where it gets processed, and where it gets saved.

```
+----------------+          Types marks (Q1 - Q4)          +--------------------------+
|    LECTURER    | -------------------------------------> |                          |
+----------------+                                        |                          |
                                                          |                          |
+----------------+          Enters student biography      |      STUDENT PORTAL      |
|     ADMIN      | -------------------------------------> |          SYSTEM          |
+----------------+                                        |   (Core PHP Application) |
                                                          |                          |
+----------------+          Logs in, asks AI for advice   |                          |
|    STUDENT     | <====================================> |                          |
+----------------+    Gets marks, advice & PDF transcript +--------------------------+
                                                                       |
                                         Saves & reads data            |
                                                                       v
                                                          +--------------------------+
                                                          |       university_db      |
                                                          |   (8 Relational Tables)  |
                                                          +--------------------------+
```

---

### 5.2 The Entity Relationship Diagram (ERD)

An **ERD** is the map of our database tables. Here is what our 8 tables store and how they connect:

```
  [ users ]
     |
     | (logs in with username & Bcrypt password)
     v
  [ students ] <====== maintains history ======> [ enrollments ] <====== admits ====== [ courses ]
     |                                                                                       |
     |                                                                                       | offers
     | obtains marks                                                                         v
     v                                                                                 [ course_modules ]
  [ module_results ] <=== evaluates === [ modules ]                                          |
                                            ^                                                |
                                            | assigned to                                    v
                                    [ module_lecturers ] <=== teaches === [ lecturers ] (shares modules)
```

1. **`students`**: Stores basic info (name, surname, national ID, student number, email).
2. **`courses`**: Stores degree/diploma programmes (e.g., *Systems Development*, `SD101`).
3. **`modules`**: Stores subjects (e.g., *Database Systems*, `DBS201`, 15 credits).
4. **`lecturers`**: Stores teacher names and official emails.
5. **`enrollments`**: Connects a student to a course. **Rule**: A student can only have ONE `'Active'` course at a time. Old courses are marked `'Completed'` so their history is never lost.
6. **`course_modules`**: Connects courses to modules. Allows one subject (like Database Systems) to be taught in multiple degrees.
7. **`module_lecturers`**: Connects lecturers to modules. Allows a module to have a lead lecturer and a co-lecturer.
8. **`module_results`**: Stores the marks for Quarter 1, 2, 3, and 4, plus the calculated average.
9. **`users`**: Stores logins, encrypted passwords, and user roles (`admin`, `lecturer`, `student`).

---

### 5.3 The UML Class Diagram

A **UML Class Diagram** shows how our PHP backend code is structured.

```
+------------------------------------+           +------------------------------------+
|              Student               |           |               Course               |
+------------------------------------+           +------------------------------------+
| - studentNumber: string            |           | - courseCode: string               |
| - firstName: string                |           | - courseName: string               |
| - surname: string                  |           | - duration: string                 |
| - email: string                    |           +------------------------------------+
+------------------------------------+           | + getModules(): array              |
| + getActiveEnrollment(): array     |           +------------------------------------+
| + getModuleResults(): array        |                             |
+------------------------------------+                             | offers
                  |                                                v
                  | places                       +------------------------------------+
                  v                              |               Module               |
+------------------------------------+           +------------------------------------+
|             Enrollment             |           | - moduleCode: string               |
+------------------------------------+           | - moduleName: string               |
| - status: string ('Active')        |           | - credits: int (15)                |
| - registrationDate: date           |           +------------------------------------+
+------------------------------------+           | + assignLecturer(): bool           |
| + validateSingleActive(): bool     |           +------------------------------------+
+------------------------------------+                             |
                  |                                                | taught by
                  v                                                v
+------------------------------------+           +------------------------------------+
|            ModuleResult            |           |              Lecturer              |
+------------------------------------+           +------------------------------------+
| - quarter1, quarter2, quarter3, q4 |           | - firstName: string                |
| - average: float                   |           | - surname: string                  |
+------------------------------------+           | - email: string                    |
| + calculateAverage(): float        |           +------------------------------------+
| + validateMark(): void             |           | + getModules(): array              |
+------------------------------------+           +------------------------------------+
```

---

# 6. Speaker Scripts for the 3 Team Members

When presenting to the panel, use these natural, clear speaking points. Each person has a specific 2-to-3 minute section.

---

### Speaker 1: Avuyile Mthembu (Team Lead & Full Stack Developer)

> **"Good day, esteemed panel and mentors.**  
> My name is **Avuyile Mthembu**, and I served as the Full Stack Developer and Team Lead on this two-week Student Portal modernization project.
> 
> When our team was handed this assignment by our ITBS mentors, our objective was not just to build a simple school project. Our goal was to take an academic records system, apply real-world systems analysis, and build a clean, secure, multi-role web platform under realistic budget constraints.
> 
> As the Full Stack Lead, my responsibility was the core backend architecture and system integration:
> 1. **Object-Oriented Design**: I translated our domain entities into clean PHP 8 classes—such as `Student`, `Course`, `Module`, `Enrollment`, and `ModuleResult`. This ensures our business rules—like making sure a student can only be active in one course at a time—are enforced in code, not left to chance.
> 2. **Authentication & Password Security**: We take user data protection seriously under POPIA. Passwords are never saved as plain text. I implemented Bcrypt password hashing with unique 22-character cryptographic salts. Every SQL query in the system uses PDO parameterized statements to completely block SQL injection attacks.
> 3. **Automated Student Numbering**: When an admin registers a student, my `RegistrationService` automatically generates a clean institutional student number formatted as `ST` plus the year and a unique sequence—for example, `ST2600001`.
> 4. **AI Academic Coach**: We integrated a free AI model from OpenRouter (`gemini-2.0-flash-exp:free`). When students view their marks, they can click one button to get an instant academic diagnostic. If a student is failing a module under 50%, the AI highlights it and suggests a study schedule. And if the internet is down, my fallback code automatically generates rule-based advice so the student never sees an error.
> 
> Now, I will hand over to **Nomthandazo Khoza**, our Database Administrator, who will explain how she normalized our database into Third Normal Form."

---

### Speaker 2: Nomthandazo Khoza (Database Administrator - DBA)

> **"Thank you, Avuyile. Good day, members of the panel.**  
> My name is **Nomthandazo Khoza**, and I served as the Database Administrator and Data Modeler for this project.
> 
> When we started, the legacy system had a major weakness: everything was kept in a single, messy flat table. Course names, lecturer names, and modules were repeated on every single row. If an educator changed their email, or if a student took multiple subjects, the database suffered from update errors and duplicated data.
> 
> My mission was to redesign the database according to **Third Normal Form (3NF)**:
> 1. **1NF**: I removed repeating module columns and ensured every field held atomic, single values.
> 2. **2NF**: I separated qualifications into a dedicated `courses` table and academic units into a `modules` table so that course details only depend on the course ID.
> 3. **3NF**: I eliminated transitive dependencies by creating a dedicated `lecturers` table so staff details do not depend on student records.
> 
> The final schema in MySQL consists of **8 normalized tables**:
> * We created junction tables like `course_modules` so modules can be shared across multiple qualifications.
> * We created `module_lecturers` so a subject can have both a primary lead lecturer and an assistant lecturer.
> * We added foreign keys with `ON DELETE CASCADE`. That means if a test student is removed by an admin, the system automatically cleans up their enrollments and marks, preventing orphaned 'ghost' records.
> * In the `module_results` table, marks are stored with two decimal places (`DECIMAL(5,2)`), and we enforce that marks can only range from `0.00` to `100.00`.
> 
> Best of all, we maintained **100% backward compatibility**. Old columns like `full_name` and legacy student numbers remain synchronized so existing records and reports continue working without errors.
> 
> Now, I will hand over to **Qhama Nodada**, our Frontend Developer, who will walk you through the user interface and client features."

---

### Speaker 3: Qhama Nodada (Frontend Developer & UI/UX)

> **"Thank you, Nomthandazo. Good day, everyone.**  
> My name is **Qhama Nodada**, and I was responsible for the Frontend User Experience, screen designs, and client-side features.
> 
> Our goal on the frontend was to make the system intuitive, responsive, and stress-free for all three types of users: Administrators, Lecturers, and Students.
> 
> Here is what I engineered on the client side:
> 1. **Interactive Multi-Role Switcher**: On our login page, you don't need to guess usernames or hunt for demo accounts. I built a tabbed switcher where you can click between 'Student', 'Lecturer', and 'Admin', and it automatically populates working demo credentials so the panel can test the system in seconds.
> 2. **Clean Material Design Interface**: Using modern CSS, flexbox grids, and the Materialize framework, I built responsive dashboards that look sharp on desktop monitors, laptops, and mobile phones.
> 3. **Instant Live Search & Filtering**: In the Admin student directory, I wrote client-side JavaScript (`search-filter.js`) that lets you search by student name, student number, or course. The table filters instantly as you type without waiting for a slow page reload.
> 4. **One-Click Official PDF Exports**: Students don't need to wait in campus queues to get proof of registration or their transcript. I integrated the open-source `jsPDF` engine. With one click, the browser generates and downloads a clean, professional PDF transcript complete with institutional branding, marks, and averages.
> 
> Everything we built was tested across Google Chrome, Firefox, and Microsoft Edge. 
> 
> Now, Avuyile will demonstrate the working system in a quick 5-minute live walkthrough."

---

# 7. Step-by-Step 5-Minute Live Demo Walkthrough

Follow this exact script when clicking through the system for the panel:

```
[ Step 1: Open index.php ] ──> [ Step 2: Admin View ] ──> [ Step 3: Lecturer View ] ──> [ Step 4: Student View ]
Show Role Tabs & Auto-Fill    Show Auto-Numbering        Capture Q1-Q4 Marks          Show AI Coach & PDF
```

### Step 1: Show the Login Page & Role Switcher (30 seconds)
1. Open [http://localhost/university_system/index.php](http://localhost/university_system/index.php) (or the live cloud URL).
2. Point out the three tabs: **Student**, **Lecturer**, **Admin**.
3. Click the **Lecturer** tab $\rightarrow$ Notice how the fields auto-fill with username `lecturer` and password `Lecturer@dffe`.
4. Click the **Admin** tab $\rightarrow$ Auto-fills with `admin` and `TheAdmin@dffe`.
5. Explain: *"We built this so assessors can test any role instantly with zero friction."*

### Step 2: Log in as Administrator (1 minute)
1. Click **Login as Admin**.
2. You land on `admin/dashboard.php`. Point out the summary cards (total students, active courses, modules, lecturers).
3. Click **Register Student** (`admin/register_student.php`).
4. Show the student number box: Notice it is pre-filled with the next sequential number (e.g., `ST2600003`).
5. Show the manual toggle: Explain that if an old student transfers in, the admin can flip the switch to type a custom ID.
6. Click **Student List** (`admin/student_list.php`). Type `"Sipho"` or `"10ha"` into the live search box $\rightarrow$ Notice the table filters instantly.

### Step 3: Log in as Lecturer (1.5 minutes)
1. Log out, click the **Lecturer** tab, and log in.
2. You land on `lecturer/dashboard.php`.
3. Show the assigned teaching modules.
4. Click **Capture Marks** (`lecturer/results.php`).
5. Pick an enrolled student. Enter marks for Quarter 1 (`85`), Quarter 2 (`90`), Quarter 3 (`88`), and Quarter 4 (`92`).
6. Point out that the system immediately calculates their running composite average.
7. Try typing `150` into a box $\rightarrow$ Show that the form prevents invalid marks over 100%.

### Step 4: Log in as Student (2 minutes)
1. Log out, click the **Student** tab, and log in with student `10ha2304014` and password `Student@dffe`.
2. You land on `student/dashboard.php`. Show the clean greeting, registered qualification, and average grade.
3. Click **Academic Results / Transcript** (`student/results.php`).
4. Point out the table showing all enrolled modules and the four quarter marks captured by the lecturer.
5. **The AI Highlight**: Click the **"Analyze Performance with AI Coach"** button.
   - Show the panel how the AI reviews the student's marks.
   - Point out that it highlights strong subjects and gives revision tips for weaker subjects.
6. **The PDF Highlight**: Click **"Download Official Transcript (PDF)"**.
   - Show the generated PDF opening in the browser with university headers, student info, module marks, and official layout.
7. Conclude: *"All of this runs in real time, on a sub-R1,000 budget, backed by our 3NF database."*

---

# 8. Common Questions the Panel Might Ask (And Safe Answers)

Here are the most common questions an evaluation panel will ask, along with honest, impressive answers:

### Q1: "Why did you use PHP and vanilla JavaScript instead of modern frameworks like React or Laravel?"
> **Answer**:  
> *"We had a strict 2-week deadline. Using modern PHP 8 and Vanilla JavaScript gave us full control over every line of code without the heavy overhead, complex build tools, or version incompatibilities of large frameworks. It allowed us to prove to the panel that we understand core Object-Oriented principles, raw SQL transactions, and DOM manipulation from first principles before relying on framework shortcuts."*

---

### Q2: "What happens to the AI study coach if the internet goes down or the API runs out of free credits?"
> **Answer**:  
> *"We designed the AI feature with a fault-tolerant fallback engine in `OpenRouterService.php`. If the OpenRouter API is unreachable or the key is missing, the system automatically falls back to an internal heuristic engine. It examines the student's marks using simple logic, flags any subject under 50%, and generates actionable study advice locally. The student never sees a blank screen or a crash."*

---

### Q3: "How did you ensure that moving to 8 normalized tables didn't break old records?"
> **Answer**:  
> *"We used a dual-column backward compatibility strategy. In the `students` table, we kept the legacy columns like `full_name`, `student_id_number`, and `course_of_study` alongside our new normalized fields (`first_name`, `surname`). Whenever a record is saved, our code updates both columns in sync. This means old queries from Version 1.0 continue working with zero errors."*

---

### Q4: "Why didn't you add tuition fee payment with credit cards?"
> **Answer**:  
> *"We placed payment processing outside our 2-week scope because real credit card gateways require merchant bank accounts, PCI-DSS compliance audits, and real financial liability. Instead, we focused 100% of our sprint on the core academic lifecycle: registration, curriculum mapping, lecturer grading, 3NF database integrity, and student self-service."*

---

### Q5: "What was the biggest technical challenge your team faced, and how did you resolve it?"
> **Answer**:  
> *"Our biggest challenge was coordinating the relational foreign keys and cascading updates across the 8 tables without locking or corrupting data. For instance, when registering a student, we have to create a student record, create an enrollment record, and provision a login account simultaneously. We solved this by using database transactions (`beginTransaction`, `commit`, and `rollBack`) in `RegistrationService.php`. If any step fails, the whole transaction rolls back, so the database never gets corrupted."*

---

### Q6: "How did you keep the total cost to only R 750?"
> **Answer**:  
> *"By adopting open-source and free-tier technologies. We used AMPPS with Apache and MariaDB for development, open-source jsPDF for PDF generation, OpenRouter's free Gemini model for AI assistance, and InfinityFree's free cloud cluster for deployment. We avoided approximately R 48,400 in commercial software licenses while delivering a production-grade system."*

---

## Final Team Presentation Checklist

Before walking into the assessment room:
- [ ] Open the live cloud URL: [https://student-system.infinityfree.io/index.php](https://student-system.infinityfree.io/index.php) in one browser tab.
- [ ] Open the localhost URL: [http://localhost/university_system/index.php](http://localhost/university_system/index.php) in a second tab as a backup.
- [ ] Test that the Admin login works (`admin` / `TheAdmin@dffe`).
- [ ] Test that the Lecturer login works (`lecturer` / `Lecturer@dffe`).
- [ ] Test that the Student login works (`10ha2304014` / `Student@dffe`).
- [ ] Click "Download Official Transcript (PDF)" once to make sure the PDF generates cleanly.
- [ ] Have this guide open on a tablet or phone for quick reference during Q&A!
