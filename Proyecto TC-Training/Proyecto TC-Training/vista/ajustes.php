<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ajustes | <?php echo $config['nombre_empresa']; ?></title>
    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="../responsive.css">
    <script src="../jv/script.js" defer></script>
</head>
<body>
    <header class="topbar">
        <button id="hamburger-btn" class="hamburger-menu" aria-label="Abrir menú">☰</button>

        <a href="../controlador/InicioController.php" class="brand">
            <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
            TC-Control
        </a>
        <section class="user-actions">
            <span class="text-muted"><?php echo htmlspecialchars($_SESSION['rol_nombre'] ?? 'Usuario'); ?></span>
            <a href="../controlador/logout.php" class="btn btn-primary btn-sm">Salir</a>
        </section>
    </header>

    
    <main class="dashboard-layout">
        
        <svg class="svg-hidden" style="display: none;">
            <symbol id="icon-menu" viewBox="0 0 24 24">
                <line x1="3" y1="12" x2="21" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
                <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
                <line x1="3" y1="18" x2="21" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"></line>
            </symbol>
            <symbol id="icon-home" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></symbol>
            <symbol id="icon-users" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></symbol>
            <symbol id="icon-payments" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></symbol>
            <symbol id="icon-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></symbol>
        </svg>
        
        <!-- <header class="topbar">
             Botón del Menú Hamburguesa 
            <button id="menu-toggle" class="menu-toggle" aria-label="Abrir menú" style="background: none; border: none; cursor: pointer; padding: 0.5rem; display: flex; align-items: center;">
                <svg width="24" height="24" fill="none">
                    <use href="#icon-menu"></use>
                </svg>
            </button>

            <a href="../controlador/InicioController.php" class="brand">
                <img src="../Imagenes/472842978_1608453203376849_549310859470506009_n.jpg" alt="Logo">
                <?php echo $config['nombre_empresa']; ?>
            </a>
            
            <section class="user-actions" style="margin-left: auto;">
                <span class="text-muted">Administrador</span>
                <a href="../controlador/logout.php" class="btn btn-primary btn-sm">Salir</a>
            </section>
        </header> -->

        <section class="layout-body">
            <?php $active = 'ajustes'; include __DIR__ . '/partes/sidebar.php'; ?>

            <article class="main-content">
                <header class="page-header">
                    <section>
                        <h1 class="page-title font-lexend">Ajustes del Sistema</h1>
                        <p class="page-subtitle">Configura las preferencias de la aplicación</p>
                    </section>
                    <section>
                        <button type="submit" form="settings-form" class="btn btn-primary">Guardar Cambios</button>
                    </section>
                </header>

                <?php if(!empty($mensaje) && is_array($mensaje)): ?>
                    <script>
                        document.body.dataset.serverMessage = <?php echo json_encode($mensaje['text']); ?>;
                        document.body.dataset.serverMessageType = <?php echo json_encode($mensaje['type']); ?>;
                    </script>
                    <section class="alert-container">
                        <article class="alert <?php echo ($mensaje['type'] ?? '') === 'success' ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlspecialchars($mensaje['text'] ?? ''); ?></article>
                    </section>
                <?php endif; ?>

                <section class="card center-card">
                    <header class="card-header">
                        <h2 class="card-title font-lexend">Configuración General</h2>
                    </header>
                    <article class="card-body">
                        <form id="settings-form" action="../controlador/AjustesControlador.php" method="POST">
                            <fieldset class="form-group">
                                <label for="gym-name" class="form-label">Nombre de la Empresa</label>
                                <input type="text" id="gym-name" name="gym-name" class="form-control" value="<?php echo htmlspecialchars($config['nombre_empresa']); ?>">
                            </fieldset>
                            
                            <fieldset class="form-group">
                                <label class="form-label">Paleta de Colores</label>
                                <section class="flex-gap">
                                    <label><input type="radio" name="palette" value="default" checked> Default</label>
                                    <label><input type="radio" name="palette" value="ocean"> Ocean</label>
                                    <label><input type="radio" name="palette" value="sunset"> Sunset</label>
                                    <label><input type="radio" name="palette" value="custom"> Personalizada</label>
                                    <select id="palette-mode-select" name="palette_mode" class="select-inline">
                                        <option value="light">Claro</option>
                                        <option value="dark">Oscuro</option>
                                    </select>
                                </section>
                                <section class="flex-gap custom-palette-colors" aria-label="Colores personalizados">
                                    <label for="custom-color-primary">Color principal</label>
                                    <input type="color" id="custom-color-primary" data-custom-color="primary" value="#e30613" aria-label="Color principal">
                                    <label for="custom-color-primary-hover">Color al pasar el cursor</label>
                                    <input type="color" id="custom-color-primary-hover" data-custom-color="primary-hover" value="#b5000b" aria-label="Color al pasar el cursor">
                                    <label for="custom-color-background">Fondo</label>
                                    <input type="color" id="custom-color-background" data-custom-color="background" value="#e9ecef" aria-label="Fondo">
                                    <label for="custom-color-surface">Superficies</label>
                                    <input type="color" id="custom-color-surface" data-custom-color="surface" value="#f8f9fa" aria-label="Superficies">
                                    <label for="custom-color-text">Texto</label>
                                    <input type="color" id="custom-color-text" data-custom-color="text-main" value="#212529" aria-label="Texto">
                                    <label for="custom-color-border">Bordes</label>
                                    <input type="color" id="custom-color-border" data-custom-color="border" value="#ced4da" aria-label="Bordes">
                                </section>
                                <p class="muted-note">Cada usuario puede elegir su paleta y modo; se almacenará en su navegador.</p>
                            </fieldset>
                            <fieldset class="form-group">
                                <label class="form-label">Días para próximos vencimientos</label>
                                <input type="number" name="vencimiento-dias" min="1" max="365" class="form-control" value="<?php echo isset($config['vencimiento_dias']) ? intval($config['vencimiento_dias']) : 7; ?>">
                                <p class="muted-note">Número de días antes del vencimiento para mostrar al cliente en la lista de próximos vencimientos.</p>
                            </fieldset>
                            <fieldset class="form-group">
                                <label class="form-label">Período de ingresos</label>
                                <select name="ingresos-periodo" class="form-control">
                                    <?php $selectedPeriod = $config['ingresos_periodo'] ?? 'semanal'; ?>
                                    <option value="semanal" <?php echo $selectedPeriod === 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                                    <option value="mensual" <?php echo $selectedPeriod === 'mensual' ? 'selected' : ''; ?>>Mensual</option>
                                    <option value="anual" <?php echo $selectedPeriod === 'anual' ? 'selected' : ''; ?>>Anual</option>
                                </select>
                                <p class="muted-note">Selecciona si los ingresos se calculan por semana, mes o año actual.</p>
                            </fieldset>
                        </form>
                    </article>
                </section>

                <?php if ($esAdmin): ?>
                <section class="center-card center-card-spaced">
                    <section class="flex-gap">
                        <button type="button" id="toggle-admin-management" class="btn btn-outline btn-flex" onclick="toggleAdminPanel()">Gestión de Usuarios</button>
                        <a href="../controlador/RegistroAdminControlador.php" class="btn btn-primary btn-sm">Registrar Usuarios</a>
                    </section>
                </section>
                <?php endif; ?>

                <?php if ($esAdmin): ?>
                <section id="admin-management-card" class="card center-card hidden mb-1">
                    <header class="card-header">
                        <h2 class="card-title font-lexend">Gestión de Usuarios</h2>
                    </header>
                    <article class="card-body">
                        <section class="table-responsive">
                            <table class="payments-table tabla-usuarios">
                                <thead>
                                    <tr><th>Usuario</th><th>Rol</th><th>Cambiar rol</th><th>Acciones</th></tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($admins)): ?>
                                    <?php foreach ($admins as $a): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($a['Usuario']); ?></td>
                                            <td><?php echo htmlspecialchars($a['rol'] ?? 'Sin rol'); ?></td>
                                            <td>
                                                <form action="../controlador/UsuariosControlador.php" method="POST" class="flex-gap">
                                                    <input type="hidden" name="accion" value="cambiar_rol">
                                                    <input type="hidden" name="id_admin" value="<?php echo (int) $a['id_admin']; ?>">
                                                    <select name="id_rol" class="select-inline select-rol" data-id="<?php echo (int) $a['id_admin']; ?>" data-actual="<?php echo (int) $a['id_rol']; ?>">
                                                        <?php foreach ($roles as $r): ?>
                                                            <option value="<?php echo (int) $r['id_rol']; ?>" <?php echo (int) $r['id_rol'] === (int) $a['id_rol'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['nombre']); ?></option>
                                                        <?php endforeach; ?>
                                                        <option value="nuevo">➕ Nuevo rol...</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-outline btn-sm">Cambiar rol</button>
                                                </form>
                                            </td>
                                            <td>
                                                <section class="flex-gap">
                                                    <button type="button" class="btn btn-outline btn-sm btn-modificar-usuario" data-id="<?php echo (int) $a['id_admin']; ?>" data-usuario="<?php echo htmlspecialchars($a['Usuario']); ?>">Modificar</button>
                                                    <form action="../controlador/UsuariosControlador.php" method="POST" onsubmit="return confirm('¿Eliminar este usuario?');">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="id_admin" value="<?php echo (int) $a['id_admin']; ?>">
                                                        <button type="submit" class="btn btn-outline btn-sm">Eliminar</button>
                                                    </form>
                                                </section>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4">No hay usuarios registrados.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </section>
                    </article>
                </section>

                <dialog id="dialogo-nuevo-rol">
                    <form action="../controlador/UsuariosControlador.php" method="POST">
                        <h2 class="card-title font-lexend">Nuevo rol</h2>
                        <input type="hidden" name="accion" value="nuevo_rol">
                        <input type="hidden" name="id_admin" id="nuevo-rol-id-admin" value="">
                        <fieldset class="form-group">
                            <label for="nombre-rol" class="form-label">Tipo de rol</label>
                            <input type="text" id="nombre-rol" name="nombre_rol" class="form-control" placeholder="Ej: Contador" required>
                        </fieldset>
                        <fieldset class="lista-modulos">
                            <legend>Módulos a los que tendrá acceso</legend>
                            <?php foreach (Rol::MODULOS as $clave => $etiqueta): ?>
                                <label><input type="checkbox" name="modulos[]" value="<?php echo $clave; ?>"> <?php echo $etiqueta; ?></label>
                            <?php endforeach; ?>
                        </fieldset>
                        <footer class="dialogo-acciones">
                            <button type="button" class="btn btn-outline btn-sm btn-cerrar-dialogo">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm">Crear y asignar</button>
                        </footer>
                    </form>
                </dialog>

                <dialog id="dialogo-modificar">
                    <form action="../controlador/UsuariosControlador.php" method="POST">
                        <h2 class="card-title font-lexend">Modificar usuario</h2>
                        <input type="hidden" name="accion" value="modificar">
                        <input type="hidden" name="id_admin" id="modificar-id-admin" value="">
                        <fieldset class="form-group">
                            <label for="modificar-usuario" class="form-label">Usuario</label>
                            <input type="text" id="modificar-usuario" name="usuario" class="form-control" required>
                        </fieldset>
                        <fieldset class="form-group">
                            <label for="modificar-password" class="form-label">Nueva contraseña</label>
                            <input type="password" id="modificar-password" name="password" class="form-control" placeholder="Dejar vacío para no cambiarla">
                        </fieldset>
                        <footer class="dialogo-acciones">
                            <button type="button" class="btn btn-outline btn-sm btn-cerrar-dialogo">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
                        </footer>
                    </form>
                </dialog>
                <?php endif; ?>
            </article>
        </section>

    </main>
</body>
</html>
