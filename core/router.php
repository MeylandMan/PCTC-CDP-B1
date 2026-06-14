<?php

class Router
{
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => [],
    ];

    // -----------------------------------------------------------------------
    // Enregistrement des routes
    // -----------------------------------------------------------------------

    public function get(string $pattern, string $controller, string $action): void
    {
        $this->addRoute('GET', $pattern, $controller, $action);
    }

    public function post(string $pattern, string $controller, string $action): void
    {
        $this->addRoute('POST', $pattern, $controller, $action);
    }

    public function put(string $pattern, string $controller, string $action): void
    {
        $this->addRoute('PUT', $pattern, $controller, $action);
    }

    public function delete(string $pattern, string $controller, string $action): void
    {
        $this->addRoute('DELETE', $pattern, $controller, $action);
    }

    /**
     * Enregistre GET + POST sur le même pattern (formulaires).
     */
    public function form(string $pattern, string $controller, string $action): void
    {
        $this->addRoute('GET',  $pattern, $controller, $action);
        $this->addRoute('POST', $pattern, $controller, $action);
    }

    /**
     * Génère les 6 routes RESTful standard pour une ressource.
     */
    public function resource(string $base, string $controller): void
    {
        $this->get("/{$base}",             $controller, 'index');
        $this->get("/{$base}/create",      $controller, 'create');
        $this->post("/{$base}/store",      $controller, 'store');
        $this->get("/{$base}/:id/edit",    $controller, 'edit');
        $this->post("/{$base}/:id/update", $controller, 'update');
        $this->post("/{$base}/:id/delete", $controller, 'delete');
        $this->get("/{$base}/:id",         $controller, 'show');
    }

    // -----------------------------------------------------------------------
    // Dispatch
    // -----------------------------------------------------------------------

    public function dispatch(): void
    {
        $method = $this->resolveMethod();
        $uri    = $this->resolveUri();

        $match = $this->match($method, $uri);

        if ($match === null) {
            $this->handleNotFound($uri);
            return;
        }

        [$route, $params] = $match;

        $this->callAction($route['controller'], $route['action'], $params);
    }

    // -----------------------------------------------------------------------
    // Résolution méthode HTTP
    // -----------------------------------------------------------------------

    private function resolveMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper($_POST['_method']);
            if (in_array($spoofed, ['PUT', 'DELETE'], true)) {
                $method = $spoofed;
            }
        }

        return $method;
    }

    /**
     * Résout l'URI en supprimant le préfixe du sous-dossier.
     *
     * Exemple :
     *   BASE_URL  = 'http://localhost/cisco-pk'
     *   REQUEST_URI = '/cisco-pk/auth/login'
     *   → URI retournée : '/auth/login'
     *
     * Sans ce strip, toutes les routes retournent 404 car le router
     * compare '/cisco-pk/auth/login' contre le pattern '/auth/login'.
     */
    private function resolveUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = $uri ?: '/';

        // Supprime le préfixe du sous-dossier tiré de BASE_URL
        $basePath = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');

        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        // Normalise : toujours commencer par '/', jamais de slash final
        $uri = '/' . trim($uri, '/');

        return ($uri === '' || $uri === '//') ? '/' : $uri;
    }

    // -----------------------------------------------------------------------
    // Correspondance URI ↔ route
    // -----------------------------------------------------------------------

    private function match(string $method, string $uri): ?array
    {
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                array_shift($matches); // supprime le match complet [0]

                $params = array_combine($route['params'], $matches) ?: [];

                return [$route, $params];
            }
        }

        return null;
    }

    // -----------------------------------------------------------------------
    // Appel du controller
    // -----------------------------------------------------------------------

    private function controllerToFilename(string $controllerName): string
    {
        // "HomeController" → "home-controller"
        $kebab = strtolower(
            preg_replace('/(?<!^)[A-Z]/', '-$0', $controllerName)
        );
        return $kebab . '.php';
    }

    private function callAction(string $controllerName, string $action, array $params): void
    {
        $filename       = $this->controllerToFilename($controllerName);
        $controllerFile = ROOT_PATH . "/app/controllers/{$filename}";

        if (!file_exists($controllerFile)) {
            error_log("[Router] Controller introuvable : {$controllerFile}");
            $this->handleNotFound();
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            error_log("[Router] Classe introuvable : {$controllerName} dans {$controllerFile}");
            $this->handleNotFound();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            error_log("[Router] Méthode introuvable : {$controllerName}::{$action}");
            $this->handleNotFound();
            return;
        }

        call_user_func_array([$controller, $action], $params);
    }

    // -----------------------------------------------------------------------
    // Gestion 404
    // -----------------------------------------------------------------------

    private function handleNotFound(string $uri = ''): void
    {
        http_response_code(404);

        $errorView = ROOT_PATH . '/app/views/errors/404.php';

        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo '<h1>404 — Page introuvable</h1>';
            if (defined('APP_ENV') && APP_ENV === 'development' && $uri !== '') {
                echo '<p>URI : <code>' . htmlspecialchars($uri) . '</code></p>';
            }
        }
    }

    // -----------------------------------------------------------------------
    // Ajout de route (compilation regex)
    // -----------------------------------------------------------------------

    private function addRoute(string $method, string $pattern, string $controller, string $action): void
    {
        preg_match_all('/:([a-zA-Z_]+)/', $pattern, $matches);
        $params = $matches[1];

        $regex = preg_replace('/:([a-zA-Z_]+)/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        $this->routes[$method][] = [
            'pattern'    => $pattern,
            'regex'      => $regex,
            'params'     => $params,
            'controller' => $controller,
            'action'     => $action,
        ];
    }
}
