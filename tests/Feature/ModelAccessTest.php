<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Authorization\ModelAccess;
use IlBronza\AccountManager\Models\ModelRoleAction;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ModelAccessResource extends Model
{
    use \IlBronza\AccountManager\Models\Traits\HasModelRoleActions;

    protected $table = 'access_resources';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}

class OtherModelAccessResource extends ModelAccessResource
{
}

class ModelAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');
        (require __DIR__ . '/../../database/migrations/2026_11_16_000001_create_accountmanager_model_role_actions_table.php')->up();
        Schema::create('access_resources', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('accountmanager_model_role_actions');
        parent::tearDown();
    }

    public function test_generic_resource_supports_inheritance_grants_and_explicit_denial(): void
    {
        $access = new ModelAccess;
        $resource = ModelAccessResource::create(['id' => 'a-uuid-or-ulid']);
        $reader = $this->makeUserWithRole('reader');
        $writer = $this->makeUserWithRole('writer', 'writer@example.test');
        $this->assertNull($access->roles($resource, 'read'));
        $this->assertTrue($access->allows($resource, 'read', $reader));
        $resource->setActionRoles('read', [$reader->roles->first()->id]);
        $this->assertTrue($access->allows($resource, 'read', $reader));
        $this->assertFalse($access->allows($resource, 'read', $writer));
        $this->assertFalse($access->allows($resource, 'read', null));
        $this->assertTrue($access->allows($resource, 'export.documents', $writer));
        $resource->setActionRoles('read', []);
        $this->assertSame([], $access->roles($resource, 'read'));
        $this->assertNull(ModelRoleAction::firstOrFail()->role_id);
        $this->assertFalse($access->allows($resource, 'read', $reader));
        $resource->setActionRoles('read', null);
        $this->assertDatabaseCount('accountmanager_model_role_actions', 0);
        $this->assertTrue($access->allows($resource, 'read', $writer));
    }

    public function test_model_types_and_actions_are_isolated_and_assignments_are_deduplicated(): void
    {
        $resource = ModelAccessResource::create(['id' => '12']);
        $other = OtherModelAccessResource::findOrFail('12');
        $user = $this->makeUserWithRole('reader');
        $id = $user->roles->first()->id;
        $resource->setActionRoles('read', [$id, (string) $id]);
        $resource->setActionRoles('write', []);
        $this->assertDatabaseCount('accountmanager_model_role_actions', 2);
        $this->assertTrue((new ModelAccess)->allows($other, 'write', $user));
        $this->assertTrue((new ModelAccess)->allows($resource, 'read', $user));
        $this->assertFalse((new ModelAccess)->allows($resource, 'write', $user));
        $this->assertTrue($resource->modelRoleActions->first()->model->is($resource));
    }

    public function test_invalid_role_rolls_back_all_actions(): void
    {
        $resource = ModelAccessResource::create(['id' => 'resource']);
        $resource->setActionRoles('read', []);
        try {
            (new ModelAccess)->replaceMany($resource, [
                'read' => null,
                'write' => [987654321],
            ]);
            $this->fail('Expected invalid role to reject the entire change.');
        } catch (ValidationException $exception) {
            $this->assertSame([], (new ModelAccess)->roles($resource, 'read'));
            $this->assertNull((new ModelAccess)->roles($resource, 'write'));
        }
    }

    public function test_revocations_apply_to_previously_loaded_resources_and_users(): void
    {
        $resource = ModelAccessResource::create(['id' => 'resource']);
        $user = $this->makeUserWithRole('reader');
        $role = $user->roles->first();
        $resource->setActionRoles('read', [$role->id]);
        $resource->load('modelRoleActions');
        $this->assertTrue((new ModelAccess)->allows($resource, 'read', $user));
        $user->roles()->detach($role);
        $this->assertFalse((new ModelAccess)->allows($resource, 'read', $user));
        $this->assertDatabaseCount('accountmanager_model_role_actions', 1);
    }

    public function test_deleting_an_assigned_role_cannot_reopen_access(): void
    {
        $resource = ModelAccessResource::create(['id' => 'resource']);
        $role = Role::findOrCreate('reader', 'web');
        $resource->setActionRoles('read', [$role->id]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        $role->forceDelete();
    }
}
