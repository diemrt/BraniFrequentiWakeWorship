// Vanilla JS for interactions

function deleteScaletta(date) {
    if (confirm('Sei sicuro di voler eliminare la scaletta per questa data? Questa azione non può essere annullata.')) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('delete_date', date);
        window.location.href = 'index.php?' + urlParams.toString();
    }
}

function copyScaletta(date, day_it, titoli) {
    const formatted_date = date + ' (' + day_it + ')';
    let text = `Ciao a tutti! Ecco la scaletta in programma per ${formatted_date}:\n`;
    titoli.forEach(t => text += `- ${t}\n`);
    navigator.clipboard.writeText(text).then(() => {
        alert('Scaletta copiata negli appunti!');
    }).catch(err => {
        console.error('Errore nella copia:', err);
        alert('Errore nella copia della scaletta: ' + err.message);
    });
}