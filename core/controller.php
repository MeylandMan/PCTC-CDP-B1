<?php

abstract class Controller
{
    // ---------------------------------------------------------------------------
    // Chargement des vues
    // ---------------------------------------------------------------------------

    /**
     * Charge et affiche une vue en l'encapsulant dans le layout principal.
     *
     * @param string $view   Chemin relatif depuis app/views/ sans extension
     *                       Exemple : 'dashboard/index' → app/views/dashboard/index.php
     * @param array  $data   Variables injectées dans la vue (extract)
     * @param string $layout Layout à utiliser (app/views/layouts/<layout>.php)
     */
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        // Injecte automatiquement les compteurs de la sidebar (alertes/incidents)
        // pour tous les controllers, sans que chacun ait à y penser explicitement.
        // Un controller peut toujours surcharger ces valeurs en les passant
        // explicitement dans $data (la fusion ci-dessous donne priorité à $data).
        if ($this->isLoggedIn() && !isset($data['alertCount'])) {
            $data['alertCount'] = $this->sidebarAlertCount();
        }
        if ($this->isLoggedIn() && !isset($data['incidentCount'])) {
            $data['incidentCount'] = $this->sidebarIncidentCount();
        }

        // Rend les clés du tableau $data disponibles comme variables dans la vue
        // Exemple : ['title' => 'Dashboard'] → $title dans la vue
        extract($data, EXTR_SKIP);

        // Chemin complet vers le fichier de vue
        $viewFile = ROOT_PATH . "/app/views/{$view}.php";

        if (!file_exists($viewFile)) {
            throw new RuntimeException("Vue introuvable : {$viewFile}");
        }

