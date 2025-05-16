// Auto-Refresh Funktionalität
document.addEventListener('DOMContentLoaded', function() {
    let lastActivity = Date.now();
    let refreshInterval = 30000; // 30 Sekunden
    let checkInterval;

    // Zum letzten Eintrag scrollen
    const logContainer = document.querySelector('.log-content-container');
    if (logContainer) {
        const lastEntry = document.getElementById('lastentry');
        if (lastEntry) {
            lastEntry.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Aktivitäten tracken
    function updateLastActivity() {
        lastActivity = Date.now();
    }

    // Event Listener für Benutzeraktivitäten
    ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'].forEach(function(event) {
        document.addEventListener(event, updateLastActivity, true);
    });

    function checkAndRefresh() {
        const now = Date.now();
        const timeSinceLastActivity = now - lastActivity;
        
        // Wenn die Seite nicht fokussiert ist ODER wenn sie fokussiert ist aber inaktiv für 30+ Sekunden
        if (!document.hasFocus() || timeSinceLastActivity >= refreshInterval) {
            location.reload();
        }
    }

    // Prüfintervall starten
    checkInterval = setInterval(checkAndRefresh, refreshInterval);

    // Debug Log Filter Funktionalität
    const checkboxes = document.querySelectorAll('.type-filter');
    const items = document.querySelectorAll('.wpdebug-item');
    
    function updateVisibility() {
        // Get all checked types
        const checkedTypes = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.dataset.type);
        
        // If no types are checked, show all items
        if (checkedTypes.length === 0) {
            items.forEach(item => {
                item.style.display = '';
            });
            return;
        }
        
        // Otherwise, show only items that match checked types
        items.forEach(item => {
            const itemClasses = item.className.split(' ');
            const shouldShow = checkedTypes.some(type => itemClasses.includes(type));
            item.style.display = shouldShow ? '' : 'none';
        });
    }
    
    // Initial state: uncheck all boxes but show all items
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    items.forEach(item => {
        item.style.display = '';
    });
    
    // Add click handlers
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateVisibility);
    });
}); 