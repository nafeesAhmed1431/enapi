<?php

namespace NafeesAhmed\Enapi\Tests;

use NafeesAhmed\Enapi\Model;
use NafeesAhmed\Enapi\Tests\Support\SqliteDatabase;
use PHPUnit\Framework\TestCase;

class TestUser extends Model
{
    protected string $table = 'users';
    protected string $resourceName = 'users';
    protected array $fillable = ['name', 'email'];
    protected array $filterable = ['name'];
}

class TestUserNoFillable extends Model
{
    protected string $table = 'users';
    protected string $resourceName = 'users';
    // Intentionally empty fillable — must NOT allow arbitrary columns.
}

class ModelTest extends TestCase
{
    private SqliteDatabase $db;

    protected function setUp(): void
    {
        $this->db = new SqliteDatabase();
        $this->db->execute('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, password TEXT)');
    }

    public function testCreateAndFind(): void
    {
        $model = new TestUser($this->db);
        $id = $model->create(['name' => 'Ada', 'email' => 'ada@example.com']);

        $record = $model->find($id);
        $this->assertSame('Ada', $record['name']);
        $this->assertSame('ada@example.com', $record['email']);
    }

    public function testCreateFiltersNonFillableFields(): void
    {
        $model = new TestUser($this->db);
        $id = $model->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']);

        $record = $model->find($id);
        $this->assertNull($record['password']);
    }

    public function testCreateWithEmptyFillableAllowsNothing(): void
    {
        $model = new TestUserNoFillable($this->db);

        $this->expectException(\Exception::class);
        $model->create(['name' => 'Ada']);
    }

    public function testUpdateFiltersNonFillableFields(): void
    {
        $model = new TestUser($this->db);
        $id = $model->create(['name' => 'Ada', 'email' => 'ada@example.com']);

        $model->update($id, ['name' => 'Ada Lovelace', 'password' => 'hacked']);

        $record = $model->find($id);
        $this->assertSame('Ada Lovelace', $record['name']);
        $this->assertNull($record['password']);
    }

    public function testDelete(): void
    {
        $model = new TestUser($this->db);
        $id = $model->create(['name' => 'Ada', 'email' => 'ada@example.com']);

        $this->assertTrue($model->delete($id));
        $this->assertNull($model->find($id));
    }

    public function testWhereRejectsColumnNotInFilterable(): void
    {
        $model = new TestUser($this->db);
        $model->create(['name' => 'Ada', 'email' => 'ada@example.com']);

        $this->expectException(\Exception::class);
        $model->where('password', 'x');
    }

    public function testWhereRejectsNonIdentifierColumn(): void
    {
        $model = new TestUser($this->db);

        $this->expectException(\Exception::class);
        // Regression: previously interpolated raw into "WHERE $column = :value".
        $model->where('id; DROP TABLE users; --', 'x');
    }

    public function testWhereAllowsFilterableColumn(): void
    {
        $model = new TestUser($this->db);
        $model->create(['name' => 'Ada', 'email' => 'ada@example.com']);

        $results = $model->where('name', 'Ada');
        $this->assertCount(1, $results);
    }
}
