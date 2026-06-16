<?php

require_once ROOT_PATH . '/core/model.php';

class UserModel extends Model
{
    protected string $table      = 'users';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'avatar',
    ];

    // -----------------------------------------------------------------------
    // Authentification
    // -----------------------------------------------------------------------

    public function findActiveByEmail(string $email): array|false
    {
        return $this->queryOne(
            'SELECT u.*, r.role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
               AND u.is_active = 1
             LIMIT 1',
            [':email' => $email]
        );
    }

    public function getPermissions(int $userId): array
    {
        $rows = $this->query(
            'SELECT p.permission_name
             FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             JOIN users u             ON u.role_id         = rp.role_id
             WHERE u.id = :user_id',
            [':user_id' => $userId]
        );

        return array_column($rows, 'permission_name');
    }

    public function recordLogin(int $userId, string $ip): void
    {
        $this->execute(
            'UPDATE users
             SET last_login = NOW(), last_ip = :ip
             WHERE id = :id',
            [':ip' => $ip, ':id' => $userId]
        );
    }

    // -----------------------------------------------------------------------
    // Réinitialisation du mot de passe
    // -----------------------------------------------------------------------

    public function createResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));

        $_SESSION['password_reset'] = [
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => time() + (15 * 60), // 15 minutes
        ];

        return $token;
    }

    public function validateResetToken(string $token): ?int
    {
        $reset = $_SESSION['password_reset'] ?? null;

        if (
            $reset === null
            || !hash_equals($reset['token'], $token)
            || time() > $reset['expires_at']
        ) {
            return null;
        }

        return (int) $reset['user_id'];
    }

    public function updatePassword(int $userId, string $plainPassword): void
    {
        $this->execute(
            'UPDATE users SET password = :hash, updated_at = NOW() WHERE id = :id',
            [
                ':hash' => password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]),
                ':id'   => $userId,
            ]
        );

        unset($_SESSION['password_reset']);
    }

    // -----------------------------------------------------------------------
    // OTP (2FA par email)
    // -----------------------------------------------------------------------

    public function createOtp(int $userId): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $_SESSION['otp'] = [
            'user_id'    => $userId,
            'code'       => $otp,
            'expires_at' => time() + (10 * 60), // 10 minutes
            'attempts'   => 0,
        ];

        return $otp;
    }

    public function verifyOtp(string $submitted): bool
    {
        $otp = $_SESSION['otp'] ?? null;

        if ($otp === null || time() > $otp['expires_at']) {
            unset($_SESSION['otp']);
            return false;
        }

        // Incrément des tentatives
        $_SESSION['otp']['attempts']++;

        if ($_SESSION['otp']['attempts'] > 3) {
            unset($_SESSION['otp']);
            return false;
        }

        if (!hash_equals($otp['code'], $submitted)) {
            return false;
        }

        // OTP valide — on le supprime pour éviter la réutilisation
        unset($_SESSION['otp']);
        return true;
    }

    // -----------------------------------------------------------------------
    // Gestion des utilisateurs (CRUD admin)
    // -----------------------------------------------------------------------


    public function createUser(array $data): int
    {
        return $this->execute(
            'INSERT INTO users
                (firstname, lastname, email, password, role_id, phone, is_active, created_at, updated_at)
             VALUES
                (:firstname, :lastname, :email, :password, :role_id, :phone, 1, NOW(), NOW())',
            [
                ':firstname' => $data['firstname'],
                ':lastname'  => $data['lastname'],
                ':email'     => $data['email'],
                ':password'  => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                ':role_id'   => $data['role_id'],
                ':phone'     => $data['phone'] ?? null,
            ]
        );

        return (int) Database::getInstance()->lastInsertId();
    }

    public function toggleActive(int $userId): void
    {
        $this->execute(
            'UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE id = :id',
            [':id' => $userId]
        );
    }

    // Méthodes ajoutées pour UserController

    /**
     * Trouve un utilisateur avec son rôle.
     */
    public function findWithRole(int $id): array|false
    {
        return $this->queryOne(
            'SELECT u.*, r.role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id',
            [':id' => $id]
        );
    }

    /**
     * Liste paginée avec rôle + filtre recherche.
     */
    public function allWithRole(int $page = 1, int $perPage = 15, string $search = ''): array
    {
        $where    = '1 = 1';
        $bindings = [];

        if ($search !== '') {
            $where              = '(u.firstname LIKE :s OR u.lastname LIKE :s OR u.email LIKE :s)';
            $bindings[':s']     = '%' . $search . '%';
        }

        $offset = ($page - 1) * $perPage;

        $data = $this->query(
            "SELECT u.id, u.firstname, u.lastname, u.email, u.phone,
                    u.is_active, u.last_login, u.created_at, r.role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE {$where}
             ORDER BY u.created_at DESC
             LIMIT :limit OFFSET :offset",
            array_merge($bindings, [':limit' => $perPage, ':offset' => $offset])
        );

        $total = (int) $this->queryOne(
            "SELECT COUNT(*) AS n FROM users u WHERE {$where}",
            $bindings
        )['n'];

        return [
            'data'        => $data,
            'total'       => $total,
            'perPage'     => $perPage,
            'currentPage' => $page,
            'lastPage'    => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Retourne tous les rôles pour le <select>.
     */
    public function allRoles(): array
    {
        return $this->query('SELECT id, role_name, description FROM roles ORDER BY id');
    }

    /**
     * Met à jour le profil (sans mot de passe).
     */
    public function updateProfile(int $userId, array $data): void
    {
        $this->execute(
            'UPDATE users
             SET firstname = :firstname, lastname = :lastname, email = :email,
                 phone = :phone, role_id = :role_id, updated_at = NOW()
             WHERE id = :id',
            [
                ':firstname' => $data['firstname'],
                ':lastname'  => $data['lastname'],
                ':email'     => $data['email'],
                ':phone'     => $data['phone'] ?? null,
                ':role_id'   => $data['role_id'],
                ':id'        => $userId,
            ]
        );
    }

}