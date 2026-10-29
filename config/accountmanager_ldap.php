<?php

return [
    // Additive login: the existing guard/provider and local passwords are unchanged.
    'enabled' => env('ACCOUNTMANAGER_LDAP_ENABLED', false),
    // Inventory and manual association are independent from enabling AD sign-in.
    'directory_enabled' => false,
    'management_roles' => ['superadmin'],
    'connection' => env('ACCOUNTMANAGER_LDAP_CONNECTION', 'default'),
    // userprincipalname, samaccountname or mail. Values are matched exactly.
    'login_attribute' => env('ACCOUNTMANAGER_LDAP_LOGIN_ATTRIBUTE', 'userprincipalname'),
    'required_group_dn' => env('ACCOUNTMANAGER_LDAP_REQUIRED_GROUP_DN'),
    // Explicit opt-in; email, password, roles and active status are never synced.
    'sync_name' => env('ACCOUNTMANAGER_LDAP_SYNC_NAME', false),
];
