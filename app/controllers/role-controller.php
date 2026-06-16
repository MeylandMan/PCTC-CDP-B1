<?php

require_once ROOT_PATH . '/core/controller.php';

class RoleController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /roles
    public function index(): void
    {
        $this->requireRole(['super_admin', 'admin']);

        $stmt = $this->db->prepare(
            "SELECT r.*, COUNT(u.id) AS user_count
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id
             GROUP BY r.id
             ORDER BY r.id"
        );
        $stmt->execute();
        $roles = $stmt->fetchAll();

        $this->view('roles/index', [
            'title'         => 'Rôles & Permissions',
            'breadcrumbs'   => ['Rôles' => null],
            'roles'         => $roles,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // GET /roles/create
    public function create(): void
    {
        $this->requireRole('super_admin');

        $permissions = $this->allPermissions();

        $this->view('roles/form', [
            'title'         => 'Créer un rôle',
            'breadcrumbs'   => ['Rôles' => '/roles', 'Créer' => null],
            'role'          => null,
            'permissions'   => $permissions,
            'rolePermIds'   => [],
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // POST /roles/store
    public function store(): void
    {
        $this->requireRole('super_admin');
        $this->verifyCsrfToken();

        $name = $this->input('role_name');
        if (empty($name)) {
            $this->flash('error', 'Le nom du rôle est obligatoire.');
            $this->redirect('/roles/create');
            return;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO roles (role_name, description) VALUES (:name, :desc)"
        );
        $stmt->execute([
            ':name' => $name,
            ':desc' => $this->input('description'),
        ]);

        $roleId = (int) $this->db->lastInsertId();
        $this->syncPermissions($roleId);

        $this->logAction('CREATE_ROLE', 'roles');
        $this->flash('success', 'Rôle créé avec succès.');
        $this->redirect('/roles');
    }

    // GET /roles/:id/edit
    public function edit(string $id): void
    {
        $this->requireRole('super_admin');

        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $role = $stmt->fetch();

        if (!$role) $this->abort(404, 'Rôle introuvable.');

        $permStmt = $this->db->prepare(
            "SELECT permission_id FROM role_permissions WHERE role_id = :id"
        );
        $permStmt->execute([':id' => $id]);
        $rolePermIds = array_column($permStmt->fetchAll(), 'permission_id');

        $this->view('roles/form', [
            'title'         => 'Modifier — ' . e($role['role_name']),
            'breadcrumbs'   => ['Rôles' => '/roles', 'Modifier' => null],
            'role'          => $role,
            'permissions'   => $this->allPermissions(),
            'rolePermIds'   => $rolePermIds,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // POST /roles/:id/update
    public function update(string $id): void
    {
        $this->requireRole('super_admin');
        $this->verifyCsrfToken();

        $stmt = $this->db->prepare(
            "UPDATE roles SET role_name = :name, description = :desc WHERE id = :id"
        );
        $stmt->execute([
            ':name' => $this->input('role_name'),
            ':desc' => $this->input('description'),
            ':id'   => $id,
        ]);

        $this->syncPermissions((int) $id);

        $this->logAction('UPDATE_ROLE', 'roles');
        $this->flash('success', 'Rôle mis à jour.');
        $this->redirect('/roles');
    }

    // POST /roles/:id/delete
    public function delete(string $id): void
    {
        $this->requireRole('super_admin');
        $this->verifyCsrfToken();

        // Empêche la suppression si des utilisateurs ont ce rôle
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE role_id = :id");
        $stmt->execute([':id' => $id]);

        if ((int) $stmt->fetchColumn() > 0) {
            $this->flash('error', 'Impossible de supprimer un rôle assigné à des utilisateurs.');
            $this->redirect('/roles');
            return;
        }

        $del = $this->db->prepare("DELETE FROM roles WHERE id = :id");
        $del->execute([':id' => $id]);

        $this->logAction('DELETE_ROLE', 'roles');
        $this->flash('success', 'Rôle supprimé.');
        $this->redirect('/roles');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function allPermissions(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions ORDER BY permission_name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Synchronise les permissions cochées dans le formulaire avec role_permissions.
     */
    private function syncPermissions(int $roleId): void
    {
        $selected = $_POST['permissions'] ?? [];
        $selected = array_map('intval', $selected);

        // Supprime toutes les permissions actuelles du rôle
        $del = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = :rid");
        $del->execute([':rid' => $roleId]);

        // Réinsère celles sélectionnées
        if (!empty($selected)) {
            $insert = $this->db->prepare(
                "INSERT INTO role_permissions (role_id, permission_id) VALUES (:rid, :pid)"
            );
            foreach ($selected as $pid) {
                $insert->execute([':rid' => $roleId, ':pid' => $pid]);
            }
        }
    }
}