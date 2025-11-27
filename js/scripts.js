// Vanilla JS for interactions

function deleteScaletta(date) {
    if (confirm('Sei sicuro di voler eliminare la scaletta per questa data? Questa azione non può essere annullata.')) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('delete_date', date);
        window.location.href = 'index.php?' + urlParams.toString();
    }
}