# Generic model access restrictions

`accountmanager_model_role_actions` is independent of FileCabinet. Its only resource identifiers are `model_type` (Laravel morph class) and `model_id` (string, supporting integer, UUID and ULID keys). `action` is an application-defined name, up to 64 characters; e.g. `read`, `write`, `delete` or `export.documents`.

The package loads migration `2026_11_16_000001_create_accountmanager_model_role_actions_table.php`. Run the normal host application's migrations before using this API. The table lives on the resource's database connection, alongside the role table; migrate every connection used for these resources. The existing role IDs use Spatie's unsigned bigint key. No changes are made to `model_has_roles`, `model_has_permissions` or users' role assignments.

Use `Authorization\ModelAccess`, or optionally add `Models\Traits\HasModelRoleActions` to a model:

```php
$access = app(\IlBronza\AccountManager\Authorization\ModelAccess::class);
$access->replace($resource, 'read', null); // no rows: inherit
$access->replace($resource, 'read', []); // one row with NULL role_id: deny everyone
$access->replace($resource, 'read', [$readerRole->getKey(), $editorRole->getKey()]);
$access->replaceMany($resource, [
    'read' => [$readerRole->getKey()],
    'write' => [],
    'delete' => null,
]);
$allowedLocally = $access->allows($resource, 'read', $user);
```

`roles()` returns `null`, `[]`, or an array of allowed role IDs. Local inheritance returns true and must be combined with the consuming application's normal authorization and hierarchy. This service does not introduce a superadmin bypass, ownership assumptions or dependencies on FileCabinet.

All changes to a resource/action must go through `replace`/`replaceMany`: these validate role IDs, lock the resource even for the first assignment, replace all rows atomically and prevent mixing NULL denial markers with grants. Empty role selections always produce explicit denial. SQL's nullable unique key alone cannot enforce exclusivity of the denial marker; direct pivot writes are deliberately mass-assignment guarded. Readers give any NULL marker precedence over grants if legacy/manual data is inconsistent.

Removing a referenced role is restricted by its foreign key: otherwise deleting the last grant could accidentally reopen inherited access. Remove or replace its resource rules explicitly first. Soft-deleted roles grant nothing through the user membership query. No authorization results or memberships are cached across requests; the checks use fresh pivot/membership queries.
