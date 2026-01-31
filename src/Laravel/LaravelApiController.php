<?php

namespace NafeesAhmed\Enapi\Laravel;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class LaravelApiController
{
    protected array $models = [];
    protected string $modelsPath = '';
    protected string $modelsNamespace = '';

    /**
     * Register Laravel models for API generation
     */
    public function registerModels(string $modelsPath, string $modelsNamespace): self
    {
        try {
            $this->modelsPath = $modelsPath;
            $this->modelsNamespace = $modelsNamespace;
            $this->loadModels();
        } catch (\Exception $e) {
            Log::error('ENAPI: Failed to register models', [
                'path' => $modelsPath,
                'namespace' => $modelsNamespace,
                'error' => $e->getMessage()
            ]);
        }
        
        return $this;
    }

    /**
     * Load all Eloquent models from directory
     */
    protected function loadModels(): void
    {
        if (!is_dir($this->modelsPath)) {
            Log::warning('ENAPI: Models directory not found', ['path' => $this->modelsPath]);
            return;
        }

        try {
            $files = glob($this->modelsPath . '/*.php') ?: [];
            
            foreach ($files as $file) {
                try {
                    $className = basename($file, '.php');
                    $fullClassName = $this->modelsNamespace . '\\' . $className;
                    
                    if (!class_exists($fullClassName)) {
                        Log::debug('ENAPI: Class not found', ['class' => $fullClassName]);
                        continue;
                    }
                    
                    if (!is_subclass_of($fullClassName, EloquentModel::class)) {
                        Log::debug('ENAPI: Class is not Eloquent model', ['class' => $fullClassName]);
                        continue;
                    }
                    
                    $model = new $fullClassName();
                    $resourceName = $this->getResourceName($model);
                    $this->models[$resourceName] = $fullClassName;
                    
                    Log::info('ENAPI: Model registered', [
                        'model' => $className,
                        'resource' => $resourceName
                    ]);
                } catch (\Exception $e) {
                    Log::error('ENAPI: Failed to load model', [
                        'file' => $file,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('ENAPI: Failed to scan models directory', [
                'path' => $this->modelsPath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get resource name from model
     */
    protected function getResourceName(?EloquentModel $model): string
    {
        if (!$model) {
            return 'unknown';
        }
        
        try {
            return strtolower(class_basename($model)) . 's';
        } catch (\Exception $e) {
            Log::error('ENAPI: Failed to get resource name', ['error' => $e->getMessage()]);
            return 'unknown';
        }
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
        try {
            if (!isset($this->models[$resource])) {
                Log::warning('ENAPI: Resource not found', ['resource' => $resource]);
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found'
                ], 404);
            }

            $modelClass = $this->models[$resource];
            $query = $modelClass::query();

            // Search across all columns
            $search = $request->get('search');
            if ($search && is_string($search)) {
                try {
                    $model = new $modelClass();
                    $searchable = $model->getFillable() ?: [];
                    
                    if (!empty($searchable)) {
                        $query->where(function($q) use ($searchable, $search) {
                            foreach ($searchable as $field) {
                                if (is_string($field)) {
                                    $q->orWhere($field, 'LIKE', "%{$search}%");
                                }
                            }
                        });
                    }
                } catch (\Exception $e) {
                    Log::warning('ENAPI: Search failed', [
                        'resource' => $resource,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Filter by specific fields
            try {
                $excludedParams = ['search', 'sort', 'order', 'page', 'per_page', 'fields'];
                $filters = $request->except($excludedParams);
                
                foreach ($filters as $key => $value) {
                    if (!empty($value) && is_string($key)) {
                        $query->where($key, $value);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ENAPI: Filtering failed', [
                    'resource' => $resource,
                    'error' => $e->getMessage()
                ]);
            }

            // Sorting
            try {
                $sortBy = $request->get('sort', 'id');
                $sortOrder = $request->get('order', 'asc');
                
                if (is_string($sortBy) && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                    $query->orderBy($sortBy, $sortOrder);
                }
            } catch (\Exception $e) {
                Log::warning('ENAPI: Sorting failed', [
                    'resource' => $resource,
                    'error' => $e->getMessage()
                ]);
            }

            // Pagination
            try {
                $perPage = (int) $request->get('per_page', 15);
                $perPage = max(1, min($perPage, 100)); // Limit between 1-100
                
                if ($request->has('page')) {
                    $records = $query->paginate($perPage);
                    return response()->json([
                        'success' => true,
                        'data' => $records->items() ?: [],
                        'pagination' => [
                            'total' => $records->total() ?? 0,
                            'per_page' => $records->perPage() ?? $perPage,
                            'current_page' => $records->currentPage() ?? 1,
                            'last_page' => $records->lastPage() ?? 1,
                            'from' => $records->firstItem(),
                            'to' => $records->lastItem(),
                        ]
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('ENAPI: Pagination failed', [
                    'resource' => $resource,
                    'error' => $e->getMessage()
                ]);
            }

            $records = $query->get();

            // Select specific fields
            try {
                $fields = $request->get('fields');
                if ($fields && is_string($fields)) {
                    $fieldsArray = array_filter(array_map('trim', explode(',', $fields)));
                    if (!empty($fieldsArray)) {
                        $records = $records->map(function($item) use ($fieldsArray) {
                            return $item ? $item->only($fieldsArray) : null;
                        })->filter();
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ENAPI: Field selection failed', [
                    'resource' => $resource,
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $records ?: [],
                'count' => $records ? $records->count() : 0
            ]);
            
        } catch (\Exception $e) {
            Log::error('ENAPI: Index failed', [
                'resource' => $resource,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve records',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get single record
     */
    public function show(string $resource, int|string $id): JsonResponse
    {
        try {
            if (!isset($this->models[$resource])) {
                Log::warning('ENAPI: Resource not found for show', ['resource' => $resource]);
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found'
                ], 404);
            }

            $modelClass = $this->models[$resource];
            $record = $modelClass::find($id);

            if (!$record) {
                Log::info('ENAPI: Record not found', [
                    'resource' => $resource,
                    'id' => $id
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Record not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $record
            ]);
        } catch (\Exception $e) {
            Log::error('ENAPI: Show failed', [
                'resource' => $resource,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve record',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create new record
     */
    public function store(Request $request, string $resource): JsonResponse
    {
        try {
            if (!isset($this->models[$resource])) {
                Log::warning('ENAPI: Resource not found for store', ['resource' => $resource]);
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found'
                ], 404);
            }

            $modelClass = $this->models[$resource];
            $model = new $modelClass();
            
            // Get fillable fields safely
            $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];
            $data = !empty($fillable) ? $request->only($fillable) : [];
            
            if (empty($data)) {
                Log::warning('ENAPI: No valid data provided', [
                    'resource' => $resource,
                    'fillable' => $fillable
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'No valid data provided'
                ], 400);
            }
            
            $record = $modelClass::create($data);

            Log::info('ENAPI: Record created', [
                'resource' => $resource,
                'id' => $record->id ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Record created successfully',
                'data' => $record
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('ENAPI: Database error on create', [
                'resource' => $resource,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Database error',
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to create record'
            ], 400);
        } catch (\Exception $e) {
            Log::error('ENAPI: Store failed', [
                'resource' => $resource,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to create record',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 400);
        }
    }

    /**
     * Update record
     */
    public function update(Request $request, string $resource, int|string $id): JsonResponse
    {
        try {
            if (!isset($this->models[$resource])) {
                Log::warning('ENAPI: Resource not found for update', ['resource' => $resource]);
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found'
                ], 404);
            }

            $modelClass = $this->models[$resource];
            $record = $modelClass::find($id);

            if (!$record) {
                Log::info('ENAPI: Record not found for update', [
                    'resource' => $resource,
                    'id' => $id
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Record not found'
                ], 404);
            }

            // Get fillable fields safely
            $fillable = method_exists($record, 'getFillable') ? $record->getFillable() : [];
            $data = !empty($fillable) ? $request->only($fillable) : [];
            
            if (empty($data)) {
                Log::warning('ENAPI: No valid data provided for update', [
                    'resource' => $resource,
                    'id' => $id
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'No valid data provided'
                ], 400);
            }
            
            $record->update($data);

            Log::info('ENAPI: Record updated', [
                'resource' => $resource,
                'id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully',
                'data' => $record->fresh()
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('ENAPI: Database error on update', [
                'resource' => $resource,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Database error',
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to update record'
            ], 400);
        } catch (\Exception $e) {
            Log::error('ENAPI: Update failed', [
                'resource' => $resource,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to update record',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 400);
        }
    }

    /**
     * Delete record
     */
    public function destroy(string $resource, int|string $id): JsonResponse
    {
        try {
            if (!isset($this->models[$resource])) {
                Log::warning('ENAPI: Resource not found for destroy', ['resource' => $resource]);
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found'
                ], 404);
            }

            $modelClass = $this->models[$resource];
            $record = $modelClass::find($id);

            if (!$record) {
                Log::info('ENAPI: Record not found for delete', [
                    'resource' => $resource,
                    'id' => $id
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Record not found'
                ], 404);
            }

            $record->delete();

            Log::info('ENAPI: Record deleted', [
                'resource' => $resource,
                'id' => $id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('ENAPI: Database error on delete', [
                'resource' => $resource,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Database error',
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to delete record'
            ], 500);
        } catch (\Exception $e) {
            Log::error('ENAPI: Destroy failed', [
                'resource' => $resource,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete record',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get stats for a resource
     */
    public function stats(string $resource, Request $request): JsonResponse
    {
        try {
            if (!isset($this->models[$resource])) {
                Log::warning('ENAPI: Resource not found for stats', ['resource' => $resource]);
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found'
                ], 404);
            }

            $modelClass = $this->models[$resource];
            $query = $modelClass::query();

            // Apply filters safely
            try {
                $excludedParams = ['group_by', 'aggregate', 'date_field', 'date_from', 'date_to'];
                $filters = $request->except($excludedParams);
                
                foreach ($filters as $key => $value) {
                    if (!empty($value) && is_string($key)) {
                        $query->where($key, $value);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ENAPI: Stats filtering failed', [
                    'resource' => $resource,
                    'error' => $e->getMessage()
                ]);
            }

            // Date range filtering
            try {
                $dateField = $request->get('date_field');
                if ($dateField && is_string($dateField)) {
                    $dateFrom = $request->get('date_from');
                    $dateTo = $request->get('date_to');
                    
                    if ($dateFrom) {
                        $query->where($dateField, '>=', $dateFrom);
                    }
                    if ($dateTo) {
                        $query->where($dateField, '<=', $dateTo);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ENAPI: Date range filtering failed', [
                    'resource' => $resource,
                    'error' => $e->getMessage()
                ]);
            }

            $stats = [
                'total_count' => (int) ($query->count() ?? 0),
            ];

            // Aggregations
            try {
                $aggregate = $request->get('aggregate');
                if ($aggregate && is_string($aggregate)) {
                    $aggregateFields = array_filter(array_map('trim', explode(',', $aggregate)));
                    
                    foreach ($aggregateFields as $field) {
                        if (is_string($field)) {
                            $stats["sum_{$field}"] = $query->sum($field) ?? 0;
                            $stats["avg_{$field}"] = round((float)($query->avg($field) ?? 0), 2);
                            $stats["min_{$field}"] = $query->min($field) ?? 0;
                            $stats["max_{$field}"] = $query->max($field) ?? 0;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ENAPI: Aggregation failed', [
                    'resource' => $resource,
                    'error' => $e->getMessage()
                ]);
            }

            // Group by
            try {
                $groupBy = $request->get('group_by');
                if ($groupBy && is_string($groupBy)) {
                    $grouped = $modelClass::selectRaw("{$groupBy}, COUNT(*) as count")
                        ->groupBy($groupBy)
                        ->get();
                    
                    $stats['grouped_by'] = $groupBy;
                    $stats['groups'] = $grouped ?? [];
                }
            } catch (\Exception $e) {
                Log::warning('ENAPI: Group by failed', [
                    'resource' => $resource,
                    'groupBy' => $groupBy ?? null,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('ENAPI: Stats generated', [
                'resource' => $resource,
                'total_count' => $stats['total_count'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'resource' => $resource,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('ENAPI: Stats failed', [
                'resource' => $resource,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate statistics',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Batch operations
     */
    public function batch(Request $request, string $resource): JsonResponse
    {
        try {
            if (!isset($this->models[$resource])) {
                Log::warning('ENAPI: Resource not found for batch', ['resource' => $resource]);
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found'
                ], 404);
            }

            $action = $request->get('action');
            $ids = $request->get('ids', []);

            if (!in_array($action, ['delete', 'update'])) {
                Log::warning('ENAPI: Invalid batch action', [
                    'resource' => $resource,
                    'action' => $action
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid action. Use "delete" or "update"'
                ], 400);
            }

            if (empty($ids) || !is_array($ids)) {
                Log::warning('ENAPI: Invalid IDs for batch', [
                    'resource' => $resource,
                    'ids' => $ids
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'IDs array required'
                ], 400);
            }

            // Sanitize IDs
            $ids = array_filter($ids, function($id) {
                return is_numeric($id) || is_string($id);
            });

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No valid IDs provided'
                ], 400);
            }

            $modelClass = $this->models[$resource];

            switch ($action) {
                case 'delete':
                    try {
                        $count = $modelClass::whereIn('id', $ids)->delete();
                        
                        Log::info('ENAPI: Batch delete completed', [
                            'resource' => $resource,
                            'count' => $count,
                            'ids_count' => count($ids)
                        ]);
                        
                        return response()->json([
                            'success' => true,
                            'message' => "{$count} record(s) deleted",
                            'count' => $count
                        ]);
                    } catch (\Exception $e) {
                        Log::error('ENAPI: Batch delete failed', [
                            'resource' => $resource,
                            'ids' => $ids,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }

                case 'update':
                    try {
                        $data = $request->get('data', []);
                        
                        if (empty($data) || !is_array($data)) {
                            return response()->json([
                                'success' => false,
                                'error' => 'Update data required'
                            ], 400);
                        }
                        
                        $model = new $modelClass();
                        $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];
                        $data = !empty($fillable) ? array_intersect_key($data, array_flip($fillable)) : [];
                        
                        if (empty($data)) {
                            return response()->json([
                                'success' => false,
                                'error' => 'No valid fields to update'
                            ], 400);
                        }
                        
                        $count = $modelClass::whereIn('id', $ids)->update($data);
                        
                        Log::info('ENAPI: Batch update completed', [
                            'resource' => $resource,
                            'count' => $count,
                            'ids_count' => count($ids)
                        ]);
                        
                        return response()->json([
                            'success' => true,
                            'message' => "{$count} record(s) updated",
                            'count' => $count
                        ]);
                    } catch (\Exception $e) {
                        Log::error('ENAPI: Batch update failed', [
                            'resource' => $resource,
                            'ids' => $ids,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }

                default:
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid action'
                    ], 400);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('ENAPI: Batch database error', [
                'resource' => $resource,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Database error',
                'message' => config('app.debug') ? $e->getMessage() : 'Batch operation failed'
            ], 400);
        } catch (\Exception $e) {
            Log::error('ENAPI: Batch operation failed', [
                'resource' => $resource,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Batch operation failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 400);
        }
    }

    /**
     * Export data
     */
    public function export(string $resource, Request $request): JsonResponse
    {
        try {
            if (!isset($this->models[$resource])) {
                Log::warning('ENAPI: Resource not found for export', ['resource' => $resource]);
                return response()->json([
                    'success' => false,
                    'error' => 'Resource not found'
                ], 404);
            }

            $modelClass = $this->models[$resource];
            $query = $modelClass::query();

            // Apply filters safely
            try {
                $filters = $request->except(['format', 'limit']);
                foreach ($filters as $key => $value) {
                    if (!empty($value) && is_string($key)) {
                        $query->where($key, $value);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('ENAPI: Export filtering failed', [
                    'resource' => $resource,
                    'error' => $e->getMessage()
                ]);
            }

            // Limit for safety
            $limit = (int) $request->get('limit', 1000);
            $limit = max(1, min($limit, 10000)); // Max 10k records
            $query->limit($limit);

            $records = $query->get();
            $format = $request->get('format', 'json');

            Log::info('ENAPI: Export completed', [
                'resource' => $resource,
                'format' => $format,
                'count' => $records ? $records->count() : 0
            ]);

            if ($format === 'csv') {
                return response()->json([
                    'success' => true,
                    'format' => 'csv',
                    'data' => $records ? $records->toArray() : [],
                    'count' => $records ? $records->count() : 0,
                    'message' => 'Convert to CSV on client side'
                ]);
            }

            return response()->json([
                'success' => true,
                'format' => 'json',
                'data' => $records ?: [],
                'count' => $records ? $records->count() : 0
            ]);
        } catch (\Exception $e) {
            Log::error('ENAPI: Export failed', [
                'resource' => $resource,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Export failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
