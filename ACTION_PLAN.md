# Piano di Sviluppo: BraniFrequentiWakeWorship

## Introduzione
Questo piano di sviluppo dettaglia l'implementazione completa della web application "BraniFrequentiWakeWorship" basata sulla documentazione fornita. L'obiettivo è creare un'applicazione PHP/MySQL funzionale, sicura e user-friendly per la gestione e il tracciamento dei brani suonati durante le sessioni di worship. Il progetto sarà sviluppato seguendo un approccio database-first, con enfasi su sicurezza, validazione e usabilità.

## Tecnologie Scelte
Per colmare le lacune tecnologiche descritte nel README, proponiamo le seguenti scelte dettagliate:

### Backend
- **PHP**: Versione 7.4+ (supportata da InfinityFree). Utilizzeremo puro PHP senza framework pesanti per mantenere la semplicità e la compatibilità con l'hosting gratuito.
- **Database**: MySQL (già specificato). Utilizzeremo mysqli per le connessioni e prepared statements per prevenire SQL injection.
- **Autenticazione**: Sessioni PHP native con password hashing (password_hash() e password_verify()).

### Frontend
- **HTML5/CSS3**: Struttura base.
- **TailwindCSS**: Libreria CSS utility-first per styling moderno, responsive e veloce. Scegliamo TailwindCSS perché è leggero, non richiede JavaScript per il funzionamento base e permette un design pulito senza scrivere CSS custom. Verrà incluso via CDN per semplicità su InfinityFree.
- **JavaScript**: Vanilla JS per interazioni basilari (es. validazione client-side, AJAX per form dinamici). Eviteremo jQuery per ridurre dipendenze.
- **Template Engine**: Puro PHP con include() per semplicità, senza framework come Twig (troppo pesante per questo progetto).

### Sicurezza e Validazione
- **Validazione Input**: Server-side con PHP (filter_var, strlen, etc.). Client-side con JS per UX migliorata.
- **Protezione**: Prepared statements mysqli, escaping output con htmlspecialchars(), CSRF tokens per form.
- **Autenticazione**: Login semplice con username/password hardcoded o tabella utenti (se necessario espandere).

### Altri Strumenti
- **Version Control**: Git (assunto già in uso).
- **Testing**: Test manuali e unit test basilari con PHPUnit (se possibile installare su InfinityFree, altrimenti test locali).
- **Deployment**: FTP per upload su InfinityFree. Configurazione database via phpMyAdmin fornito dall'hosting.

## Struttura del Progetto
Organizzeremo il codice in cartelle logiche per mantenere ordine. Poiché tutto verrà pubblicato nella cartella /htdocs di InfinityFree, la struttura è progettata per essere copiata direttamente lì, con i file pubblici nella root e gli includes in una sottocartella protetta:

```
BraniFrequentiWakeWorship/
├── README.md
├── DBScripts/
│   └── 001 - create_tables.sql
├── index.php  # Landing page
├── login.php  # Pagina login
├── logout.php  # Logout
├── manage_brani.php  # Gestione brani
├── register_frequency.php  # Registrazione frequenza
├── css/
│   └── styles.css  # Custom CSS (se necessario oltre Tailwind)
├── js/
│   └── scripts.js  # JavaScript client-side
├── includes/  # File inclusi (non pubblici)
│   ├── db.php  # Configurazione database
│   ├── auth.php  # Funzioni autenticazione
│   ├── functions.php  # Funzioni utility
│   └── header.php
│   └── footer.php  # Template header/footer
└── .gitignore  # Escludere config locali, logs, etc.
```

