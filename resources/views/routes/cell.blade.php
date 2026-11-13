@if($cell === 'selection')
    <input type="checkbox" class="uk-checkbox" data-route-ids="{{ json_encode($entry->route_ids) }}" aria-label="{{ __('accountmanager::route-access.select', ['name' => $entry->route?->name ?? $entry->label]) }}" @if(! $entry->route_ids) disabled @endif>
@elseif($cell === 'path')
    <span class="uk-text-nowrap">
        @if(! $entry->route)
            <strong>{{ $entry->label }}</strong>
        @else
            <code>{{ $entry->label }} /{{ ltrim($entry->route->uri, '/') }}</code>
        @endif
    </span>
@elseif($cell === 'name' && ! $entry->route)
    <span class="uk-text-muted">@lang('accountmanager::route-access.uri_group')</span>
@elseif($cell === 'name' && $entry->route)
    <span title="{{ $entry->route->action }}">{{ $entry->route->name ?? __('accountmanager::route-access.unnamed') }}</span>
    @if($entry->route->hasMissingControllerAction())
        <span class="uk-label uk-label-warning" title="{{ $entry->route->action }}">@lang('accountmanager::route-access.missing_action')</span>
    @endif
    @if(request('related_to') === $entry->route->name && $entry->route->name !== null)<span class="uk-label uk-label-success">@lang('accountmanager::route-access.current_only')</span>@endif
    @if($entry->excluded)<span class="uk-label">@lang('accountmanager::route-access.excluded')</span>@endif
@elseif($cell === 'description' && $entry->route)
    <span class="uk-hidden" aria-hidden="true">{{ $entry->route->description }}</span>
    <input type="text" class="uk-input ib-editor-text" data-field="description"
        data-url="{{ $entry->route->getKeyedRoute('update') }}"
        data-save-trigger="{{ config('datatables.editor.saveTrigger', 'enter') }}"
        data-originalvalue="{{ $entry->route->description }}" value="{{ $entry->route->description }}"
        aria-label="{{ __('accountmanager::route-access.edit_description', ['route' => $entry->route->name ?? $entry->route->uri]) }}">
@elseif($cell === 'system_roles' && $entry->route)
    @foreach(array_diff($entry->route->system_roles ?? [], app(\IlBronza\AccountManager\Routing\RouteAccess::class)->bypassRoles()) as $role)<span class="uk-label">{{ $role }}</span> @endforeach
@elseif($cell === 'assignments' && $entry->route)
    @php($assignedRoles = $entry->route->roles->whereNotIn('name', app(\IlBronza\AccountManager\Routing\RouteAccess::class)->bypassRoles()))
    @foreach($assignedRoles as $role)<span class="uk-label" title="{{ $role->guard_name }}">{{ $role->name }}</span> @endforeach
    @foreach($entry->route->permissions as $permission)<span class="uk-label uk-label-success" title="{{ $permission->guard_name }}">{{ $permission->name }}</span> @endforeach
    @if(! $entry->excluded && $assignedRoles->isEmpty() && $entry->route->permissions->isEmpty())
        <span class="uk-text-muted">@lang('accountmanager::route-access.denied')</span>
    @endif
@endif
