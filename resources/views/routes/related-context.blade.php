@php($relatedContext = ['related_to' => $related->anchor->name, 'scope' => $related->scope, 'iframed' => 'true'])
<script>
(() => {
    const form = document.getElementById('accountmanager-route-associations');
    if (!form) return;
    const context = @json($relatedContext);
    Object.entries(context).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.append(input);
    });
    @if(session('routeAccessRelatedUpdated') === $related->anchor->name)
        window.parent.postMessage({type: 'accountmanager:related-routes-saved', routeName: context.related_to}, window.location.origin);
    @endif
})();
</script>
