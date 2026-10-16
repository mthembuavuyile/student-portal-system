<?php
/**
 * Enrollment Class
 * 
 * Enrollment: enrollmentId, registrationDate, status
 * 
 * Business Rule:
 * Student Enrollment: A student may have only one active course enrollment at a time. 
 * The Enrollment class maintains registration history.
 * 
 * Prepared by: Mr Avuyile Mthembu
 * Position: Intern: Software Developer
 * Department: Department of Forestry, Fisheries and the Environment (DFFE)
 */

class Enrollment {
    private ?int $enrollmentId;
    private int $studentId;
    private int $courseId;
    private string $registrationDate;
    private string $status;

    public function __construct(array $data = []) {
        $this->enrollmentId = isset($data['enrollment_id']) ? (int)$data['enrollment_id'] : (isset($data['enrollmentId']) ? (int)$data['enrollmentId'] : null);
        $this->studentId = (int)($data['student_id'] ?? $data['studentId'] ?? 0);
        $this->courseId = (int)($data['course_id'] ?? $data['courseId'] ?? 0);
        $this->registrationDate = $data['registration_date'] ?? $data['registrationDate'] ?? date('Y-m-d');
        $this->status = $data['status'] ?? 'Active';
    }

    public function getEnrollmentId(): ?int { return $this->enrollmentId; }
    public function getStudentId(): int { return $this->studentId; }
    public function setStudentId(int $sid): void { $this->studentId = $sid; }
    public function getCourseId(): int { return $this->courseId; }
    public function setCourseId(int $cid): void { $this->courseId = $cid; }
    public function getRegistrationDate(): string { return $this->registrationDate; }
    public function setRegistrationDate(string $date): void { $this->registrationDate = $date; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    /**
     * Enforce Business Rule:
     * A student may have only one active course enrollment at a time.
     */
    public function validateSingleActive(PDO $pdo): bool {
        if (strcasecmp($this->status, 'Active') !== 0) {
            return true; // Non-active statuses (Completed, Suspended, Withdrawn) do not violate this rule
        }

        $sql = "SELECT enrollment_id FROM enrollments WHERE student_id = :sid AND status = 'Active'";
        $params = [':sid' => $this->studentId];

        if ($this->enrollmentId) {
            $sql .= " AND enrollment_id != :eid";
            $params[':eid'] = $this->enrollmentId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $existing = $stmt->fetch();

        if ($existing) {
            throw new Exception("Business Rule Violation: Student ID {$this->studentId} already has an active course enrollment (Enrollment ID: {$existing['enrollment_id']}). A student may have only one active course enrollment at a time.");
        }

        return true;
    }

    /**
     * Save or update enrollment, enforcing business rules
     */
    public function save(PDO $pdo): bool {
        $this->validateSingleActive($pdo);

        if ($this->enrollmentId) {
            $stmt = $pdo->prepare("
                UPDATE enrollments 
                SET student_id = :sid, course_id = :cid, registration_date = :rdate, status = :status 
                WHERE enrollment_id = :eid
            ");
            return $stmt->execute([
                ':sid' => $this->studentId,
                ':cid' => $this->courseId,
                ':rdate' => $this->registrationDate,
                ':status' => $this->status,
                ':eid' => $this->enrollmentId
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO enrollments (student_id, course_id, registration_date, status) 
                VALUES (:sid, :cid, :rdate, :status)
            ");
            $success = $stmt->execute([
                ':sid' => $this->studentId,
                ':cid' => $this->courseId,
                ':rdate' => $this->registrationDate,
                ':status' => $this->status
            ]);
            if ($success) {
                $this->enrollmentId = (int)$pdo->lastInsertId();
            }
            return $success;
        }
    }

    /**
     * Retrieve current active enrollment for student
     */
    public static function getActiveForStudent(PDO $pdo, int $studentId): ?array {
        $stmt = $pdo->prepare("
            SELECT e.*, c.course_code, c.course_name, c.duration 
            FROM enrollments e 
            JOIN courses c ON c.course_id = e.course_id 
            WHERE e.student_id = :sid AND e.status = 'Active' 
            LIMIT 1
        ");
        $stmt->execute([':sid' => $studentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Retrieve full enrollment history for student
     */
    public static function getHistoryForStudent(PDO $pdo, int $studentId): array {
        $stmt = $pdo->prepare("
            SELECT e.*, c.course_code, c.course_name, c.duration 
            FROM enrollments e 
            JOIN courses c ON c.course_id = e.course_id 
            WHERE e.student_id = :sid 
            ORDER BY e.registration_date DESC, e.enrollment_id DESC
        ");
        $stmt->execute([':sid' => $studentId]);
        return $stmt->fetchAll();
    }

    /**
     * Find enrollment by ID
     */
    public static function findById(PDO $pdo, int $id): ?Enrollment {
        $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE enrollment_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    public function toArray(): array {
        return [
            'enrollmentId' => $this->enrollmentId,
            'enrollment_id' => $this->enrollmentId,
            'studentId' => $this->studentId,
            'student_id' => $this->studentId,
            'courseId' => $this->courseId,
            'course_id' => $this->courseId,
            'registrationDate' => $this->registrationDate,
            'registration_date' => $this->registrationDate,
            'status' => $this->status
        ];
    }
}
