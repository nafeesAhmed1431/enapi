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
     * List all records with filtering, search, pagination
     */
    public function index(string $resource, Request $request): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        $modelClass = $this->models[$resource];
        $query = $modelClass::query();

        // Search across all columns
        if ($search = $request->get('search')) {
            $model = new $modelClass();
            $searchable = $model->getFillable();
            
            $query->where(function($q) use ($searchable, $search) {
                foreach ($searchable as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }

        // Filter by specific fields
        foreach ($request->except(['search', 'sort', 'order', 'page', 'per_page', 'fields']) as $key => $value) {
            if (!empty($value)) {
                $query->where($key, $value);
            }
        }

        // Sorting
        $sortBy = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        
        if ($request->has('page')) {
            $records = $query->paginate($perPage);
            return response()->json([
                'success' => true,
                'data' => $records->items(),
                'pagination' => [
                    'total' => $records->total(),
                    'per_page' => $records->perPage(),
                    'current_page' => $records->currentPage(),
                    'last_page' => $records->lastPage(),
                    'from' => $records->firstItem(),
                    'to' => $records->lastItem(),
                ]
            ]);
        }

        $records = $query->get();

        // Select specific fields
        if ($fields = $request->get('fields')) {
            $fieldsArray = explode(',', $fields);
            $records = $records->map(fn($item) => $item->only($fieldsArray));
        }

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

    /**
     * Get stats for a resource
     */
    public function stats(string $resource, Request $request): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        try {
            $modelClass = $this->models[$resource];
            $query = $modelClass::query();

            // Apply filters
            foreach ($request->except(['group_by', 'aggregate', 'date_field', 'date_from', 'date_to']) as $key => $value) {
                if (!empty($value)) {
                    $query->where($key, $value);
                }
            }

            // Date range filtering
            if ($dateField = $request->get('date_field')) {
                if ($dateFrom = $request->get('date_from')) {
                    $query->where($dateField, '>=', $dateFrom);
                }
                if ($dateTo = $request->get('date_to')) {
                    $query->where($dateField, '<=', $dateTo);
                }
            }

            $stats = [
                'total_count' => $query->count(),
            ];

            // Aggregations
            if ($aggregate = $request->get('aggregate')) {
                $aggregateFields = explode(',', $aggregate);
                foreach ($aggregateFields as $field) {
                    if (method_exists($query, 'sum')) {
                        $stats["sum_{$field}"] = $query->sum($field);
                        $stats["avg_{$field}"] = $query->avg($field);
                        $stats["min_{$field}"] = $query->min($field);
                        $stats["max_{$field}"] = $query->max($field);
                    }
                }
            }

            // Group by
            if ($groupBy = $request->get('group_by')) {
                $grouped = $modelClass::selectRaw("{$groupBy}, COUNT(*) as count")
                    ->groupBy($groupBy)
                    ->get();
                $stats['grouped_by'] = $groupBy;
                $stats['groups'] = $grouped;
            }

            return response()->json([
                'success' => true,
                'resource' => $resource,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch operations
     */
    public function batch(Request $request, string $resource): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        try {
            $action = $request->get('action');
            $ids = $request->get('ids', []);

            if (empty($ids) || !is_array($ids)) {
                return response()->json(['error' => 'IDs array required'], 400);
            }

            $modelClass = $this->models[$resource];

            switch ($action) {
                case 'delete':
                    $count = $modelClass::whereIn('id', $ids)->delete();
                    return response()->json([
                        'success' => true,
                        'message' => "{$count} records deleted",
                        'count' => $count
                    ]);

                case 'update':
                    $data = $request->get('data', []);
                    $model = new $modelClass();
                    $data = array_intersect_key($data, array_flip($model->getFillable()));
                    
                    $count = $modelClass::whereIn('id', $ids)->update($data);
                    return response()->json([
                        'success' => true,
                        'message' => "{$count} records updated",
                        'count' => $count
                    ]);

                default:
                    return response()->json(['error' => 'Invalid action'], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Export data
     */
    public function export(string $resource, Request $request): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return response()->json(['error' => 'Resource not found'], 404);
        }

        try {
            $modelClass = $this->models[$resource];
            $query = $modelClass::query();

            // Apply filters
            foreach ($request->all() as $key => $value) {
                if (!empty($value) && $key !== 'format') {
                    $query->where($key, $value);
                }
            }

            $records = $query->get();
            $format = $request->get('format', 'json');

            if ($format === 'csv') {
                // Return CSV data structure
                return response()->json([
                    'success' => true,
                    'format' => 'csv',
                    'data' => $records->toArray(),
                    'message' => 'Convert to CSV on client side'
                ]);
            }

            return response()->json([
                'success' => true,
                'format' => 'json',
                'data' => $records,
                'count' => $records->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
