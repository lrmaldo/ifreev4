/**
 * Script para integración de Select2 con Livewire en selector de zonas
 * Versión 3.1 - Con inicialización manual forzada y comprobación mejorada
 */

(function() {
    'use strict';

    // Variables de control para evitar bucles
    let isUpdatingFromLivewire = false;
    let lastSelectedValues = [];
    let isInitialized = false;
    let mutationObserver = null;
    let manualInitTimeoutId = null;

    // Función para comparar arrays
    function arraysEqual(a, b) {
        if (a.length !== b.length) return false;
        return a.every((val, index) => val === b[index]);
    }

    // Contadores para reintentos
    let dependencyRetries = 0;
    let initRetries = 0;
    const MAX_RETRIES = 10; // Aumentar reintentos para dar más margen
    
    // Función para cargar jQuery usando script tag si no está disponible
    function cargarJQuery(callback) {
        console.log('Select2 Zonas: 🔄 Intentando cargar jQuery manualmente...');
        
        const script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
        script.integrity = 'sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=';
        script.crossOrigin = 'anonymous';
        script.onload = function() {
            console.log('Select2 Zonas: ✅ jQuery cargado manualmente:', window.jQuery.fn.jquery);
            if (callback) callback();
        };
        script.onerror = function() {
            console.error('Select2 Zonas: ❌ Error al cargar jQuery manualmente');
        };
        document.head.appendChild(script);
    }

    // Función para cargar Select2 usando script tag si no está disponible
    function cargarSelect2(callback) {
        console.log('Select2 Zonas: 🔄 Intentando cargar Select2 manualmente...');
        
        // Primero el CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css';
        document.head.appendChild(link);
        
        // Luego el JS
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js';
        script.onload = function() {
            console.log('Select2 Zonas: ✅ Select2 cargado manualmente');
            if (callback) callback();
        };
        script.onerror = function() {
            console.error('Select2 Zonas: ❌ Error al cargar Select2 manualmente');
        };
        document.head.appendChild(script);
    }
    
    // Función para verificar dependencias con reintentos limitados
    function checkDependencies() {
        // Verificar jQuery
        if (typeof window.jQuery === 'undefined' || typeof $ === 'undefined') {
            dependencyRetries++;
            if (dependencyRetries <= MAX_RETRIES) {
                console.log(`Select2 Zonas: ⏳ jQuery no está disponible, reintento ${dependencyRetries}/${MAX_RETRIES}...`);
                
                // Si llevamos varios reintentos, intentar cargar jQuery manualmente
                if (dependencyRetries === 3) {
                    cargarJQuery();
                }
            } else {
                console.error('Select2 Zonas: ❌ jQuery no disponible después de varios reintentos');
            }
            return false;
        }
        
        // Si jQuery está disponible pero Select2 no
        if (typeof $.fn.select2 === 'undefined') {
            dependencyRetries++;
            if (dependencyRetries <= MAX_RETRIES) {
                console.log(`Select2 Zonas: ⏳ Select2 no está disponible, reintento ${dependencyRetries}/${MAX_RETRIES}...`);
                
                // Si llevamos varios reintentos, intentar cargar Select2 manualmente
                if (dependencyRetries === 3) {
                    cargarSelect2();
                }
            } else {
                console.error('Select2 Zonas: ❌ Plugin Select2 no disponible después de varios reintentos');
            }
            return false;
        }
        
        console.log(`Select2 Zonas: ✅ Dependencias verificadas: jQuery v${$.fn.jquery} y Select2 v${$.fn.select2.amd.require.VERSION || 'desconocida'}`);
        dependencyRetries = 0; // Reiniciar contador si tenemos éxito
        return true;
    }

    // Función para inicializar Select2
    function initializeSelect2() {
        const element = document.getElementById('zonas_select');

        if (!element) {
            console.log('Select2 Zonas: Elemento #zonas_select no encontrado');
            return false;
        }

        if (isInitialized) {
            console.log('Select2 Zonas: Ya está inicializado');
            return true;
        }

        // Verificar dependencias antes de inicializar con límite de reintentos
        if (!checkDependencies()) {
            initRetries++;
            if (initRetries <= MAX_RETRIES) {
                console.log(`Select2 Zonas: ⏳ Reintento ${initRetries}/${MAX_RETRIES} en ${200 * initRetries}ms...`);
                setTimeout(() => {
                    initializeSelect2();
                }, 200 * initRetries); // Incrementar tiempo de espera con cada reintento
            } else {
                console.error('Select2 Zonas: ❌ No se pudo inicializar después de 5 intentos - Verifica la consola para más detalles.');
            }
            return false;
        }

        // Reiniciar contador si tenemos éxito
        initRetries = 0;

        console.log('Select2 Zonas: Inicializando Select2...');

        // Configurar Select2
        $(element).select2({
            placeholder: "Seleccione zonas...",
            allowClear: true,
            width: '100%'
        });

        // Obtener valores iniciales de Livewire
        const livewireValues = element.getAttribute('data-livewire-values');
        if (livewireValues) {
            try {
                const values = JSON.parse(livewireValues);
                if (Array.isArray(values) && values.length > 0) {
                    isUpdatingFromLivewire = true;
                    $(element).val(values).trigger('change');
                    lastSelectedValues = [...values];
                    console.log('Select2 Zonas: Valores iniciales establecidos:', values);
                    isUpdatingFromLivewire = false;
                }
            } catch (e) {
                console.error('Select2 Zonas: Error al parsear valores iniciales:', e);
            }
        }

        // Evento change para enviar cambios a Livewire
        $(element).on('change', function(e) {
            if (isUpdatingFromLivewire) {
                console.log('Select2 Zonas: Cambio desde Livewire, ignorando...');
                return;
            }

            const currentValues = $(this).val() || [];

            // Solo procesar si hay cambios reales
            if (!arraysEqual(currentValues.sort(), lastSelectedValues.sort())) {
                console.log('Select2 Zonas: Enviando cambios a Livewire:', currentValues);
                lastSelectedValues = [...currentValues];

                // Evitar que Livewire recargue el elemento durante 1 segundo
                e.preventDefault();

                // Si hay un modelo wire:model vinculado, actualizar directamente
                if (element.hasAttribute('wire:model') || 
                    element.hasAttribute('wire:model.live') || 
                    element.hasAttribute('wire:model.live.debounce.500ms')) {
                    
                    // El modelo se actualiza automáticamente por wire:model
                    console.log('Select2 Zonas: 📤 Usando wire:model para actualizar Livewire');
                    
                    // Actualizar también data-livewire-values para mantener consistencia
                    element.setAttribute('data-livewire-values', JSON.stringify(currentValues));
                } else {
                    // Usar el método sincronizarZonas si no hay wire:model
                    try {
                        const wireElement = element.closest('[wire\\:id]') || document.querySelector('[wire\\:id]');
                        if (!wireElement) {
                            console.error('Select2 Zonas: ❌ No se encontró un elemento Livewire');
                            return;
                        }

                        const wireId = wireElement.getAttribute('wire:id');
                        if (!wireId) {
                            console.error('Select2 Zonas: ❌ El elemento Livewire no tiene ID');
                            return;
                        }

                        const livewireComponent = window.Livewire.find(wireId);
                        if (livewireComponent && livewireComponent.call) {
                            console.log('Select2 Zonas: 📤 Enviando datos a Livewire usando sincronizarZonas:', currentValues);
                            
                            // Actualizar el atributo antes de llamar
                            element.setAttribute('data-livewire-values', JSON.stringify(currentValues));
                            
                            livewireComponent.call('sincronizarZonas', currentValues);
                        } else {
                            console.error('Select2 Zonas: ❌ Componente Livewire no encontrado o método call no disponible');
                        }
                    } catch (error) {
                        console.error('Select2 Zonas: ❌ Error al comunicar con Livewire:', error);
                    }
                }
            }
        });

        isInitialized = true;
        console.log('Select2 Zonas: ✅ Inicialización completada exitosamente');
        return true;
    }

    // Función para actualizar valores desde Livewire
    function updateFromLivewire(values) {
        const element = document.getElementById('zonas_select');

        if (!element || !isInitialized) {
            console.log('Select2 Zonas: Elemento no encontrado o no inicializado para actualización');
            return;
        }

        // Solo actualizar si hay cambios reales
        const currentValues = $(element).val() || [];
        if (!arraysEqual(values.sort(), currentValues.sort())) {
            console.log('Select2 Zonas: Actualizando desde Livewire:', values);
            isUpdatingFromLivewire = true;
            $(element).val(values).trigger('change');
            lastSelectedValues = [...values];
            isUpdatingFromLivewire = false;
        }
    }

    // Función para configurar el observador de mutaciones
    function setupMutationObserver() {
        if (mutationObserver) {
            return; // Ya está configurado
        }

        mutationObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Verificar si se agregaron nodos
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Verificar si el elemento zonas_select aparece
                    const element = document.getElementById('zonas_select');
                    if (element && !isInitialized) {
                        console.log('Select2 Zonas: 🎯 Elemento detectado por MutationObserver, inicializando...');

                        // Pequeño delay para asegurar que el DOM está completamente renderizado
                        setTimeout(() => {
                            initializeSelect2();
                        }, 50);
                    }
                }
            });
        });

        // Observar todo el documento por cambios en el DOM
        mutationObserver.observe(document.body, {
            childList: true,
            subtree: true
        });

        console.log('Select2 Zonas: MutationObserver configurado');
    }

    // Event listeners de Livewire
    document.addEventListener('livewire:updated', function() {
        console.log('Select2 Zonas: Livewire actualizado');

        if (!isInitialized) {
            // Intentar inicializar si no está inicializado
            initializeSelect2();
        } else {
            // Actualizar valores si ya está inicializado
            const element = document.getElementById('zonas_select');
            if (element) {
                const livewireValues = element.getAttribute('data-livewire-values');
                if (livewireValues) {
                    try {
                        const values = JSON.parse(livewireValues);
                        if (Array.isArray(values)) {
                            updateFromLivewire(values);
                        }
                    } catch (e) {
                        console.error('Select2 Zonas: Error al parsear valores actualizados:', e);
                    }
                }
            }
        }
    });

    // Escuchar eventos personalizados
    document.addEventListener('initializeZonasSelect', function(event) {
        console.log('Select2 Zonas: 📡 Evento initializeZonasSelect recibido:', event.detail);

        if (!event.detail) {
            console.warn('Select2 Zonas: ⚠️ Event detail es undefined');
        } else if (!Array.isArray(event.detail)) {
            console.warn('Select2 Zonas: ⚠️ Event detail no es un array:', event.detail);
        } else if (event.detail.length === 0) {
            console.log('Select2 Zonas: ℹ️ Array de zonas vacío - Esto es normal si no hay zonas seleccionadas');
        }

        // Delay más largo para modales
        setTimeout(() => {
            if (initializeSelect2()) {
                if (event.detail && Array.isArray(event.detail)) {
                    updateFromLivewire(event.detail);
                }
            }
        }, 250); // Aumentar delay para asegurar que el modal esté completamente renderizado
    });

    document.addEventListener('updateLivewireAttribute', function(event) {
        console.log('Select2 Zonas: 📡 Evento updateLivewireAttribute recibido:', event.detail);
        if (event.detail && Array.isArray(event.detail)) {
            updateFromLivewire(event.detail);
        }
    });

    // Funciones de debugging y utilidades disponibles globalmente
    window.select2Debug = {
        getStatus: function() {
            return {
                isInitialized: isInitialized,
                isUpdatingFromLivewire: isUpdatingFromLivewire,
                lastSelectedValues: lastSelectedValues,
                elementExists: !!document.getElementById('zonas_select'),
                observerActive: !!mutationObserver,
                dependencyRetries: dependencyRetries,
                initRetries: initRetries
            };
        },
        reinitialize: function() {
            isInitialized = false;
            dependencyRetries = 0;
            initRetries = 0;
            return initializeSelect2();
        },
        updateValues: function(values) {
            updateFromLivewire(values);
        },
        cargarDependencias: function(callback) {
            console.log('Select2 Zonas: 🔧 Cargando dependencias manualmente...');
            if (typeof jQuery === 'undefined') {
                cargarJQuery(function() {
                    cargarSelect2(callback);
                });
            } else {
                cargarSelect2(callback);
            }
        },
        forceInit: function(conDependencias) {
            console.log('Select2 Zonas: 🔧 Forzando inicialización...');
            
            // Reiniciar contadores
            isInitialized = false;
            dependencyRetries = 0;
            initRetries = 0;
            
            if (conDependencias) {
                this.cargarDependencias(function() {
                    const success = initializeSelect2();
                    console.log('Select2 Zonas: Resultado de inicialización forzada con dependencias:', success);
                });
                return "Cargando dependencias...";
            } else {
                const success = initializeSelect2();
                console.log('Select2 Zonas: Resultado de inicialización forzada:', success);
                return success;
            }
        }
    };
    
    // Exponer la función para uso global
    window.initializeZonasSelect2 = function() {
        console.log('Select2 Zonas: 🚀 Inicialización manual activada');
        return window.select2Debug.forceInit(true);
    };

    // Configurar el observador cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Select2 Zonas: DOM listo, configurando observer...');
            setupMutationObserver();
            initializeSelect2(); // Intentar inicializar inmediatamente también
        });
    } else {
        console.log('Select2 Zonas: DOM ya listo, configurando observer...');
        setupMutationObserver();
        initializeSelect2(); // Intentar inicializar inmediatamente también
    }

    console.log('Select2 Zonas v3.0: 🚀 Script cargado y listo');
})();
