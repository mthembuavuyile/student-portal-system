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
