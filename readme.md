# AccountManager

Gestione di utenti, ruoli, permessi, profili e avatar per applicazioni Laravel che
usano i componenti CRUD e UI IlBronza. Include accessi GitHub, restrizioni IP,
presenza online e registro degli accessi.

## Requisiti e installazione

PHP 8.2+ e Laravel, con versione minima dichiarata in Composer.

I vincoli Composer delle dipendenze Laravel e delle relative integrazioni indicano soltanto una versione minima, senza un limite sulle major successive. La scelta delle versioni concrete spetta all’applicazione ospite e al suo `composer.lock`; la compatibilità del codice va verificata con i test.

La matrice CI comprende Laravel 11/PHP 8.2, Laravel 12/PHP 8.4 e
Laravel 13/PHP 8.4. Le verifiche locali nel workspace usano Laravel 11
con il vendor condiviso `.ibtest`.

```sh
composer require ilbronza/accountmanager
php artisan vendor:publish --tag=accountmanager.config
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
```

L'applicazione deve fornire la tabella `users`, le tabelle Spatie Permission,
`App\Models\User` e `App\Http\Controllers\Controller`. Le migrazioni di
AccountManager vengono caricate automaticamente: non pubblicarle una seconda volta.
Per un'app esistente controllare l'ordine delle migrazioni: `users`, `roles` e
`permissions` devono esistere prima delle migrazioni storiche del package.

Il modello del package estende `App\Models\User`, che deve a sua volta estendere
`Illuminate\Foundation\Auth\User`; non farlo estendere dal modello del package,
per evitare ereditarietà circolare. Il modello base dell'app deve usare `Notifiable`
se si vogliono inviare gli inviti per l'impostazione della password.

Configurare `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => \IlBronza\AccountManager\Models\User::class,
    ],
],
```

Per estensioni di progetto, creare una sottoclasse del modello del package e
impostarla sia in `auth.providers.users.model` sia in
`accountmanager.models.user.class`. La configurazione distribuita non richiede
`App\Models\ProjectSpecific\User`.

Configurare `config/permission.php`:

```php
'models' => [
    'role' => \IlBronza\AccountManager\Models\Role::class,
    'permission' => \IlBronza\AccountManager\Models\Permission::class,
],
```

Il rendering dei form e delle liste richiede il layout UikitTemplate e la sua
configurazione nell'applicazione. Il package rimane un componente dell'ecosistema
IlBronza, non uno scaffolding Laravel indipendente.

Per usare i controller di autenticazione distribuiti:

```sh
php artisan vendor:publish --tag=accountmanager.authControllers
php artisan vendor:publish --tag=accountmanager.views
```

Registrare `Auth::routes()` nel gruppo web dell'applicazione. Il broker password
Laravel deve avere la tabella `password_reset_tokens`, un mailer funzionante e
la route `password.reset`. La creazione rapida invia il normale link Laravel per
impostare una password; non mostra né invia password in chiaro. Applicare le
configurazioni e le migrazioni prima di creare utenti:

```sh
php artisan migrate
php artisan accountmanager:create-admin admin@example.com --name="Administrator"
```

Il comando chiede password e conferma senza mostrarle, richiede almeno 12
caratteri e rifiuta email già presenti. Le migrazioni non creano amministratori
né assegnano privilegi automaticamente all'utente con ID 1.

## Recupero password con Laravel UI

Il flusso usa `Auth::routes(['register' => false])` e i trait di `laravel/ui` 4:
broker, scadenza e throttling dei token, notifiche e regole di validazione restano
quelli di Laravel UI. Il package mantiene i propri nomi delle viste e il layout
UIkit; i controller pubblicati selezionano esplicitamente queste viste.

| Richiesta | Nome route | Controller / vista |
| --- | --- | --- |
| GET `/password/reset` | `password.request` | `ForgotPasswordController@showLinkRequestForm` → `auth.forgot-password` |
| POST `/password/email` | `password.email` | `ForgotPasswordController@sendResetLinkEmail` |
| GET `/password/reset/{token}?email=...` | `password.reset` | `ResetPasswordController@showResetForm` → `auth.reset-password`, con `$token` e `$email` |
| POST `/password/reset` | `password.update` | `ResetPasswordController@reset` |

L'email viene inclusa nel link dalla notifica Laravel. Un reset riuscito aggiorna
la password con hash, ruota il remember token, elimina il token di reset, emette
`PasswordReset` e autentica l'utente con redirect a `/home`, come previsto dal
controller distribuito. Il GET mostra il modulo; la verifica della validità e
della scadenza del token avviene al POST. Gli errori sono mostrati nel modulo;
la richiesta del link mostra anche il messaggio di successo in sessione.

### Allineare le copie già pubblicate, incluso GeG

