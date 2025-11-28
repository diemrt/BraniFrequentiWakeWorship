// Minimal JavaScript - only essential functionality
// Most logic has been moved to PHP for better mobile compatibility

// Close modals on backdrop click (for mobile)
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('[id$="-modal"]');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                // For PHP-generated modals, navigate back
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('confirm_delete');
                currentUrl.searchParams.delete('copy_date');
                window.location.href = currentUrl.toString();
            }
        });
    });
});