- **Configurazione Database**: File `includes/db.php` con credenziali (da non committare; usare variabili d'ambiente o file separato su server).
- **Autenticazione**: Sessioni PHP; proteggere pagine admin con check in ogni file.

## Fasi di Sviluppo
Dividiamo l'implementazione in fasi iterative, partendo dal database e arrivando al deploy.

### Fase 1: Setup Ambiente e Database (1-2 giorni)
- **Obiettivo**: Preparare l'ambiente di sviluppo e il database.
- **Attività**:
  - Creare la struttura cartelle come sopra.
  - Eseguire lo script SQL `DBScripts/001 - create_tables.sql` su un database MySQL locale (usare XAMPP o WAMP per test).
  - Creare `includes/db.php` con connessione mysqli:
    ```php
    <?php
    $host = 'localhost'; // Su InfinityFree: fornito dall'hosting
    $db = 'nome_db';
    $user = 'user';
    $pass = 'password';
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Errore di connessione: " . $conn->connect_error);
    }
    ?>
    ```
  - Testare connessione con un semplice script PHP.
- **Milestone**: Database creato e connessione funzionante.

### Fase 2: Autenticazione e Sicurezza Base (2-3 giorni)
- **Obiettivo**: Implementare login/logout sicuro.
- **Attività**:
  - Creare `includes/auth.php` con funzioni: `login($username, $password)`, `logout()`, `is_logged_in()`.
  - Usare password_hash() per hashing; per semplicità, username/password hardcoded (es. admin/admin) o tabella `Utenti` (aggiungere allo schema SQL se necessario).
  - Creare `login.php`: Form HTML con TailwindCSS per styling (es. input fields, button).
  - Creare `logout.php`: Distrugge sessione e reindirizza.
  - Aggiungere check autenticazione in pagine protette.
- **Sicurezza**: Validare input, usare session_regenerate_id() dopo login.
- **Milestone**: Login funzionante; accesso negato senza autenticazione.

### Fase 3: Landing Page (2 giorni)
- **Obiettivo**: Pagina principale con elenco ultimi brani suonati.
- **Attività**:
  - Creare `index.php`: Query SQL per recuperare ultimi brani da `BraniSuonati` filtrati per ultimo venerdì e domenica.
  - Usare mysqli prepared statements.
  - Template HTML con TailwindCSS: Header, lista brani (tabella o cards), footer.
  - Includere `includes/header.php` e `footer.php` per navigazione (link a gestione e registrazione).
- **Validazione**: Nessuna input; solo display.
- **Milestone**: Pagina visualizza correttamente dati recenti.

### Fase 4: Gestione Brani (3-4 giorni)
- **Obiettivo**: CRUD per tabella `Brani`.
- **Attività**:
  - Creare `manage_brani.php`: Lista brani con pulsanti Aggiungi/Modifica/Elimina.
  - Form per aggiungere/modificare: Campi Titolo (text), Tipologia (select: Lode/Adorazione).
  - Validazione: Titolo non vuoto, tipologia valida.
  - Eliminazione: Check foreign key (se associato a `BraniSuonati`, impedire o avvisare).
  - Usare AJAX/JS per form dinamici senza reload pagina.
  - Styling con TailwindCSS: Tabelle responsive, modali per form.
- **Sicurezza**: CSRF token nei form.
- **Milestone**: Aggiungere, modificare, eliminare brani funziona con validazione.

### Fase 5: Registrazione Frequenza (3 giorni)
- **Obiettivo**: Registrare esecuzione brano.
- **Attività**:
  - Creare `register_frequency.php`: Select per scegliere brano da `Brani`, input data (solo venerdì/domenica).
  - Validazione: Data valida (check giorno settimana con DateTime PHP), brano esistente.
  - Inserimento in `BraniSuonati` via mysqli.
  - Feedback utente (successo/errore) con TailwindCSS alerts.
- **Sicurezza**: Prepared statements, validazione server-side.
- **Milestone**: Registrazione funziona; aggiorna landing page.

### Fase 6: Testing e Ottimizzazioni (2-3 giorni)
- **Obiettivo**: Assicurare funzionalità e sicurezza.
- **Attività**:
  - Test manuali: Login, CRUD, registrazione, validazione errori.
  - Unit test con PHPUnit per funzioni PHP (se installabile).
  - Ottimizzazioni: Minificare CSS/JS, caching query se necessario.
  - Responsive design: Test su mobile con TailwindCSS.
- **Milestone**: Applicazione stabile senza errori.

### Fase 7: Deploy e Configurazione Finale (1-2 giorni)
- **Obiettivo**: Pubblicare su InfinityFree.
- **Attività**:
  - Creare database su InfinityFree via phpMyAdmin; eseguire script SQL.
  - Upload file via FTP (escludere `includes/db.php` con credenziali reali).
  - Configurare `includes/db.php` con credenziali InfinityFree.
  - Testare su server live.
- **Milestone**: Applicazione live e funzionante.

## Considerazioni Finali
- **Tempo Totale**: Circa 14-20 giorni per un singolo sviluppatore.
- **Budget**: Gratuito (PHP/MySQL su InfinityFree, TailwindCSS CDN).
- **Scalabilità**: Se cresce, considerare migrazione a framework come Laravel.
- **Manutenzione**: Monitorare logs PHP per errori; backup database regolare.
- **Rischi**: Limitazioni InfinityFree (es. no cron jobs); se necessario, migrare a hosting pagato.

Questo piano copre tutte le feature descritte, colmando lacune con scelte pratiche e dettagliate. Procedi con la Fase 1 per iniziare.