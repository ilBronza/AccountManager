# AccountManager

Gestione di utenti, ruoli, permessi, profili e avatar per applicazioni Laravel che
usano i componenti CRUD e UI IlBronza. Include accessi GitHub, restrizioni IP,
presenza online e registro degli accessi.

## Requisiti e installazione

PHP 8.2+, Laravel 11 o 12. Le dipendenze dirette sono dichiarate in Composer;
la matrice CI copre Laravel 11/PHP 8.2 e Laravel 12/PHP 8.4. Le verifiche locali
nel workspace usano Laravel 11 con il vendor condiviso `.ibtest`.

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
