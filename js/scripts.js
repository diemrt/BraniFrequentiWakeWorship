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

    // Try Web Share API first (great for mobile)
    if (navigator.share) {
        navigator.share({
            title: 'Scaletta Wake Worship',
            text: text
        }).then(() => {
            alert('Scaletta condivisa!');
        }).catch(err => {
            console.error('Errore nella condivisione:', err);
            // If share fails or is cancelled, try copy
            tryCopyToClipboard(text);
        });
    } else {
        // Fallback to copy
        tryCopyToClipboard(text);
    }
}

function tryCopyToClipboard(text) {
    // Try modern clipboard API
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Scaletta copiata negli appunti!');
        }).catch(err => {
            console.error('Errore nella copia moderna:', err);
            fallbackCopyTextToClipboard(text);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyTextToClipboard(text);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            alert('Scaletta copiata negli appunti!');
        } else {
            alert('Copia non riuscita. Copia manualmente il testo seguente:\n\n' + text);
        }
    } catch (err) {
        console.error('Errore nella copia fallback:', err);
        alert('Copia non riuscita. Copia manualmente il testo seguente:\n\n' + text);
    }
    document.body.removeChild(textArea);
}