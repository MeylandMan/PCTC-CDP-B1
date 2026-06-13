<?php

class Router
{
    // ---------------------------------------------------------------------------
    // Registre des routes, indexé par méthode HTTP
    // ---------------------------------------------------------------------------

    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => [],
    ];

    // ---------------------------------------------------------------------------
    // Enregistrement des routes
    // ---------------------------------------------------------------------------

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

    public function form(string $pattern, string $controller, string $action): void
    {
        $this->addRoute('GET',  $pattern, $controller, $action);
        $this->addRoute('POST', $pattern, $controller, $action);
    }

    public function resource(string $base, string $controller): void
    {
        $this->get("/{$base}",                $controller, 'index');
        $this->get("/{$base}/create",         $controller, 'create');
        $this->post("/{$base}/store",         $controller, 'store');
        $this->get("/{$base}/:id/edit",       $controller, 'edit');
        $this->post("/{$base}/:id/update",    $controller, 'update');
        $this->post("/{$base}/:id/delete",    $controller, 'delete');
        $this->get("/{$base}/:id",            $controller, 'show');
    }

    // ---------------------------------------------------------------------------
    // Dispatch
    // ---------------------------------------------------------------------------

    public function dispatch(): void
    {
        $method = $this->resolveMethod();
        $uri    = $this->resolveUri();

        $match = $this->match($method, $uri);

        if ($match === null) {
            $this->handleNotFound();
            return;
        }

        [$route, $params] = $match;

        $this->callAction($route['controller'], $route['action'], $params);
    }

    // ---------------------------------------------------------------------------
    // Résolution de la méthode HTTP
    // ---------------------------------------------------------------------------

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

    private function resolveUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');

        return $uri === '//' ? '/' : $uri;
    }

    // ---------------------------------------------------------------------------
    // Correspondance URL ↔ route
    // ---------------------------------------------------------------------------

    private function match(string $method, string $uri): ?array
    {
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (preg_match($route['regex'], $uri, $matches)) {
                // $matches[0] = URI complète, on ne garde que les groupes capturés
                array_shift($matches);

                // Association nom de paramètre → valeur capturée
                $params = array_combine($route['params'], $matches) ?: [];

                return [$route, $params];
            }
        }

        return null;
    }

    // ---------------------------------------------------------------------------
    // Appel du controller
    // ---------------------------------------------------------------------------

    private function callAction(string $controllerName, string $action, array $params): void
    {
        $controllerFile = ROOT_PATH . "/app/controllers/{$controllerName}.php";

        if (!file_exists($controllerFile)) {
            error_log("[Router] Controller introuvable : {$controllerFile}");
            $this->handleNotFound();
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            error_log("[Router] Classe introuvable : {$controllerName}");
            $this->handleNotFound();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            error_log("[Router] Méthode introuvable : {$controllerName}::{$action}");
            $this->handleNotFound();
            return;
        }

        // Appel de l'action avec les paramètres URL en argument
        // Exemple : $controller->show('3') pour /devices/:id
        call_user_func_array([$controller, $action], $params);
    }

    // ---------------------------------------------------------------------------
    // Gestion des erreurs de routing
    // ---------------------------------------------------------------------------

    private function handleNotFound(): void
    {
        http_response_code(404);

        $errorView = ROOT_PATH . '/app/views/errors/404.php';

        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo '<h1>404 — Page introuvable</h1>';
        }
    }

    // ---------------------------------------------------------------------------
    // Méthodes internes
    // ---------------------------------------------------------------------------

    private function addRoute(string $method, string $pattern, string $controller, string $action): void
    {
        $params = [];

        preg_match_all('/:([a-zA-Z_]+)/', $pattern, $matches);
        $params = $matches[1]; // ['id'], ['slug'], etc.

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