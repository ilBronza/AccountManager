# Test di AccountManager

La suite usa Testbench e SQLite in memoria, senza `RefreshDatabase`.
Le richieste HTTP attraversano middleware, controller, form e viste reali.
GitHub e notifiche email sono simulati: non vengono contattati servizi esterni.

- `GetRoutesTest`: tutte le pagine GET registrate, accesso e rendering HTML.
  Le route CRUD supplementari non implementate non vengono registrate.
- `SessionGetRoutesTest`: OAuth, associazione GitHub, account sospesi/eliminati,
  stato OAuth invalido, logout POST e duplicazione POST.
- `UserWritesTest`: inviti, transazioni, password, copia indipendente di userdata,
  protezione superadmin, cancellazione/ripristino e protezione CSRF reale.
- `HeartbeatTest` e `AccessRulesTest`: presenza autenticata, TTL, limiti di
  frequenza, account sospesi, configurazione dei ruoli e IPv4/IPv6/CIDR.
- `MigrationsTest`: migrazioni del package su tabelle host minime, rollback e
  reinstallazione; nessun account privilegiato automatico o cancellazione ruoli.
- `CreateAdminTest` e `AccessLogsTest`: provisioning esplicito dell'amministratore,
  esclusione query string dai log e conservazione dei dati recenti.
- `PasswordRecoveryTest`: route reali di Laravel UI con registrazione disabilitata,
  sorgenti dei controller pubblicabili e viste del package con layout UIkit.
  Copre apertura dei due moduli, notifica e URL con token/email, messaggio di
  successo, aggiornamento dell'hash, login, evento `PasswordReset`, consumo e
  riutilizzo del token, scadenza, token di un altro utente, throttling e
  validazione di email, token, password e conferma. Usa esclusivamente SQLite
  in memoria e `Notification::fake()`, senza inviare email reali.

Le normali feature usano lo schema fixture. Solo i test di installazione
eseguono le migrazioni storiche. La connessione SQLite traduce `DESCRIBE` e
`SHOW COLUMNS` necessari a CRUD/FormField per i form; tutte le query sui dati
sono reali. La suite non sostituisce una verifica MySQL dell'applicazione host.

## Esecuzione

Con il vendor locale:

```sh
composer test
composer test:lint
```

Nel workspace IlBronza, dalla cartella `pacchetti`:

```sh
python3 .ibtest/bin/ibtest.py AccountManager
```

Se PHP-Wasm non è disponibile, usare PHP locale con il bootstrap condiviso già
generato. Dalla cartella AccountManager:

```sh
ACCOUNTMANAGER_TEST_AUTOLOAD="$(pwd)/../.ibtest/bootstrap_AccountManager.php" \
  php ../.ibtest/vendor/phpunit/phpunit/phpunit -c phpunit.xml --do-not-cache-result
```

`tests/bootstrap.php` usa cartelle temporanee per manifest e viste compilate,
senza scrivere nel vendor condiviso. Aggiungere `--filter NomeTest` per una
verifica mirata. Non occorre installare dipendenze nel workspace condiviso.

Per la sola regressione del recupero password, con il vendor locale:

```sh
vendor/bin/phpunit --filter PasswordRecoveryTest
```

Le viste `auth.*` sono risolte direttamente da `resources/views` del package,
come dopo la pubblicazione nell'app host; i controller `App\Http\Controllers\Auth`
sono caricati dai sorgenti distribuiti. Non ci sono copie dei controller nei
fixture né viste o broker simulati. La notifica è simulata, ma il suo URL viene
generato con `toMail()` e aperto tramite una vera richiesta HTTP di test.

La CI configura repository Composer di tipo path per i pacchetti IlBronza e
verifica Laravel 11/PHP 8.2 e Laravel 12/PHP 8.4. Le dipendenze di questi test
d'integrazione seguono i branch dei pacchetti sorella, come negli altri package
del workspace; i risultati locali riguardano le working copy presenti.

Il 19 ottobre 2026 il flusso password è stato verificato anche su Laravel
10.50.3/Testbench 8/PHPUnit 10.5.63 e Laravel 12.69.1/Testbench 10/PHPUnit 11.5.55,
con PHP 8.4.23. Per queste due verifiche sono stati usati bootstrap isolati in
`/tmp`, framework e dipendenze già presenti nel workspace e sorgenti ufficiali
Testbench, senza caricare le applicazioni host o i loro database. Il vendor
condiviso standard rimane Laravel 11.51.0/Testbench 9/PHPUnit 11.5.55.

## LDAP opzionale

`LdapLoginTest` verifica la compatibilità del login locale, le associazioni esplicite,
CSRF, throttling, account sospesi e rollback della migrazione opzionale.
`LdapRecordDirectoryTest` usa DirectoryEmulator di LdapRecord con SQLite in memoria
per password, GUID e gruppi annidati. Richiede `directorytree/ldaprecord-laravel:^3.4`
e `ext-ldap`; viene saltato esplicitamente quando la dipendenza opzionale manca.
Copre anche LDAP semplice sulla porta 389 quando SSL e TLS sono esplicitamente
disabilitati. `LdapTestCommandTest` verifica il comando diagnostico, i prompt
e gli errori senza modifiche a utenti, associazioni o sessioni e con login spento.
Nessun test contatta Active Directory reale.

`LdapDirectoryImportTest` copre importazioni ripetute e parziali, identità
disabilitate/fuori gruppo, isolamento delle directory e conservazione di utenti,
ruoli e collegamenti. `LdapDirectoryPageTest` copre la pagina amministrativa,
ricerca, filtri, CSRF, controllo dei ruoli e associazioni manuali senza modificare
password o ruoli. Il lettore AD viene verificato anche con l'emulatore reale.
