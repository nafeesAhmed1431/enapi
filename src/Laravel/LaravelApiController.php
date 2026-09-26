<?php

namespace NafeesAhmed\Enapi\Laravel;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $excluded = config('enapi.excluded_models', []);

        try {
            $files = glob($this->modelsPath . '/*.php') ?: [];

            foreach ($files as $file) {
                try {
                    $className = basename($file, '.php');

                    if (in_array($className, $excluded, true)) {
                        Log::debug('ENAPI: Model excluded by config', ['class' => $className]);
                        continue;
                    }

                    $fullClassName = $this->modelsNamespace . '\\' . $className;

                    if (in_array($fullClassName, $excluded, true)) {
                        Log::debug('ENAPI: Model excluded by config', ['class' => $fullClassName]);
                        continue;
                    }

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
     * Run a controller action, converting exceptions into a uniform JSON error response
     * and centralizing logging.
     */
    protected function respond(callable $action, string $context, int $defaultErrorCode = 500): JsonResponse
    {
        try {
            return $action();
        } catch (QueryException $e) {
            Log::error("ENAPI: {$context} database error", ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Database error',
                'message' => config('app.debug') ? $e->getMessage() : 'Operation failed',
            ], 400);
        } catch (\Exception $e) {
            Log::error("ENAPI: {$context} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Operation failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], $defaultErrorCode);
        }
    }

    /**
     * Resource-not-found helper.
     */
    protected function notFound(string $resource, string $context): JsonResponse
    {
        Log::warning("ENAPI: Resource not found for {$context}", ['resource' => $resource]);

        return response()->json([
            'success' => false,
            'error' => 'Resource not found'
        ], 404);
    }

    /**
     * Columns a model allows to be used in filters/sort/group-by.
     * Falls back to fillable — never allow arbitrary request keys to become
     * raw SQL identifiers.
     */
    protected function getFilterableColumns(string $modelClass): array
    {
        $model = new $modelClass();

        if (method_exists($model, 'getFilterable')) {
            $filterable = $model->getFilterable();
            if (!empty($filterable)) {
                return $filterable;
            }
        }

        return $model->getFillable() ?: [];
    }

    /**
     * Whether a column name is safe to use as a filter/sort/group-by target.
     */
    protected function isAllowedColumn(string $column, array $allowedColumns): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column) === 1
            && in_array($column, $allowedColumns, true);
    }

    /**
     * Apply search + column filters + sorting to a query builder, restricted to
     * a whitelist of allowed columns.
     */
    protected function applyQueryFilters($query, Request $request, array $allowedColumns, array $excludedParams): void
    {
        // Search across allowed columns
        $search = $request->get('search');
        if ($search && is_string($search) && !empty($allowedColumns)) {
            $query->where(function ($q) use ($allowedColumns, $search) {
                foreach ($allowedColumns as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }

        // Filter by specific fields
        $filters = $request->except(array_merge($excludedParams, ['search']));

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || !is_string($key)) {
                continue;
            }

            if (!$this->isAllowedColumn($key, $allowedColumns)) {
                continue;
            }

            $query->where($key, $value);
        }
    }

    /**
     * List all records with filtering, search, pagination
     */
    public function index(string $resource, Request $request): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return $this->notFound($resource, 'index');
        }

        return $this->respond(function () use ($resource, $request) {
            $modelClass = $this->models[$resource];
            $query = $modelClass::query();
            $allowedColumns = $this->getFilterableColumns($modelClass);

            $this->applyQueryFilters(
                $query,
                $request,
                $allowedColumns,
                ['sort', 'order', 'page', 'per_page', 'fields']
            );

            // Sorting
            $sortBy = $request->get('sort', 'id');
            $sortOrder = $request->get('order', 'asc');

            if (is_string($sortBy) && $this->isAllowedColumn($sortBy, array_merge($allowedColumns, ['id']))
                && in_array(strtolower($sortOrder), ['asc', 'desc'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
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

            $records = $query->get();

            // Select specific fields (restricted to allowed columns)
            $fields = $request->get('fields');
            if ($fields && is_string($fields)) {
                $fieldsArray = array_values(array_intersect(
                    array_filter(array_map('trim', explode(',', $fields))),
                    $allowedColumns
                ));

                if (!empty($fieldsArray)) {
                    $records = $records->map(fn ($item) => $item ? $item->only($fieldsArray) : null)->filter();
                }
            }

            return response()->json([
                'success' => true,
                'data' => $records ?: [],
                'count' => $records ? $records->count() : 0
            ]);
        }, 'Index');
    }

    /**
     * Get single record
     */
    public function show(string $resource, int|string $id): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return $this->notFound($resource, 'show');
        }

        return $this->respond(function () use ($resource, $id) {
            $modelClass = $this->models[$resource];
            $record = $modelClass::find($id);

            if (!$record) {
                Log::info('ENAPI: Record not found', ['resource' => $resource, 'id' => $id]);
                return response()->json(['success' => false, 'error' => 'Record not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $record]);
        }, 'Show');
    }

    /**
     * Create new record
     */
    public function store(Request $request, string $resource): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return $this->notFound($resource, 'store');
        }

        return $this->respond(function () use ($request, $resource) {
            $modelClass = $this->models[$resource];
            $model = new $modelClass();

            $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];
            $data = !empty($fillable) ? $request->only($fillable) : [];

            if (empty($data)) {
                Log::warning('ENAPI: No valid data provided', ['resource' => $resource, 'fillable' => $fillable]);
                return response()->json(['success' => false, 'error' => 'No valid data provided'], 400);
            }

            $record = $modelClass::create($data);

            Log::info('ENAPI: Record created', ['resource' => $resource, 'id' => $record->id ?? null]);

            return response()->json([
                'success' => true,
                'message' => 'Record created successfully',
                'data' => $record
            ], 201);
        }, 'Store', 400);
    }

    /**
     * Update record
     */
    public function update(Request $request, string $resource, int|string $id): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return $this->notFound($resource, 'update');
        }

        return $this->respond(function () use ($request, $resource, $id) {
            $modelClass = $this->models[$resource];
            $record = $modelClass::find($id);

            if (!$record) {
                Log::info('ENAPI: Record not found for update', ['resource' => $resource, 'id' => $id]);
                return response()->json(['success' => false, 'error' => 'Record not found'], 404);
            }

            $fillable = method_exists($record, 'getFillable') ? $record->getFillable() : [];
            $data = !empty($fillable) ? $request->only($fillable) : [];

            if (empty($data)) {
                Log::warning('ENAPI: No valid data provided for update', ['resource' => $resource, 'id' => $id]);
                return response()->json(['success' => false, 'error' => 'No valid data provided'], 400);
            }

            $record->update($data);

            Log::info('ENAPI: Record updated', ['resource' => $resource, 'id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully',
                'data' => $record->fresh()
            ]);
        }, 'Update', 400);
    }

    /**
     * Delete record
     */
    public function destroy(string $resource, int|string $id): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return $this->notFound($resource, 'destroy');
        }

        return $this->respond(function () use ($resource, $id) {
            $modelClass = $this->models[$resource];
            $record = $modelClass::find($id);

            if (!$record) {
                Log::info('ENAPI: Record not found for delete', ['resource' => $resource, 'id' => $id]);
                return response()->json(['success' => false, 'error' => 'Record not found'], 404);
            }

            $record->delete();

            Log::info('ENAPI: Record deleted', ['resource' => $resource, 'id' => $id]);

            return response()->json(['success' => true, 'message' => 'Record deleted successfully']);
        }, 'Destroy');
    }

    /**
     * Get stats for a resource
     */
    public function stats(string $resource, Request $request): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return $this->notFound($resource, 'stats');
        }

        return $this->respond(function () use ($resource, $request) {
            $modelClass = $this->models[$resource];
            $query = $modelClass::query();
            $allowedColumns = $this->getFilterableColumns($modelClass);

            $this->applyQueryFilters(
                $query,
                $request,
                $allowedColumns,
                ['group_by', 'aggregate', 'date_field', 'date_from', 'date_to']
            );

            // Date range filtering
            $dateField = $request->get('date_field');
            if ($dateField && is_string($dateField) && $this->isAllowedColumn($dateField, $allowedColumns)) {
                $dateFrom = $request->get('date_from');
                $dateTo = $request->get('date_to');

                if ($dateFrom) {
                    $query->where($dateField, '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->where($dateField, '<=', $dateTo);
                }
            }

            $stats = [
                'total_count' => (int) ($query->count() ?? 0),
            ];

            // Aggregations, restricted to allowed columns
            $aggregate = $request->get('aggregate');
            if ($aggregate && is_string($aggregate)) {
                $aggregateFields = array_filter(
                    array_map('trim', explode(',', $aggregate)),
                    fn ($field) => $this->isAllowedColumn($field, $allowedColumns)
                );

                foreach ($aggregateFields as $field) {
                    $stats["sum_{$field}"] = $query->sum($field) ?? 0;
                    $stats["avg_{$field}"] = round((float) ($query->avg($field) ?? 0), 2);
                    $stats["min_{$field}"] = $query->min($field) ?? 0;
                    $stats["max_{$field}"] = $query->max($field) ?? 0;
                }
            }

            // Group by, restricted to allowed columns — never interpolated into raw SQL
            $groupBy = $request->get('group_by');
            if ($groupBy && is_string($groupBy) && $this->isAllowedColumn($groupBy, $allowedColumns)) {
                $grouped = $modelClass::query()
                    ->select($groupBy)
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy($groupBy)
                    ->get();

                $stats['grouped_by'] = $groupBy;
                $stats['groups'] = $grouped ?? [];
            }

            Log::info('ENAPI: Stats generated', ['resource' => $resource, 'total_count' => $stats['total_count'] ?? 0]);

            return response()->json(['success' => true, 'resource' => $resource, 'stats' => $stats]);
        }, 'Stats');
    }

    /**
     * Batch operations
     */
    public function batch(Request $request, string $resource): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return $this->notFound($resource, 'batch');
        }

        return $this->respond(function () use ($request, $resource) {
            $action = $request->get('action');
            $ids = $request->get('ids', []);

            if (!in_array($action, ['delete', 'update'], true)) {
                Log::warning('ENAPI: Invalid batch action', ['resource' => $resource, 'action' => $action]);
                return response()->json(['success' => false, 'error' => 'Invalid action. Use "delete" or "update"'], 400);
            }

            if (empty($ids) || !is_array($ids)) {
                Log::warning('ENAPI: Invalid IDs for batch', ['resource' => $resource, 'ids' => $ids]);
                return response()->json(['success' => false, 'error' => 'IDs array required'], 400);
            }

            $ids = array_filter($ids, fn ($id) => is_numeric($id) || is_string($id));

            if (empty($ids)) {
                return response()->json(['success' => false, 'error' => 'No valid IDs provided'], 400);
            }

            $modelClass = $this->models[$resource];

            if ($action === 'delete') {
                $count = $modelClass::whereIn('id', $ids)->delete();

                Log::info('ENAPI: Batch delete completed', [
                    'resource' => $resource,
                    'count' => $count,
                    'ids_count' => count($ids),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "{$count} record(s) deleted",
                    'count' => $count,
                ]);
            }

            // action === 'update'
            $data = $request->get('data', []);

            if (empty($data) || !is_array($data)) {
                return response()->json(['success' => false, 'error' => 'Update data required'], 400);
            }

            $model = new $modelClass();
            $fillable = method_exists($model, 'getFillable') ? $model->getFillable() : [];
            $data = !empty($fillable) ? array_intersect_key($data, array_flip($fillable)) : [];

            if (empty($data)) {
                return response()->json(['success' => false, 'error' => 'No valid fields to update'], 400);
            }

            $count = $modelClass::whereIn('id', $ids)->update($data);

            Log::info('ENAPI: Batch update completed', [
                'resource' => $resource,
                'count' => $count,
                'ids_count' => count($ids),
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$count} record(s) updated",
                'count' => $count,
            ]);
        }, 'Batch', 400);
    }

    /**
     * Export data
     */
    public function export(string $resource, Request $request): JsonResponse
    {
        if (!isset($this->models[$resource])) {
            return $this->notFound($resource, 'export');
        }

        return $this->respond(function () use ($resource, $request) {
            $modelClass = $this->models[$resource];
            $query = $modelClass::query();
            $allowedColumns = $this->getFilterableColumns($modelClass);

            $this->applyQueryFilters($query, $request, $allowedColumns, ['format', 'limit']);

            // Limit for safety
            $limit = (int) $request->get('limit', 1000);
            $limit = max(1, min($limit, 10000)); // Max 10k records
            $query->limit($limit);

            $records = $query->get();
            $format = $request->get('format', 'json');

            Log::info('ENAPI: Export completed', [
                'resource' => $resource,
                'format' => $format,
                'count' => $records ? $records->count() : 0,
            ]);

            if ($format === 'csv') {
                return response()->json([
                    'success' => true,
                    'format' => 'csv',
                    'data' => $records ? $records->toArray() : [],
                    'count' => $records ? $records->count() : 0,
                    'message' => 'Convert to CSV on client side',
                ]);
            }

            return response()->json([
                'success' => true,
                'format' => 'json',
                'data' => $records ?: [],
                'count' => $records ? $records->count() : 0,
            ]);
        }, 'Export');
    }
}
