<?php
/**
 * User Class
 * 
 * Domain model representing a system user with authentication credentials
 * and role-based permissions (admin, student).
 * 
 * Prepared by: Mr Avuyile Mthembu
 * Position: Intern: Software Developer
 * Directorate: Information Technology Business Solutions (ITBS)
 * Department: Department of Forestry, Fisheries and the Environment (DFFE)
 */

class User {
    private ?int $id;
    private ?string $username;
    private ?string $password;
    private ?string $role;
    private ?string $studentIdNumber;

    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (int)$data['id'] : null;
        $this->username = $data['username'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->role = $data['role'] ?? 'student';
        $this->studentIdNumber = $data['student_id_number'] ?? null;
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsername(): ?string { return $this->username; }
    public function getRole(): ?string { return $this->role; }
    public function getStudentIdNumber(): ?string { return $this->studentIdNumber; }
    public function getPasswordHash(): ?string { return $this->password; }

    /**
     * Verifies a plain-text password against the user's stored bcrypt hash.
     */
    public function verifyPassword(string $plainPassword): bool {
        if (empty($this->password)) {
            return false;
        }
        return password_verify($plainPassword, $this->password);
    }

    /**
     * Updates the password hash in the database for this user.
     * 
     * @param PDO $pdo PDO database connection
     * @param string $newPassword Plain-text new password
     * @return bool True on success
     * @throws InvalidArgumentException If password fails complexity rules
     */
    public function updatePassword(PDO $pdo, string $newPassword): bool {
        if (strlen($newPassword) < 6) {
            throw new InvalidArgumentException("Password must be at least 6 characters long.");
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :pwd WHERE id = :id");
        $success = $stmt->execute([
            ':pwd' => $hash,
            ':id'  => $this->id
        ]);

        if ($success) {
            $this->password = $hash;
        }

        return $success;
    }

    /**
     * Find a user record by user ID.
     */
    public static function findById(PDO $pdo, int $id): ?User {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        return $data ? new self($data) : null;
    }

    /**
     * Find a user record by username.
     */
    public static function findByUsername(PDO $pdo, string $username): ?User {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :uname LIMIT 1");
        $stmt->execute([':uname' => $username]);
        $data = $stmt->fetch();
        return $data ? new self($data) : null;
    }

    /**
     * Find a student's user account by student number.
     */
    public static function findByStudentNumber(PDO $pdo, string $studentNumber): ?User {
        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE student_id_number = :num OR username = :num2 
            LIMIT 1
        ");
        $stmt->execute([':num' => $studentNumber, ':num2' => $studentNumber]);
        $data = $stmt->fetch();
        return $data ? new self($data) : null;
    }

    /**
     * Change a user's password with verification of current password.
     * 
     * @param PDO $pdo
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @param string $confirmPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public static function changePassword(PDO $pdo, int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array {
        if (empty($currentPassword)) {
            return ['success' => false, 'message' => 'Please enter your current password.'];
        }

        if (empty($newPassword)) {
            return ['success' => false, 'message' => 'Please enter a new password.'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters long.'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'New password and confirmation do not match.'];
        }

        $user = self::findById($pdo, $userId);
        if (!$user) {
            return ['success' => false, 'message' => 'User account not found.'];
        }

        if (!$user->verifyPassword($currentPassword)) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        if ($currentPassword === $newPassword) {
            return ['success' => false, 'message' => 'New password cannot be identical to your current password.'];
        }

        try {
            $user->updatePassword($pdo, $newPassword);
            return ['success' => true, 'message' => 'Your password has been successfully updated!'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update password: ' . $e->getMessage()];
        }
    }

    /**
     * Administrative password reset for an existing student.
     * If user account does not exist yet, creates it.
     * 
     * @param PDO $pdo
     * @param string $studentNumber
     * @param string $newPassword
     * @return array ['success' => bool, 'message' => string]
     */
    public static function adminResetPassword(PDO $pdo, string $studentNumber, string $newPassword): array {
        $studentNumber = trim($studentNumber);
        if (empty($studentNumber)) {
            return ['success' => false, 'message' => 'Student number cannot be empty.'];
        }

        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters long.'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $user = self::findByStudentNumber($pdo, $studentNumber);

        try {
            if ($user) {
                $stmt = $pdo->prepare("UPDATE users SET password = :pwd WHERE id = :id");
                $stmt->execute([':pwd' => $hash, ':id' => $user->getId()]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password, role, student_id_number) 
                    VALUES (:uname, :pwd, 'student', :sid)
                ");
                $stmt->execute([
                    ':uname' => $studentNumber,
                    ':pwd'   => $hash,
                    ':sid'   => $studentNumber
                ]);
            }
            return [
                'success' => true,
                'message' => "Password for student {$studentNumber} has been updated successfully!"
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error updating password: ' . $e->getMessage()
            ];
        }
    }
}
