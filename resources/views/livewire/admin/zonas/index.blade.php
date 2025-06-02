<div>
    <script>
        // Fix para Alpine.js en dispositivos móviles - asegura que x-show funcione correctamente        document.addEventListener('alpine:init', function() {
            Alpine.directive('touchout', (el, { expression }, { evaluate }) => {
                // Tiempo mínimo necesario para considerar un toque como válido (ms)
                const touchTimeThreshold = 120;
                // Distancia mínima para considerar un desplazamiento como scroll
                const touchMoveThreshold = 5;
                // Delay para evitar que se cierre inmediatamente al abrir
                const touchActivationDelay = 250;

                let touchStartTime = 0;
                let touchStartPosition = { x: 0, y: 0 };
                let isScrolling = false;
                let isActive = false;
                let activationTimeout = null;

                // Activar después de un pequeño delay para prevenir cierres accidentales
                activationTimeout = setTimeout(() => {
                    isActive = true;
                }, touchActivationDelay);

                // Verificar si el elemento es un dropdown
                const isDropdown = el.id && el.id.includes('dropdown');

                // Capturar inicio de toque
                const touchStartHandler = (event) => {
                    if (!isActive) return;

                    // No procesar si el toque está dentro del elemento o sus botones/enlaces
                    if (el.contains(event.target)) return;

                    if (event.touches && event.touches[0]) {
                        touchStartTime = Date.now();
                        touchStartPosition = {
                            x: event.touches[0].clientX,
                            y: event.touches[0].clientY
                        };
                        isScrolling = false;
                    }
                };

                // Detectar si el usuario está haciendo scroll
                const touchMoveHandler = (event) => {
                    if (!isActive) return;

                    if (!isScrolling && event.touches && event.touches[0]) {
                        const dx = Math.abs(event.touches[0].clientX - touchStartPosition.x);
                        const dy = Math.abs(event.touches[0].clientY - touchStartPosition.y);

                        // Si se mueve más de la distancia umbral, considerarlo como scroll
                        if (dx > touchMoveThreshold || dy > touchMoveThreshold) {
                            isScrolling = true;
                        }
                    }
                };

                // Manejar el final del toque
                const touchEndHandler = (event) => {
                    if (!isActive) return;

                    // Si el tiempo de toque es muy corto o se detectó scroll, ignorar
                    if (Date.now() - touchStartTime < touchTimeThreshold || isScrolling) {
                        return;
                    }

                    // Solo cerrar si el toque es fuera del elemento
                    if (!el.contains(event.target)) {
                        // Para dropdowns, verificar que el toque no fue en un botón relacionado
                        if (isDropdown) {
                            // Obtener el ID del dropdown
                            const dropdownId = el.id;
                            // Buscar botones que puedan haber abierto este dropdown
                            const buttons = document.querySelectorAll('button');
                            for (let i = 0; i < buttons.length; i++) {
                                if (buttons[i].outerHTML.includes(dropdownId)) {
                                    // Si el toque fue en el botón, no cerrar el dropdown
                                    if (buttons[i].contains(event.target)) {
                                        return;
                                    }
                                }
                            }
                        }

                        evaluate(expression);
                    }
                };

                // Manejar toques en el documento de forma optimizada
                const touchHandler = (event) => {
                    if (!isActive) return;

                    // Ignorar si el elemento ya no está en el DOM
                    if (!document.body.contains(el)) {
                        cleanup();
                        return;
                    }

                    // Si el toque es fuera del elemento y no en su botón asociado, cerrar
                    if (!el.contains(event.target)) {
                        // Similar al código anterior, verificar que no sea un botón relacionado
                        if (isDropdown) {
                            const dropdownId = el.id;
                            const buttons = document.querySelectorAll('button');
                            for (let i = 0; i < buttons.length; i++) {
                                if (buttons[i].outerHTML.includes(dropdownId)) {
                                    if (buttons[i].contains(event.target)) {
                                        return;
                                    }
                                }
                            }
                        }

                        evaluate(expression);
                    }
                };

                // Limpieza de eventos
                const cleanup = () => {
                    document.body.removeEventListener('touchstart', touchStartHandler);
                    document.body.removeEventListener('touchmove', touchMoveHandler);
                    document.body.removeEventListener('touchend', touchEndHandler);
                    document.body.removeEventListener('touchstart', touchHandler);
                    clearTimeout(activationTimeout);
                };

                // Registrar eventos
                document.body.addEventListener('touchstart', touchStartHandler, { passive: true });
                document.body.addEventListener('touchmove', touchMoveHandler, { passive: true });
                document.body.addEventListener('touchend', touchEndHandler, { passive: true });
                document.body.addEventListener('touchstart', touchHandler, { passive: true });

                // Devolver la función de limpieza
                return cleanup;
            });
        });

        document.addEventListener('livewire:initialized', function () {
            // Escucha los cambios en la propiedad showInstructionsModal
            Livewire.on('showInstructionsModal', function() {
                console.log('Modal debe mostrarse ahora!');
                let modal = document.getElementById('instructions-modal');
                if (modal) {
                    modal.style.display = 'block';
                }
            });

            // Función para detectar si es un dispositivo móvil
            function esMobile() {
                return window.innerWidth < 640 || ('ontouchstart' in window) ||
                       (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);
            }

            // Añadir soporte para eventos táctiles en dispositivos móviles
            document.addEventListener('touchstart', function() {
                // Esto activa el procesamiento de eventos táctiles
            }, { passive: true });

            // Inicializar los dropdowns para dispositivos móviles
            if (esMobile()) {
                // En móviles, hacer que los botones de dropdown sean más fáciles de clickear
                document.querySelectorAll('[id^="dropdown-"]').forEach(function(dropdown) {
                    // Asegurarse que los clicks en los enlaces dentro del dropdown funcionen
                    dropdown.querySelectorAll('a, button').forEach(function(el) {
                        el.addEventListener('touchstart', function(e) {
                            e.stopPropagation();
                        }, { passive: false });
                    });
                });
            }

            // Repositionar dropdowns al hacer scroll (vertical u horizontal)
            window.addEventListener('scroll', handleScroll);

            // También capturar el scroll horizontal en la tabla
            document.querySelectorAll('.overflow-x-auto').forEach(el => {
                el.addEventListener('scroll', handleScroll, { passive: true });
            });

            // Mantener un registro de los botones que activaron los dropdowns
            const dropdownButtonMap = new Map();

            // Función mejorada para manejar el scroll
            function handleScroll() {
                // Buscar todos los dropdowns visibles
                document.querySelectorAll('[id^="dropdown-"]').forEach(function(dropdown) {
                    if (dropdown.style.display !== 'none' && dropdown.offsetParent !== null) {
                        // Encontrar el botón correspondiente
                        const dropdownId = dropdown.id;

                        // Intentar obtener el botón del registro primero (más eficiente)
                        let targetButton = dropdownButtonMap.get(dropdownId);

                        // Si no existe en el registro, buscarlo
                        if (!targetButton) {
                            const buttons = document.querySelectorAll('button');

                            for (let i = 0; i < buttons.length; i++) {
                                const button = buttons[i];
                                if (button.outerHTML.includes(dropdownId)) {
                                    targetButton = button;
                                    // Guardar en el registro para uso futuro
                                    dropdownButtonMap.set(dropdownId, button);
                                    break;
                                }
                            }
                        }

                        // Si el botón no está visible en el viewport, cerrar el dropdown
                        if (targetButton) {
                            const rect = targetButton.getBoundingClientRect();
                            const isVisible = rect.top >= 0 && rect.left >= 0 &&
                                             rect.bottom <= window.innerHeight &&
                                             rect.right <= window.innerWidth;

                            if (!isVisible) {
                                // Si el botón ya no es visible, cerrar el dropdown a través de Alpine
                                if (targetButton.__x && targetButton.__x.$data.open) {
                                    targetButton.__x.$data.open = false;
                                    return;
                                }
                            }

                            // Simular un evento para reposicionar
                            const event = { currentTarget: targetButton };
                            detectPosition(event, dropdownId);
                        }
                    }
                });
            }
        });        // Función para detectar y ajustar la posición de los dropdowns
        window.detectPosition = function(event, dropdownId) {
            const button = event.currentTarget;
            const dropdown = document.getElementById(dropdownId);
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;
            const isMobile = windowWidth < 640; // Punto de corte para dispositivos móviles (sm en Tailwind)
            const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0);

            // Obtener la posición de scroll
            const scrollX = window.scrollX || window.pageXOffset;
            const scrollY = window.scrollY || window.pageYOffset;

            if (!dropdown || !button) return;

            // Obtener las coordenadas del botón relativas a la ventana
            const buttonRect = button.getBoundingClientRect();

            // Calcular espacio disponible en cada dirección, considerando el scroll
            const spaceRight = windowWidth - buttonRect.right;
            const spaceLeft = buttonRect.left;
            const spaceBelow = windowHeight - buttonRect.bottom;
            const spaceAbove = buttonRect.top;

            // Guardar temporalmente el display original
            const originalDisplay = dropdown.style.display;
            dropdown.style.display = 'block';
            dropdown.style.visibility = 'hidden';

            // Obtener dimensiones del dropdown
            const dropdownWidth = dropdown.offsetWidth;
            const dropdownHeight = dropdown.offsetHeight;

            // Restaurar el display original
            dropdown.style.display = originalDisplay;
            dropdown.style.visibility = '';

            // Determinar la mejor posición según el espacio disponible
            let position = 'right';

            if (isMobile || isTouchDevice) {
                // En dispositivos móviles, elegimos la mejor posición considerando el espacio disponible
                // y la posición del botón en la pantalla

                // Verificar si el botón está en la mitad izquierda o derecha de la pantalla
                const isButtonOnRightSide = buttonRect.left > (windowWidth / 2);

                // Por defecto, mostramos abajo
                position = 'center-bottom';

                // Si no hay suficiente espacio abajo pero hay arriba, mostrarlo arriba
                if (spaceBelow < Math.min(250, dropdownHeight) && spaceAbove > dropdownHeight) {
                    position = 'center-top';
                }

                // Configuración para mejorar la experiencia en dispositivos móviles
                dropdown.style.maxHeight = Math.min(300, windowHeight * 0.7) + 'px';
                dropdown.style.overflowY = 'auto';
                dropdown.style.overflowX = 'hidden';
                dropdown.style.webkitOverflowScrolling = 'touch'; // Para mejorar el scroll en iOS
            } else {
                // Lógica para pantallas más grandes no táctiles
                if (spaceRight < dropdownWidth && spaceLeft > dropdownWidth) {
                    position = 'left';
                } else if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
                    position = spaceLeft > spaceRight ? 'top-left' : 'top-right';
                }
            }

            // Aplicar posicionamiento al dropdown
            dropdown.style.position = 'fixed'; // Usar posicionamiento fijo para evitar problemas con scroll
            dropdown.style.zIndex = '999'; // Aumentar el z-index para asegurar que esté por encima de otros elementos            // Mejorar la visibilidad en todos los dispositivos
            dropdown.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2)';
            dropdown.style.transition = 'opacity 0.25s ease-out, transform 0.25s ease-out';
            dropdown.style.opacity = '0';
            dropdown.style.transform = 'scale(0.95)';
            dropdown.style.borderRadius = '0.5rem'; // Bordes más redondeados (equivalente a rounded-lg)
            dropdown.style.border = '1px solid rgba(209, 213, 219, 0.7)'; // Borde más visible
            dropdown.style.backgroundColor = 'rgba(255, 255, 255, 0.98)'; // Fondo más opaco

            // Configuraciones adicionales para dispositivos móviles
            if (isMobile || isTouchDevice) {
                dropdown.style.touchAction = 'pan-y'; // Permitir scroll vertical pero prevenir zoom

                // Añadir un pequeño desplazamiento para evitar que el click se registre cerca del botón
                dropdown.style.marginTop = '8px';

                // Mejorar visibilidad para dispositivos móviles
                dropdown.style.backdropFilter = 'blur(2px)'; // Efecto de desenfoque detrás del menú

                // Agregar un indicador visual (flecha) en el dropdown
                const arrow = document.createElement('div');
                arrow.style.position = 'absolute';
                arrow.style.width = '12px';
                arrow.style.height = '12px';
                arrow.style.backgroundColor = 'white';
                arrow.style.border = '1px solid rgba(209, 213, 219, 0.7)';
                arrow.style.borderBottom = 'none';
                arrow.style.borderRight = 'none';
                arrow.style.transform = 'rotate(45deg)';
                arrow.style.top = '-6px';
                arrow.style.left = '20px';
                arrow.style.zIndex = '1';

                // Si el dropdown se muestra arriba, mover la flecha
                if (position === 'center-top' || position.includes('top-')) {
                    arrow.style.top = 'auto';
                    arrow.style.bottom = '-6px';
                    arrow.style.transform = 'rotate(225deg)';
                }

                // Añadir la flecha al dropdown
                dropdown.insertBefore(arrow, dropdown.firstChild);

                // Mejorar la respuesta táctil
                dropdown.querySelectorAll('a, button').forEach(el => {
                    // Asegurar que los elementos del menú sean fáciles de tocar
                    el.style.minHeight = '44px'; // Altura mínima recomendada para controles táctiles
                    el.style.display = 'flex';
                    el.style.alignItems = 'center';
                });

                // Prevenir que el evento touchstart cierre el dropdown cuando es dentro del mismo dropdown
                dropdown.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                }, { passive: false });

                // Añadir un botón de cerrar explícito para móviles
                const closeButton = document.createElement('div');
                closeButton.style.position = 'absolute';
                closeButton.style.top = '5px';
                closeButton.style.right = '5px';
                closeButton.style.width = '24px';
                closeButton.style.height = '24px';
                closeButton.style.borderRadius = '50%';
                closeButton.style.display = 'flex';
                closeButton.style.alignItems = 'center';
                closeButton.style.justifyContent = 'center';
                closeButton.style.cursor = 'pointer';
                closeButton.style.color = '#6B7280';
                closeButton.innerHTML = '&times;';
                closeButton.style.fontSize = '18px';

                closeButton.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                    if (button.__x && button.__x.$data.open) {
                        button.__x.$data.open = false;
                    }
                }, { passive: false });

                // Añadir el botón de cerrar al dropdown
                dropdown.appendChild(closeButton);
            }// Determinar si el botón está dentro de una tabla con scroll horizontal
            const isInScrollableTable = (function() {
                let el = button;
                while (el && el.tagName !== 'BODY') {
                    if (el.classList && (el.classList.contains('overflow-x-auto') ||
                        window.getComputedStyle(el).overflowX === 'auto' ||
                        window.getComputedStyle(el).overflowX === 'scroll')) {
                        return true;
                    }
                    el = el.parentElement;
                }
                return false;
            })();

            // Si estamos en vista móvil y dentro de una tabla con scroll, asegurarnos que el dropdown esté visible
            if ((isMobile || isTouchDevice) && isInScrollableTable) {
                // Para tablas con scroll horizontal en móviles, siempre posicionar desde el borde izquierdo
                // independientemente de la posición del botón para asegurar que sea visible
                position = 'fixed-left';
            }

            // Posicionar el dropdown basado en la posición determinada
            if (position === 'fixed-left') {
                // Posición fija para tablas con scroll horizontal en móviles
                dropdown.style.left = '10px';  // Margen desde el borde izquierdo
                dropdown.style.top = (buttonRect.bottom + 8) + 'px';
                dropdown.style.maxWidth = (windowWidth - 20) + 'px';
                dropdown.style.minWidth = Math.min(280, windowWidth - 20) + 'px';
                dropdown.style.right = 'auto';

                // Si está muy abajo, mostrarlo arriba
                if (buttonRect.bottom + dropdownHeight + 20 > windowHeight) {
                    dropdown.style.top = 'auto';
                    dropdown.style.bottom = (windowHeight - buttonRect.top + 8) + 'px';
                }
            } else if (position === 'center-bottom') {
                // Para móviles: calcular mejor posición horizontal
                const buttonCenter = buttonRect.left + (buttonRect.width / 2);
                const idealLeft = buttonCenter - (dropdownWidth / 2);

                // Asegurarnos que no se salga por los bordes
                const safeLeft = Math.max(10, Math.min(windowWidth - dropdownWidth - 10, idealLeft));

                dropdown.style.left = safeLeft + 'px';
                dropdown.style.top = (buttonRect.bottom + 8) + 'px';
                dropdown.style.maxWidth = (windowWidth - 20) + 'px';
                dropdown.style.minWidth = Math.min(250, windowWidth - 20) + 'px';
                dropdown.style.right = 'auto';
            } else if (position === 'center-top') {
                // Para mostrar arriba del botón
                const buttonCenter = buttonRect.left + (buttonRect.width / 2);
                const idealLeft = buttonCenter - (dropdownWidth / 2);

                // Asegurarnos que no se salga por los bordes
                const safeLeft = Math.max(10, Math.min(windowWidth - dropdownWidth - 10, idealLeft));

                dropdown.style.left = safeLeft + 'px';
                dropdown.style.bottom = (windowHeight - buttonRect.top + 8) + 'px';
                dropdown.style.top = 'auto'; // Usar bottom en lugar de top para mejor alineación
                dropdown.style.maxWidth = (windowWidth - 20) + 'px';
                dropdown.style.minWidth = Math.min(250, windowWidth - 20) + 'px';
                dropdown.style.right = 'auto';
            } else if (position === 'right') {
                // Posicionamiento estándar a la derecha
                let leftPos = buttonRect.left;

                // Asegurarse que no se salga de la pantalla por la derecha
                if (leftPos + dropdownWidth > windowWidth - 10) {
                    leftPos = windowWidth - dropdownWidth - 10;
                }

                dropdown.style.left = leftPos + 'px';
                dropdown.style.top = buttonRect.bottom + 5 + 'px';
            } else if (position === 'left') {
                // Posicionamiento a la izquierda
                let leftPos = buttonRect.right - dropdownWidth;

                // Asegurarse que no se salga de la pantalla por la izquierda
                if (leftPos < 10) {
                    leftPos = 10;
                }

                dropdown.style.left = leftPos + 'px';
                dropdown.style.top = buttonRect.bottom + 5 + 'px';
            } else if (position === 'top-right') {
                // Arriba a la derecha
                let leftPos = buttonRect.left;

                // Asegurarse que no se salga de la pantalla
                if (leftPos + dropdownWidth > windowWidth - 10) {
                    leftPos = windowWidth - dropdownWidth - 10;
                }

                dropdown.style.left = leftPos + 'px';
                dropdown.style.bottom = (windowHeight - buttonRect.top + 5) + 'px';
                dropdown.style.top = 'auto'; // Usar bottom en lugar de top
            } else if (position === 'top-left') {
                // Arriba a la izquierda
                let leftPos = buttonRect.right - dropdownWidth;

                // Asegurarse que no se salga de la pantalla
                if (leftPos < 10) {
                    leftPos = 10;
                }

                dropdown.style.left = leftPos + 'px';
                dropdown.style.bottom = (windowHeight - buttonRect.top + 5) + 'px';
                dropdown.style.top = 'auto'; // Usar bottom en lugar de top
            }            // Forzar repintado del DOM para que la animación funcione correctamente
            requestAnimationFrame(() => {
                dropdown.style.opacity = '1';
                dropdown.style.transform = 'scale(1)';
            });

            // Añadir detección de scroll para cerrar el dropdown si el usuario hace scroll
            const scrollHandler = () => {
                const alpineComponent = button.__x;
                if (alpineComponent && alpineComponent.$data.open) {
                    alpineComponent.$data.open = false;
                }
                window.removeEventListener('scroll', scrollHandler, { passive: true });
            };

            // Cerrar dropdown al hacer tap en cualquier parte de la pantalla (para móviles)
            const documentTouchHandler = (e) => {
                // No cerrar si el toque es dentro del dropdown o en el botón
                if (dropdown.contains(e.target) || button.contains(e.target)) {
                    return;
                }

                const alpineComponent = button.__x;
                if (alpineComponent && alpineComponent.$data.open) {
                    alpineComponent.$data.open = false;
                }

                // Limpiar este evento después de usarlo
                document.removeEventListener('touchstart', documentTouchHandler);
            };

            // En dispositivos móviles, agregar más manejadores para mejorar la interacción
            if (isMobile || isTouchDevice) {
                window.addEventListener('scroll', scrollHandler, { passive: true });

                // Registrar un handler para cerrar al tocar en cualquier lugar fuera
                // Pequeño timeout para evitar que se cierre inmediatamente
                setTimeout(() => {
                    document.addEventListener('touchstart', documentTouchHandler, { passive: true });
                }, 100);

                // También cerrar al hacer scroll horizontal en la tabla
                const tableScrollHandler = (e) => {
                    const alpineComponent = button.__x;
                    if (alpineComponent && alpineComponent.$data.open) {
                        alpineComponent.$data.open = false;
                    }
                };

                // Buscar tablas con scroll horizontal
                const scrollableTables = document.querySelectorAll('.overflow-x-auto');
                scrollableTables.forEach(table => {
                    table.addEventListener('scroll', tableScrollHandler, { passive: true, once: true });
                });

                // Eliminar los listeners después de un tiempo (para evitar problemas de memoria)
                setTimeout(() => {
                    if (button.__x && !button.__x.$data.open) {
                        window.removeEventListener('scroll', scrollHandler);
                        document.removeEventListener('touchstart', documentTouchHandler);
                        scrollableTables.forEach(table => {
                            table.removeEventListener('scroll', tableScrollHandler);
                        });
                    }
                }, 5000); // 5 segundos
            }

            // Actualizar el valor de position en el componente Alpine
            if (button.__x) {
                button.__x.$data.position = position;
            }
        };
    </script>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Mensajes flash -->
        @if (session()->has('message'))
            <div class="mb-4 p-4 bg-green-100 text-green-900 rounded-md shadow-sm">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 p-4 bg-red-100 text-red-900 rounded-md shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Controles de búsqueda y paginación -->
        <div class="mb-6 sm:flex sm:items-center sm:justify-between">
            <div class="w-full max-w-sm">
                <label for="search" class="sr-only">Buscar</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        wire:model.live.debounce.300ms="search"
                        id="search"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Buscar por nombre..."
                        type="search"
                    >
                </div>
            </div>
            <div class="mt-3 sm:mt-0">
                <select
                    wire:model.live="perPage"
                    id="perPage"
                    class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                    <option value="5">5 por página</option>
                    <option value="10">10 por página</option>
                    <option value="25">25 por página</option>
                    <option value="50">50 por página</option>
                </select>
            </div>
        </div>

        <!-- Tabla de zonas -->
        <div class="overflow-x-auto bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="text-sm text-gray-500 px-4 py-2 bg-gray-100 md:hidden">
                Desliza horizontalmente para ver toda la tabla →
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nombre
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                            Tipo de Registro
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                            Cuenta regresiva (Segundos)
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                            Auth Mikrotik
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                            Selec. Campañas
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                            Propietario
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Campos
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($zonas as $zona)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $zona->nombre }}
                                <div class="sm:hidden mt-1 text-xs text-gray-500">
                                    <div>Tipo: {{ $zona->getTipoRegistroLabelAttribute() }}</div>
                                    <div class="md:hidden">Segundos: {{ $zona->segundos }}</div>
                                    <div class="md:hidden">Auth: {{ $zona->getTipoAutenticacionMikrotikLabelAttribute() }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden sm:table-cell">
                                {{ $zona->getTipoRegistroLabelAttribute() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                {{ $zona->segundos }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                {{ $zona->getTipoAutenticacionMikrotikLabelAttribute() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $zona->seleccion_campanas === 'aleatorio' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                                    {{ $zona->seleccion_campanas === 'aleatorio' ? 'Aleatorio' : 'Por prioridad' }}
                                </span>
                                <br>
                                <span class="text-xs text-gray-500 mt-1">
                                    Tiempo: {{ $zona->tiempo_visualizacion ?? 15 }}s
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">
                                {{ $zona->user->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($zona->tipo_registro != 'sin_registro')
                                    {{ $zona->campos->count() }}
                                    <button
                                        wire:click="openFieldModal({{ $zona->id }})"
                                        class="ml-2 inline-flex items-center p-1 border border-transparent rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </button>
                                @else
                                    <span class="italic text-gray-400">No aplica</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex flex-wrap gap-2 sm:space-x-2">
                                    <button
                                        wire:click="openModal(true, {{ $zona->id }})"
                                        class="text-indigo-600 hover:text-indigo-900 py-1 px-1.5 rounded border border-transparent hover:border-indigo-200"
                                    >
                                        Editar
                                    </button>

                                    <a
                                        href="{{ route('admin.zonas.configuracion-campanas', ['zonaId' => $zona->id]) }}"
                                        class="text-orange-600 hover:text-orange-900 py-1 px-1.5 rounded border border-transparent hover:border-orange-200"
                                    >
                                        Campañas
                                    </a>

                                    @if($zona->tipo_registro != 'sin_registro')
                                    <a
                                        href="{{ route('admin.zone.form-fields', ['zonaId' => $zona->id]) }}"
                                        class="text-green-600 hover:text-green-900 py-1 px-1.5 rounded border border-transparent hover:border-green-200"
                                    >
                                        Campos
                                    </a>

                                    <a
                                        href="{{ route('cliente.zona.formulario', ['zonaId' => $zona->id]) }}"
                                        class="text-purple-600 hover:text-purple-900 py-1 px-1.5 rounded border border-transparent hover:border-purple-200"
                                        target="_blank"
                                    >
                                        <span class="hidden xs:inline">Ver </span>Formulario
                                    </a>
                                    @endif

                                    <div x-data="{ open: false, position: 'right' }" class="relative inline-block">
                                        <button @click="open = !open; setTimeout(() => detectPosition($event, 'dropdown-vista-{{ $zona->id }}'), 10)"
                                                x-on:touchstart.stop="open = !open; setTimeout(() => detectPosition($event, 'dropdown-vista-{{ $zona->id }}'), 10)"
                                                class="whitespace-nowrap text-amber-600 hover:text-amber-900 focus:outline-none py-2 px-3 rounded border border-transparent hover:border-amber-200 hover:bg-amber-50 active:bg-amber-100 transition-colors duration-150 touch-manipulation">
                                            <span class="hidden xs:inline">Vista </span>previa
                                            <svg class="h-5 w-5 inline ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="open"
                                             @click.away="open = false"
                                             @touchstart.stop
                                             x-touchout="open = false"
                                             @mouseenter="detectPosition($event, 'dropdown-vista-{{ $zona->id }}')"
                                             id="dropdown-vista-{{ $zona->id }}"
                                             class="fixed bg-white rounded-md shadow-lg z-50 overflow-hidden border border-gray-200"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 transform scale-95"
                                             x-transition:enter-end="opacity-100 transform scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 transform scale-100"
                                             x-transition:leave-end="opacity-0 transform scale-95">
                                            <div class="py-1 whitespace-nowrap">
                                                <a href="{{ route('cliente.zona.preview', ['id' => $zona->id]) }}"
                                                   class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 active:bg-gray-200 sm:py-2 transition-colors duration-150"
                                                   target="_blank"
                                                   style="touch-action: manipulation;">
                                                    <span class="flex items-center">
                                                        <svg class="h-4 w-4 mr-2 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        Vista previa normal
                                                    </span>
                                                </a>
                                                <a href="{{ route('cliente.zona.preview.carrusel', ['id' => $zona->id]) }}"
                                                   class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 active:bg-gray-200 sm:py-2 transition-colors duration-150"
                                                   target="_blank"
                                                   style="touch-action: manipulation;">
                                                    <span class="flex items-center">
                                                        <svg class="h-4 w-4 mr-2 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                                                        </svg>
                                                        Vista con carrusel
                                                    </span>
                                                </a>
                                                <a href="{{ route('cliente.zona.preview.video', ['id' => $zona->id]) }}"
                                                   class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 active:bg-gray-200 sm:py-2 transition-colors duration-150"
                                                   target="_blank"
                                                   style="touch-action: manipulation;">
                                                    <span class="flex items-center">
                                                        <svg class="h-4 w-4 mr-2 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Vista con video
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div x-data="{ open: false, position: 'right' }" class="relative inline-block">
                                        <button @click="open = !open; setTimeout(() => detectPosition($event, 'dropdown-files-{{ $zona->id }}'), 10)"
                                               x-on:touchstart.stop="open = !open; setTimeout(() => detectPosition($event, 'dropdown-files-{{ $zona->id }}'), 10)"
                                               class="whitespace-nowrap text-blue-600 hover:text-blue-900 focus:outline-none py-2 px-3 rounded border border-transparent hover:border-blue-200 hover:bg-blue-50 active:bg-blue-100 transition-colors duration-150 touch-manipulation">
                                            <span class="hidden sm:inline">Archivos </span>Mikrotik
                                            <svg class="h-5 w-5 inline ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div x-show="open"
                                             @click.away="open = false"
                                             @touchstart.stop
                                             x-touchout="open = false"
                                             @mouseenter="detectPosition($event, 'dropdown-files-{{ $zona->id }}')"
                                             id="dropdown-files-{{ $zona->id }}"
                                             class="fixed bg-white rounded-md shadow-lg z-50 overflow-hidden border border-gray-200"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 transform scale-95"
                                             x-transition:enter-end="opacity-100 transform scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 transform scale-100"
                                             x-transition:leave-end="opacity-0 transform scale-95">
                                            <div class="py-1 whitespace-nowrap">
                                                <a href="{{ route('admin.zonas.download', ['zonaId' => $zona->id, 'fileType' => 'login']) }}"
                                                   class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 active:bg-gray-200 sm:py-2 transition-colors duration-150"
                                                   style="touch-action: manipulation;">
                                                    <span class="flex items-center">
                                                        <svg class="h-4 w-4 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                        Descargar login.html
                                                    </span>
                                                </a>
                                                <a href="{{ route('admin.zonas.download', ['zonaId' => $zona->id, 'fileType' => 'alogin']) }}"
                                                   class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 active:bg-gray-200 sm:py-2 transition-colors duration-150"
                                                   style="touch-action: manipulation;">
                                                    <span class="flex items-center">
                                                        <svg class="h-4 w-4 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                        Descargar alogin.html
                                                    </span>
                                                </a>
                                                <button
                                                    wire:click.prevent="openInstructionsModal({{ $zona->id }})"
                                                    class="block w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 active:bg-gray-200 sm:py-2 transition-colors duration-150"
                                                    style="touch-action: manipulation;"
                                                >
                                                    Ver instrucciones
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    @if (auth()->user()->hasRole('admin') || $zona->user_id === auth()->id())
                                        <button
                                            wire:click="confirmZonaDeletion({{ $zona->id }})"
                                            class="text-red-600 hover:text-red-900 py-1 px-1.5 rounded border border-transparent hover:border-red-200"
                                        >
                                            Eliminar
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <!-- Lista de campos por zona -->
                        @if ($zona->campos->count() > 0 && $zona->tipo_registro != 'sin_registro')
                            <tr class="bg-gray-50">
                                <td colspan="7" class="px-6 py-2">
                                    <div class="border rounded-md divide-y">
                                        <div class="px-4 py-2 bg-gray-100 text-sm font-medium">
                                            Campos de "{{ $zona->nombre }}"
                                        </div>
                                        <div class="divide-y">
                                            @foreach ($zona->campos->sortBy('orden') as $campo)
                                                <div class="px-4 py-2 flex justify-between items-center">
                                                    <div class="flex-1">
                                                        <div class="flex items-center">
                                                            <span class="font-medium">{{ $campo->etiqueta }}</span>
                                                            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100">
                                                                {{ $campo->tipo }}
                                                            </span>
                                                            @if ($campo->obligatorio)
                                                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                                    Obligatorio
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            Campo: {{ $campo->campo }} | Orden: {{ $campo->orden }}
                                                        </div>
                                                    </div>
                                                    <div class="flex space-x-2">
                                                        <button
                                                            wire:click="openFieldModal({{ $zona->id }}, true, {{ $campo->id }})"
                                                            class="text-indigo-600 hover:text-indigo-900 text-sm"
                                                        >
                                                            Editar
                                                        </button>
                                                        <button
                                                            wire:click="confirmFieldDeletion({{ $campo->id }})"
                                                            class="text-red-600 hover:text-red-900 text-sm"
                                                        >
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                No hay zonas disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="mt-4">
            {{ $zonas->links() }}
        </div>

        <!-- Modal para crear/editar zona -->
        @if ($showModal)
            <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        {{ $isEditing ? 'Editar Zona' : 'Nueva Zona' }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                                            <input
                                                type="text"
                                                wire:model="zona.nombre"
                                                id="nombre"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('zona.nombre')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="id_personalizado" class="block text-sm font-medium text-gray-700">ID personalizado para URL</label>
                                            <div class="mt-1 flex rounded-md shadow-sm">
                                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                                    https://i-free.com.mx/login_formulario/
                                                </span>
                                                <input
                                                    type="text"
                                                    wire:model="zona.id_personalizado"
                                                    id="id_personalizado"
                                                    placeholder="Opcional - si no se especifica, se usará el ID real"
                                                    class="flex-1 min-w-0 block w-full rounded-none rounded-r-md sm:text-sm border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                                                >
                                            </div>
                                            <p class="mt-1 text-sm text-gray-500">
                                                Si ya tienes Mikrotiks configurados con una URL específica, puedes mantener el mismo ID.
                                                Solo se permiten letras, números, guiones y guiones bajos.
                                            </p>
                                            @error('zona.id_personalizado')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="tipo_registro" class="block text-sm font-medium text-gray-700">Tipo de Registro</label>
                                            <select
                                                wire:model="zona.tipo_registro"
                                                id="tipo_registro"
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            >
                                                @foreach ($tipoRegistroOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('zona.tipo_registro')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="segundos" class="block text-sm font-medium text-gray-700">Segundos (Tiempo retroceso)</label>
                                            <input
                                                type="number"
                                                wire:model="zona.segundos"
                                                id="segundos"
                                                min="15"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('zona.segundos')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                type="checkbox"
                                                wire:model="zona.login_sin_registro"
                                                id="login_sin_registro"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            >
                                            <label for="login_sin_registro" class="ml-2 block text-sm text-gray-900">
                                                Boton "no quiero registrarme"
                                            </label>
                                        </div>
                                        <div>
                                            <label for="tipo_autenticacion_mikrotik" class="block text-sm font-medium text-gray-700">Tipo de Autenticación Mikrotik</label>
                                            <select
                                                wire:model="zona.tipo_autenticacion_mikrotik"
                                                id="tipo_autenticacion_mikrotik"
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            >
                                                @foreach ($tipoAutenticacionMikrotikOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('zona.tipo_autenticacion_mikrotik')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="script_head" class="block text-sm font-medium text-gray-700">Script Head</label>
                                            <textarea
                                                wire:model="zona.script_head"
                                                id="script_head"
                                                rows="3"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            ></textarea>
                                        </div>
                                        <div>
                                            <label for="script_body" class="block text-sm font-medium text-gray-700">Script Body</label>
                                            <textarea
                                                wire:model="zona.script_body"
                                                id="script_body"
                                                rows="3"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="saveZona"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Guardar
                            </button>
                            <button
                                wire:click="closeModal"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal para crear/editar campo -->
        @if ($showFieldModal)
            <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        {{ $isEditingField ? 'Editar Campo' : 'Nuevo Campo' }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label for="campo" class="block text-sm font-medium text-gray-700">Identificador del Campo</label>
                                            <input
                                                type="text"
                                                wire:model="formField.campo"
                                                id="campo"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('formField.campo')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="etiqueta" class="block text-sm font-medium text-gray-700">Etiqueta</label>
                                            <input
                                                type="text"
                                                wire:model="formField.etiqueta"
                                                id="etiqueta"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('formField.etiqueta')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo de Campo</label>
                                            <select
                                                wire:model="formField.tipo"
                                                id="tipo"
                                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            >
                                                @foreach ($tipoFieldOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('formField.tipo')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="orden" class="block text-sm font-medium text-gray-700">Orden</label>
                                            <input
                                                type="number"
                                                wire:model="formField.orden"
                                                id="orden"
                                                min="0"
                                                class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                                            >
                                            @error('formField.orden')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="flex items-center">
                                            <input
                                                type="checkbox"
                                                wire:model="formField.obligatorio"
                                                id="obligatorio"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            >
                                            <label for="obligatorio" class="ml-2 block text-sm text-gray-900">
                                                Campo obligatorio
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="saveField"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Guardar
                            </button>
                            <button
                                wire:click="closeFieldModal"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de confirmación para eliminar zona -->
        @if ($confirmingZonaDeletion)
            <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Eliminar Zona
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            ¿Estás seguro de que deseas eliminar esta zona? Esta acción eliminará también todos los campos asociados y no se puede deshacer.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="deleteZona"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Eliminar
                            </button>
                            <button
                                wire:click="$set('confirmingZonaDeletion', false)"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de confirmación para eliminar campo -->
        @if ($confirmingFieldDeletion)
            <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Eliminar Campo
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            ¿Estás seguro de que deseas eliminar este campo del formulario? Esta acción no se puede deshacer.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="deleteField"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Eliminar
                            </button>
                            <button
                                wire:click="$set('confirmingFieldDeletion', false)"
                                type="button"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de instrucciones de instalación (Versión simplificada) -->
        @if ($showInstructionsModal)
            <div id="instructions-modal" class="fixed z-50 inset-0 overflow-y-auto" style="display: block !important;">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div>

                    <!-- Modal content -->
                    <div class="relative bg-white rounded-lg w-full max-w-2xl mx-auto p-6" style="z-index: 100;">
                        <h3 class="text-xl font-bold mb-4">Instrucciones de Instalación</h3>

                        @if($activeZonaForInstructions)
                            <h4 class="font-semibold text-lg mb-3">Zona: {{ $activeZonaForInstructions->nombre }}</h4>

                            <!-- Pasos sencillos -->
                            <div class="mb-4 p-3 bg-gray-50 rounded">
                                <h5 class="font-bold">1. Descargar archivos</h5>
                                <p>Descarga los archivos login.html y alogin.html desde el menú de acciones.</p>
                            </div>

                            <div class="mb-4 p-3 bg-gray-50 rounded">
                                <h5 class="font-bold">2. Subir a Mikrotik</h5>
                                <p>Sube los archivos a tu router Mikrotik en la carpeta Hotspot.</p>
                            </div>

                            <div class="mb-4 p-3 bg-gray-50 rounded">
                                <h5 class="font-bold">3. Configurar autenticación</h5>
                                <p>Tipo de autenticación configurado: <strong>{{ $activeZonaForInstructions->tipo_autenticacion_mikrotik_label }}</strong></p>
                            </div>
                        @else
                            <p class="text-red-500">No se ha seleccionado ninguna zona.</p>
                        @endif

                        <!-- Botón de cerrar -->
                        <div class="mt-6 flex justify-end">
                            <button
                                wire:click="closeInstructionsModal"
                                type="button"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Instrucciones de Instalación - {{ $activeZonaForInstructions->nombre }}
                                    </h3>
                                    <div class="mt-2">
                                        <h4 class="font-bold text-base mb-2">Configuración en Mikrotik RouterOS</h4>

                                        <div class="mb-4 p-3 bg-gray-50 rounded-md">
                                            <h5 class="font-bold mb-2">1. Configuración de Hotspot</h5>
                                            <p class="mb-2">Para configurar correctamente el portal cautivo en Mikrotik con los archivos proporcionados:</p>
                                            <ol class="list-decimal pl-5 space-y-2">
                                                <li>Accede a tu router Mikrotik mediante WinBox o SSH.</li>
                                                <li>Ve a IP > Hotspot y configura un nuevo servidor hotspot.</li>
                                                <li>Después de la configuración básica, accede a la carpeta de archivos HTML.</li>
                                                <li>Reemplaza los archivos login.html y alogin.html con los que has descargado.</li>
                                            </ol>
                                        </div>

                                        <div class="mb-4 p-3 bg-gray-50 rounded-md">
                                            <h5 class="font-bold mb-2">2. Tipo de Autenticación: {{ $activeZonaForInstructions->tipo_autenticacion_mikrotik_label }}</h5>
                                            <p class="mb-2">Esta zona está configurada para autenticación por {{ $activeZonaForInstructions->tipo_autenticacion_mikrotik_label }}.</p>

                                            @if($activeZonaForInstructions->tipo_autenticacion_mikrotik == 'pin')
                                                <p>Para la autenticación por PIN:</p>
                                                <ul class="list-disc pl-5 space-y-1">
                                                    <li>Los usuarios solo necesitan ingresar un código PIN para autenticarse.</li>
                                                    <li>Debes crear usuarios en Mikrotik donde el nombre de usuario sea el PIN.</li>
                                                    <li>La contraseña puede ser la misma que el PIN o dejarla vacía.</li>
                                                </ul>
                                            @else
                                                <p>Para la autenticación por Usuario y Contraseña:</p>
                                                <ul class="list-disc pl-5 space-y-1">
                                                    <li>Los usuarios deberán ingresar tanto el nombre de usuario como la contraseña.</li>
                                                    <li>Debes crear usuarios en Mikrotik con sus respectivos nombres y contraseñas.</li>
                                                </ul>
                                            @endif
                                        </div>

                                        <div class="p-3 bg-gray-50 rounded-md">
                                            <h5 class="font-bold mb-2">3. Ajustes adicionales</h5>
                                            <ul class="list-disc pl-5 space-y-2">
                                                <li>En Hotspot > Server Profiles, asegúrate de configurar la URL de redirección a tu portal: <span class="font-mono bg-gray-200 px-1 rounded">https://i-free.com.mx/login_formulario/{{ $activeZonaForInstructions->login_form_id }}</span></li>
                                                <li>Si necesitas personalizar los archivos HTML descargados, puedes editar los estilos o agregar tu logo.</li>
                                                <li>Recuerda ajustar las reglas de firewall si es necesario para permitir el tráfico adecuado.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                wire:click="closeInstructionsModal"
                                type="button"
                                class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
