# Accesso alle route

La funzione è disattivata per impostazione predefinita. Si attiva impostando
`routeAccess.enabled` a `true` nel file PHP `config/accountmanager.php`
dell’applicazione. L’opzione è gestita esclusivamente dalla configurazione PHP.
Quando è spenta, il middleware non interroga il catalogo, la pagina di gestione
restituisce 404 e il collegamento nel menu non viene mostrato.

## Installazione e utilizzo

1. Eseguire `php artisan migrate`: crea `accountmanager_routes` e le pivot
   `accountmanager_route_role` / `accountmanager_route_permission`. Le tabelle
   Spatie dei ruoli e dei permessi devono già esistere; sono rispettati i modelli
   e le tabelle configurati in `permission.models`.
2. Copiare l’intera sezione `routeAccess` nella configurazione pubblicata
   `config/accountmanager.php` dell’applicazione e impostare `enabled` a `true`.
   Se si usa la cache di configurazione, rigenerarla.
3. Aprire `/account-management/routes`: il catalogo viene sincronizzato
   automaticamente a ogni apertura della tabella, anche nei caricamenti AJAX.
4. Selezionare i rami o le singole route, scegliere i ruoli e/o i permessi,
   quindi usare **Associa alle selezionate**. **Rimuovi dalle selezionate**
   elimina soltanto i collegamenti ai ruoli/permessi scelti.

Il collegamento **Accesso alle route** compare nel menu Impostazioni → Account
quando il menu AccountManager è abilitato (`accountmanager.enabled`) e l’utente
può gestire le route. La tabella usa controller, trait e parametri di `ilbronza/crud` e i
campi di `ilbronza/datatables`, con le normali impostazioni di paginazione,
ricerca, ordinamento e salvataggio dello stato del progetto.

## Gestione dalla pagina corrente

Il pulsante **Accesso alla pagina**, accanto al pannello utente nella barra destra,
apre una modale UIkit con i ruoli e i permessi già associati alla route corrente.
È disponibile ai ruoli `superadmin`, `superadministrator` (compatibilità storica),
`administrator` e `routesManager`, quando `routeAccess.enabled` è attivo, anche
se il menu centrale AccountManager è disabilitato. La modale è inserita dal package
nel layout `uikittemplate::app`, conservando le altre viste aggiuntive del progetto.

I ruoli abilitati alla gestione sono configurabili in `routeAccess.managementRoles`;
sono ammessi anche i ruoli in `routeAccess.bypassRoles`. La migration
`2026_11_13_000003_create_routes_manager_role.php`, caricata automaticamente dal
package, crea `routesManager` per il guard `web`. È idempotente e non assegna il
ruolo a nessun utente; il rollback conserva il ruolo e le sue assegnazioni.
Eseguire `php artisan migrate` nelle applicazioni che usano il package.

`routesManager` abilita la gestione del catalogo e delle associazioni, comprese
le modifiche alla descrizione e l’anteprima. Non è un ruolo di bypass e deve
avere le normali associazioni per accedere alle altre pagine del gestionale.
Gli endpoint di gestione rimangono raggiungibili ai gestori anche senza
esclusioni dal controllo generale.

La modale carica il catalogo all’apertura, offre una ricerca per ruoli e permessi
e salva senza cambiare pagina. **Salva** sostituisce entrambe le selezioni della
singola route; deselezionare tutto revoca le associazioni. Le modifiche valgono
per tutti gli utenti e tutti i record di quella route. Per `create` ed `edit`
valgono anche per il salvataggio corrispondente, secondo le regole sotto.
Le altre route e le assegnazioni dei ruoli agli utenti non vengono modificate.

I ruoli di bypass (`administrator`, `superadmin` e il nome storico
`superadministrator` per impostazione predefinita) hanno già accesso implicito
a ogni route: sono esclusi dalle selezioni, dalle associazioni mostrate e dai
ruoli di sistema visualizzati, sia nella modale sia nel catalogo. L’esclusione
segue `routeAccess.bypassRoles`; `routesManager` resta associabile. Anche gli
endpoint rifiutano associazioni manuali ai ruoli di bypass. Eventuali collegamenti
storici restano conservati e non vengono modificati dal salvataggio della modale.

