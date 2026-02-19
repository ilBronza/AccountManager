<?php

return [
    // Etichette e sezioni
    'permissions'  => 'Permessi',
    'users'        => 'Utenti',
    'user'         => 'Utente',
    'roles'        => 'Ruoli',
    'accounts'     => 'Utenze',
    'userdata'     => 'Dati utente',
    'trashedUsers' => 'Utenti cestinati',

    // Testi relativi all'autenticazione (duplicati di auth.php per comodità)
    'passwordConfirmation'             => 'Conferma password',
    'rememberMe'                       => 'Ricordami',
    'register'                         => 'Registrati',
    'forgottenPassword'               => 'Password dimenticata?',
    'alreadyRegisteredGoToLogin'      => 'Sei già registrato? Vai al login',
    'sendResetPasswordLink'           => 'Invia link di reset',
    'resetPasswordDescription'        => 'Inserisci l\'indirizzo email con il quale ti sei registrato. Ti invieremo un link per reimpostare la password.',
    'confirmPasswordText'             => 'Questa è un\'area protetta dell\'applicazione. Inserisci la tua password prima di continuare.',
    'resendVerificationEmail'         => 'Invia una nuova email di verifica',
    'logout'                          => 'Logout',
    'thanksForSigninEmailConfirmationText' => 'Grazie per esserti registrato! Prima di iniziare, verifica il tuo indirizzo email cliccando sul link che ti abbiamo inviato. Se non l\'hai ricevuto, clicca sul link qui sotto e te ne invieremo uno nuovo.',
    'aNewConfirmationLinkHasBeenSent' => 'Un nuovo link di verifica è stato inviato all\'indirizzo email che hai fornito in fase di registrazione.',

    // Azioni account (usate da menu/config)
    'edit'         => 'Modifica account',
    'editUserdata' => 'Modifica dati utente',
    'editPassword' => 'Modifica password',
    'editAvatar'   => 'Modifica avatar',
    'resetPassword' => 'Reimposta password',

    // Messaggi usati in middleware / controller
    'userNotActive' => 'Il tuo account non è attivo.',
    'userCloned'    => 'L\'utente :user è stato duplicato.',
];
