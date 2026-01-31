<?php

namespace NafeesAhmed\Enapi;

abstract class Model
{
    protected ?Database $db = null;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected string $resourceName = '';

    /**
     * Constructor
     */
    public function __construct(?Database $db = null)
    {
        $this->db = $db;
        
        // Auto-set table name from class name if not defined
        if (empty($this->table)) {
            $className = (new \ReflectionClass($this))->getShortName();
            $this->table = strtolower($className) . 's';
        }

        // Auto-set resource name
        if (empty($this->resourceName)) {
            $this->resourceName = $this->table;
        }
    }

    /**
     * Get resource name for API endpoints
     */
    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    /**
     * Get table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get all records
     */
    public function all(): array
    {
        if (!$this->db) {
            return [];
        }

        $query = "SELECT * FROM {$this->table}";
        return $this->db->query($query);
    }

    /**
     * Find a record by ID
     */
    public function find($id): ?array
    {
        if (!$this->db) {
            return null;
        }

        $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $results = $this->db->query($query, ['id' => $id]);
        
        return $results[0] ?? null;
    }

    /**
     * Create a new record
     */
    public function create(array $data): int|string
    {
        if (!$this->db) {
            throw new \Exception('Database connection not set');
        }

        // Filter only fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        $fields = array_keys($data);
        $placeholders = array_map(fn($field) => ":$field", $fields);
        
        $fieldsStr = implode(', ', $fields);
        $placeholdersStr = implode(', ', $placeholders);
        
        $query = "INSERT INTO {$this->table} ($fieldsStr) VALUES ($placeholdersStr)";
        
        return $this->db->execute($query, $data);
    }

    /**
     * Update a record
     */
    public function update($id, array $data): bool
    {
        if (!$this->db) {
            throw new \Exception('Database connection not set');
        }

        // Filter only fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        $fields = array_keys($data);
        $setClause = implode(', ', array_map(fn($field) => "$field = :$field", $fields));
        
        $data['id'] = $id;
        $query = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :id";
        
        return $this->db->execute($query, $data) > 0;
    }

    /**
     * Delete a record
     */
    public function delete($id): bool
    {
        if (!$this->db) {
            throw new \Exception('Database connection not set');
        }

        $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        
        return $this->db->execute($query, ['id' => $id]) > 0;
    }

    /**
     * Find records by condition
     */
    public function where(string $column, $value): array
    {
        if (!$this->db) {
            return [];
        }

        $query = "SELECT * FROM {$this->table} WHERE $column = :value";
        return $this->db->query($query, ['value' => $value]);
    }

    /**
     * Custom query
     */
    public function query(string $sql, array $params = []): array
    {
        if (!$this->db) {
            return [];
        }

        return $this->db->query($sql, $params);
    }
}