Le pagine senza nome e quelle escluse dal controllo mostrano una spiegazione
senza modulo di modifica. La modale contiene anche il collegamento al catalogo
completo. Caricamento e salvataggio richiedono un account attivo autorizzato;
il salvataggio è protetto da CSRF e valida tutte le selezioni prima di modificare
le pivot nella stessa transazione.

### Route sorelle e imparentate

La scheda **Route collegate** della modale contiene la stessa DataTable CRUD
del catalogo globale, con albero, ricerca, paginazione, descrizioni e associazioni
multiple. Viene caricata quando si apre la scheda, senza lasciare la pagina.
La route corrente è evidenziata con l’etichetta **Pagina corrente**.

Il prefisso iniziale è quello del nome Laravel senza l’ultimo segmento:
`clients.edit` mostra `clients.*`, incluse le route annidate come
`clients.notes.index`. Dal selettore si possono scegliere i prefissi superiori
del nome oppure un prefisso del percorso URL della pagina. Il confronto dei nomi
rispetta il punto separatore, quello degli URI lo slash: `clients` non include
`clients-other`. Sono incluse solo route con lo stesso dominio registrato.
Per una home priva di prefissi viene mostrata soltanto la route corrente.

Si selezionano le route o i rami e si usano **Associa alle selezionate** o
**Rimuovi dalle selezionate**. Il salvataggio resta nella modale e nel prefisso
scelto, senza modificare le altre route. Anche il server verifica l’appartenenza
delle selezioni al prefisso. Le esclusioni, i ruoli di bypass e l’ereditarietà
di `store`/`update` conservano le stesse regole del catalogo globale.
Lo stato della tabella è distinto per pagina e prefisso.

Quando la tabella aggiorna le associazioni, la scheda della pagina corrente
viene ricaricata se non contiene modifiche non salvate. In caso contrario le
selezioni dell’operatore restano visibili con un messaggio che segnala
l’aggiornamento. Salvando la pagina corrente si aggiorna anche la tabella
alla successiva apertura della relativa scheda.

## Regole di accesso

Con la funzione attiva, **nessuna associazione significa accesso negato**.
Questo vale anche per route nuove non sincronizzate, route senza nome e record
del catalogo segnati come non più registrati.

Per gli altri utenti basta **uno** dei ruoli **oppure uno** dei permessi
associati alla route. Sono validi i permessi diretti e quelli ereditati tramite
un ruolo. I controlli usano i modelli Spatie, distinguendo le identità dei ruoli
e dei permessi anche quando lo stesso nome esiste su guard diversi.

Le route `*.store` ereditano ruoli e permessi dalla corrispondente `*.create`,
e le route `*.update` dalla corrispondente `*.edit`, quando entrambe risultano
registrate nel catalogo con lo stesso prefisso del nome. Il middleware legge
le associazioni della route del form a ogni richiesta: aggiunte e revoche
hanno effetto immediato, senza copiare collegamenti nel database. Le eventuali
associazioni proprie della route nascosta restano salvate ma non si applicano
finché esiste la corrispondente route del form. Un form senza associazioni
nega quindi anche il salvataggio. Se manca il form, `store`/`update` usano le
proprie associazioni e restano visibili nella tabella.

I ruoli in `accountmanager.routeAccess.bypassRoles` passano sempre questo
controllo: i valori predefiniti sono `superadmin` (nome storico del pacchetto),
`superadministrator` e `administrator`.

Il middleware `accountmanager.routeAccess` è registrato automaticamente nei
gruppi `web` e `api`, dopo l’autenticazione. L’elenco è configurabile con
`routeAccess.middlewareGroups`; per route fuori da questi gruppi applicare
l’alias esplicitamente. Il controllo del pacchetto `accountmanager.roles`, quando
la funzione è attiva, usa le nuove associazioni; quando è spenta torna alle
regole preesistenti `routeRoles` / `defaultRoles`.
Gli altri vincoli dell’applicazione (autenticazione, account attivo, policy e
ulteriori middleware) continuano a essere applicati.

### Login e altre route pubbliche

`routeAccess.exceptRoutes` contiene pattern di nomi delle route e
`routeAccess.exceptUris` contiene pattern degli URI registrati, senza slash
iniziale. Supportano `*`. Le esclusioni predefinite comprendono login locale,
GitHub/LDAP, recupero password e logout, così è possibile autenticarsi anche
con il blocco predefinito attivo. Aggiungere qui eventuali endpoint di
autenticazione personalizzati, pagine pubbliche o webhook che devono restare
accessibili. Un’esclusione evita solo questo controllo e conserva gli altri
middleware della route.

