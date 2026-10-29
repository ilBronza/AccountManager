# Accesso opzionale ad Active Directory

Il modulo aggiunge `GET/POST /login/ldap`, mantenendo il provider Eloquent e il
guard `web` dell'applicazione. Non modifica il login locale, GitHub, il recupero
password, le password salvate, i ruoli o la struttura della tabella utenti.
È disabilitato per impostazione predefinita e il package funziona anche senza
LdapRecord o estensione LDAP installati. Non viene creato alcun utente durante
il login e non vengono associate identità in base alla sola email.

## Installazione nell'applicazione ospite

La dipendenza è opzionale a runtime, richiesta solo per usare LDAP. Il ramo 3.x
di LdapRecord-Laravel supporta anche Laravel 9; controllare separatamente i
vincoli Composer dell'app e degli altri package prima di aggiornare il lock.

```sh
composer require directorytree/ldaprecord-laravel:^3.4
php artisan vendor:publish --tag=ldap-config
php artisan vendor:publish --tag=accountmanager-ldap-config
php artisan vendor:publish --tag=accountmanager-ldap-migrations
php artisan migrate --path=database/migrations/2026_10_27_000000_create_accountmanager_ldap_identities_table.php
```

L'estensione PHP `ldap` deve essere disponibile sia al web server sia alla CLI.
La migrazione è pubblicata esplicitamente e non fa parte delle migrazioni
automatiche di AccountManager. Crea esclusivamente
`accountmanager_ldap_identities`, sulla connessione del modello utente del
progetto, con chiavi univoche per `(connection, guid)` e `(connection, user_id)`.
Il rollback elimina solo queste associazioni; non elimina utenti o ruoli.

Configurare `config/ldap.php` con host, Base DN, account di lettura, password,
timeout e trasporto: LDAPS (porta 636), StartTLS (porta 389), oppure LDAP semplice
(porta 389, `use_ssl=false` e `use_tls=false`). Il package rispetta le opzioni
della connessione; non abilita automaticamente TLS. Non abilitare SSL e StartTLS
insieme. Con un trasporto cifrato la connessione deve verificare il certificato
del domain controller; configurare la CA aziendale
nel sistema/PHP LDAP oppure tramite `LDAP_OPT_X_TLS_CACERTFILE`. Il modulo
rifiuta bind anonimi e verifica certificati disattivata. Con LDAP semplice le
credenziali viaggiano in chiaro sulla connessione al domain controller.
Il sito deve usare HTTPS per proteggere le credenziali inserite nel browser.

In `config/accountmanager_ldap.php` scegliere:

- `connection`: nome stabile della connessione LdapRecord; fa parte dell'identità.
- `login_attribute`: `userprincipalname` (default), `samaccountname` oppure `mail`.
  L'utente deve inserire il valore esatto dell'attributo scelto. Per esempio, con
  `samaccountname` inserisce `mrossi`, con `userprincipalname` `mrossi@azienda.local`.
  I risultati ambigui vengono rifiutati.
- `required_group_dn`: facoltativo, DN del gruppo autorizzato; include i gruppi
  annidati. Non assegna né rimuove ruoli applicativi.
- `sync_name`: default `false`. Se abilitato aggiorna solo `users.name` dal
  display name AD al login; email, password, stato attivo e permessi restano locali.

## Verificare il package senza attivare il login

Il comando di diagnostica usa lo stesso adapter del login: ricerca esatta
dell'attributo configurato, stato AD attivo e gruppo richiesto (anche annidato).
Funziona con `ACCOUNTMANAGER_LDAP_ENABLED=false` e senza migrazione delle identità.

```sh
php artisan accountmanager:ldap-test mrossi
php artisan accountmanager:ldap-test mrossi --authenticate
```

Il primo comando controlla la ricerca con l'account di servizio. Il secondo
richiede la password dell'utente AD con input nascosto e la verifica sul dominio.
Omettendo il nome utente viene richiesto interattivamente. Non passare la password
come argomento: non esiste un'opzione per farlo. Nessun comando crea utenti,
associazioni, sessioni o modifiche alla configurazione. Un esito positivo verifica
AD, ma non implica che l'utente sia già autorizzato all'accesso locale: serve
ancora un collegamento esplicito a un utente applicativo attivo.

## Collegare gli utenti

### Rubrica completa e collegamento manuale

È possibile importare tutti gli account utente sotto il Base DN in una rubrica
separata. Sono inclusi gli account disabilitati e quelli fuori dal gruppo
ammesso; computer e contatti non sono importati. Le letture sono paginate.

Pubblicare il tag `accountmanager-ldap-migrations` ed eseguire anche
`2026_10_28_000001_create_accountmanager_ldap_directory_users_table.php`, quindi:

```sh
php artisan accountmanager:ldap-import --dry-run
php artisan accountmanager:ldap-import
```

