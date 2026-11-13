<div id="accountmanager-current-route-access" uk-modal aria-labelledby="accountmanager-current-route-access-title"
     data-route-name="{{ $currentRouteName }}"
     data-load-url="{{ route(config('accountmanager.routePrefix').'routes.access.show') }}">
    <div class="uk-modal-dialog uk-modal-body" style="width: min(1440px, 96vw); margin: 16px auto">
        <button class="uk-modal-close-default" type="button" uk-close aria-label="@lang('accountmanager::route-access.close')"></button>
        <h2 id="accountmanager-current-route-access-title" class="uk-modal-title">@lang('accountmanager::route-access.current_page')</h2>
        <p class="uk-margin-small-bottom"><strong>{{ $currentRouteName }}</strong><br><span class="uk-text-small uk-text-break">{{ $currentRoutePath }}</span></p>
        @if($currentRouteNotice)
            <p class="uk-alert-primary" role="status">{{ $currentRouteNotice }}</p>
        @else
            <ul uk-tab="connect: #current-route-access-panels">
                <li><a href="#">@lang('accountmanager::route-access.current_only')</a></li>
                <li><a href="#">@lang('accountmanager::route-access.related_title')</a></li>
            </ul>
            <div id="current-route-access-panels" class="uk-switcher">
            <section>
            <p data-route-description class="uk-text-small"></p>
            <p class="uk-text-small" data-route-system-roles hidden></p>
            <p class="uk-text-small">@lang('accountmanager::route-access.current_help')</p>
            <p class="uk-text-small uk-text-muted">@lang('accountmanager::route-access.implicit_roles_help')</p>
            <p class="uk-alert-warning uk-text-small">@lang('accountmanager::route-access.empty_help')</p>
            <div data-route-message role="status" aria-live="polite"></div>
            <form method="POST" action="{{ route(config('accountmanager.routePrefix').'routes.access.update') }}" data-current-route-form>
                @csrf
                @method('PUT')
                <fieldset class="uk-fieldset" disabled>
                    <div class="uk-grid-small uk-child-width-1-2@s" uk-grid>
                        @foreach(['roles', 'permissions'] as $type)
                            <div>
                                <h3 class="uk-h4" id="current-route-{{ $type }}-title">@lang('accountmanager::route-access.'.$type)</h3>
                                <input type="search" class="uk-input uk-form-small uk-margin-small-bottom" data-filter="{{ $type }}"
                                       aria-label="@lang('accountmanager::route-access.search_'.$type)" placeholder="@lang('accountmanager::route-access.search_'.$type)">
                                <div data-choices="{{ $type }}" role="group" aria-labelledby="current-route-{{ $type }}-title"
                                     class="uk-overflow-auto" style="max-height: 260px"></div>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
                <p class="uk-text-right uk-margin-top">
                    <button class="uk-button uk-button-default uk-modal-close" type="button">@lang('accountmanager::route-access.close')</button>
                    <button class="uk-button uk-button-primary" type="submit" disabled>@lang('accountmanager::route-access.save')</button>
                </p>
            </form>
            </section>
            <section data-related-panel>
                <p data-related-loading class="uk-text-small" role="status">@lang('accountmanager::route-access.loading')</p>
                <iframe data-related-frame title="@lang('accountmanager::route-access.related_title')"
                        class="uk-width-1-1" style="height: calc(100vh - 300px); min-height: 300px; border: 0"></iframe>
            </section>
            </div>
        @endif
        <p class="uk-text-small uk-margin-remove-bottom"><a href="{{ route(config('accountmanager.routePrefix').'routes.index') }}">@lang('accountmanager::route-access.open_catalog')</a></p>
    </div>
