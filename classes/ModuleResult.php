<?php
/**
 * ModuleResult Class
 * 
 * ModuleResult: markId, quarter1, quarter2, quarter3, quarter4, average
 * 
 * Business Rules:
 * - Quarterly Marks: ModuleResult stores four quarterly marks for one student, 
 *   one module, and one academic year. The calculateAverage() method calculates 
 *   the average of the four marks.
 * - Mark Validation: Each quarterly mark must fall within the institution's permitted range, 
 *   typically 0-100.
 * 
 * Prepared by: Mr Avuyile Mthembu
 * Position: Intern: Software Developer
 * Department: Department of Forestry, Fisheries and the Environment (DFFE)
 */

class ModuleResult {
    private ?int $markId;
    private int $studentId;
    private int $moduleId;
    private int $academicYear;
    private ?float $quarter1;
    private ?float $quarter2;
    private ?float $quarter3;
    private ?float $quarter4;
    private ?float $average;

    public function __construct(array $data = []) {
        $this->markId = isset($data['mark_id']) ? (int)$data['mark_id'] : (isset($data['markId']) ? (int)$data['markId'] : null);
        $this->studentId = (int)($data['student_id'] ?? $data['studentId'] ?? 0);
        $this->moduleId = (int)($data['module_id'] ?? $data['moduleId'] ?? 0);
        $this->academicYear = (int)($data['academic_year'] ?? $data['academicYear'] ?? date('Y'));

        $this->setQuarter1(isset($data['quarter1']) && $data['quarter1'] !== '' ? (float)$data['quarter1'] : null);
        $this->setQuarter2(isset($data['quarter2']) && $data['quarter2'] !== '' ? (float)$data['quarter2'] : null);
        $this->setQuarter3(isset($data['quarter3']) && $data['quarter3'] !== '' ? (float)$data['quarter3'] : null);
        $this->setQuarter4(isset($data['quarter4']) && $data['quarter4'] !== '' ? (float)$data['quarter4'] : null);

        if (isset($data['average']) && $data['average'] !== '') {
            $this->average = (float)$data['average'];
        } else {
            $this->calculateAverage();
        }
    }

    public function getMarkId(): ?int { return $this->markId; }
    public function getStudentId(): int { return $this->studentId; }
    public function setStudentId(int $sid): void { $this->studentId = $sid; }
    public function getModuleId(): int { return $this->moduleId; }
    public function setModuleId(int $mid): void { $this->moduleId = $mid; }
    public function getAcademicYear(): int { return $this->academicYear; }
    public function setAcademicYear(int $year): void { $this->academicYear = $year; }

    public function getQuarter1(): ?float { return $this->quarter1; }
    public function setQuarter1(?float $q1): void {
        self::validateMark($q1, 'Quarter 1');
        $this->quarter1 = $q1 !== null ? round($q1, 2) : null;
    }

    public function getQuarter2(): ?float { return $this->quarter2; }
    public function setQuarter2(?float $q2): void {
        self::validateMark($q2, 'Quarter 2');
        $this->quarter2 = $q2 !== null ? round($q2, 2) : null;
    }

    public function getQuarter3(): ?float { return $this->quarter3; }
    public function setQuarter3(?float $q3): void {
        self::validateMark($q3, 'Quarter 3');
        $this->quarter3 = $q3 !== null ? round($q3, 2) : null;
    }

    public function getQuarter4(): ?float { return $this->quarter4; }
    public function setQuarter4(?float $q4): void {
        self::validateMark($q4, 'Quarter 4');
        $this->quarter4 = $q4 !== null ? round($q4, 2) : null;
    }

    public function getAverage(): ?float { return $this->average; }

    /**
     * Mark Validation Business Rule:
     * Each quarterly mark must fall within the institution's permitted range (0-100).
     */
    public static function validateMark(?float $mark, string $field = 'Mark'): void {
        if ($mark === null) return;
        if ($mark < 0 || $mark > 100) {
            throw new InvalidArgumentException("Mark Validation Error: {$field} must be between 0.00 and 100.00. Value provided: {$mark}");
        }
    }