Aggiornare il package non modifica i file già pubblicati nell'applicazione.
Dopo aver reso disponibile la versione aggiornata, confrontare **solo questi
quattro file**, facendo un backup o un commit delle modifiche dell'app prima
del merge:

| Sorgente nel package | Copia nell'applicazione |
| --- | --- |
| `src/Http/Controllers/Auth/ForgotPasswordController.php` | `app/Http/Controllers/Auth/ForgotPasswordController.php` |
| `src/Http/Controllers/Auth/ResetPasswordController.php` | `app/Http/Controllers/Auth/ResetPasswordController.php` |
| `resources/views/auth/forgot-password.blade.php` | `resources/views/auth/forgot-password.blade.php` |
| `resources/views/auth/reset-password.blade.php` | `resources/views/auth/reset-password.blade.php` |

Per esempio, dalla root di GeG, il confronto dei controller si esegue con:

```sh
diff -u app/Http/Controllers/Auth/ForgotPasswordController.php vendor/ilbronza/accountmanager/src/Http/Controllers/Auth/ForgotPasswordController.php
diff -u app/Http/Controllers/Auth/ResetPasswordController.php vendor/ilbronza/accountmanager/src/Http/Controllers/Auth/ResetPasswordController.php
diff -u resources/views/auth/forgot-password.blade.php vendor/ilbronza/accountmanager/resources/views/auth/forgot-password.blade.php
diff -u resources/views/auth/reset-password.blade.php vendor/ilbronza/accountmanager/resources/views/auth/reset-password.blade.php
```

Se si usa il checkout locale tramite repository Composer di tipo path,
`vendor/ilbronza/accountmanager` può essere un symlink al package: confrontare
sempre i sorgenti aggiornati con le copie dell'applicazione. `diff` termina con
codice 1 quando trova differenze, senza modificare alcun file.

Applicare manualmente questi cambiamenti, conservando personalizzazioni,
middleware, regole e redirect dell'applicazione:

1. In `ForgotPasswordController`, aggiungere o allineare `showLinkRequestForm()`
   affinché restituisca `view('auth.forgot-password')`; mantenere
   `SendsPasswordResetEmails`.
2. In `ResetPasswordController`, importare `Illuminate\Http\Request` e aggiungere
   o allineare `showResetForm(Request $request)` affinché restituisca
   `view('auth.reset-password', ['token' => $request->route('token'), 'email' => $request->email])`;
   mantenere `ResetsPasswords` e il redirect previsto dall'applicazione.
3. Nella vista di reset usare `route('password.update')`, `$token` nel campo
   nascosto e `old('email', $email)` per l'email. Conservare `@csrf`, mostrare gli
   errori di `token`, `email` e `password` e usare `autocomplete="new-password"`
   per password e conferma.
4. Nella vista di richiesta link mantenere `route('password.email')` e `@csrf`,
   mostrare `session('status')` e gli errori email. In entrambe le viste usare
   `type="email"`, `required`, `autocomplete="email"` e pulsanti `type="submit"`.

Non occorre creare viste `auth/passwords/*`, aggiungere `password.store` o cambiare
`Auth::routes(['register' => false])`. Evitare `vendor:publish --force` sui tag
`accountmanager`, `accountmanager.views` e `accountmanager.authControllers`:
pubblicano anche altri file e possono sovrascrivere personalizzazioni.

Dopo il merge, dalla root dell'applicazione:

```sh
php artisan view:clear
php artisan route:list --name=password
```

Verificare le quattro route della tabella. Se l'applicazione usa una cache delle
rotte, rigenerarla con il normale processo di deploy. Il fix non cambia schema
o configurazione del broker: la sua tabella token deve già esistere nella
connessione configurata in `config/auth.php`.

## Autorizzazioni

Le route di gestione richiedono autenticazione, account attivo e un ruolo
consentito. `defaultRoles` accetta una lista di nomi o una mappa `nome => true`;
un valore `false` disabilita il ruolo. `routeRoles` prevale sui valori predefiniti:
una lista esplicitamente vuota nega l'accesso. I nomi delle route esistenti sono
mantenuti, compresi i prefissi storici, per compatibilità.

Solo un superadmin può modificare, eliminare, ripristinare o duplicare un altro
superadmin. Le modifiche a ruoli, permessi e restrizioni di accesso richiedono
superadmin o administrator, oltre all'autorizzazione per la route. Se si abilita
administrator nelle route, questo ruolo non può assegnare superadmin.

Per applicare restrizioni IP a una route dell'app:

```php
Route::middleware(['web', \IlBronza\AccountManager\Http\Middleware\AuthenticateWithIp::class])
    ->get('/private', ...);
```

