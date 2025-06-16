/**
 * Script para mejorar la interacción con el selector de zonas
 */
document.addEventListener('DOMContentLoaded', function() {
    // Manejar clicks fuera del dropdown para cerrarlo
    document.addEventListener('click', function(event) {
        const zonasDropdown = document.querySelector('[data-zonas-dropdown]');
        const zonasInput = document.querySelector('#zonas_search');

        if (zonasDropdown && zonasInput && !zonasDropdown.contains(event.target) && !zonasInput.contains(event.target)) {
            if (window.Livewire) {
                window.Livewire.dispatch('cerrarDropdownZonas');
            }
        }
    });

    // Para mejorar la experiencia en dispositivos móviles
    document.addEventListener('touchstart', function(event) {
        const zonasDropdown = document.querySelector('[data-zonas-dropdown]');
        const zonasInput = document.querySelector('#zonas_search');

        if (zonasDropdown && !zonasDropdown.contains(event.target) && !zonasInput.contains(event.target)) {
            if (window.Livewire) {
                window.Livewire.dispatch('cerrarDropdownZonas');
            }
        }
    });
});