</div>
@unless($currentRouteNotice)
@php($currentRouteMessages = \Illuminate\Support\Arr::only(trans('accountmanager::route-access'), ['loading', 'saving', 'load_failed', 'save_failed', 'system_roles', 'no_choices', 'related_changed']))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('accountmanager-current-route-access');
    const form = modal.querySelector('[data-current-route-form]');
    const fieldset = form.querySelector('fieldset');
    const save = form.querySelector('[type="submit"]');
    const message = modal.querySelector('[data-route-message]');
    const relatedPanel = modal.querySelector('[data-related-panel]');
    const relatedFrame = modal.querySelector('[data-related-frame]');
    const relatedLoading = modal.querySelector('[data-related-loading]');
    const text = @json($currentRouteMessages);
    let loadRequest;
    let saving = false;
    let dirty = false;
    let relatedUrl;
    let refreshRelated = false;

    function showRelated() {
        if (!relatedUrl) return;
        relatedLoading.hidden = true;
        if (!relatedFrame.getAttribute('src')) relatedFrame.src = relatedUrl;
        else if (refreshRelated) relatedFrame.contentWindow.location.reload();
        refreshRelated = false;
    }

    function status(value, error) {
        message.textContent = value;
        message.className = error ? 'uk-alert-danger uk-padding-small' : 'uk-text-small';
        message.setAttribute('role', error ? 'alert' : 'status');
    }

    function choices(type, items, selected) {
        const container = modal.querySelector('[data-choices="' + type + '"]');
        const ids = new Set(selected.map(String));
        container.replaceChildren();
        modal.querySelector('[data-filter="' + type + '"]').value = '';
        if (!items.length) container.textContent = text.no_choices;
        items.forEach(function (item) {
            const label = document.createElement('label');
            label.className = 'uk-display-block uk-margin-small-bottom';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'uk-checkbox uk-margin-small-right';
            checkbox.name = type + '[]';
            checkbox.value = item.id;
            checkbox.checked = ids.has(String(item.id));
            label.append(checkbox, document.createTextNode(item.name + ' (' + item.guard + ')'));
            container.append(label);
        });
    }

    async function responseData(response, fallback) {
        const data = await response.json().catch(function () { return {}; });
        if (!response.ok) {
            const errors = data.errors ? Object.values(data.errors).flat().join(' ') : null;
            throw new Error(errors || fallback);
        }
        return data;
    }

    async function loadAssociations() {
        if (loadRequest) loadRequest.abort();
        loadRequest = new AbortController();
        fieldset.disabled = true;
        save.disabled = true;
        status(text.loading, false);
        try {
            const url = new URL(modal.dataset.loadUrl, window.location.href);
            url.searchParams.set('route_name', modal.dataset.routeName);
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin', signal: loadRequest.signal});
            const data = await responseData(response, text.load_failed);
            relatedUrl = data.related_url;
            if (relatedPanel.classList.contains('uk-active')) showRelated();
            choices('roles', data.roles, data.selected_roles);
            choices('permissions', data.permissions, data.selected_permissions);
            modal.querySelector('[data-route-description]').textContent = data.description || '';
            const systemRoles = modal.querySelector('[data-route-system-roles]');
            systemRoles.textContent = text.system_roles + ': ' + data.system_roles.join(', ');
            systemRoles.hidden = !data.system_roles.length;
            fieldset.disabled = false;
            save.disabled = false;
            status('', false);
            dirty = false;
        } catch (error) {
            if (error.name !== 'AbortError') {
                status(error.message || text.load_failed, true);
                relatedLoading.textContent = error.message || text.load_failed;
                relatedLoading.hidden = false;
            }
        }
    }

    UIkit.util.on(modal, 'beforeshow', function (event) {
        if (event.target !== modal || saving) return;
        refreshRelated = true;
        loadAssociations();
    });
    UIkit.util.on(relatedPanel, 'show', function (event) {
        if (event.target === relatedPanel) showRelated();
    });
    fieldset.addEventListener('change', function () { dirty = true; });
    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin || event.source !== relatedFrame.contentWindow
            || event.data?.type !== 'accountmanager:related-routes-saved'
            || event.data.routeName !== modal.dataset.routeName) return;
        if (dirty || saving) status(text.related_changed, false);
        else loadAssociations();
    });

    modal.querySelectorAll('[data-filter]').forEach(function (input) {
        input.addEventListener('input', function () {
            const query = input.value.toLocaleLowerCase();
            modal.querySelectorAll('[data-choices="' + input.dataset.filter + '"] label').forEach(function (label) {
                label.hidden = !label.textContent.toLocaleLowerCase().includes(query);
            });
        });
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (save.disabled || saving) return;
        const values = new FormData(form);
        saving = true;
        fieldset.disabled = true;
        save.disabled = true;
        status(text.saving, false);
        try {
            const response = await fetch(form.action, {
                method: 'PUT', credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': values.get('_token')},
                body: JSON.stringify({route_name: modal.dataset.routeName, roles: values.getAll('roles[]'), permissions: values.getAll('permissions[]')})
            });
            const data = await responseData(response, text.save_failed);
            status(data.message, false);
            dirty = false;
            refreshRelated = true;
        } catch (error) {
            status(error.message || text.save_failed, true);
        } finally {
            saving = false;
            fieldset.disabled = false;
            save.disabled = false;
        }
    });
});
</script>
@endunless
