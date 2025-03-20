<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Sistema de Rifas</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-2xl">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Instalador del Sistema de Rifas</h1>
                <p class="text-gray-600 mt-2">Complete la información necesaria para configurar su sistema</p>
            </div>

            <form id="installerForm" method="POST" action="process.php" enctype="multipart/form-data" class="space-y-6">
                <!-- Modo de instalación -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Modo de instalación</label>
                    <div class="flex space-x-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="installation_mode" value="localhost" class="form-radio" checked>
                            <span class="ml-2">Localhost</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="installation_mode" value="remote" class="form-radio">
                            <span class="ml-2">Remoto</span>
                        </label>
                    </div>
                </div>

                <!-- Configuración de Base de Datos -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-800">Configuración de Base de Datos</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Host</label>
                            <input type="text" name="db_host" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" value="localhost" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nombre de Base de Datos</label>
                            <input type="text" name="db_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Usuario</label>
                            <input type="text" name="db_user" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                            <input type="password" name="db_pass" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Administrador -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-800">Configuración de Administrador</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Usuario Admin</label>
                            <input type="text" name="admin_user" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contraseña Admin</label>
                            <input type="password" name="admin_pass" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                    </div>
                </div>

                <!-- Configuración de WhatsApp -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold text-gray-800">Configuración de WhatsApp</h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Código de País</label>
                            <input type="text" name="whatsapp_code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="+57" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número de WhatsApp</label>
                            <input type="text" name="whatsapp_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="3001234567" required>
                        </div>
                    </div>
                </div>

                <!-- Logo -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Logo del Sistema</label>
                    <input type="file" name="logo" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                </div>

                <!-- Botón de instalación -->
                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                        Instalar Sistema
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('installerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Aquí irá la lógica de validación y envío del formulario
            this.submit();
        });
    </script>
</body>
</html>