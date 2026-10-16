<?php
/**
 * Student Class
 * 
 * Prepared by: Mr Avuyile Mthembu
 * Position: Intern: Software Developer
 * Directorate: Information Technology Business Solutions (ITBS)
 * Department: Department of Forestry, Fisheries and the Environment (DFFE)
 */

class Student {
    private ?int $id;
    private ?string $studentNumber;
    private ?string $firstName;
    private ?string $surname;
    private ?string $idNumber;
    private ?string $email;
    private ?string $dateOfBirth;

    // Backward compatibility attributes
    private ?string $fullName;
    private ?string $studentIdNumber;
    private ?string $courseOfStudy;
    private ?string $enrollmentDate;
    private ?string $academicStatus;
    private ?string $registrationTimestamp;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        
        // Populate new normalized fields
        $this->studentNumber = $data['student_number'] ?? $data['studentNumber'] ?? $data['student_id_number'] ?? null;
        $this->firstName = $data['first_name'] ?? $data['firstName'] ?? null;
        $this->surname = $data['surname'] ?? null;
        $this->idNumber = $data['id_number'] ?? $data['idNumber'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->dateOfBirth = $data['date_of_birth'] ?? $data['dateOfBirth'] ?? null;

        // Auto-split full_name if first_name/surname are empty (backward compatibility)
        if (empty($this->firstName) && !empty($data['full_name'])) {
            $parts = explode(' ', trim($data['full_name']), 2);
            $this->firstName = $parts[0] ?? '';
            $this->surname = $parts[1] ?? 'Student';
        }

        // Backward compatibility mappings
        $this->fullName = $data['full_name'] ?? trim(($this->firstName ?? '') . ' ' . ($this->surname ?? ''));
        $this->studentIdNumber = $this->studentNumber;
        $this->courseOfStudy = $data['course_of_study'] ?? null;
        $this->enrollmentDate = $data['enrollment_date'] ?? null;
        $this->academicStatus = $data['academic_status'] ?? 'Active';
        $this->registrationTimestamp = $data['registration_timestamp'] ?? date('Y-m-d H:i:s');
    }

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    public function getStudentNumber(): ?string { return $this->studentNumber; }
    public function setStudentNumber(?string $num): void { 
        $this->studentNumber = $num; 
        $this->studentIdNumber = $num;
    }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): void { 
        $this->firstName = $firstName;
        $this->fullName = trim(($this->firstName ?? '') . ' ' . ($this->surname ?? ''));
    }

    public function getSurname(): ?string { return $this->surname; }
    public function setSurname(?string $surname): void { 
        $this->surname = $surname;
        $this->fullName = trim(($this->firstName ?? '') . ' ' . ($this->surname ?? ''));
    }

    public function getFullName(): string {
        if (!empty($this->firstName) || !empty($this->surname)) {
            return trim(($this->firstName ?? '') . ' ' . ($this->surname ?? ''));
        }
        return $this->fullName ?? '';
    }

    public function getIdNumber(): ?string { return $this->idNumber; }
    public function setIdNumber(?string $idNumber): void { $this->idNumber = $idNumber; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): void { $this->email = $email; }

    public function getDateOfBirth(): ?string { return $this->dateOfBirth; }
    public function setDateOfBirth(?string $dob): void { $this->dateOfBirth = $dob; }

    // Legacy getters for 100% backward compatibility
    public function getStudentIdNumber(): ?string { return $this->getStudentNumber(); }
    public function getCourseOfStudy(): ?string { return $this->courseOfStudy; }
    public function setCourseOfStudy(?string $course): void { $this->courseOfStudy = $course; }
    public function getAcademicStatus(): ?string { return $this->academicStatus; }
    public function setAcademicStatus(?string $status): void { $this->academicStatus = $status; }
    public function getEnrollmentDate(): ?string { return $this->enrollmentDate; }
    public function setEnrollmentDate(?string $date): void { $this->enrollmentDate = $date; }
    public function getRegistrationTimestamp(): ?string { return $this->registrationTimestamp; }

    /**
     * Get active course enrollment for this student
     */
    public function getActiveEnrollment(PDO $pdo): ?array {
        if (!$this->id) return null;
        $stmt = $pdo->prepare("
            SELECT e.*, c.course_code, c.course_name, c.duration 
            FROM enrollments e 
            JOIN courses c ON c.course_id = e.course_id 
            WHERE e.student_id = :sid AND e.status = 'Active' 
            LIMIT 1
        ");
        $stmt->execute([':sid' => $this->id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get full enrollment history for this student
     */
    public function getEnrollmentHistory(PDO $pdo): array {
        if (!$this->id) return [];
        $stmt = $pdo->prepare("
            SELECT e.*, c.course_code, c.course_name, c.duration 
            FROM enrollments e 
            JOIN courses c ON c.course_id = e.course_id 
            WHERE e.student_id = :sid 
            ORDER BY e.registration_date DESC, e.enrollment_id DESC
        ");
        $stmt->execute([':sid' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Get all quarterly marks/module results for this student
     */
    public function getModuleResults(PDO $pdo, ?int $year = null): array {
        if (!$this->id) return [];
        $sql = "
            SELECT r.*, m.module_code, m.module_name, m.credits,
                   GROUP_CONCAT(DISTINCT CONCAT(l.first_name, ' ', l.surname) SEPARATOR ', ') AS lecturers
            FROM module_results r
            JOIN modules m ON m.module_id = r.module_id
            LEFT JOIN module_lecturers ml ON ml.module_id = m.module_id
            LEFT JOIN lecturers l ON l.lecturer_id = ml.lecturer_id
            WHERE r.student_id = :sid
        ";
        $params = [':sid' => $this->id];
        if ($year !== null) {
            $sql .= " AND r.academic_year = :year";
            $params[':year'] = $year;
        }
        $sql .= " GROUP BY r.mark_id ORDER BY m.module_code ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find student by ID
     */
    public static function findById(PDO $pdo, int $id): ?Student {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        return $data ? new self($data) : null;
    }

    /**
     * Find student by student number / student ID number
     */
    public static function findByStudentNumber(PDO $pdo, string $studentNumber): ?Student {
        $stmt = $pdo->prepare("
            SELECT * FROM students 
            WHERE student_number = :num OR student_id_number = :num2 
            LIMIT 1
        ");
        $stmt->execute([':num' => $studentNumber, ':num2' => $studentNumber]);
        $data = $stmt->fetch();
        return $data ? new self($data) : null;
    }

    /**
     * Convert to array supporting both new and legacy keys
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'studentNumber' => $this->studentNumber,
            'student_number' => $this->studentNumber,
            'firstName' => $this->firstName,
            'first_name' => $this->firstName,
            'surname' => $this->surname,
            'idNumber' => $this->idNumber,
            'id_number' => $this->idNumber,
            'email' => $this->email,
            'dateOfBirth' => $this->dateOfBirth,
            'date_of_birth' => $this->dateOfBirth,
            // Legacy keys
            'full_name' => $this->getFullName(),
            'student_id_number' => $this->studentNumber,
            'course_of_study' => $this->courseOfStudy,
            'academic_status' => $this->academicStatus,
            'enrollment_date' => $this->enrollmentDate,
            'registration_timestamp' => $this->registrationTimestamp
        ];
    }
}
