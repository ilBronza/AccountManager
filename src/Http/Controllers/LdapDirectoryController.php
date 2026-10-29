<?php

namespace IlBronza\AccountManager\Http\Controllers;

use DomainException;
use IlBronza\AccountManager\Auth\Ldap\DirectoryIdentity;
use IlBronza\AccountManager\Auth\Ldap\LdapAccounts;
use IlBronza\AccountManager\Auth\Ldap\LdapDirectoryUsers;
use IlBronza\AccountManager\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LdapDirectoryController
{
    protected function authorize(Request $request): void
    {
        abort_unless(config('accountmanager_ldap.directory_enabled'), 404);
        abort_unless($request->user()?->hasAnyRole((array) config('accountmanager_ldap.management_roles', ['superadmin'])), 403);
    }

    protected function protectPrivilegedUser(Request $request, $user): void
    {
        abort_if($user && ! $request->user()->hasRole('superadmin')
            && ((string) $user->getKey() === '1' || $user->hasRole('superadmin')), 403);
    }

    public function index(Request $request, LdapDirectoryUsers $inventory)
    {
        $this->authorize($request);
        $filters = $request->validate(['status' => ['nullable', 'in:unlinked,linked,all'], 'q' => ['nullable', 'string', 'max:255']]);
        $status = $filters['status'] ?? 'unlinked';
        $search = trim($filters['q'] ?? '');
        $ready = $inventory->ready();
        $entries = null;
        $localUsers = $linkedUsers = collect();
        $counts = ['all' => 0, 'linked' => 0, 'unlinked' => 0];
        if ($ready) {
            $query = $inventory->database()->table(LdapDirectoryUsers::TABLE.' as directory')
                ->leftJoin(LdapAccounts::TABLE.' as links', function ($join) {
                    $join->on('links.connection', '=', 'directory.connection')->on('links.guid', '=', 'directory.guid');
                })->where('directory.connection', (string) config('accountmanager_ldap.connection'));
            $counts = ['all' => (clone $query)->count('directory.id'),
                'linked' => (clone $query)->whereNotNull('links.user_id')->count('directory.id'),
                'unlinked' => (clone $query)->whereNull('links.user_id')->count('directory.id')];
            if ($status !== 'all') $status === 'linked' ? $query->whereNotNull('links.user_id') : $query->whereNull('links.user_id');
            if ($search !== '') {
                $query->where(function ($query) use ($search) {
                    foreach (['name', 'samaccountname', 'userprincipalname', 'email'] as $column) {
                        $query->orWhere('directory.'.$column, 'like', '%'.$search.'%');
                    }
                });
            }
            $entries = $query->select(['directory.*', 'links.user_id as local_user_id'])
                ->orderBy('directory.name')->orderBy('directory.id')->paginate(25)->withQueryString();
            $model = User::getProjectClassName();
            $localUsers = $model::query()->without('userdata')->with('roles')->where('active', true)
                ->orderBy('name')->get(['id', 'name', 'email', 'active']);
            if (! $request->user()->hasRole('superadmin')) {
                $localUsers = $localUsers->reject(fn ($user) => (string) $user->getKey() === '1' || $user->hasRole('superadmin'));
            }
            $linkedUsers = $model::query()->without('userdata')->with('roles')
                ->whereKey($entries->pluck('local_user_id')->filter()->unique()->all())
                ->get(['id', 'name', 'email', 'active'])->keyBy(fn ($user) => (string) $user->getKey());
        }

        return view('accountmanager::ldap.directory', compact('ready', 'entries', 'localUsers', 'linkedUsers', 'counts', 'status', 'search'));
    }

    public function link(Request $request, string $directoryUser, LdapDirectoryUsers $inventory, LdapAccounts $accounts)
    {
        $this->authorize($request);
        abort_unless($inventory->ready(), 409);
        $record = $inventory->query()->where('id', $directoryUser)->first();
        abort_unless($record, 404);
        if ($record->missing) throw ValidationException::withMessages(['user_id' => __('accountmanager::directory.missing_cannot_link')]);
        $input = $request->validate(['user_id' => ['required', 'string', 'max:128']]);
        $model = User::getProjectClassName();
        $target = $model::query()->without('userdata')->whereKey($input['user_id'])->first();
        $this->protectPrivilegedUser($request, $target);
        try {
            $accounts->link($input['user_id'], new DirectoryIdentity($record->connection, $record->guid, $record->name));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['user_id' => __('accountmanager::directory.link_conflict')]);
        }

        return redirect()->route(config('accountmanager.routePrefix').'ldapUsers.index')->with('status', __('accountmanager::directory.linked_success'));
    }

    public function unlink(Request $request, string $directoryUser, LdapDirectoryUsers $inventory)
    {
        $this->authorize($request);
        abort_unless($inventory->ready(), 409);
        $record = $inventory->query()->where('id', $directoryUser)->first();
        abort_unless($record, 404);
        $input = $request->validate(['user_id' => ['required', 'string', 'max:128']]);
        $model = User::getProjectClassName();
        $target = $model::withoutGlobalScopes()->without('userdata')->whereKey($input['user_id'])->first();
        $this->protectPrivilegedUser($request, $target);
        $deleted = $inventory->database()->table(LdapAccounts::TABLE)->where('connection', $record->connection)
            ->where('guid', $record->guid)->where('user_id', $input['user_id'])->delete();
        if (! $deleted) throw ValidationException::withMessages(['user_id' => __('accountmanager::directory.link_conflict')]);

        return redirect()->route(config('accountmanager.routePrefix').'ldapUsers.index')->with('status', __('accountmanager::directory.unlinked_success'));
    }
}
