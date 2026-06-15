<?php

require_once ROOT_PATH . '/core/controller.php';
require_once ROOT_PATH . '/app/models/user-model.php';

// PHPMailer — à installer via Composer ou en téléchargeant les sources
// require_once ROOT_PATH . '/vendor/autoload.php';

class AuthController extends Controller
{
    private UserModel $users;

    // Nombre maximum de tentatives de connexion avant blocage temporaire
    private const MAX_ATTEMPTS  = 5;
    private const LOCKOUT_TIME  = 15 * 60; // 15 minutes en secondes

    public function __construct()
    {
        $this->users = new UserModel();
    }

    // -----------------------------------------------------------------------
    // Connexion
    // -----------------------------------------------------------------------

    /**
     * GET  /auth/login → affiche le formulaire
     * POST /auth/login → traite la connexion
     */
    public function login(): void
    {
        // Redirige si déjà connecté
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        if ($this->isPost()) {
            $this->handleLogin();
            return;
        }

        $this->view('auth/login', ['title' => 'Connexion'], 'auth');
    }

    private function handleLogin(): void
    {
        $this->verifyCsrfToken();

        $email    = $this->input('email');
        $password = $this->input('password');

        // --- Validation basique ---
        if (empty($email) || empty($password)) {
            $this->flash('error', 'Veuillez remplir tous les champs.');
            $this->redirect('/auth/login');
            return;
        }

        // --- Rate limiting : vérifie si l'IP est temporairement bloquée ---
        if ($this->isLockedOut()) {
            $remaining = $this->lockoutRemaining();
            $this->flash('error', "Trop de tentatives. Réessayez dans {$remaining} minute(s).");
            $this->redirect('/auth/login');
            return;
        }

        // --- Recherche de l'utilisateur ---
        $user = $this->users->findActiveByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->incrementLoginAttempts();
            $remaining = self::MAX_ATTEMPTS - $this->getLoginAttempts();

            if ($remaining > 0) {
                $this->flash('error', "Email ou mot de passe incorrect. Il vous reste {$remaining} tentative(s).");
            } else {
                $this->flash('error', 'Compte temporairement bloqué suite à trop de tentatives. Réessayez dans 15 minutes.');
            }

            $this->redirect('/auth/login');
            return;
        }

        // --- Connexion réussie → réinitialise le compteur de tentatives ---
        $this->resetLoginAttempts();

        // --- Génère et envoie l'OTP (2FA) ---
        $otp = $this->users->createOtp($user['id']);
        $this->sendOtpEmail($user['email'], $user['firstname'], $otp);

        // Stocke temporairement les infos utilisateur en attendant la validation OTP
        $_SESSION['pending_user'] = [
            'id'        => $user['id'],
            'email'     => $user['email'],
            'firstname' => $user['firstname'],
        ];

