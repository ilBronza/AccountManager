<div><span class="uk-label {{ $entry->enabled ? 'uk-label-success' : 'uk-label-warning' }}">{{ __('accountmanager::directory.'.($entry->enabled ? 'active' : 'disabled')) }}</span></div>
@if(config('accountmanager_ldap.required_group_dn'))<div class="uk-text-small uk-margin-small-top">{{ __('accountmanager::directory.'.($entry->in_required_group ? 'in_group' : 'outside_group')) }}</div>@endif
@if($entry->missing)<div class="uk-text-danger uk-text-small">@lang('accountmanager::directory.missing')</div>@endif
