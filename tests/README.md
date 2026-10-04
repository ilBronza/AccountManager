# Test di AccountManager

La suite esegue richieste HTTP sulle route reali del pacchetto, con middleware,
controller e viste attivi. Ogni pagina disponibile deve rispondere `200` e
contenere HTML; un `500` fa fallire il test.

- `GetRoutesTest`: pagine GET, autenticazione, ruoli esclusi e route aggiunte
  dal registrar CRUD (`archived` e `deleted`, non abilitate dai controller).
  Un controllo confronta tutte le GET registrate dal pacchetto con quelle
  coperte dalla suite, per rilevare nuove route senza test.
- `SessionGetRoutesTest`: redirect GitHub, callback per utenti esistenti e nuovi,
  logout e duplicazione con verifica di ruoli e permessi copiati.
- `UsersIndexAccessTest`: accesso alla lista utenti per i ruoli autorizzati
  (`200`) e rifiuto degli altri (`403`).
- `HeartbeatTest`: i due test POST già presenti.

GitHub viene simulato tramite il provider Socialite: nessuna richiesta esterna.
I dati, inclusi utenti, userdata, media e access log, sono salvati in SQLite
in memoria e ricreati per ogni test. Non viene usato `RefreshDatabase`.
La connessione di test traduce soltanto `DESCRIBE` e `SHOW COLUMNS`, richiesti
da CRUD/FormField per costruire i form; le query sui dati restano reali.
Lo schema fixture riproduce le tabelle necessarie senza eseguire le migrazioni
storiche del pacchetto, che dipendono da tabelle dell'applicazione ospite.

## Esecuzione nel workspace dei pacchetti

Dalla cartella `pacchetti`, con il vendor condiviso di `.ibtest`:

```bash
python3 .ibtest/bin/ibtest.py AccountManager
python3 .ibtest/bin/ibtest.py AccountManager --filter GetRoutesTest
```

Serve anche Laravel Socialite 5 per le route GitHub. Se manca nel vendor
condiviso, seguire `.ibtest/README.md` per aggiungerlo e rigenerare l'autoload.

Se il runtime PHP-Wasm non è disponibile, dopo che il runner ha generato
`.ibtest/bootstrap_AccountManager.php` si può usare il PHP locale:

```bash
php .ibtest/vendor/phpunit/phpunit/phpunit \
  --bootstrap .ibtest/bootstrap_AccountManager.php \
  -c AccountManager/phpunit.xml --do-not-cache-result
```

Con un normale vendor dell'applicazione che contiene tutte le dipendenze del
pacchetto, dalla cartella `AccountManager`:

```bash
vendor/bin/phpunit
```