        $this->logAction('LOGIN_OTP_SENT', 'auth');
        $this->redirect('/auth/verify-otp');
    }

    // -----------------------------------------------------------------------
    // Vérification OTP (2FA)
    // -----------------------------------------------------------------------

    /**
     * GET  /auth/verify-otp → affiche le formulaire OTP
     * POST /auth/verify-otp → vérifie le code saisi
     */
    public function verifyOtp(): void
    {
        // Doit venir du login — sinon on redirige
        if (empty($_SESSION['pending_user'])) {
            $this->redirect('/auth/login');
        }

        if ($this->isPost()) {
            $this->handleOtp();
            return;
        }

        $this->view('auth/verify-otp', [
            'title' => 'Vérification en deux étapes',
            'email' => $_SESSION['pending_user']['email'],
        ], 'auth');
    }

    private function handleOtp(): void
    {
        $this->verifyCsrfToken();

        // Recompose le code OTP depuis les 6 champs séparés
        $digits = $this->input('otp');

        // Support aussi d'un champ unique otp_code
        if (empty($digits)) {
            $digits = '';
            for ($i = 1; $i <= 6; $i++) {
                $digits .= $_POST["otp_{$i}"] ?? '';
            }
        }

        $pending = $_SESSION['pending_user'] ?? null;

        if (!$pending || !$this->users->verifyOtp($digits)) {
            $this->flash('error', 'Code incorrect ou expiré. Veuillez réessayer.');
            $this->redirect('/auth/verify-otp');
            return;
        }

        // --- OTP valide → charge les données complètes et crée la session ---
        $userId = (int) $pending['id'];
        $user   = $this->users->findActiveByEmail($pending['email']);

        if (!$user) {
            $this->flash('error', 'Erreur lors de la connexion. Veuillez réessayer.');
            $this->redirect('/auth/login');
            return;
        }

        $permissions = $this->users->getPermissions($userId);

        $_SESSION['user'] = [
            'id'          => $user['id'],
            'firstname'   => $user['firstname'],
            'lastname'    => $user['lastname'],
            'email'       => $user['email'],
            'role_name'   => $user['role_name'],
            'avatar'      => $user['avatar'],
            'permissions' => $permissions,
        ];

        unset($_SESSION['pending_user']);

        // Met à jour last_login et last_ip
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->users->recordLogin($userId, $ip);

        // Régénère l'ID de session pour éviter la fixation de session
        session_regenerate_id(true);

        $this->logAction('LOGIN_SUCCESS', 'auth');

        // Redirige vers la page demandée avant le login, ou le dashboard
        $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
        unset($_SESSION['redirect_after_login']);

        $this->redirect($redirect);
    }

    // -----------------------------------------------------------------------
    // Déconnexion
    // -----------------------------------------------------------------------

    /**
     * GET /auth/logout
     */
    public function logout(): void
    {
        $this->requireAuth();
        $this->logAction('LOGOUT', 'auth');

        // Détruit proprement la session
        $_SESSION = [];
        session_destroy();

        $this->redirect('/auth/login');
    }

    // -----------------------------------------------------------------------
    // Mot de passe oublié
    // -----------------------------------------------------------------------

    /**
     * GET  /auth/forgot-password → formulaire email
     * POST /auth/forgot-password → envoie le lien de reset
     */
    public function forgotPassword(): void
    {
        if ($this->isPost()) {
            $this->handleForgotPassword();
            return;
        }

        $this->view('auth/forgot-password', ['title' => 'Mot de passe oublié'], 'auth');
    }

    private function handleForgotPassword(): void
    {
        $this->verifyCsrfToken();

        $email = $this->input('email');

        if (empty($email)) {
            $this->flash('error', 'Veuillez saisir votre adresse email.');
            $this->redirect('/auth/forgot-password');
            return;
        }

        $user = $this->users->findActiveByEmail($email);

        // On affiche toujours le même message pour ne pas révéler
        // si un email est enregistré ou non (sécurité)
        $this->flash('info', 'Si cet email est associé à un compte, un lien de réinitialisation vous a été envoyé.');

        if ($user) {
            $token = $this->users->createResetToken((int) $user['id']);
            $link  = url('/auth/reset-password/' . $token);
            $this->sendResetEmail($user['email'], $user['firstname'], $link);
            $this->logAction('PASSWORD_RESET_REQUESTED', 'auth');
        }

        $this->redirect('/auth/forgot-password');
    }

    // -----------------------------------------------------------------------
    // Réinitialisation du mot de passe
    // -----------------------------------------------------------------------

    public function resetPassword(string $token): void
    {
        $userId = $this->users->validateResetToken($token);

        if (!$userId) {
            $this->flash('error', 'Ce lien est invalide ou a expiré. Veuillez recommencer.');
            $this->redirect('/auth/forgot-password');
            return;
        }

        if ($this->isPost()) {
            $this->handleResetPassword($userId);
            return;
        }

        $this->view('auth/reset-password', [
            'title' => 'Nouveau mot de passe',
            'token' => $token,
        ], 'auth');
    }

    private function handleResetPassword(int $userId): void
    {
        $this->verifyCsrfToken();

        $password        = $_POST['password']         ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 8) {
            $this->flash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            $this->redirect('/auth/forgot-password');
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->flash('error', 'Les mots de passe ne correspondent pas.');
            $this->redirect('/auth/forgot-password');
            return;
        }

        $this->users->updatePassword($userId, $password);
        $this->logAction('PASSWORD_RESET_SUCCESS', 'auth');

        $this->flash('success', 'Mot de passe modifié avec succès. Vous pouvez vous connecter.');
        $this->redirect('/auth/login');
    }

    // -----------------------------------------------------------------------
    // Rate limiting (basé sur la session)
    // -----------------------------------------------------------------------

    private function isLockedOut(): bool
    {
        $attempts  = $_SESSION['login_attempts']   ?? 0;
        $lockedAt  = $_SESSION['login_locked_at']  ?? 0;

        if ($attempts >= self::MAX_ATTEMPTS) {
            if (time() - $lockedAt < self::LOCKOUT_TIME) {
                return true;
            }
            // Verrou expiré → réinitialise
            $this->resetLoginAttempts();
        }

        return false;
    }

    private function lockoutRemaining(): int
    {
        $lockedAt = $_SESSION['login_locked_at'] ?? 0;
        $seconds  = self::LOCKOUT_TIME - (time() - $lockedAt);

        return max(1, (int) ceil($seconds / 60));
    }

    private function getLoginAttempts(): int
    {
        return $_SESSION['login_attempts'] ?? 0;
    }

    private function incrementLoginAttempts(): void
    {
        $_SESSION['login_attempts'] = ($this->getLoginAttempts()) + 1;

        if ($_SESSION['login_attempts'] >= self::MAX_ATTEMPTS) {
            $_SESSION['login_locked_at'] = time();
        }
    }

    private function resetLoginAttempts(): void
    {
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_at']);
    }

    // -----------------------------------------------------------------------
    // Envoi d'emails (PHPMailer)
    // -----------------------------------------------------------------------

    private function sendOtpEmail(string $to, string $firstname, string $otp): void
    {
        // TODO : intégrer PHPMailer
        // Pour les tests locaux, on logue le code dans error_log
        error_log("[AUTH] OTP pour {$to} : {$otp}");

        /*
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER');
        $mail->Password   = getenv('SMTP_PASS');
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(getenv('MAIL_FROM'), 'NetWatch Monitoring');
        $mail->addAddress($to, $firstname);
        $mail->isHTML(true);
        $mail->Subject = 'Votre code de vérification';
        $mail->Body    = "<p>Bonjour {$firstname},</p>
                          <p>Votre code de vérification est : <strong>{$otp}</strong></p>
                          <p>Il expire dans 10 minutes.</p>";
        $mail->send();
        */
    }

    private function sendResetEmail(string $to, string $firstname, string $link): void
    {
        error_log("[AUTH] Lien reset pour {$to} : {$link}");

        /*
        // Même configuration PHPMailer que sendOtpEmail()
        $mail->Subject = 'Réinitialisation de votre mot de passe';
        $mail->Body    = "<p>Bonjour {$firstname},</p>
                          <p><a href='{$link}'>Cliquez ici</a> pour réinitialiser votre mot de passe.</p>
                          <p>Ce lien expire dans 15 minutes.</p>";
        */
    }
}