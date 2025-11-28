// Vanilla JS for mobile-first interactions

// Toast notification system
class Toast {
    constructor(message, type = 'info', duration = 3000) {
        this.message = message;
        this.type = type;
        this.duration = duration;
        this.show();
    }

    show() {
        // Create toast container if it doesn't exist
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed bottom-20 md:bottom-6 left-4 right-4 md:left-auto md:right-6 z-50 space-y-2 pointer-events-none';
            document.body.appendChild(container);
        }

        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        const typeStyles = {
            'success': 'bg-green-500',
            'error': 'bg-red-500',
            'warning': 'bg-orange-500',
            'info': 'bg-blue-500'
        };

        toast.className = `${typeStyles[this.type] || typeStyles['info']} text-white px-4 py-3 rounded-lg shadow-lg pointer-events-auto animate-fade-in text-sm md:text-base`;
        toast.textContent = this.message;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('animate-fade-out');
            setTimeout(() => toast.remove(), 300);
        }, this.duration);
    }
}

// Enhanced delete function with confirmation modal and undo
function deleteScaletta(date) {
    showDeleteModal('Eliminare la scaletta?', 
        `Sei sicuro di voler eliminare la scaletta per il ${date}? Questa azione non può essere annullata.`,
        () => {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('delete_date', date);
            window.location.href = 'index.php?' + urlParams.toString();
        });
}

// Delete modal (bottom sheet on mobile)
function showDeleteModal(title, message, onConfirm) {
    const modalId = 'delete-modal-' + Date.now();
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-end md:items-center md:justify-center animate-fade-in';
    modal.innerHTML = `
        <div class="bg-white w-full md:w-96 rounded-t-2xl md:rounded-2xl p-6 md:p-8 space-y-6 max-h-96 overflow-y-auto animate-slide-up md:animate-zoom-in">
            <h2 class="text-xl md:text-2xl font-bold text-center text-gray-900">${title}</h2>
            
            <p class="text-gray-700 text-sm md:text-base">
                ${message}
            </p>
            
            <div class="flex gap-4">
                <button onclick="document.getElementById('${modalId}').remove()" 
                        class="flex-1 px-4 py-3 md:py-4 bg-gray-100 hover:bg-gray-200 rounded-lg md:rounded-xl font-medium text-gray-900 transition-colors">
                    Annulla
                </button>
                <button onclick="(${onConfirm.toString()})(); document.getElementById('${modalId}').remove();" 
                        class="flex-1 px-4 py-3 md:py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg md:rounded-xl font-medium transition-colors">
                    Elimina
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    // Close on backdrop click (mobile)
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Copy scaletta with Web Share API and fallback
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
            new Toast('Scaletta condivisa!', 'success');
        }).catch(err => {
            console.error('Share error:', err);
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
            new Toast('Scaletta copiata negli appunti!', 'success');
        }).catch(err => {
            console.error('Modern clipboard error:', err);
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
            new Toast('Scaletta copiata negli appunti!', 'success');
        } else {
            new Toast('Copia non riuscita', 'error');
        }
    } catch (err) {
        console.error('Fallback copy error:', err);
        new Toast('Copia non riuscita', 'error');
    }
    document.body.removeChild(textArea);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
    
    @keyframes zoomIn {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    
    .animate-fade-in { animation: fadeIn 0.3s ease-out; }
    .animate-fade-out { animation: fadeOut 0.3s ease-out; }
    .animate-slide-up { animation: slideUp 0.3s ease-out; }
    .animate-zoom-in { animation: zoomIn 0.3s ease-out; }
    
    /* Ensure toast doesn't get cut off by bottom nav on mobile */
    @media (max-width: 768px) {
        #toast-container {
            bottom: 80px !important;
        }
    }
`;
document.head.appendChild(style);