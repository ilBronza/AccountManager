# Prompt per popolare le descrizioni delle route nei package

```text
Lavora sui package Laravel presenti in questo workspace. Leggi gli AGENTS.md
applicabili e rispetta le modifiche già presenti nei singoli repository.

In AccountManager è già implementata la lettura automatica dei file
config/route-descriptions.php dei package installati tramite Composer.
Il sync li abbina ai nomi esatti delle route registrate e ne mostra le
descrizioni nella tabella di gestione degli accessi.

Crea o completa config/route-descriptions.php in ogni package, popolandolo
con descrizioni sensate in italiano. Il formato è un array PHP piatto:

<?php
return [
    'clients.index' => 'Mostra l’elenco dei clienti.',
    'clients.create' => 'Mostra il modulo per creare un cliente.',
    'clients.store' => 'Salva un nuovo cliente.',
];

Per ogni package:
1. Esamina composer.json, service provider, file di routing, configurazioni
   e helper che registrano route CRUD. Ricostruisci i nomi finali considerando
   tutti i gruppi, i prefissi dei nomi e le registrazioni resource/apiResource.
2. Leggi le azioni dei controller e i trait necessari a capirne il comportamento.
   Non dedurre la funzione soltanto dal nome della route o dal verbo HTTP.
3. Descrivi ogni route nominata appartenente al package, incluse quelle di
   scrittura, API, AJAX, import/export e quelle registrate condizionalmente.
   Non includere route commentate o nomi inventati. Se non è possibile
   determinare un nome concreto, segnala il caso nel resoconto.
4. Rispetta i prefissi configurabili: costruisci le chiavi con config(...) e
   lo stesso valore predefinito e gli stessi separatori usati dal routing.
   Non fissare nella stringa il prefisso predefinito. Il file è valutato
   durante il sync, dopo il caricamento della configurazione dei package.
5. Scrivi frasi brevi e specifiche, comprensibili all’amministratore che deve
   assegnare gli accessi. Distingui apertura di moduli, salvataggio,
   eliminazione, ripristino, download e invio. Specifica oggetto ed effetti
   concreti verificati nel codice; evita descrizioni generiche come
   “Gestione dati” e affermazioni su autorizzazioni non dimostrate.
6. Mantieni le descrizioni esistenti corrette. Nei package senza route
   nominate concrete crea un file che restituisce [], con un commento
   che ne spiega il motivo. I package CRUD generici non giustificano
   l’invenzione dei nomi delle risorse dell’applicazione ospite.

Vincoli del formato:
- Chiavi: nomi completi esatti delle route, non URI e non nomi di controller.
- Valori: stringhe italiane non vuote, in testo semplice.
- Niente wildcard, array annidati o chiavi duplicate.
- Il file restituisce soltanto dati, senza registrare route, interrogare
  database o introdurre dipendenze. Sono ammesse letture di config per i prefissi.
- Descrizioni discordanti dello stesso nome in package diversi fanno fallire
  il sync: verifica proprietà delle route e collisioni tra i file creati.

Intervieni sui file delle descrizioni; l’infrastruttura di sync e la tabella
sono già implementate in AccountManager. Non cambiare route, controller,
permessi o provider per introdurre le descrizioni. Non eseguire migrazioni,
sync su database reali, commit o push.

Verifica sintassi PHP, corrispondenza dei nomi e copertura con gli strumenti
locali disponibili e gli AGENTS.md. Non avviare applicazioni collegate a dati
reali per ricostruire i nomi delle route.

Completa il lavoro sui package, poi riporta: file creati/aggiornati,
numero di descrizioni per package, package senza route, casi dinamici
non risolvibili, eventuali collisioni e verifiche eseguite.
```
