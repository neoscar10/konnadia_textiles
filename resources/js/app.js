import './bootstrap';

// Prevent mouse wheel scroll from modifying values in number inputs across the app
document.addEventListener('wheel', function (e) {
    if (e.target && e.target.tagName === 'INPUT' && e.target.type === 'number') {
        if (document.activeElement === e.target) {
            e.target.blur();
        }
    } else if (document.activeElement && document.activeElement.tagName === 'INPUT' && document.activeElement.type === 'number') {
        document.activeElement.blur();
    }
}, { passive: true });
