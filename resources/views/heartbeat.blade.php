@if(config('accountmanager.heartbeat.enabled', true))
@php
	$heartbeatIntervalMs = max(5, (int) config('accountmanager.heartbeat.interval_seconds', 30)) * 1000;
@endphp
<script>
(function () {
	'use strict';
	var url = @json(route('accountmanager.heartbeat'));
	var userId = @json(auth()->id());
	var intervalMs = {{ $heartbeatIntervalMs }};

	if (! userId) {
		return;
	}

	function send() {
		if (document.hidden) {
			return;
		}

		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: JSON.stringify({ user_id: userId })
		}).catch(function () {});
	}

	send();
	setInterval(send, intervalMs);

	document.addEventListener('visibilitychange', function () {
		if (! document.hidden) {
			send();
		}
	});
})();
</script>
@endif
