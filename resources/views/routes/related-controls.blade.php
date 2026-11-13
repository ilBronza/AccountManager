<form method="GET" action="{{ route(config('accountmanager.routePrefix').'routes.related') }}" class="uk-margin-bottom">
    <input type="hidden" name="related_to" value="{{ $related->anchor->name }}">
    <input type="hidden" name="iframed" value="true">
    <label for="related-route-scope" class="uk-form-label">@lang('accountmanager::route-access.related_scope')</label>
    <div class="uk-flex uk-flex-middle uk-flex-wrap uk-grid-small" uk-grid>
        <div class="uk-width-expand">
            <select id="related-route-scope" class="uk-select" name="scope">
                @foreach($related->options as $value => $label)<option value="{{ $value }}" @selected($related->scope === $value)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div><button type="submit" class="uk-button uk-button-default">@lang('accountmanager::route-access.show_related')</button></div>
    </div>
    <p class="uk-text-small uk-text-muted">@lang('accountmanager::route-access.related_help')</p>
</form>
