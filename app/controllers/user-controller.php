<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/user-model.php';

class UserController extends Controller
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    // GET /users
    public function index(): void
    {
        $this->requirePermission('manage_users');

        $page      = (int) $this->query('page', 1);
        $search    = (string) $this->query('search', '');
        $paginator = $this->users->allWithRole($page, 15, $search);

        $actorId       = (int) $this->currentUser()['id'];
        $isSuperAdmin  = $this->hasRole('super_admin');

        // Calcule, pour chaque ligne du tableau, si l'utilisateur connecté
        // a le droit d'agir sur cette personne. Évite de recalculer
        // canActOn() à répétition dans la vue (et évite d'exposer
        // la logique de hiérarchie dans le template).
        foreach ($paginator['data'] as &$row) {
            $row['can_act'] = $isSuperAdmin || $this->users->canActOn($actorId, (int) $row['id']);
        }
        unset($row);

        $this->view('users/index', [
            'title'         => 'Utilisateurs',
            'breadcrumbs'   => ['Utilisateurs' => null],
            'paginator'     => $paginator,
            'search'        => $search,
            'currentUserId' => $actorId,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // GET /users/:id
    public function show(string $id): void
    {
        $this->requirePermission('manage_users');

        $user = $this->users->findWithRole((int) $id);
        if (!$user) $this->abort(404, 'Utilisateur introuvable.');

        $actorId  = (int) $this->currentUser()['id'];
        $canAct   = $this->hasRole('super_admin')
                 || $this->users->canActOn($actorId, (int) $id);

        $this->view('users/show', [
            'title'         => e($user['firstname'] . ' ' . $user['lastname']),
            'breadcrumbs'   => ['Utilisateurs' => '/users', $user['firstname'] => null],
            'user'          => $user,
            'canAct'        => $canAct,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // GET /users/create
    public function create(): void
    {
        $this->requirePermission('manage_users');

        // Filtre les rôles proposés selon le rang de l'acteur — un admin
        // ne doit jamais voir 'admin' ou 'super_admin' dans la liste,
        // sinon il pourrait créer un compte plus privilégié que lui
        // (ou aussi privilégié), ce qui est une faille d'escalade de privilèges.
        $actorRoleRank = $this->users->roleRankByName($this->currentUser()['role_name']);
        $roles         = $this->users->assignableRoles($actorRoleRank);

        $this->view('users/form', [
            'title'         => 'Ajouter un utilisateur',
            'breadcrumbs'   => ['Utilisateurs' => '/users', 'Ajouter' => null],
            'user'          => null,
            'roles'         => $roles,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // POST /users/store
    public function store(): void
    {
        $this->requirePermission('manage_users');
        $this->verifyCsrfToken();

        $errors = $this->validateUser();

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/users/create');
            return;
        }

        $this->users->createUser([
            'firstname' => $this->input('firstname'),
            'lastname'  => $this->input('lastname'),
            'email'     => $this->input('email'),
            'password'  => $this->input('password'),
            'role_id'   => (int) $this->input('role_id'),
            'phone'     => $this->input('phone'),
        ]);

        $this->logAction('CREATE_USER', 'users');
        $this->flash('success', 'Utilisateur créé avec succès.');
        $this->redirect('/users');
    }

    // GET /users/:id/edit
    public function edit(string $id): void
    {
        $this->requirePermission('manage_users');

        $user = $this->users->findWithRole((int) $id);
        if (!$user) $this->abort(404, 'Utilisateur introuvable.');

        $actorId = (int) $this->currentUser()['id'];

        // Un super_admin peut tout faire (y compris s'éditer lui-même).
        // Un admin ne peut éditer que des comptes de rang strictement
        // inférieur (technicien, utilisateur, auditeur) — jamais un autre
        // admin ni un super_admin.
        if (!$this->hasRole('super_admin') && !$this->users->canActOn($actorId, (int) $id)) {
            $this->flash('error', 'Vous n\'avez pas les droits pour modifier ce compte.');
            $this->redirect('/users');
            return;
        }

        // Filtre les rôles proposés de la même façon qu'à la création :
        // un admin ne doit jamais pouvoir promouvoir quelqu'un vers
        // admin ou super_admin via le formulaire d'édition.
        $actorRoleRank = $this->users->roleRankByName($this->currentUser()['role_name']);
        $roles         = $this->hasRole('super_admin')
            ? $this->users->allRoles()
            : $this->users->assignableRoles($actorRoleRank);

        $this->view('users/form', [
            'title'         => 'Modifier — ' . e($user['firstname']),
            'breadcrumbs'   => ['Utilisateurs' => '/users', 'Modifier' => null],
            'user'          => $user,
            'roles'         => $roles,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // POST /users/:id/update
    public function update(string $id): void
    {
        $this->requirePermission('manage_users');
        $this->verifyCsrfToken();

        $user = $this->users->findById((int) $id);
        if (!$user) $this->abort(404, 'Utilisateur introuvable.');

        $actorId = (int) $this->currentUser()['id'];

        // Vérification côté serveur indépendante de edit() : empêche
        // un POST direct vers /users/:id/update qui contournerait le
        // contrôle fait sur la page d'édition.
        if (!$this->hasRole('super_admin') && !$this->users->canActOn($actorId, (int) $id)) {
            $this->flash('error', 'Vous n\'avez pas les droits pour modifier ce compte.');
            $this->redirect('/users');
            return;
        }

        $errors = $this->validateUser((int) $id);
        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/users/' . $id . '/edit');
            return;
        }

        $data = [
            'firstname' => $this->input('firstname'),
            'lastname'  => $this->input('lastname'),
            'email'     => $this->input('email'),
            'phone'     => $this->input('phone'),
            'role_id'   => (int) $this->input('role_id'),
        ];

        // Mise à jour mot de passe uniquement si renseigné
        $newPassword = $this->input('password');
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                $this->flash('error', 'Le mot de passe doit faire au moins 8 caractères.');
                $this->redirect('/users/' . $id . '/edit');
                return;
            }
            $this->users->updatePassword((int) $id, $newPassword);
        }

        $this->users->updateProfile((int) $id, $data);
        $this->logAction('UPDATE_USER', 'users');
        $this->flash('success', 'Utilisateur mis à jour.');
        $this->redirect('/users/' . $id);
    }

    // POST /users/:id/delete
    public function delete(string $id): void
    {
        $this->requireRole(['super_admin', 'admin']);
        $this->verifyCsrfToken();

        $actorId = (int) $this->currentUser()['id'];

        // Empêche la suppression de soi-même
        if ((int) $id === $actorId) {
            $this->flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            $this->redirect('/users');
            return;
        }

        // Un admin ne peut supprimer que des comptes de rang strictement
        // inférieur. Un super_admin peut supprimer n'importe qui (sauf lui-même,
        // déjà bloqué ci-dessus).
        if (!$this->hasRole('super_admin') && !$this->users->canActOn($actorId, (int) $id)) {
            $this->flash('error', 'Vous n\'avez pas les droits pour supprimer ce compte.');
            $this->redirect('/users');
            return;
        }

        $this->users->delete((int) $id);
        $this->logAction('DELETE_USER', 'users');
        $this->flash('success', 'Utilisateur supprimé.');
        $this->redirect('/users');
    }

    // POST /users/:id/toggle-active
    public function toggleActive(string $id): void
    {
        $this->requirePermission('manage_users');
        $this->verifyCsrfToken();

        $actorId = (int) $this->currentUser()['id'];

        if ((int) $id === $actorId) {
            $this->json(['success' => false, 'message' => 'Impossible de désactiver votre propre compte.']);
            return;
        }

        // Un admin ne peut activer/désactiver que des comptes de rang
        // strictement inférieur. Bloque toute tentative sur un pair ou
        // un super_admin, y compris via un appel AJAX direct.
        if (!$this->hasRole('super_admin') && !$this->users->canActOn($actorId, (int) $id)) {
            $this->json(['success' => false, 'message' => 'Vous n\'avez pas les droits pour modifier ce compte.']);
            return;
        }

        $this->users->toggleActive((int) $id);
        $this->logAction('TOGGLE_USER_ACTIVE', 'users');

        $user = $this->users->findById((int) $id);
        $this->json([
            'success'   => true,
            'is_active' => (bool) $user['is_active'],
            'message'   => $user['is_active'] ? 'Compte activé.' : 'Compte désactivé.',
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function validateUser(?int $excludeId = null): array
    {
        $errors = [];

        if (empty($this->input('firstname'))) $errors[] = 'Le prénom est obligatoire.';
        if (empty($this->input('lastname')))  $errors[] = 'Le nom est obligatoire.';
        if (empty($this->input('role_id')))   $errors[] = 'Le rôle est obligatoire.';

        $email = $this->input('email');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse email est invalide.';
        } else {
            $existing = $this->users->findOneBy(['email' => $email]);
            if ($existing && (int) $existing['id'] !== $excludeId) {
                $errors[] = 'Cet email est déjà utilisé.';
            }
        }

        // Mot de passe obligatoire uniquement à la création
        if ($excludeId === null && empty($this->input('password'))) {
            $errors[] = 'Le mot de passe est obligatoire.';
        }

        // -------------------------------------------------------------------
        // Empêche l'escalade de privilèges : un admin ne peut pas attribuer
        // un rôle 'admin' ou 'super_admin', même via une requête POST forgée
        // qui contournerait le <select> filtré côté vue. C'est la vraie
        // barrière de sécurité — le filtre dans create()/edit() n'est qu'une
        // aide visuelle, cette vérification serveur est ce qui compte vraiment.
        $roleId = (int) $this->input('role_id');
        if ($roleId > 0 && !$this->hasRole('super_admin')) {
            $targetRoleRank = $this->roleRankOfRoleId($roleId);
            $actorRoleRank  = $this->users->roleRankByName($this->currentUser()['role_name']);

            if ($targetRoleRank >= $actorRoleRank) {
                $errors[] = 'Vous n\'avez pas le droit d\'attribuer ce rôle.';
            }
        }

        return $errors;
    }

    /**
     * Rang du rôle correspondant à un role_id (et non un user_id).
     * roleRank() dans UserModel prend un user_id ; ici on a besoin
     * du rang directement depuis l'ID du rôle choisi dans le formulaire.
     */
    private function roleRankOfRoleId(int $roleId): int
    {
        $roles = $this->users->allRoles();
        foreach ($roles as $role) {
            if ((int) $role['id'] === $roleId) {
                return $this->users->roleRankByName($role['role_name']);
            }
        }
        return 0;
    }
}