L'importazione non crea utenti applicativi, non associa identità in base a nome
o email e non assegna ruoli. Aggiorna i dati AD per GUID e conserva i collegamenti
manuali esistenti. Le identità non rilevate in una successiva lettura vengono
segnalate, senza eliminare utenti o collegamenti. Una lettura LDAP fallita non
applica un'importazione parziale. Le due tabelle usano la connessione del modello
utente dell'applicazione ospite.

Con `accountmanager_ldap.directory_enabled=true`, la pagina
`/account-management/ldap-users` mostra per default le identità da collegare.
L'accesso è riservato ai ruoli `management_roles`, per default `superadmin`,
indipendentemente dal flag del login LDAP. Da ogni riga si sceglie un utente
locale attivo; il collegamento usa lo stesso meccanismo GUID della CLI. I ruoli
restano quelli del singolo utente locale e sono visibili nella pagina, con un
collegamento alla gestione utenti esistente. Non si copiano password o ruoli AD.
Una corrispondenza sbagliata va rimossa esplicitamente prima di sceglierne un'altra.
Anche abilitando altri ruoli di gestione, solo un superadmin può associare gli
account locali superadmin o l'utente con ID 1. I moduli richiedono CSRF e rifiutano
conflitti, identità di altre directory e identità assenti dall'ultima importazione.

### Collegamento tramite CLI

Con LDAP ancora disabilitato nell'interfaccia, l'amministratore associa ogni
utente locale a un account AD tramite CLI. Il comando interroga AD con l'account
di servizio e salva il GUID, non il nome di login o la password dell'utente.

```sh
php artisan ldap:test
php artisan accountmanager:ldap-link 42 mrossi@azienda.local
```

Il comando è idempotente per la stessa coppia e rifiuta associazioni in conflitto,
utenti locali mancanti/sospesi, utenti AD disabilitati o fuori dal gruppo ammesso.
Una modifica del nome di login AD non cambia il collegamento, perché resta il GUID.
Per rimuovere esplicitamente un collegamento della directory configurata:

```sh
php artisan accountmanager:ldap-link 42 --unlink
```

Non cambiare il significato della connessione configurata per riutilizzarne il
nome con un altro dominio. Per un'altra directory usare un nuovo nome e nuove
associazioni.

## Attivazione e comportamento

Impostare `ACCOUNTMANAGER_LDAP_ENABLED=true` e ricostruire la cache di
configurazione. Il template login del package include il pulsante. In una vista
già pubblicata nell'applicazione aggiungere senza ripubblicarla integralmente:

```blade
@includeIf('accountmanager::auth.ldap-link')
```

Il login verifica la password su AD, controlla l'utente locale, apre la sessione
Laravel e rigenera il suo ID. Nessuna password AD viene salvata/sincronizzata;
non viene creato un cookie “ricordami” dal login LDAP. CSRF e limiti per IP e
nome utente proteggono il nuovo endpoint. Un errore LDAP non produce un tentativo
automatico con la password locale. L'utente può usare il login standard esistente.

Le credenziali locali restano valide anche per gli utenti collegati: questa è
un'aggiunta, non un criterio per imporre AD. Una disabilitazione in AD blocca i
successivi login LDAP; non invalida automaticamente sessioni Laravel già aperte
né blocca il login locale. Per sospendere l'account nell'app usare anche il suo
stato `active`. L'eventuale revoca centralizzata delle sessioni è un requisito
separato.

Per nascondere e disabilitare il nuovo accesso impostare
`ACCOUNTMANAGER_LDAP_ENABLED=false` e aggiornare la cache di configurazione.
Non serve rimuovere la tabella o modificare gli utenti.

## Verifiche

`LdapLoginTest` copre HTTP, login locale anche con LDAP attivo, associazioni,
password/ruoli invariati, sospensioni, CSRF, throttling, errori e rollback della
sola migrazione opzionale. Funziona anche senza libreria LDAP installata.
`LdapRecordDirectoryTest` usa la libreria reale con DirectoryEmulator e SQLite
in memoria: bind con password corretta/errata, GUID, rename, gruppi annidati,
account disabilitati, trasporti configurati e configurazioni non valide.
`LdapTestCommandTest` verifica la diagnostica senza login, i prompt, il rifiuto
di password vuote/errate e la riservatezza degli errori. Solo i test con emulatore
richiedono la dipendenza di sviluppo e `ext-ldap`, altrimenti vengono saltati
esplicitamente.
`LdapDirectoryImportTest` verifica l'importazione completa, l'aggiornamento per
GUID, la conservazione dei collegamenti e l'atomicità in caso di errori.
`LdapDirectoryPageTest` copre ricerca e filtri, autorizzazioni, CSRF, associazioni
manuali, conflitti, dati locali invariati e isolamento fra directory.

L'emulatore non certifica rete, trust TLS, lockout/scadenza password e policy del
dominio reale: completarli con l'ambiente AD del cliente prima dell'attivazione.
