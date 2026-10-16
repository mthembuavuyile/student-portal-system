<?php
/**
 * Course Class
 * 
 * Course: courseId, courseName, courseCode, duration
 * 
 * Prepared by: Mr Avuyile Mthembu
 * Position: Intern: Software Developer
 * Department: Department of Forestry, Fisheries and the Environment (DFFE)
 */

class Course {
    private ?int $courseId;
    private string $courseName;
    private string $courseCode;
    private string $duration;

    public function __construct(array $data = []) {
        $this->courseId = isset($data['course_id']) ? (int)$data['course_id'] : (isset($data['courseId']) ? (int)$data['courseId'] : null);
        $this->courseName = $data['course_name'] ?? $data['courseName'] ?? '';
        $this->courseCode = $data['course_code'] ?? $data['courseCode'] ?? '';
        $this->duration = $data['duration'] ?? '3 Years';
    }

    public function getCourseId(): ?int { return $this->courseId; }
    public function getCourseName(): string { return $this->courseName; }
    public function setCourseName(string $name): void { $this->courseName = $name; }
    public function getCourseCode(): string { return $this->courseCode; }
    public function setCourseCode(string $code): void { $this->courseCode = $code; }
    public function getDuration(): string { return $this->duration; }
    public function setDuration(string $duration): void { $this->duration = $duration; }

    /**
     * Get all modules associated with this course (Many-to-Many via course_modules)
     */
    public function getModules(PDO $pdo): array {
        if (!$this->courseId) return [];
        $stmt = $pdo->prepare("
            SELECT m.*, cm.semester 
            FROM modules m 
            JOIN course_modules cm ON cm.module_id = m.module_id 
            WHERE cm.course_id = :cid 
            ORDER BY cm.semester ASC, m.module_code ASC
        ");
        $stmt->execute([':cid' => $this->courseId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all students currently enrolled in this course
     */
    public function getStudents(PDO $pdo, string $status = 'Active'): array {
        if (!$this->courseId) return [];
        $stmt = $pdo->prepare("
            SELECT s.*, e.registration_date, e.status AS enrollment_status 
            FROM students s 
            JOIN enrollments e ON e.student_id = s.id 
            WHERE e.course_id = :cid AND e.status = :status 
            ORDER BY s.full_name ASC
        ");
        $stmt->execute([':cid' => $this->courseId, ':status' => $status]);
        return $stmt->fetchAll();
    }

    /**
     * Assign a module to this course
     */
    public function addModule(PDO $pdo, int $moduleId, string $semester = 'Semester 1'): bool {
        if (!$this->courseId) return false;
        $stmt = $pdo->prepare("
            INSERT INTO course_modules (course_id, module_id, semester) 
            VALUES (:cid, :mid, :sem) 
            ON DUPLICATE KEY UPDATE semester = VALUES(semester)
        ");
        return $stmt->execute([':cid' => $this->courseId, ':mid' => $moduleId, ':sem' => $semester]);
    }

    /**
     * Fetch all courses
     */
    public static function getAll(PDO $pdo): array {
        $stmt = $pdo->query("SELECT * FROM courses ORDER BY course_name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Find by ID
     */
    public static function findById(PDO $pdo, int $id): ?Course {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    /**
     * Find by Course Code
     */
    public static function findByCode(PDO $pdo, string $code): ?Course {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    public function toArray(): array {
        return [
            'courseId' => $this->courseId,
            'course_id' => $this->courseId,
            'courseName' => $this->courseName,
            'course_name' => $this->courseName,
            'courseCode' => $this->courseCode,
            'course_code' => $this->courseCode,
            'duration' => $this->duration
        ];
    }
}
