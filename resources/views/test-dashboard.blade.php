<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MikroWISP - Panel Administrativo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fff5f0',
                            100: '#ffece3',
                            200: '#ffd5c2',
                            300: '#ffb399',
                            400: '#ff8056',
                            500: '#ff5e2c',
                            600: '#ff3f00',
                            700: '#e63300',
                            800: '#cc2d00',
                            900: '#a82900',
                            950: '#591100',
                        },
                        dark: '#1E293B',
                        lightblue: '#EBF5FF',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .sidebar-item {
                @apply flex items-center py-3 px-4 text-gray-200 hover:bg-gray-700 rounded-lg mb-1;
            }
            .sidebar-item.active {
                @apply bg-gray-700;
            }
            .card {
                @apply bg-white rounded-lg shadow-md p-4 relative overflow-hidden;
            }
            .card-header {
                @apply text-lg font-bold mb-2;
            }
            .card-value {
                @apply text-3xl font-bold;
            }
            .card-footer {
                @apply mt-2 text-sm;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-dark text-white flex flex-col">
            <!-- Logo -->
            <div class="p-4 bg-primary-600">
                <div class="flex items-center">
                    <img src="https://via.placeholder.com/40" alt="MikroWISP Logo" class="mr-2">
                    <h1 class="text-xl font-bold">MikroWISP</h1>
                </div>
            </div>

            <!-- Admin Profile -->
            <div class="p-4 flex flex-col items-center border-b border-gray-700">
                <div class="w-16 h-16 rounded-full bg-gray-400 mb-2 overflow-hidden">
                    <img src="https://via.placeholder.com/100" alt="Admin" class="w-full h-full object-cover">
                </div>
                <div class="text-center">
                    <h2 class="font-bold">Administrador principal</h2>
                    <p class="text-sm text-gray-400">Administrador</p>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="py-4 flex-1 overflow-y-auto">
                <p class="px-4 text-xs text-gray-400 mb-2">Menú</p>

                <a href="#" class="sidebar-item active">
                    <i class="fas fa-home mr-3 w-5 text-center"></i> Inicio
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-network-wired mr-3 w-5 text-center"></i> Gestión de Red
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-cogs mr-3 w-5 text-center"></i> Servicios
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-users mr-3 w-5 text-center"></i> Clientes
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-wifi mr-3 w-5 text-center"></i> Fichas Hotspot
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-tasks mr-3 w-5 text-center"></i> Tareas
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-money-bill-wave mr-3 w-5 text-center"></i> Finanzas
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-warehouse mr-3 w-5 text-center"></i> Almacén
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-ticket-alt mr-3 w-5 text-center"></i> Tickets
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-envelope mr-3 w-5 text-center"></i> Mensajería
                </a>
                <a href="#" class="sidebar-item">
                    <i class="fas fa-sliders-h mr-3 w-5 text-center"></i> Ajustes
                </a>
            </div>

            <!-- Collapse Button -->
            <div class="p-4 border-t border-gray-700">
                <button class="w-full text-center">
                    <i class="fas fa-angle-double-left"></i>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <div class="bg-white shadow-sm flex justify-between items-center p-4">
                <div class="flex items-center">
                    <a href="#" class="text-gray-500 mr-4">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <a href="#" class="text-gray-500 mr-4">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#" class="text-gray-500 mr-4">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                    <div class="relative flex-1 max-w-xl">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-search text-gray-400"></i>
                        </span>
                        <input type="text" value="https://demo.mikrosystem.net/admin/" class="pl-10 pr-4 py-2 w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-600">
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="#" class="text-gray-500 mr-4">
                        <i class="fas fa-paper-plane"></i>
                    </a>
                    <a href="#" class="text-gray-500 mr-4">
                        <i class="fas fa-dollar-sign"></i>
                    </a>
                    <a href="#" class="text-gray-500 mr-4">
                        <i class="fas fa-bell"></i>
                    </a>
                    <a href="#" class="text-gray-500 mr-4">
                        <i class="fas fa-user-circle"></i>
                    </a>
                    <div class="flex items-center">
                        <img src="https://via.placeholder.com/32" alt="Profile" class="w-8 h-8 rounded-full">
                        <span class="ml-2">ADMIN</span>
                        <i class="fas fa-chevron-down ml-1 text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 bg-gray-100">
                <h1 class="text-2xl font-bold mb-6">Bienvenido <span class="text-gray-500">Administrador principal</span></h1>

                <!-- Dashboard Cards -->                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <!-- Online Clients Card -->
                    <div class="card bg-primary-500 text-white">
                        <div class="card-header">CLIENTES ONLINE</div>
                        <div class="card-value">0</div>
                        <div class="card-footer">Total Registrados 65</div>
                        <a href="#" class="absolute bottom-4 right-4 text-white flex items-center">
                            <span class="mr-1">Ver clientes</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/10 -mr-12 -mt-12"></div>
                        <div class="absolute top-20 right-10 w-16 h-16 rounded-full bg-white/10"></div>
                    </div>

                    <!-- Transactions Card -->
                    <div class="card bg-primary-600 text-white">
                        <div class="card-header">TRANSACCIONES HOY</div>
                        <div class="card-value">$ 0,00</div>
                        <div class="card-footer">Cobrado este mes $ 720,00</div>
                        <a href="#" class="absolute bottom-4 right-4 text-white flex items-center">
                            <span class="mr-1">Ver transacciones</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/10 -mr-12 -mt-12"></div>
                        <div class="absolute top-20 right-10 w-16 h-16 rounded-full bg-white/10"></div>
                    </div>

                    <!-- Unpaid Invoices Card -->
                    <div class="card bg-primary-700 text-white">
                        <div class="card-header">FACTURAS NO PAGADAS</div>
                        <div class="card-value">0</div>
                        <div class="card-footer">Total vencidas 0</div>
                        <a href="#" class="absolute bottom-4 right-4 text-white flex items-center">
                            <span class="mr-1">Ver Facturas</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/10 -mr-12 -mt-12"></div>
                        <div class="absolute top-20 right-10 w-16 h-16 rounded-full bg-white/10"></div>
                    </div>

                    <!-- Support Tickets Card -->
                    <div class="card bg-primary-800 text-white">
                        <div class="card-header">TICKET SOPORTE</div>
                        <div class="card-value">5</div>
                        <div class="card-footer">Total Abiertos 6</div>
                        <a href="#" class="absolute bottom-4 right-4 text-white flex items-center">
                            <span class="mr-1">Ver Tickets</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/10 -mr-12 -mt-12"></div>
                        <div class="absolute top-20 right-10 w-16 h-16 rounded-full bg-white/10"></div>
                    </div>
                </div>

                <!-- Traffic Chart and System Summary -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Traffic Chart -->
                    <div class="card">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-bold text-lg">Tráfico Clientes</h3>
                            <span class="text-sm text-gray-500">Últimos 7 días</span>
                        </div>

                        <div class="mb-4">
                            <!-- Simple chart representation -->
                            <div class="h-64 bg-gray-100 rounded-lg relative">
                                <div class="absolute left-0 bottom-0 w-full h-full flex items-end">
                                    <!-- Chart bars -->
                                    <div class="w-1/6 h-1/3 bg-primary-500 mx-1 rounded-t-lg"></div>
                                    <div class="w-1/6 h-1/2 bg-primary-500 mx-1 rounded-t-lg"></div>
                                    <div class="w-1/6 h-2/5 bg-primary-500 mx-1 rounded-t-lg"></div>
                                    <div class="w-1/6 h-3/5 bg-primary-500 mx-1 rounded-t-lg"></div>
                                    <div class="w-1/6 h-2/3 bg-primary-500 mx-1 rounded-t-lg"></div>
                                    <div class="w-1/6 h-4/5 bg-primary-500 mx-1 rounded-t-lg"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Traffic stats -->
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-bold text-2xl">1345.5 GB</p>
                                <p class="text-sm text-gray-500">Total tráfico</p>
                            </div>

                            <!-- Donut chart -->
                            <div class="relative w-32 h-32">
                                <div class="w-32 h-32 rounded-full bg-primary-100 absolute"></div>
                                <div class="w-32 h-32 rounded-full overflow-hidden absolute">
                                    <div class="w-32 h-32 bg-primary-600 absolute" style="clip-path: polygon(50% 50%, 50% 0, 100% 0, 100% 100%, 0 100%, 0 70%, 50% 50%);"></div>
                                </div>
                                <div class="w-24 h-24 bg-white rounded-full absolute top-4 left-4 flex items-center justify-center">
                                    <div>
                                        <p class="font-bold text-2xl text-center">86%</p>
                                        <p class="text-xs text-center">DESCARGA</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Traffic details -->
                        <div class="flex justify-between mt-4">
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-primary-600 rounded-full mr-2"></span>
                                <span class="text-sm">1151.1 GB Descarga</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-3 h-3 bg-primary-400 rounded-full mr-2"></span>
                                <span class="text-sm">194.4 GB Subida</span>
                            </div>
                        </div>
                    </div>

                    <!-- System Summary -->
                    <div class="card">
                        <h3 class="font-bold text-lg mb-4">Resumen del sistema</h3>

                        <ul class="space-y-3">
                            <li class="flex justify-between items-center p-2 rounded-lg hover:bg-gray-50">
                                <span>1. Routers Activos</span>
                                <span class="bg-primary-400 text-white px-2 py-1 rounded-full">2</span>
                            </li>
                            <li class="flex justify-between items-center p-2 rounded-lg hover:bg-gray-50">
                                <span>2. Routers desconectados</span>
                                <span class="bg-primary-900 text-white px-2 py-1 rounded-full">0</span>
                            </li>
                            <li class="flex justify-between items-center p-2 rounded-lg hover:bg-gray-50">
                                <span>3. Clientes Activos</span>
                                <span class="bg-primary-500 text-white px-2 py-1 rounded-full">40</span>
                            </li>
                            <li class="flex justify-between items-center p-2 rounded-lg hover:bg-gray-50">
                                <span>4. Clientes suspendidos</span>
                                <span class="bg-primary-700 text-white px-2 py-1 rounded-full">37</span>
                            </li>
                            <li class="flex justify-between items-center p-2 rounded-lg hover:bg-gray-50">
                                <span>5. Servicios Activos</span>
                                <span class="bg-primary-300 text-white px-2 py-1 rounded-full">29</span>
                            </li>
                            <li class="flex justify-between items-center p-2 rounded-lg hover:bg-gray-50">
                                <span>6. Monitoreo Activos</span>
                                <span class="bg-primary-400 text-white px-2 py-1 rounded-full">2</span>
                            </li>
                            <li class="flex justify-between items-center p-2 rounded-lg hover:bg-gray-50">
                                <span>7. Monitoreo Caídos</span>
                                <span class="bg-primary-800 text-white px-2 py-1 rounded-full">1</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
