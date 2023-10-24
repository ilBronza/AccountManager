@if($avatarUrl = $modelInstance->getUserData()?->getAvatarImage())

<img class="uk-width-small" src="{{ $avatarUrl }}" />

@endif