Le route escluse non vengono mostrate nella tabella, neppure nei dati AJAX,
e non generano rami vuoti nell’albero. Restano nel catalogo sincronizzato con
le associazioni già salvate, disponibili se si rimuove l’esclusione. Una route
senza nome resta visibile, se non esclusa, ma non configurabile: assegnarle un
nome Laravel e sincronizzare prima di associarla a ruoli o permessi.

## Albero e sincronizzazione

L’albero raggruppa prima per dominio, poi per segmenti dell’URI:
`clients` → `{client}` → `edit`. Vengono creati nodi di raggruppamento anche se
non esiste una route esatta per quel percorso. Le foglie mostrano nome e metodi
HTTP: `GET clients.index` e `POST clients.store` restano distinte anche quando
condividono lo stesso URI. I parametri, anche opzionali, restano nella loro forma
registrata (`{client}`, `{client?}`).

La colonna gerarchica usa il campo condiviso `ibDtV2.rowParentId`, al posto della
colonna di posizione. Le righe di presentazione `RouteTreeRow` espongono il getter
`row_parent_id`, normalizzato nel JSON `{"value": ID_PADRE, "row_opened": BOOLEAN}`.
I domini sono radici aperte per default; i rami URI sono inizialmente chiusi.
Il chevron singolo apre/chiude il nodo, conservando le aperture dei discendenti;
quello doppio apre/chiude l'intero ramo. I tooltip indicano i figli diretti e totali.
La paginazione conta i domini e mantiene ogni ramo con i propri discendenti nella
stessa pagina; ricerca, ordinamento tra fratelli e aperture durante i reload sono
gestiti dal campo DataTables.

All’apertura il catalogo segue i segmenti dell’URI in ordine naturale.
Ordinando una colonna, il campo condiviso mostra un elenco delle sole route con
metodo HTTP e percorso completo. **Torna all’albero** ripristina i gruppi e le
aperture precedenti. Le route registrate con un controller o un metodo mancante
mostrano **Azione non disponibile** accanto al nome.

La gerarchia rimane calcolata da dominio e URI: il model persistito `Route` e la
tabella `accountmanager_routes` non hanno un `parent_id`. Il riferimento viene
aggiunto solo alle righe di presentazione e punta agli ID virtuali `branch-...`
o `route-...` già usati nella tabella. Non servono migrazioni o modifiche alla
sincronizzazione del catalogo. Il browser riceve tutti i nodi necessari via AJAX;
la tabella non usa `serverSide: true`.

Il bundle dell'applicazione deve importare l'entrypoint condiviso aggiornato
`ilBronza.datatables.js`, che include `datatables.vendor.rowHierarchy.js` e gli
stili del pacchetto. Se l'app usa asset copiati in `resources`, eseguire
`php artisan vendor:publish --tag=datatables.assets --force` e ricompilare il
bundle con lo script NPM del progetto. Se importa direttamente l'entrypoint dal
pacchetto collegato in `vendor`, basta ricompilare il bundle. Non occorre aggiungere
script o stili gerarchici specifici di AccountManager.

La tabella nasconde le route `*.store` quando esiste la corrispondente
`*.create`, e le route `*.update` quando esiste la corrispondente `*.edit`.
Il prefisso del nome deve coincidere esattamente e la route del form deve
essere attualmente registrata. Per esempio, `notesmanagernotetypes.edit`
nasconde `notesmanagernotetypes.update`, mentre `suckbanana.store` resta
visibile se manca `suckbanana.create`. Le route nascoste usano nel middleware
i ruoli e i permessi del rispettivo form, come descritto nelle regole di accesso.

Selezionare un ramo seleziona tutte le route configurabili discendenti, anche
nelle altre pagine della tabella o nascoste dalla ricerca. Una selezione parziale
è indicata dal checkbox intermedio. Il salvataggio agisce sulle singole route selezionate:
non introduce ereditarietà automatica per le route aggiunte in futuro.

