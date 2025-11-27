# BraniFrequentiWakeWorship

## Descrizione
Web application per la gestione e tracciamento dei brani suonati durante le sessioni di worship. Pubblicata su InfinityFree, utilizza PHP per la logica applicativa e MySQL come database relazionale.

## Tecnologie
- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: HTML, CSS (Tailwind CSS), JavaScript
- **Hosting**: InfinityFree (hosting gratuito con supporto PHP e MySQL)

## Approccio di Sviluppo
- **Database-First**: Il database viene progettato e creato prima dello sviluppo del codice applicativo. Gli script SQL per la creazione delle tabelle sono disponibili nella cartella `DBScripts`.

## Struttura del Database

### Tabella `Brani`
- `Id` (INT, PRIMARY KEY, AUTO_INCREMENT): Identificatore univoco del brano.
- `Titolo` (VARCHAR(255)): Titolo del brano.
- `Tipologia` (ENUM('Lode', 'Adorazione')): Categoria del brano.

### Tabella `BraniSuonati`
- `Id` (INT, PRIMARY KEY, AUTO_INCREMENT): Identificatore univoco della registrazione.
- `IdBrano` (INT, FOREIGN KEY REFERENCES Brani(Id)): Riferimento al brano suonato.
- `BranoSuonatoIl` (DATE): Data in cui il brano è stato suonato (solo venerdì o domeniche).

### Tabella `Utenti`
- `Id` (INT, PRIMARY KEY, AUTO_INCREMENT): Identificatore univoco dell'utente.
- `Username` (VARCHAR(255), UNIQUE): Nome utente.
- `Password` (VARCHAR(255)): Password hashata (non implementata attualmente, autenticazione hardcoded).

## Funzionalità Implementate

### Landing Page (`index.php`)
Visualizza l'elenco degli ultimi 10 brani suonati negli ultimi venerdì e domeniche. Recupera i dati dalla tabella `BraniSuonati` filtrando per le date più recenti valide, ordinati per data discendente.

### Gestione Brani (`manage_brani.php`)
Interfaccia per amministrare la tabella `Brani`:
- Aggiungere nuovi brani con titolo e tipologia.
- Modificare titoli e tipologie esistenti.
- Eliminare brani (con controllo di integrità referenziale per evitare rimozione se associati a registrazioni in `BraniSuonati`).
- Paginazione per gestire grandi quantità di dati (10 brani per pagina).
- Ordinamento alfabetico per titolo.

### Registrazione Frequenza (`register_frequency.php`)
Permette di registrare l'esecuzione di un brano selezionato dalla lista, associandolo a una data valida (solo venerdì o domeniche). Inserisce record nella tabella `BraniSuonati`.

### Autenticazione (`login.php`, `logout.php`)
Sistema di login semplice con credenziali hardcoded (username: admin, password: admin) per proteggere le pagine di gestione e registrazione. Utilizza sessioni PHP per mantenere lo stato di login.

### Sicurezza e Validazione
- Autenticazione richiesta per accesso alle pagine di gestione e registrazione.
- Validazione input: titoli non vuoti, tipologie limitate, date controllate per giorni della settimana.
- Protezione SQL injection tramite prepared statements.
- Protezione CSRF (Cross-Site Request Forgery) tramite token.
- Sanitizzazione input per prevenire XSS.
- Rigenerazione ID sessione al login per prevenire session fixation.

### Interfaccia Utente
- Design responsive con Tailwind CSS.
- Navigazione intuitiva con header comune.
- Messaggi di feedback per azioni (successo/errore).
- Icone SVG per migliorare l'usabilità.

## Deploy
- Script PowerShell (`build.ps1`) per creare un archivio `dist.zip` contenente tutti i file necessari per il deploy.
- Caricare i file PHP e configurare il database MySQL su InfinityFree.
- Assicurare che le credenziali di connessione siano protette (file di configurazione separato, non committato).

## Uso
- Accedere alla landing page per visualizzare gli ultimi brani suonati.
- Effettuare il login per accedere alle funzionalità di gestione.
- Utilizzare le sezioni dedicate per gestire brani e registrare esecuzioni.