/**
 * Script para mejorar la interacción con el selector de zonas
 */
document.addEventListener('DOMContentLoaded', function() {
    Livewire.hook('component.initialized', (component) => {
        if (component.name === 'admin.campanas.index') {
            console.log('Component campanas index initialized');
        }
    });

    // Interceptar clics fuera del dropdown para cerrarlo
    document.addEventListener('click', function(event) {
        const zonasDropdown = document.querySelector('[data-zonas-dropdown]');
        const zonasInput = document.querySelector('#zonas_search');
        
        if (zonasDropdown && zonasInput && !zonasDropdown.contains(event.target) && !zonasInput.contains(event.target)) {
            Livewire.dispatch('cerrarDropdownZonas');
        }
    });
});
