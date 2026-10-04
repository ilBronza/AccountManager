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

La CI configura repository Composer di tipo path per i pacchetti IlBronza e
verifica Laravel 11/PHP 8.2 e Laravel 12/PHP 8.4. Le dipendenze di questi test
d'integrazione seguono i branch dei pacchetti sorella, come negli altri package
del workspace; i risultati locali riguardano le working copy presenti.
