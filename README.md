# BraniFrequentiWakeWorship

## Descrizione
Web application per la gestione e tracciamento dei brani suonati durante le sessioni di worship. Pubblicata su InfinityFree, utilizza PHP per la logica applicativa e MySQL come database relazionale.

## Tecnologie
- **Backend**: PHP
- **Database**: MySQL
- **Hosting**: InfinityFree (hosting gratuito con supporto PHP e MySQL)

## Struttura del Database

### Tabella `Brani`
- `Id` (INT, PRIMARY KEY, AUTO_INCREMENT): Identificatore univoco del brano.
- `Titolo` (VARCHAR(255)): Titolo del brano.
- `Tipologia` (ENUM('Lode', 'Adorazione')): Categoria del brano.

### Tabella `BraniSuonati`
- `Id` (INT, PRIMARY KEY, AUTO_INCREMENT): Identificatore univoco della registrazione.
- `IdBrano` (INT, FOREIGN KEY REFERENCES Brani(Id)): Riferimento al brano suonato.
- `BranoSuonatoIl` (DATE): Data in cui il brano è stato suonato (solo venerdì o domeniche).

## Funzionalità

### Landing Page
Visualizza l'elenco degli ultimi brani suonati nell'ultimo venerdì e nell'ultima domenica. Recupera i dati dalla tabella `BraniSuonati` filtrando per le date più recenti valide.

### Gestione Brani
Interfaccia per amministrare la tabella `Brani`:
- Aggiungere nuovi brani con titolo e tipologia.
- Modificare titoli e tipologie esistenti.
- Eliminare brani (con controllo di integrità referenziale per evitare rimozione se associati a registrazioni in `BraniSuonati`).

### Registrazione Frequenza
Permette di registrare l'esecuzione di un brano selezionato dalla lista, associandolo a una data valida (solo venerdì o domeniche). Inserisce record nella tabella `BraniSuonati`.

## Sicurezza e Validazione
- Autenticazione richiesta per accesso alle pagine di gestione e registrazione (implementare tramite sessioni PHP o sistema di login).
- Validazione input: titoli non vuoti, tipologie limitate, date controllate per giorni della settimana.
- Protezione SQL injection tramite prepared statements.

## Deploy
Caricare i file PHP e configurare il database MySQL su InfinityFree. Assicurare che le credenziali di connessione siano protette (file di configurazione separato, non committato).

## Uso
Accedere alla landing page per visualizzare gli ultimi brani. Utilizzare le sezioni dedicate per gestire brani e registrare esecuzioni.