La sincronizzazione usa il nome come identità stabile per le route nominate,
aggiorna URI/dominio/metodi e conserva i collegamenti. Quelle rimosse sono
segnate `is_registered=false` e non compaiono nell’albero; se tornano con lo
stesso nome recuperano i collegamenti precedenti. Le route rinominate sono
nuove identità e richiedono nuove associazioni. Nomi duplicati vengono rifiutati
prima di modificare il catalogo. A ogni apertura della tabella il catalogo viene
aggiornato prima di leggere i dati, conservando le associazioni già salvate.
La sincronizzazione è contenuta in `RouteSyncHelper::sync()`, chiamato dal
normale hook `beforeIndex()` del controller CRUD a ogni accesso a `index`.
Se l’applicazione usa `route:cache`, la sincronizzazione legge le route attive
nella cache: rigenerarla dopo le modifiche alle route.

## Ruoli previsti dal sistema

Eseguire `php artisan migrate` per aggiungere la colonna JSON nullable
`system_roles` al catalogo esistente. Il sincronizzatore la aggiorna a ogni
apertura della tabella e la mostra nella colonna **Ruoli previsti dal sistema**,
accanto alle associazioni gestite manualmente.

I nomi vengono letti dai middleware Spatie `role:...` (anche con guard esplicito)
e dai middleware dei package derivati da
`CRUDBasePackageMiddlewareRolesPermissions`, riutilizzando il loro risolutore
di `routeRoles` / `defaultRoles` e gli eventuali override. Vengono risolti alias,
gruppi e middleware dei controller, rispettando le esclusioni
`withoutMiddleware()`. Per le route di gestione sono mostrati i ruoli
configurati in `routeAccess.managementRoles` e `routeAccess.bypassRoles`. La lettura funziona
anche con le route in cache e non esegue i controlli di autorizzazione.

La colonna è informativa: non crea ruoli né associazioni e non cambia le regole
di accesso. Elenca senza duplicati i nomi rilevati; con più middleware non
rappresenta una regola OR complessiva, né include policy, permessi o controlli
personalizzati esterni ai middleware supportati. Se non vengono rilevati ruoli,
la cella resta vuota, anche sui rami dell’albero. I nomi sono visualizzati con
escaping HTML.

## Nomi tradotti delle route

La colonna **Nome tradotto**, accanto al nome tecnico, usa le traduzioni già
distribuite nei file `resources/lang/{locale}/routes.php` dei package.
Riutilizza `RouteTitleTranslator` di UikitTemplate per individuare il namespace
tramite `routeTranslationPrefix` o `ibPackage` della singola route e per cercare
sia le chiavi con i punti sia quelle storiche con i primi due segmenti concatenati.
Le route dell’applicazione prive di questi metadati usano il gruppo `routes`
dell’applicazione.

Il testo viene risolto durante il rendering nella lingua corrente, con il
normale fallback Laravel e con escaping HTML. Non viene salvato nel database e
non richiede un’ulteriore migrazione. Se manca una traduzione testuale, la cella
resta vuota; anche rami e route senza nome hanno la cella vuota. Eventuali
segnaposto nei titoli restano tali, perché il catalogo non rappresenta uno
specifico record della pagina.

## Descrizioni delle route

Eseguire `php artisan migrate` anche nelle applicazioni che hanno già il catalogo:
la migrazione aggiuntiva introduce la colonna nullable `description` senza
modificare le associazioni esistenti. La tabella mostra **Descrizione** accanto
al nome tecnico della route. Gli utenti autorizzati a gestire il catalogo possono
modificarla direttamente nella cella, usando il normale editor DataTables e il
trigger di salvataggio configurato (per impostazione predefinita, Invio).
Il testo viene visualizzato con escaping HTML; il salvataggio usa il controller,
il trait editor e il fieldset di CRUD e consente di modificare solo `description`.

Ogni package può distribuire un file opzionale `config/route-descriptions.php`:

```php
<?php

$prefix = (string) config('nomepackage.routePrefix', 'nomepackage');

return [
    $prefix.'clients.index' => 'Mostra l’elenco dei clienti.',
    $prefix.'clients.create' => 'Mostra il modulo per creare un cliente.',
    $prefix.'clients.store' => 'Salva un nuovo cliente.',
];
```

Il file deve restituire un array piatto `nome completo route => descrizione`:
chiavi e valori devono essere stringhe non vuote. Le chiavi coincidono esattamente
con i nomi finali registrati da Laravel, comprese concatenazioni e separatori del
prefisso configurabile. Non sono supportati wildcard o array annidati. Usare
descrizioni italiane comprensibili a chi gestisce gli accessi. Un package privo
di route concrete può omettere il file oppure restituire un array vuoto.

