<?php

namespace NafeesAhmed\Enapi\Laravel;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LaravelApiController
{
    protected array $models = [];
    protected string $modelsPath;
    protected string $modelsNamespace;

    /**
     * Register Laravel models for API generation
     */
    public function registerModels(string $modelsPath, string $modelsNamespace): self
    {
        $this->modelsPath = $modelsPath;
        $this->modelsNamespace = $modelsNamespace;
        $this->loadModels();
        
        return $this;
    }

    /**
     * Load all Eloquent models from directory
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
            
            if (class_exists($fullClassName) && is_subclass_of($fullClassName, EloquentModel::class)) {
                $model = new $fullClassName();
                $resourceName = $this->getResourceName($model);
                $this->models[$resourceName] = $fullClassName;
            }
        }
    }

    /**
     * Get resource name from model
     */
    protected function getResourceName(EloquentModel $model): string
    {
        return strtolower(class_basename($model)) . 's';
    }

    /**
     * Get all registered models
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * List all records
     */
    public function index(string $resource): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        $modelClass = $this->models[$resource];
        $records = $modelClass::all();

        return response()->json([
            'success' => true,
            'data' => $records,
            'count' => $records->count()
        ]);
    }

    /**
     * Get single record
     */
    public function show(string $resource, int|string $id): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        $modelClass = $this->models[$resource];
        $record = $modelClass::find($id);

        if (!$record) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $record
        ]);
    }

    /**
     * Create new record
     */
    public function store(Request $request, string $resource): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        try {
            $modelClass = $this->models[$resource];
            $model = new $modelClass();
            
            // Get fillable fields
            $data = $request->only($model->getFillable());
            
            $record = $modelClass::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Record created successfully',
                'data' => $record
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update record
     */
    public function update(Request $request, string $resource, int|string $id): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        try {
            $modelClass = $this->models[$resource];
            $record = $modelClass::find($id);

            if (!$record) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            // Get fillable fields
            $data = $request->only($record->getFillable());
            
            $record->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully',
                'data' => $record
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete record
     */
    public function destroy(string $resource, int|string $id): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        try {
            $modelClass = $this->models[$resource];
            $record = $modelClass::find($id);

            if (!$record) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
