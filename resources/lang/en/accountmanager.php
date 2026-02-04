<?php

return [
    // Labels and sections
    'permissions'  => 'Permissions',
    'users'        => 'Users',
    'user'         => 'User',
    'roles'        => 'Roles',
    'accounts'     => 'Accounts',
    'userdata'     => 'User data',
    'trashedUsers' => 'Deleted users',

    // Auth-related texts (kept for convenience)
    'passwordConfirmation'             => 'Password confirmation',
    'rememberMe'                       => 'Remember me',
    'register'                         => 'Register',
    'forgottenPassword'               => 'Forgotten password?',
    'alreadyRegisteredGoToLogin'      => 'Already registered? Go to login',
    'sendResetPasswordLink'           => 'Send reset password link',
    'resetPasswordDescription'        => 'Enter the email address you used to register. We will send you a password reset link.',
    'confirmPasswordText'             => 'This is a secure area of the application. Please enter your password before continuing.',
    'resendVerificationEmail'         => 'Send a new verification email',
    'logout'                          => 'Logout',
    'thanksForSigninEmailConfirmationText' => 'Thank you for registering! Before you can start, please verify your email address by clicking on the link we sent you. If you did not receive it, click the link below and we will send you a new one.',
    'aNewConfirmationLinkHasBeenSent' => 'A new verification link has been sent to the email address you provided during registration.',

    // Account actions (used by menu/config)
    'edit'         => 'Edit account',
    'editUserdata' => 'Edit user data',
    'editPassword' => 'Change password',
    'editAvatar'   => 'Edit avatar',

    // Status/messages used in middleware / controllers
    'userNotActive' => 'Your account is not active.',
    'userCloned'    => 'User :user has been duplicated.',
];