    /**
     * Calculate Average Method Business Rule:
     * Calculates the average of the four quarterly marks.
     */
    public function calculateAverage(): ?float {
        $marks = array_filter([$this->quarter1, $this->quarter2, $this->quarter3, $this->quarter4], function($val) {
            return $val !== null;
        });

        if (count($marks) === 0) {
            $this->average = null;
            return null;
        }

        // Standard average of the submitted quarters
        $avg = array_sum($marks) / count($marks);
        $this->average = round($avg, 2);
        return $this->average;
    }

    /**
     * Determine letter grade and pass/fail indicator
     */
    public function getGradeStatus(): array {
        if ($this->average === null) {
            return ['grade' => 'N/A', 'status' => 'Pending', 'color' => 'grey'];
        }
        if ($this->average >= 75) {
            return ['grade' => 'Distinction (A)', 'status' => 'Passed', 'color' => 'green'];
        } elseif ($this->average >= 60) {
            return ['grade' => 'Merit (B)', 'status' => 'Passed', 'color' => 'teal'];
        } elseif ($this->average >= 50) {
            return ['grade' => 'Pass (C)', 'status' => 'Passed', 'color' => 'blue'];
        } else {
            return ['grade' => 'Fail (F)', 'status' => 'Failed', 'color' => 'red'];
        }
    }

    /**
     * Save record to database
     */
    public function save(PDO $pdo): bool {
        // Enforce average calculation before persisting
        $this->calculateAverage();

        $stmt = $pdo->prepare("
            INSERT INTO module_results (student_id, module_id, academic_year, quarter1, quarter2, quarter3, quarter4, average)
            VALUES (:sid, :mid, :year, :q1, :q2, :q3, :q4, :avg)
            ON DUPLICATE KEY UPDATE 
                quarter1 = VALUES(quarter1),
                quarter2 = VALUES(quarter2),
                quarter3 = VALUES(quarter3),
                quarter4 = VALUES(quarter4),
                average = VALUES(average)
        ");

        $success = $stmt->execute([
            ':sid'  => $this->studentId,
            ':mid'  => $this->moduleId,
            ':year' => $this->academicYear,
            ':q1'   => $this->quarter1,
            ':q2'   => $this->quarter2,
            ':q3'   => $this->quarter3,
            ':q4'   => $this->quarter4,
            ':avg'  => $this->average
        ]);

        if ($success && !$this->markId) {
            $this->markId = (int)$pdo->lastInsertId();
        }

        return $success;
    }

    /**
     * Get module results for a student
     */
    public static function getResultsForStudent(PDO $pdo, int $studentId, ?int $year = null): array {
        $sql = "
            SELECT r.*, m.module_code, m.module_name, m.credits,
                   GROUP_CONCAT(DISTINCT CONCAT(l.first_name, ' ', l.surname) SEPARATOR ', ') AS lecturers
            FROM module_results r
            JOIN modules m ON m.module_id = r.module_id
            LEFT JOIN module_lecturers ml ON ml.module_id = m.module_id
            LEFT JOIN lecturers l ON l.lecturer_id = ml.lecturer_id
            WHERE r.student_id = :sid
        ";
        $params = [':sid' => $studentId];
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
     * Find single result entry
     */
    public static function find(PDO $pdo, int $studentId, int $moduleId, int $year): ?ModuleResult {
        $stmt = $pdo->prepare("
            SELECT * FROM module_results 
            WHERE student_id = :sid AND module_id = :mid AND academic_year = :year 
            LIMIT 1
        ");
        $stmt->execute([':sid' => $studentId, ':mid' => $moduleId, ':year' => $year]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    public function toArray(): array {
        return [
            'markId' => $this->markId,
            'mark_id' => $this->markId,
            'studentId' => $this->studentId,
            'student_id' => $this->studentId,
            'moduleId' => $this->moduleId,
            'module_id' => $this->moduleId,
            'academicYear' => $this->academicYear,
            'academic_year' => $this->academicYear,
            'quarter1' => $this->quarter1,
            'quarter2' => $this->quarter2,
            'quarter3' => $this->quarter3,
            'quarter4' => $this->quarter4,
            'average' => $this->average
        ];
    }
}
