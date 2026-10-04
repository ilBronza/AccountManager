# Changelog

## Unreleased

- Declare runtime and test dependencies, factory autoload and Composer test scripts.
- Fix permission form routes and normalize configured role lists/maps, denying explicitly empty rules.
- Register only implemented resource routes and enforce active accounts on package routes.
- Require authenticated, CSRF-protected, rate-limited heartbeat requests.
- Use POST for logout and duplication; GET logout displays confirmation only.
- Persist GitHub IDs, reject unavailable accounts and stop storing provider tokens or displaying passwords.
- Generate unpredictable passwords, send password setup links for quick creation and protect privileged accounts.
- Make account creation, duplication and delete/restore transactional; remove debug exits.
- Remove embedded Slack credentials and automatic admin provisioning from migrations.
- Preserve roles during rollback and add an explicit create-admin command.
- Default access logs to the application database, omit query parameters and provide a retention command.
- Cover HTTP writes, OAuth failures, CSRF, IP rules and real migration install/rollback cycles.
- Add Laravel 11/12 CI and document host requirements and upgrade steps.
