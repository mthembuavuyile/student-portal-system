<?php
/**
 * Module Class
 * 
 * Module: moduleId, moduleName, moduleCode, credits
 * 
 * Prepared by: Mr Avuyile Mthembu
 * Position: Intern: Software Developer
 * Department: Department of Forestry, Fisheries and the Environment (DFFE)
 */

class Module {
    private ?int $moduleId;
    private string $moduleName;
    private string $moduleCode;
    private int $credits;

    public function __construct(array $data = []) {
        $this->moduleId = isset($data['module_id']) ? (int)$data['module_id'] : (isset($data['moduleId']) ? (int)$data['moduleId'] : null);
        $this->moduleName = $data['module_name'] ?? $data['moduleName'] ?? '';
        $this->moduleCode = $data['module_code'] ?? $data['moduleCode'] ?? '';
        $this->credits = isset($data['credits']) ? (int)$data['credits'] : 15;
    }

    public function getModuleId(): ?int { return $this->moduleId; }
    public function getModuleName(): string { return $this->moduleName; }
    public function setModuleName(string $name): void { $this->moduleName = $name; }
    public function getModuleCode(): string { return $this->moduleCode; }
    public function setModuleCode(string $code): void { $this->moduleCode = $code; }
    public function getCredits(): int { return $this->credits; }
    public function setCredits(int $credits): void { $this->credits = $credits; }

    /**
     * Get all lecturers assigned to this module (Many-to-Many via module_lecturers)
     */
    public function getLecturers(PDO $pdo): array {
        if (!$this->moduleId) return [];
        $stmt = $pdo->prepare("
            SELECT l.*, ml.is_primary 
            FROM lecturers l 
            JOIN module_lecturers ml ON ml.lecturer_id = l.lecturer_id 
            WHERE ml.module_id = :mid 
            ORDER BY ml.is_primary DESC, l.surname ASC
        ");
        $stmt->execute([':mid' => $this->moduleId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all courses offering this module (Many-to-Many via course_modules)
     */
    public function getCourses(PDO $pdo): array {
        if (!$this->moduleId) return [];
        $stmt = $pdo->prepare("
            SELECT c.*, cm.semester 
            FROM courses c 
            JOIN course_modules cm ON cm.course_id = c.course_id 
            WHERE cm.module_id = :mid 
            ORDER BY c.course_name ASC
        ");
        $stmt->execute([':mid' => $this->moduleId]);
        return $stmt->fetchAll();
    }

    /**
     * Assign a lecturer to this module
     */
    public function assignLecturer(PDO $pdo, int $lecturerId, int $isPrimary = 1): bool {
        if (!$this->moduleId) return false;
        $stmt = $pdo->prepare("
            INSERT INTO module_lecturers (module_id, lecturer_id, is_primary) 
            VALUES (:mid, :lid, :primary) 
            ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary)
        ");
        return $stmt->execute([':mid' => $this->moduleId, ':lid' => $lecturerId, ':primary' => $isPrimary]);
    }

    /**
     * Fetch all modules with lecturer and course counts
     */
    public static function getAll(PDO $pdo): array {
        $stmt = $pdo->query("
            SELECT m.*, 
                   COUNT(DISTINCT cm.course_id) AS course_count,
                   COUNT(DISTINCT ml.lecturer_id) AS lecturer_count,
                   GROUP_CONCAT(DISTINCT CONCAT(l.first_name, ' ', l.surname) SEPARATOR ', ') AS assigned_lecturers
            FROM modules m 
            LEFT JOIN course_modules cm ON cm.module_id = m.module_id 
            LEFT JOIN module_lecturers ml ON ml.module_id = m.module_id 
            LEFT JOIN lecturers l ON l.lecturer_id = ml.lecturer_id
            GROUP BY m.module_id 
            ORDER BY m.module_code ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Find by ID
     */
    public static function findById(PDO $pdo, int $id): ?Module {
        $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    /**
     * Find by Module Code
     */
    public static function findByCode(PDO $pdo, string $code): ?Module {
        $stmt = $pdo->prepare("SELECT * FROM modules WHERE module_code = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch();
        return $row ? new self($row) : null;
    }

    public function toArray(): array {
        return [
            'moduleId' => $this->moduleId,
            'module_id' => $this->moduleId,
            'moduleName' => $this->moduleName,
            'module_name' => $this->moduleName,
            'moduleCode' => $this->moduleCode,
            'module_code' => $this->moduleCode,
            'credits' => $this->credits
        ];
    }
}
