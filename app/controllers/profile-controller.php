<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/user-model.php';

class ProfileController extends Controller
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    // -----------------------------------------------------------------------
    // GET + POST /profile
    // -----------------------------------------------------------------------
    public function index(): void
    {
        $this->requireAuth();

        if ($this->isPost()) {
            $this->handleUpdate();
            return;
        }

        $user = $this->users->findWithRole((int) $this->currentUser()['id']);

        $this->view('profile/index', [
            'title'         => 'Mon profil',
            'breadcrumbs'   => ['Mon profil' => null],
            'user'          => $user,
            'alertCount'    => 0,
            'incidentCount' => 0,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /profile/password
    // -----------------------------------------------------------------------
    public function updatePassword(): void
    {
        $this->requireAuth();
        $this->verifyCsrfToken();

        $userId  = (int) $this->currentUser()['id'];
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Vérifie l'ancien mot de passe
        $user = $this->users->findById($userId);
        if (!password_verify($current, $user['password'])) {
            $this->flash('error', 'Mot de passe actuel incorrect.');
            $this->redirect('/profile');
            return;
        }

        if (strlen($new) < 8) {
            $this->flash('error', 'Le nouveau mot de passe doit faire au moins 8 caractères.');
            $this->redirect('/profile');
            return;
        }

        if ($new !== $confirm) {
            $this->flash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            $this->redirect('/profile');
            return;
        }

        $this->users->updatePassword($userId, $new);
        $this->logAction('UPDATE_PASSWORD', 'profile');
        $this->flash('success', 'Mot de passe mis à jour avec succès.');
        $this->redirect('/profile');
    }

    // -----------------------------------------------------------------------
    // POST /profile/avatar
    // -----------------------------------------------------------------------
    public function updateAvatar(): void
    {
        $this->requireAuth();
        $this->verifyCsrfToken();

        $userId = (int) $this->currentUser()['id'];

        if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Erreur lors de l\'upload du fichier.');
            $this->redirect('/profile');
            return;
        }

        $file    = $_FILES['avatar'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $mime    = mime_content_type($file['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $this->flash('error', 'Format non supporté. Utilisez JPG, PNG ou WebP.');
            $this->redirect('/profile');
            return;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            $this->flash('error', 'Le fichier ne doit pas dépasser 2 Mo.');
            $this->redirect('/profile');
            return;
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $dest     = ROOT_PATH . '/public/assets/avatars/' . $filename;

        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->flash('error', 'Impossible de sauvegarder le fichier.');
            $this->redirect('/profile');
            return;
        }

        $avatarUrl = '/public/assets/avatars/' . $filename;
        $this->users->execute(
            'UPDATE users SET avatar = :avatar WHERE id = :id',
            [':avatar' => $avatarUrl, ':id' => $userId]
        );

        // Met à jour la session
        $_SESSION['user']['avatar'] = $avatarUrl;

        $this->logAction('UPDATE_AVATAR', 'profile');
        $this->flash('success', 'Photo de profil mise à jour.');
        $this->redirect('/profile');
    }

    // -----------------------------------------------------------------------
    // Traitement formulaire infos
    // -----------------------------------------------------------------------
    private function handleUpdate(): void
    {
        $this->verifyCsrfToken();

        $userId = (int) $this->currentUser()['id'];

        $firstname = $this->input('firstname');
        $lastname  = $this->input('lastname');
        $phone     = $this->input('phone');

        if (empty($firstname) || empty($lastname)) {
            $this->flash('error', 'Le prénom et le nom sont obligatoires.');
            $this->redirect('/profile');
            return;
        }

        $this->users->execute(
            'UPDATE users
             SET firstname = :fn, lastname = :ln, phone = :ph, updated_at = NOW()
             WHERE id = :id',
            [':fn' => $firstname, ':ln' => $lastname, ':ph' => $phone, ':id' => $userId]
        );

        // Met à jour la session immédiatement
        $_SESSION['user']['firstname'] = $firstname;
        $_SESSION['user']['lastname']  = $lastname;

        $this->logAction('UPDATE_PROFILE', 'profile');
        $this->flash('success', 'Profil mis à jour.');
        $this->redirect('/profile');
    }
}