`RouteDescriptions` trova i file tramite i percorsi dei package installati di
Composer, compresi i repository `path` con symlink e le directory vendor
personalizzate. Non occorre modificare i service provider dei package o
pubblicarne i file. È incluso anche il file di AccountManager. I file vengono
letti una sola volta per ogni sincronizzazione, prima di modificare il catalogo.

Il sync importa una descrizione solo se il campo nel catalogo è vuoto (`null`,
stringa vuota o soli spazi). Ogni descrizione già popolata viene conservata,
sia se inserita dall’utente sia se importata in precedenza. Per i campi vuoti
la precedenza, dalla più alta, è:

1. Override dell’applicazione in `config/route-descriptions.php`, con lo stesso
   formato. Il sync legge l’array dalla configurazione Laravel: le chiavi
   contenenti punti restano nomi completi di route.
2. Metadato della route: `metadata(['description' => '...'])` su Laravel 13,
   oppure la chiave `description` nell’array dell’action su Laravel 9 e successivi.
   Se sono presenti entrambi, prevale il metadato nativo.
3. Descrizione nel file del package.

Senza descrizione il valore resta `null`. Le route senza nome possono avere una
descrizione sull’action, ma non possono essere abbinate per nome ai file. Le voci
dei file relative a route non registrate non creano record nel catalogo.
File non validi o descrizioni diverse dello stesso nome in due package
interrompono il sync prima delle scritture, indicando il file e il nome coinvolti;
duplicati identici sono ammessi.

Le modifiche o rimozioni dei testi nelle fonti non sovrascrivono le descrizioni
già presenti. Svuotando una descrizione dalla tabella, al sync successivo può
essere importata nuovamente dalla prima fonte disponibile; se manca, resta
`null`. Restano invariati ID e collegamenti a ruoli e permessi. Con `config:cache`, rigenerare
la cache dopo le modifiche al file dell’applicazione o ai prefissi configurati.
Con `route:cache`, rigenerare la cache dopo modifiche ai metadati sulle route.
Per sole modifiche al testo nei file dei package basta il sync successivo.

Per popolare i file negli altri package, usare il
[prompt per le descrizioni](prompt-route-descriptions.md).

## Anteprima della pagina

Il pulsante **Anteprima** apre la pagina nell’iframe della DataTable usando la
sessione dell’amministratore. Per esempio, su `clients/{client}/edit` viene
aperto il modulo di modifica di un cliente presente nel database. I middleware
e le policy della pagina si applicano anche nell’iframe.

Sul dominio corrente il pulsante è disponibile per le viste GET di Laravel,
le pagine CRUD di elenco, dettaglio, creazione e modifica, le home, le dashboard
e le azioni con tipo di ritorno `View`. Al clic viene inviato via POST
l’ID della route nel catalogo. Il server ricava i parametri dai binding Eloquent,
dagli alias dei modelli o dalle convenzioni CRUD del progetto, quindi apre l’URL
con `iframed=true`. Per i binding annidati usa le relazioni tra i record.
Quando mancano dati utilizzabili, l’iframe indica per quale route manca l’esempio.

## Verifiche

`RouteAccessTest` copre accesso negato predefinito, bypass amministrativi,
ruoli/permessi diretti ed ereditati, isolamento tra guard, autenticazione API,
esclusioni pubbliche, gruppi di URL e domini, sincronizzazione automatica e ripetuta,
modifiche multiple atomiche e pagina/risposta AJAX DataTables. `MigrationsTest`
verifica installazione, rollback e reinstallazione con utenti e ruoli esistenti.
`RouteDescriptionsTest` verifica scoperta tramite Composer e repository `path`,
precedenza delle fonti sui campi vuoti, conservazione delle descrizioni popolate,
modifica dalla tabella, validazione e accesso amministrativo, route in cache, file
non validi, conflitti, descrizioni di AccountManager, escaping HTML e migrazione
aggiuntiva con conservazione delle associazioni.
`RouteNameTranslationsTest` verifica traduzioni dei package e dell’applicazione,
namespace e chiavi storiche, lingua corrente e fallback, route in cache, testi
mancanti, route senza nome e rendering con escaping HTML.
