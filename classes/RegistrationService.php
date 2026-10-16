<?php
/**
 * RegistrationService Class
 * 
 * Business Rules & System Logic:
 * 1. Automatic Student Number: RegistrationService generates a unique student number 
 *    upon successful registration. The database must enforce uniqueness.
 * 2. Student Enrollment: A student may have only one active course enrollment at a time. 
 *    The Enrollment class maintains registration history.
 * 3. Backward Compatibility: Synchronizes legacy full_name, student_id_number, 
 *    course_of_study, and user authentication tables.
 * 
 * Prepared by: Mr Avuyile Mthembu
 * Position: Intern: Software Developer
 * Department: Department of Forestry, Fisheries and the Environment (DFFE)
 */

require_once __DIR__ . '/Student.php';
require_once __DIR__ . '/Course.php';
require_once __DIR__ . '/Enrollment.php';

class RegistrationService {

    /**
     * Generate a unique student number enforcing database uniqueness.
     * Format: ST + 2-digit Year + 5-digit sequence (e.g., ST2600101)
     */
    public static function generateStudentNumber(PDO $pdo, string $prefix = 'ST'): string {
        $year = date('y'); // e.g. 26
        $basePrefix = $prefix . $year;

        // Find the maximum existing sequence number with this prefix
        $stmt = $pdo->prepare("
            SELECT student_number, student_id_number 
            FROM students 
            WHERE student_number LIKE :pfx OR student_id_number LIKE :pfx2 
            ORDER BY id DESC
        ");
        $stmt->execute([':pfx' => $basePrefix . '%', ':pfx2' => $basePrefix . '%']);
        $existing = $stmt->fetchAll();

        $maxSeq = 100;
        foreach ($existing as $row) {
            $num = $row['student_number'] ?: $row['student_id_number'];
            if (preg_match('/^' . preg_quote($basePrefix, '/') . '(\d+)$/', $num, $matches)) {
                $seq = (int)$matches[1];
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }

        // Loop to guarantee absolute uniqueness in the database
        do {
            $maxSeq++;
            $candidate = $basePrefix . str_pad((string)$maxSeq, 5, '0', STR_PAD_LEFT);
            $checkStmt = $pdo->prepare("
                SELECT COUNT(*) FROM students 
                WHERE student_number = :num OR student_id_number = :num2
            ");
            $checkStmt->execute([':num' => $candidate, ':num2' => $candidate]);
            $exists = $checkStmt->fetchColumn() > 0;
        } while ($exists);

        return $candidate;
    }

    /**
     * Register a new student with automatic student number generation,
     * user login creation, and active course enrollment.
     * 
     * @param PDO $pdo Database PDO connection
     * @param array $data Input parameters:
     *   - firstName / first_name (required)
     *   - surname (required)
     *   - email (required)
     *   - idNumber / id_number (required)
     *   - dateOfBirth / dob (required)
     *   - courseId / course_id OR course_name (required)
     *   - enrollmentDate / enroll_date (optional, defaults to today)
     *   - customStudentNumber (optional override for legacy migration)
     *   - password (optional, defaults to 'password123')
     * 
     * @return array [student => Student, username => string, studentNumber => string]
     * @throws Exception On validation or database error
     */
    public static function registerStudent(PDO $pdo, array $data): array {
        // Normalize fields
        $firstName = trim($data['firstName'] ?? $data['first_name'] ?? '');
        $surname   = trim($data['surname'] ?? '');
        $idNumber  = trim($data['idNumber'] ?? $data['id_number'] ?? '');
        $email     = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $dob       = trim($data['dateOfBirth'] ?? $data['dob'] ?? $data['date_of_birth'] ?? '');
        $enrollDate = trim($data['enrollmentDate'] ?? $data['enroll_date'] ?? $data['enrollment_date'] ?? date('Y-m-d'));
        $courseId  = isset($data['course_id']) ? (int)$data['course_id'] : (isset($data['courseId']) ? (int)$data['courseId'] : 0);
        $courseName = trim($data['course'] ?? $data['course_name'] ?? '');

        // Fallback for full_name if first_name / surname are not provided individually
        if (empty($firstName) && !empty($data['full_name'])) {
            $parts = explode(' ', trim($data['full_name']), 2);
            $firstName = $parts[0] ?? '';
            $surname = $parts[1] ?? 'Student';
        }

        // Validate basic inputs
        if (empty($firstName)) throw new InvalidArgumentException("First name is required.");
        if (empty($surname)) throw new InvalidArgumentException("Surname is required.");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException("A valid email address is required.");
        if (empty($dob)) throw new InvalidArgumentException("Date of birth is required.");

        // Resolve course
        $course = null;
        if ($courseId > 0) {
            $course = Course::findById($pdo, $courseId);
        } elseif (!empty($courseName)) {
            $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_name = :cname LIMIT 1");
            $stmt->execute([':cname' => $courseName]);
            $cRow = $stmt->fetch();
            if ($cRow) {
                $course = new Course($cRow);
            }
        }

        if (!$course) {
            throw new InvalidArgumentException("A valid course selection is required.");
        }

        // Generate unique student number (or use provided custom student number)
        $studentNumber = !empty($data['customStudentNumber']) ? trim($data['customStudentNumber']) : (!empty($data['student_id']) ? trim($data['student_id']) : self::generateStudentNumber($pdo));

        // Legacy full_name composite
        $fullName = trim($firstName . ' ' . $surname);

        // Begin transaction
        $pdo->beginTransaction();
        try {
            // Check for duplicate email or student number
            $chk = $pdo->prepare("
                SELECT id FROM students 
                WHERE email = :email OR student_number = :snum OR student_id_number = :snum2 
                LIMIT 1
            ");
            $chk->execute([':email' => $email, ':snum' => $studentNumber, ':snum2' => $studentNumber]);
            if ($chk->fetch()) {
                throw new Exception("A student with this Student Number or Email already exists.");
            }

            // 1. Insert into students table (with backward compatibility)
            $stmt = $pdo->prepare("
                INSERT INTO students (
                    student_number, first_name, surname, id_number, 
                    full_name, student_id_number, email, date_of_birth, 
                    course_of_study, enrollment_date, academic_status
                ) VALUES (
                    :snum, :fname, :sname, :idnum, 
                    :fname_legacy, :snum_legacy, :email, :dob, 
                    :course_name, :edate, 'Active'
                )
            ");
            $stmt->execute([
                ':snum' => $studentNumber,
                ':fname' => $firstName,
                ':sname' => $surname,
                ':idnum' => $idNumber,
                ':fname_legacy' => $fullName,
                ':snum_legacy' => $studentNumber,
                ':email' => $email,
                ':dob' => $dob,
                ':course_name' => $course->getCourseName(),
                ':edate' => $enrollDate
            ]);
            $studentId = (int)$pdo->lastInsertId();

            // 2. Insert into users table for authentication
            $plainPassword = $data['password'] ?? 'password123';
            $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
            $userStmt = $pdo->prepare("
                INSERT INTO users (username, password, role, student_id_number) 
                VALUES (:uname, :pwd, 'student', :sid_num)
            ");
            $userStmt->execute([
                ':uname' => $studentNumber,
                ':pwd' => $passwordHash,
                ':sid_num' => $studentNumber
            ]);

            // 3. Create initial Active Course Enrollment
            // Business rule: Student may have only one active course enrollment at a time
            $enrollment = new Enrollment([
                'student_id' => $studentId,
                'course_id' => $course->getCourseId(),
                'registration_date' => $enrollDate,
                'status' => 'Active'
            ]);
            $enrollment->save($pdo);

            $pdo->commit();

            $student = Student::findById($pdo, $studentId);
            return [
                'success' => true,
                'student' => $student,
                'studentId' => $studentId,
                'studentNumber' => $studentNumber,
                'username' => $studentNumber,
                'temporaryPassword' => $plainPassword,
                'enrollmentId' => $enrollment->getEnrollmentId(),
                'courseName' => $course->getCourseName()
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
