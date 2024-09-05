@if($avatarUrl = $modelInstance->getUserData()?->getAvatarImage())

<img src="{{ $avatarUrl }}" />

@endif