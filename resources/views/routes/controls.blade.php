<div class="uk-margin-bottom">
    @if(session('status'))<div class="uk-alert-success" role="status" uk-alert>{{ session('status') }}</div>@endif
    @if($errors->any())
        <div class="uk-alert-danger" role="alert" uk-alert><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form id="accountmanager-route-associations" method="POST" action="{{ route(config('accountmanager.routePrefix').'routes.associate') }}" class="uk-grid-small uk-flex-bottom" uk-grid>
        @csrf
        <input type="hidden" name="route_ids" value="[]">
        <div class="uk-width-1-3@m">
            <label for="route-access-roles" class="uk-form-label">@lang('accountmanager::route-access.roles')</label>
            <select id="route-access-roles" class="uk-select" name="roles[]" multiple size="{{ ($compact ?? false) ? 3 : 5 }}">
                @foreach($roles as $role)<option value="{{ $role->getKey() }}">{{ $role->name }} ({{ $role->guard_name }})</option>@endforeach
            </select>
        </div>
        <div class="uk-width-1-3@m">
            <label for="route-access-permissions" class="uk-form-label">@lang('accountmanager::route-access.permissions')</label>
            <select id="route-access-permissions" class="uk-select" name="permissions[]" multiple size="{{ ($compact ?? false) ? 3 : 5 }}">
                @foreach($permissions as $permission)<option value="{{ $permission->getKey() }}">{{ $permission->name }} ({{ $permission->guard_name }})</option>@endforeach
            </select>
        </div>
        <div class="uk-width-1-3@m">
            <button class="uk-button uk-button-primary uk-button-small" type="submit" name="operation" value="attach" data-route-apply disabled>@lang('accountmanager::route-access.attach')</button>
            <button class="uk-button uk-button-default uk-button-small" type="submit" name="operation" value="detach" data-route-apply disabled>@lang('accountmanager::route-access.detach')</button>
            <p class="uk-text-small" data-route-selection-count role="status" aria-live="polite">0 @lang('accountmanager::route-access.selected')</p>
        </div>
    </form>
    <p class="uk-text-small uk-text-muted">@lang('accountmanager::route-access.selection_help')</p>
    @unless($compact ?? false)<p class="uk-text-small uk-text-muted">@lang('accountmanager::route-access.implicit_roles_help')</p>@endunless
</div>