Sono supportati IP e CIDR IPv4/IPv6. La lista utente usa righe JSON con chiave
`ip`; quella del ruolo accetta anche testo separato da righe o virgole. Le reti
interne sono configurabili in `ipAccess.internalCidrs`. I ruoli possono ampliare
la lista dell'utente soltanto se `allow_from_remote` è attivo per l'utente.
Configurare correttamente i trusted proxy Laravel affinché `Request::ip()`
contenga l'indirizzo del client.

## GitHub, logout e presenza online

Configurare le credenziali GitHub in `services.github`, con redirect alla route
`/auth/callback`. Socialite verifica lo stato OAuth. Per collegare o creare un
account viene usata l'email primaria verificata del provider; gli account sospesi,
eliminati o collegati a un altro ID GitHub sono rifiutati. Il callback salva
`github_id` e non conserva access token o refresh token. Il redirect successivo
è configurabile con `accountmanager.loginRedirect`.

`POST /logout` termina la sessione. `GET /logout` mostra soltanto un form di
conferma, usato dal menu del package. I form personalizzati possono usare:

```blade
<form method="POST" action="{{ app('accountmanager')->route('accountmanager.logout') }}">
    @csrf
    <button type="submit">Esci</button>
</form>
```

La duplicazione usa **POST** su `account-management/users/duplicate/{user}` e
richiede CSRF. Copia ruoli, permessi e dati anagrafici in una transazione;
l'account creato è disattivato e ha una password casuale. Non vengono copiati
identità GitHub, token, sessioni o verifica email. Per renderlo utilizzabile,
impostare l'email corretta, attivarlo e usare il recupero password Laravel.

Includere `accountmanager::heartbeat` nel layout degli utenti autenticati.
L'endpoint richiede sessione e CSRF, accetta al massimo sei richieste al minuto
per utente e ricava sempre l'identità dalla sessione, ignorando `user_id` nel body.
Il TTL e l'intervallo sono configurabili. Non usare la presenza online come
prova di attività lavorativa o come controllo di autorizzazione.

## Registro accessi

Aggiungere `LogUserAccess` al gruppo web dell'applicazione per registrare le
richieste non AJAX degli utenti autenticati. I parametri della query string non
vengono salvati, per evitare di registrare codici OAuth e token.

I log usano per default la connessione database dell'app. Per un database
dedicato, configurare `ACCOUNTMANAGER_LOG_CONNECTION` con il nome di una
connessione esistente **prima** di eseguire le migrazioni.

```sh
php artisan accountmanager:prune-access-logs
php artisan accountmanager:prune-access-logs --days=180
```

La conservazione predefinita è 90 giorni, configurabile in
`logUserAccess.retention_days`. Il comando elimina a blocchi solo i record più
vecchi della soglia. Non è pianificato automaticamente: aggiungerlo allo
scheduler dell'app quando si desidera una pulizia ricorrente.

Il webhook Slack è opzionale e si configura tramite
`ACCOUNTMANAGER_SLACK_WEBHOOK_URL`.

## Aggiornamento da versioni precedenti

- Aggiornare i form/link personalizzati di logout e duplicazione per inviare
  POST con CSRF. La GET di logout non effettua più la disconnessione.
- Ripubblicare o aggiornare l'include heartbeat nelle viste copiate in app.
- Conservare esplicitamente `activityMysql` nella configurazione dei log se i
  dati esistenti sono su quella connessione: il nuovo default è il DB principale.
- Aggiornare manualmente eventuali copie pubblicate di controller auth e
  migrazioni. Non eseguire nuovamente migrazioni già applicate.
- Le correzioni alle migrazioni storiche proteggono nuove installazioni e
  rollback; non cambiano utenti o credenziali già creati. Cambiare le password
  degli account creati con credenziali predefinite e verificare le assegnazioni
  automatiche pregresse. Revocare il vecchio webhook Slack nel servizio.
- I token GitHub preesistenti vengono rimossi dall'account al successivo login;
  eventuali integrazioni che li usavano devono gestire le proprie credenziali.
- Il file lock del package è rimosso: ogni applicazione mantiene il proprio
  `composer.lock`. Il manifest dichiara le dipendenze prima implicite.

## Verifiche

```sh
composer test:lint
composer test
```

La suite include le pagine, le scritture, CSRF, autorizzazioni, accesso IP,
OAuth simulato, inviti simulati, conservazione dei log e migrazioni reali con
rollback e reinstallazione. Per il vendor condiviso del workspace e i limiti
dell'ambiente vedere [tests/README.md](tests/README.md).

## Accesso Active Directory opzionale

LDAP è disponibile come accesso aggiuntivo, disabilitato per default, senza
sostituire il login locale o GitHub. Vedere [installazione e configurazione LDAP](docs/ldap.md).
