<?php
/**
 * Lecturer Class
 * 
 * Lecturer: lecturerId, firstName, surname, email
 * 
 * Prepared by: Mr Avuyile Mthembu
 * Position: Intern: Software Developer
 * Department: Department of Forestry, Fisheries and the Environment (DFFE)
 */

class Lecturer {
    private ?int $lecturerId;
    private string $firstName;
    private string $surname;
    private string $email;

    public function __construct(array $data = []) {
        $this->lecturerId = isset($data['lecturer_id']) ? (int)$data['lecturer_id'] : (isset($data['lecturerId']) ? (int)$data['lecturerId'] : null);
        $this->firstName = $data['first_name'] ?? $data['firstName'] ?? '';
        $this->surname = $data['surname'] ?? '';
        $this->email = $data['email'] ?? '';
    }

    public function getLecturerId(): ?int { return $this->lecturerId; }
    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $fn): void { $this->firstName = $fn; }
    public function getSurname(): string { return $this->surname; }
    public function setSurname(string $sn): void { $this->surname = $sn; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getFullName(): string {
        return trim($this->firstName . ' ' . $this->surname);
    }

    /**
     * Get all modules taught by this lecturer
     */
    public function getModules(PDO $pdo): array {
        if (!$this->lecturerId) return [];
        $stmt = $pdo->prepare("
            SELECT m.*, ml.is_primary 
            FROM modules m 
            JOIN module_lecturers ml ON ml.module_id = m.module_id 
            WHERE ml.lecturer_id = :lid 
            ORDER BY m.module_code ASC
        ");
        $stmt->execute([':lid' => $this->lecturerId]);
        return $stmt->fetchAll();
    }

    /**
     * Fetch all lecturers with taught module summary
     */
    public static function getAll(PDO $pdo): array {
        $stmt = $pdo->query("
            SELECT l.*, 
                   COUNT(ml.module_id) AS module_count,
                   GROUP_CONCAT(DISTINCT m.module_code SEPARATOR ', ') AS module_codes
            FROM lecturers l 
            LEFT JOIN module_lecturers ml ON ml.lecturer_id = l.lecturer_id 
            LEFT JOIN modules m ON m.module_id = ml.module_id 
            GROUP BY l.lecturer_id 
            ORDER BY l.surname ASC, l.first_name ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Find by ID
     */
    public static function findById(PDO $pdo, int $id): ?Lecturer {
        $stmt = $pdo->prepare("SELECT * FROM lecturers WHERE lecturer_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    /**
     * Find by Email
     */
    public static function findByEmail(PDO $pdo, string $email): ?Lecturer {
        $stmt = $pdo->prepare("SELECT * FROM lecturers WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    /**
     * Get associated user account
     */
    public function getUser(PDO $pdo): ?User {
        if (!$this->lecturerId) return null;
        return User::findByLecturerId($pdo, $this->lecturerId);
    }

    /**
     * Get students enrolled in courses offering modules taught by this lecturer
     */
    public function getStudents(PDO $pdo): array {
        if (!$this->lecturerId) return [];
        $stmt = $pdo->prepare("
            SELECT DISTINCT s.*, c.course_name, c.course_code
            FROM students s
            JOIN enrollments e ON e.student_id = s.id AND e.status = 'Active'
            JOIN courses c ON c.course_id = e.course_id
            JOIN course_modules cm ON cm.course_id = c.course_id
            JOIN module_lecturers ml ON ml.module_id = cm.module_id
            WHERE ml.lecturer_id = :lid
            ORDER BY s.full_name ASC
        ");
        $stmt->execute([':lid' => $this->lecturerId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all module results for modules taught by this lecturer
     */
    public function getModuleResults(PDO $pdo, ?int $moduleId = null): array {
        if (!$this->lecturerId) return [];
        $sql = "
            SELECT r.*, s.full_name, s.student_number, s.student_id_number,
                   m.module_code, m.module_name, m.credits, ml.is_primary
            FROM module_results r
            JOIN students s ON s.id = r.student_id
            JOIN modules m ON m.module_id = r.module_id
            JOIN module_lecturers ml ON ml.module_id = m.module_id
            WHERE ml.lecturer_id = :lid
        ";
        $params = [':lid' => $this->lecturerId];
        if ($moduleId !== null) {
            $sql .= " AND r.module_id = :mid";
            $params[':mid'] = $moduleId;
        }
        $sql .= " ORDER BY r.academic_year DESC, m.module_code ASC, s.full_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get students enrolled in modules taught by this lecturer with enrolled module details and marks
     */
    public function getStudentsDetailed(PDO $pdo, ?int $moduleId = null): array {
        if (!$this->lecturerId) return [];
        $sql = "
            SELECT 
                s.id AS student_id,
                s.full_name,
                s.student_number,
                s.student_id_number,
                s.id_number,
                s.email,
                s.academic_status,
                s.date_of_birth,
                s.enrollment_date,
                c.course_id,
                c.course_code,
                c.course_name,
                m.module_id,
                m.module_code,
                m.module_name,
                m.credits,
                ml.is_primary,
                r.mark_id,
                r.academic_year,
                r.quarter1,
                r.quarter2,
                r.quarter3,
                r.quarter4,
                r.average
            FROM students s
            JOIN enrollments e ON e.student_id = s.id AND e.status = 'Active'
            JOIN courses c ON c.course_id = e.course_id
            JOIN course_modules cm ON cm.course_id = c.course_id
            JOIN modules m ON m.module_id = cm.module_id
            JOIN module_lecturers ml ON ml.module_id = m.module_id
            LEFT JOIN module_results r ON r.student_id = s.id AND r.module_id = m.module_id
            WHERE ml.lecturer_id = :lid
        ";
        $params = [':lid' => $this->lecturerId];
        if ($moduleId !== null) {
            $sql .= " AND m.module_id = :mid";
            $params[':mid'] = $moduleId;
        }
        $sql .= " ORDER BY s.full_name ASC, m.module_code ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Check if a specific student is taught by this lecturer
     */
    public function hasStudent(PDO $pdo, int $studentId): bool {
        if (!$this->lecturerId) return false;
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM students s
            JOIN enrollments e ON e.student_id = s.id AND e.status = 'Active'
            JOIN courses c ON c.course_id = e.course_id
            JOIN course_modules cm ON cm.course_id = c.course_id
            JOIN module_lecturers ml ON ml.module_id = cm.module_id
            WHERE ml.lecturer_id = :lid AND s.id = :sid
        ");
        $stmt->execute([':lid' => $this->lecturerId, ':sid' => $studentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get all modules taught by this lecturer that a specific student is enrolled in, with marks
     */
    public function getStudentModules(PDO $pdo, int $studentId): array {
        if (!$this->lecturerId) return [];
        $stmt = $pdo->prepare("
            SELECT 
                m.module_id,
                m.module_code,
                m.module_name,
                m.credits,
                ml.is_primary,
                c.course_name,
                c.course_code,
                r.mark_id,
                r.academic_year,
                r.quarter1,
                r.quarter2,
                r.quarter3,
                r.quarter4,
                r.average
            FROM modules m
            JOIN module_lecturers ml ON ml.module_id = m.module_id
            JOIN course_modules cm ON cm.module_id = m.module_id
            JOIN courses c ON c.course_id = cm.course_id
            JOIN enrollments e ON e.course_id = c.course_id AND e.status = 'Active'
            LEFT JOIN module_results r ON r.student_id = :sid AND r.module_id = m.module_id
            WHERE ml.lecturer_id = :lid AND e.student_id = :sid2
            ORDER BY m.module_code ASC
        ");
        $stmt->execute([
            ':lid'  => $this->lecturerId,
            ':sid'  => $studentId,
            ':sid2' => $studentId
        ]);
        return $stmt->fetchAll();
    }

    public function toArray(): array {
        return [
            'lecturerId' => $this->lecturerId,
            'lecturer_id' => $this->lecturerId,
            'firstName' => $this->firstName,
            'first_name' => $this->firstName,
            'surname' => $this->surname,
            'email' => $this->email,
            'fullName' => $this->getFullName()
        ];
    }
}
