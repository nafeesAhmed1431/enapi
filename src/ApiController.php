<?php

namespace NafeesAhmed\Enapi;

class ApiController
{
    protected array $routes = [];
    protected array $middleware = [];
    protected array $models = [];
    protected string $modelsPath;
    protected string $modelsNamespace;
    protected ?Database $db = null;

    /**
     * Initialize controller with models directory
     */
    public function __construct(string $modelsPath = '', string $modelsNamespace = '', ?Database $db = null)
    {
        $this->modelsPath = $modelsPath;
        $this->modelsNamespace = $modelsNamespace;
        $this->db = $db;

        if (!empty($modelsPath) && !empty($modelsNamespace)) {
            $this->loadModels();
            $this->registerResourceRoutes();
        }
    }

    /**
     * Set database connection
     */
    public function setDatabase(Database $db): self
    {
        $this->db = $db;
        return $this;
    }

    /**
     * Load all models from the models directory
     */
    protected function loadModels(): void
    {
        if (!is_dir($this->modelsPath)) {
            return;
        }

        $files = glob($this->modelsPath . '/*.php');
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClassName = $this->modelsNamespace . '\\' . $className;
            
            if (class_exists($fullClassName) && is_subclass_of($fullClassName, Model::class)) {
                $model = new $fullClassName($this->db);
                $this->models[$model->getResourceName()] = $model;
            }
        }
    }

    /**
     * Register CRUD routes for all models
     */
    protected function registerResourceRoutes(): void
    {
        foreach ($this->models as $resourceName => $model) {
            // GET /api/{resource} - List all
            $this->get("/api/{$resourceName}", function() use ($model) {
                return $this->listAll($model);
            });

            // GET /api/{resource}/{id} - Get one
            $this->get("/api/{$resourceName}/{id}", function() use ($model) {
                $id = $this->getPathParam('id');
                return $this->getOne($model, $id);
            });

            // POST /api/{resource} - Create
            $this->post("/api/{$resourceName}", function() use ($model) {
                $data = $this->getRequestBody();
                return $this->create($model, $data);
            });

            // PUT /api/{resource}/{id} - Update
            $this->put("/api/{$resourceName}/{id}", function() use ($model) {
                $id = $this->getPathParam('id');
                $data = $this->getRequestBody();
                return $this->update($model, $id, $data);
            });

            // DELETE /api/{resource}/{id} - Delete
            $this->delete("/api/{$resourceName}/{id}", function() use ($model) {
                $id = $this->getPathParam('id');
                return $this->deleteOne($model, $id);
            });
        }
    }

    /**
     * List all records
     */
    protected function listAll(Model $model): array
    {
        try {
            $records = $model->all();
            return [
                'success' => true,
                'data' => $records,
                'count' => count($records)
            ];
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get one record by ID
     */
    protected function getOne(Model $model, $id): array
    {
        try {
            $record = $model->find($id);
            if (!$record) {
                return $this->errorResponse('Record not found', 404);
            }
            return [
                'success' => true,
                'data' => $record
            ];
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create a new record
     */
    protected function create(Model $model, array $data): array
    {
        try {
            $id = $model->create($data);
            return [
                'success' => true,
                'message' => 'Record created successfully',
                'data' => ['id' => $id]
            ];
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Update a record
     */
    protected function update(Model $model, $id, array $data): array
    {
        try {
            $success = $model->update($id, $data);
            if (!$success) {
                return $this->errorResponse('Record not found', 404);
            }
            return [
                'success' => true,
                'message' => 'Record updated successfully'
            ];
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Delete a record
     */
    protected function deleteOne(Model $model, $id): array
    {
        try {
            $success = $model->delete($id);
            if (!$success) {
                return $this->errorResponse('Record not found', 404);
            }
            return [
                'success' => true,
                'message' => 'Record deleted successfully'
            ];
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get request body as array
     */
    protected function getRequestBody(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    /**
     * Get path parameter (simple implementation)
     */
    protected function getPathParam(string $name): mixed
    {
        // This will be populated during route matching
        return $_GET[$name] ?? null;
    }

    /**
     * Error response helper
     */
    protected function errorResponse(string $message, int $code = 400): array
    {
        http_response_code($code);
        return [
            'success' => false,
            'error' => $message
        ];
    }

    /**
     * Register a GET route
     */
    public function get(string $path, callable $handler): self
    {
        return $this->registerRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable $handler): self
    {
        return $this->registerRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, callable $handler): self
    {
        return $this->registerRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, callable $handler): self
    {
        return $this->registerRoute('DELETE', $path, $handler);
    }

    /**
     * Register a route with any HTTP method
     */
    protected function registerRoute(string $method, string $path, callable $handler): self
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];

        return $this;
    }

    /**
     * Add middleware
     */
    public function middleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Handle incoming request
     */
    public function handleRequest(string $method, string $path): mixed
    {
        foreach ($this->routes as $route) {
            $params = [];
            if ($route['method'] === $method && $this->pathMatches($route['path'], $path, $params)) {
                // Store params in $_GET for easy access
                foreach ($params as $key => $value) {
                    $_GET[$key] = $value;
                }
                return call_user_func($route['handler']);
            }
        }

        return $this->notFound();
    }

    /**
     * Check if route path matches request path
     */
    protected function pathMatches(string $routePath, string $requestPath, &$params = []): bool
    {
        $regex = $this->pathToRegex($routePath, $paramNames);
        
        if (preg_match($regex, $requestPath, $matches)) {
            array_shift($matches); // Remove full match
            foreach ($paramNames as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
            return true;
        }
        
        return false;
    }

    /**
     * Convert route path to regex
     */
    protected function pathToRegex(string $path, &$paramNames = []): string
    {
        $paramNames = [];
        $path = preg_replace_callback('/\{([^}]+)\}/', function($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '([^/]+)';
        }, $path);
        
        $path = str_replace('/', '\/', $path);
        return '/^' . $path . '$/';
    }

    /**
     * 404 Not Found response
     */
    protected function notFound(): array
    {
        http_response_code(404);
        return ['success' => false, 'error' => 'Not Found'];
    }

    /**
     * Get loaded models
     */
    public function getModels(): array
    {
        return $this->models;
    }
}