        // Capture le rendu de la vue dans un buffer
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Injecte le contenu dans le layout
        $layoutFile = ROOT_PATH . "/app/views/layouts/{$layout}.php";

        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout introuvable : {$layoutFile}");
        }

        require $layoutFile;
    }

    /**
     * Nombre d'alertes ouvertes, mis en cache statique pour éviter
     * une requête par appel si view() est appelée plusieurs fois.
     */
    private function sidebarAlertCount(): int
    {
        static $count = null;
        if ($count === null) {
            try {
                $count = (int) Database::getInstance()->getConnection()
                    ->query("SELECT COUNT(*) FROM alerts WHERE status = 'open'")
                    ->fetchColumn();
            } catch (PDOException $e) {
                $count = 0;
            }
        }
        return $count;
    }

    /**
     * Nombre d'incidents actifs (hors résolus/fermés).
     */
    private function sidebarIncidentCount(): int
    {
        static $count = null;
        if ($count === null) {
            try {
                $count = (int) Database::getInstance()->getConnection()
                    ->query("SELECT COUNT(*) FROM incidents WHERE status NOT IN ('resolved','closed')")
                    ->fetchColumn();
            } catch (PDOException $e) {
                $count = 0;
            }
        }
        return $count;
    }

    protected function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewFile = ROOT_PATH . "/app/views/{$view}.php";

        if (!file_exists($viewFile)) {
            throw new RuntimeException("Vue partielle introuvable : {$viewFile}");
        }

        require $viewFile;
    }

    // ---------------------------------------------------------------------------
    // Réponses HTTP
    // ---------------------------------------------------------------------------

    protected function redirect(string $url): void
    {
        // Si l'URL est relative (commence par /), on préfixe avec BASE_URL
        // pour que la redirection fonctionne dans un sous-dossier (ex: /cisco-pk)
        if (str_starts_with($url, '/') && defined('BASE_URL')) {
            $url = rtrim(BASE_URL, '/') . $url;
        }
        header("Location: {$url}");
        exit;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function abort(int $code = 404, string $message = ''): void
    {
        http_response_code($code);

        $errorView = ROOT_PATH . "/app/views/errors/{$code}.php";

        if (file_exists($errorView)) {
            extract(['message' => $message], EXTR_SKIP);
            require $errorView;
        } else {
            echo "<h1>Erreur {$code}</h1><p>{$message}</p>";
        }

        exit;
    }

    // ---------------------------------------------------------------------------
    // Session & authentification
    // ---------------------------------------------------------------------------

    protected function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            // Mémorise l'URL demandée pour rediriger après connexion
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';
            $this->redirect('/auth/login');
        }
    }

    /**
     * Vérifie que l'utilisateur est connecté ET possède le rôle requis.
     * Retourne une erreur 403 si le rôle est insuffisant.
     *
     * Exemple : $this->requireRole(['super_admin', 'admin'])
     *
     * @param string|array $roles Rôle(s) autorisé(s)
     */
    protected function requireRole(string|array $roles): void
    {
        $this->requireAuth();

        $roles       = (array) $roles;
        $currentRole = $_SESSION['user']['role_name'] ?? '';

        if (!in_array($currentRole, $roles, true)) {
            $this->abort(403, 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.');
        }
    }

    protected function requirePermission(string $permission): void
    {
        $this->requireAuth();

        $permissions = $_SESSION['user']['permissions'] ?? [];

        if (!in_array($permission, $permissions, true)) {
            $this->abort(403, 'Permission insuffisante : ' . $permission);
        }
    }

    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    protected function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function hasRole(string|array $roles): bool
    {
        $roles       = (array) $roles;
        $currentRole = $_SESSION['user']['role_name'] ?? '';

        return in_array($currentRole, $roles, true);
    }

    protected function hasPermission(string $permission): bool
    {
        $permissions = $_SESSION['user']['permissions'] ?? [];

        return in_array($permission, $permissions, true);
    }

    // ---------------------------------------------------------------------------
    // Requête HTTP
    // ---------------------------------------------------------------------------

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        if (!isset($_POST[$key])) {
            return $default;
        }

        return is_string($_POST[$key])
            ? trim(htmlspecialchars($_POST[$key], ENT_QUOTES, 'UTF-8'))
            : $_POST[$key];
    }

    protected function query(string $key, mixed $default = null): mixed
    {
        if (!isset($_GET[$key])) {
            return $default;
        }

        return is_string($_GET[$key])
            ? trim(htmlspecialchars($_GET[$key], ENT_QUOTES, 'UTF-8'))
            : $_GET[$key];
    }

    // ---------------------------------------------------------------------------
    // Protection CSRF
    // ---------------------------------------------------------------------------

    protected function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrfToken(): void
    {
        $submitted = $_POST['_csrf_token'] ?? '';
        $expected  = $_SESSION['csrf_token'] ?? '';

        // hash_equals résiste aux timing attacks
        if (empty($submitted) || !hash_equals($expected, $submitted)) {
            $this->abort(403, 'Token CSRF invalide. Veuillez recharger la page et réessayer.');
        }

        // NOTE : le token n'est PAS régénéré ici (pas de rotation à usage unique).
        // Il reste valide pour toute la durée de la session, ce qui est nécessaire
        // pour les pages qui font plusieurs requêtes AJAX successives (toggle,
        // changement de statut, etc.) sans recharger la page. La rotation à
        // chaque vérification cassait ces appels après la première requête :
        // le jeton JS en mémoire devenait obsolète dès la 2e tentative.
        // La protection reste effective car le cookie de session est
        // HttpOnly + SameSite=Strict (voir public/index.php).
    }

    // ---------------------------------------------------------------------------
    // Messages flash
    // ---------------------------------------------------------------------------

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    public static function getFlashMessages(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        return $messages;
    }

    // ---------------------------------------------------------------------------
    // Journalisation des actions
    // ---------------------------------------------------------------------------

    protected function logAction(string $action, string $module): void
    {
        try {
            $db = Database::getInstance()->getConnection();

            $stmt = $db->prepare(
                'INSERT INTO logs (user_id, action, module, ip_address, user_agent, created_at)
                 VALUES (:user_id, :action, :module, :ip, :ua, NOW())'
            );

            $stmt->execute([
                ':user_id' => $_SESSION['user']['id'] ?? null,
                ':action'  => $action,
                ':module'  => $module,
                ':ip'      => $_SERVER['REMOTE_ADDR']     ?? 'unknown',
                ':ua'      => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            ]);
        } catch (PDOException $e) {
            // On logue l'erreur mais on ne bloque pas l'exécution principale
            error_log('[Controller] Échec logAction : ' . $e->getMessage());
        }
    }
}