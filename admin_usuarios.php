<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['rol'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Función para registrar acciones en logs
function registrar_log($conn, $usuario_id, $evento) {
    $sql_log = "INSERT INTO logs (usuario_id, evento) VALUES (?, ?)";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bind_param("is", $usuario_id, $evento);
    $stmt_log->execute();
    $stmt_log->close();
}

// --- CRUD USUARIOS ---
if (isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];

    // Validar correo único
    $sql_check = "SELECT id FROM usuarios WHERE correo = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $correo);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensaje'] = "El correo ya está registrado";
        $_SESSION['tipo_mensaje'] = "error";
    } else {
        $sql = "INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $correo, $contrasena, $rol);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Usuario agregado correctamente";
            $_SESSION['tipo_mensaje'] = "exito";
            registrar_log($conn, $_SESSION['usuario_id'], "Agregó usuario: $nombre ($rol)");
        } else {
            $_SESSION['mensaje'] = "Error al agregar usuario";
            $_SESSION['tipo_mensaje'] = "error";
        }
        $stmt->close();
    }
    $stmt_check->close();
    header("Location: admin_usuarios.php");
    exit;
}

if (isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $rol = $_POST['rol'];

    // Verificar si el correo ya existe para otro usuario
    $sql_check = "SELECT id FROM usuarios WHERE correo = ? AND id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $correo, $id);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensaje'] = "El correo ya está registrado para otro usuario";
        $_SESSION['tipo_mensaje'] = "error";
    } else {
        $sql = "UPDATE usuarios SET nombre=?, correo=?, rol=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nombre, $correo, $rol, $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Usuario actualizado correctamente";
            $_SESSION['tipo_mensaje'] = "exito";
            registrar_log($conn, $_SESSION['usuario_id'], "Editó usuario ID $id: $nombre ($rol)");
        } else {
            $_SESSION['mensaje'] = "Error al actualizar usuario";
            $_SESSION['tipo_mensaje'] = "error";
        }
        $stmt->close();
    }
    $stmt_check->close();
    header("Location: admin_usuarios.php");
    exit;
}

if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    
    // No permitir eliminarse a sí mismo
    if ($id == $_SESSION['usuario_id']) {
        $_SESSION['mensaje'] = "No puedes eliminarte a ti mismo";
        $_SESSION['tipo_mensaje'] = "error";
    } else {
        $sql = "DELETE FROM usuarios WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Usuario eliminado correctamente";
            $_SESSION['tipo_mensaje'] = "exito";
            registrar_log($conn, $_SESSION['usuario_id'], "Eliminó usuario ID $id");
        } else {
            $_SESSION['mensaje'] = "Error al eliminar usuario";
            $_SESSION['tipo_mensaje'] = "error";
        }
        $stmt->close();
    }
    header("Location: admin_usuarios.php");
    exit;
}

// --- ASIGNACIONES ---
if (isset($_POST['accion']) && $_POST['accion'] === 'asignar') {
    $cuidador_id = $_POST['cuidador_id'];
    $paciente_id = $_POST['paciente_id'];

    // Verificar si la asignación ya existe
    $sql_check = "SELECT id FROM asignaciones WHERE cuidador_id = ? AND paciente_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $cuidador_id, $paciente_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensaje'] = "Esta asignación ya existe";
        $_SESSION['tipo_mensaje'] = "error";
    } else {
        $sql = "INSERT INTO asignaciones (cuidador_id, paciente_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cuidador_id, $paciente_id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Asignación creada correctamente";
            $_SESSION['tipo_mensaje'] = "exito";
            registrar_log($conn, $_SESSION['usuario_id'], "Asignó paciente ID $paciente_id al cuidador ID $cuidador_id");
        } else {
            $_SESSION['mensaje'] = "Error al crear asignación";
            $_SESSION['tipo_mensaje'] = "error";
        }
        $stmt->close();
    }
    $stmt_check->close();
    header("Location: admin_usuarios.php?tab=asignaciones");
    exit;
}

if (isset($_GET['eliminar_asignacion'])) {
    $id = $_GET['eliminar_asignacion'];

    $sql = "DELETE FROM asignaciones WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Asignación eliminada correctamente";
        $_SESSION['tipo_mensaje'] = "exito";
        registrar_log($conn, $_SESSION['usuario_id'], "Eliminó asignación ID $id");
    } else {
        $_SESSION['mensaje'] = "Error al eliminar asignación";
        $_SESSION['tipo_mensaje'] = "error";
    }
    $stmt->close();
    header("Location: admin_usuarios.php?tab=asignaciones");
    exit;
}

// Obtener datos para mostrar
$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY rol, nombre")->fetch_all(MYSQLI_ASSOC);
$cuidadores = $conn->query("SELECT * FROM usuarios WHERE rol='cuidador' ORDER BY nombre, correo")->fetch_all(MYSQLI_ASSOC);
$pacientes = $conn->query("SELECT * FROM usuarios WHERE rol='paciente' ORDER BY nombre, correo")->fetch_all(MYSQLI_ASSOC);
$asignaciones = $conn->query("SELECT a.id, c.nombre AS cuidador, p.nombre AS paciente
                            FROM asignaciones a
                            JOIN usuarios c ON a.cuidador_id = c.id
                            JOIN usuarios p ON a.paciente_id = p.id
                            ORDER BY c.nombre, p.nombre")->fetch_all(MYSQLI_ASSOC);
$logs = $conn->query("SELECT l.*, u.nombre FROM logs l LEFT JOIN usuarios u ON l.usuario_id = u.id ORDER BY fecha_hora DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Determinar qué tab mostrar
$tab_activa = isset($_GET['tab']) ? $_GET['tab'] : 'usuarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios | Medicontrol</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #2c7be5;
            --secondary: #6c757d;
            --success: #00d97e;
            --danger: #e63757;
            --warning: #f6c343;
            --info: #39afd1;
            --light: #f8f9fa;
            --dark: #283e59;
            --white: #ffffff;
            --gray: #95aac9;
            --gray-dark: #4a5c7b;
            --border-radius: 0.375rem;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        
        /* Responsive Styles */
@media (max-width: 992px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
}
        
        /* Sidebar Styles */
.sidebar {
    width: var(--sidebar-width);
    background: var(--white);
    box-shadow: var(--box-shadow);
    position: fixed;
    height: 100vh;
    padding: 20px 0;
    display: flex;
    flex-direction: column;
    z-index: 100;
    transition: var(--transition);
}

.sidebar-header {
    padding: 0 20px 20px;
    border-bottom: 1px solid var(--gray-light);
}

.sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.sidebar-nav ul {
    list-style: none;
}

.sidebar-nav li a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: var(--gray);
    text-decoration: none;
    transition: var(--transition);
    gap: 12px;
    font-weight: 500;
}

.sidebar-nav li a:hover,
.sidebar-nav li a.active {
    color: var(--primary);
    background: var(--primary-light);
}

.sidebar-nav li a i {
    font-size: 1.1rem;
    width: 24px;
    text-align: center;
}

.sidebar-footer {
    padding: 20px;
    border-top: 1px solid var(--gray-light);
}
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .avatar {
            font-size: 2.5rem;
            margin-right: 15px;
            color: var(--gray);
        }
        
        .user-info h3 {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 10px;
        }
        
        .badge-primary {
            background-color: rgba(44, 123, 229, 0.1);
            color: var(--primary);
        }
        
        .badge-success {
            background-color: rgba(0, 217, 126, 0.1);
            color: var(--success);
        }
        
        .badge-info {
            background-color: rgba(57, 175, 209, 0.1);
            color: var(--info);
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar-nav ul {
            list-style: none;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--gray);
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .sidebar-nav a:hover {
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-nav a.active {
            color: var(--white);
            background-color: var(--primary);
        }
        
        .sidebar-nav i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-nav span {
            flex: 1;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logout-link {
            display: flex;
            align-items: center;
            color: var(--gray);
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .logout-link:hover {
            color: var(--danger);
        }
        @media (max-width: 768px) {
    .main-content {
        padding: 20px 15px;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5eb;
        }
        
        .main-header h1 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .current-time {
            display: flex;
            align-items: center;
            color: var(--gray-dark);
            font-size: 14px;
        }
        
        .current-time i {
            margin-right: 5px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .subtitle {
            color: var(--gray-dark);
            font-size: 14px;
            font-weight: 400;
        }
        
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-primary {
            color: var(--white);
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #1c6ad8;
            border-color: #1b65d0;
        }
        
        .btn-danger {
            color: var(--white);
            background-color: var(--danger);
            border-color: var(--danger);
        }
        
        .btn-danger:hover {
            background-color: #d52d4d;
            border-color: #c92947;
        }
        
        .btn-secondary {
            color: var(--white);
            background-color: var(--secondary);
            border-color: var(--secondary);
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e1e5eb;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            color: var(--gray-dark);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .tab-btn:hover {
            color: var(--primary);
        }
        
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            padding: 20px;
        }
        
        .card-header {
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e1e5eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-weight: 600;
            color: var(--primary);
            margin: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e1e5eb;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e1e5eb;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(44, 123, 229, 0.25);
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .form-col {
            flex: 1;
            padding: 0 10px;
            min-width: 200px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background-color: rgba(0, 217, 126, 0.1);
            color: #008a50;
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background-color: rgba(230, 55, 87, 0.1);
            color: #c82333;
            border-left: 4px solid var(--danger);
        }
        
        .alert i {
            margin-right: 10px;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 10px;
        }
        
        .badge-primary {
            background-color: rgba(44, 123, 229, 0.1);
            color: var(--primary);
        }
        
        .badge-success {
            background-color: rgba(0, 217, 126, 0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background-color: rgba(246, 195, 67, 0.1);
            color: #c69500;
        }
        
        .badge-danger {
            background-color: rgba(230, 55, 87, 0.1);
            color: var(--danger);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                overflow: hidden;
            }
            
            .sidebar-header, .sidebar-footer {
                display: none;
            }
            
            .sidebar-nav span {
                display: none;
            }
            
            .sidebar-nav i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .sidebar-nav a {
                justify-content: center;
                padding: 15px 0;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .form-col {
                flex: 100%;
                margin-bottom: 15px;
            }
            
            .tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="user-info">
                <div class="avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($_SESSION['nombre']); ?></h3>
                    <span class="badge badge-primary">
                        <?php echo ucfirst($_SESSION['rol']); ?>
                    </span>
                </div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li>
                        <a href="admin_usuarios.php">
                            <i class="fas fa-user-cog"></i>
                            <span>Gestionar Usuarios</span>
                        </a>
                    </li>
                    <li>
                        <a href="crud_medicamentos.php">
                            <i class="fas fa-pills"></i>
                            <span>Medicamentos</span>
                        </a>
                    </li>
                    <li>
                        <a href="ver_tomas.php">
                            <i class="fas fa-history"></i>
                            <span>Historiales</span>
                        </a>
                    </li>
                    <li><a href="registrar_toma.php"><i class="fas fa-check-circle"></i> Registrar Tomas</a></li>
                    <li>
                        <a href="reporte.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reportes</span>
                        </a>
                    </li>
                      <li>
                        <a href="recordatorio.php">
                            <i class="fas fa-check-circle"></i>
                            <span>crear recordatorio</span>
                        </a>
                    </li>
                      <li>
                        <a href="ver_recordatorios.php">
                            <i class="fas fa-bell"></i>
                            <span>ver  recordatorio</span>
                        </a>
                    </li>
                    <li>
                        <a href="dashboard.php" class="active">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                        </a>
                        </li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <header class="main-header">
            <h1>Gestión de Usuarios</h1>
            <div class="header-actions">
                <span class="current-time">
                    <i class="far fa-clock"></i>
                    <?php echo date('d/m/Y H:i'); ?>
                </span>
            </div>
        </header>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert <?= $_SESSION['tipo_mensaje'] === 'exito' ? 'alert-success' : 'alert-error' ?>">
                <i class="fas <?= $_SESSION['tipo_mensaje'] === 'exito' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= $_SESSION['mensaje'] ?>
            </div>
            <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn <?= $tab_activa === 'usuarios' ? 'active' : '' ?>" onclick="mostrarTab('usuarios')">
                <i class="fas fa-users"></i> Usuarios
            </button>
            <button class="tab-btn <?= $tab_activa === 'asignaciones' ? 'active' : '' ?>" onclick="mostrarTab('asignaciones')">
                <i class="fas fa-link"></i> Asignaciones
            </button>
            <button class="tab-btn <?= $tab_activa === 'logs' ? 'active' : '' ?>" onclick="mostrarTab('logs')">
                <i class="fas fa-history"></i> Registros
            </button>
        </div>

        <!-- TAB USUARIOS -->
        <div id="usuarios" class="tab-content <?= $tab_activa === 'usuarios' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user-plus"></i> Agregar Nuevo Usuario</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="accion" value="agregar">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="nombre">Nombre Completo</label>
                                <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan Pérez" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="correo">Correo Electrónico</label>
                                <input type="email" id="correo" name="correo" placeholder="Ej: juan@example.com" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="contrasena">Contraseña</label>
                                <input type="password" id="contrasena" name="contrasena" placeholder="Mínimo 4 caracteres" required minlength="4">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="rol">Rol</label>
                                <select id="rol" name="rol" required>
                                    <option value="">Seleccionar Rol</option>
                                    <option value="paciente">Paciente</option>
                                    <option value="cuidador">Cuidador</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-users-cog"></i> Lista de Usuarios</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                                    <td><?= htmlspecialchars($u['correo']) ?></td>
                                    <td>
                                        <?php if ($u['rol'] === 'admin'): ?>
                                            <span class="badge badge-primary">Administrador</span>
                                        <?php elseif ($u['rol'] === 'cuidador'): ?>
                                            <span class="badge badge-success">Cuidador</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Paciente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <button class="btn btn-primary btn-sm" onclick="editarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre']) ?>', '<?= htmlspecialchars($u['correo']) ?>', '<?= $u['rol'] ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                            <a href="?eliminar=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal para editar usuario -->
            <div id="modal-editar" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
                <div style="background-color: white; margin: 5% auto; padding: 20px; border-radius: 5px; width: 80%; max-width: 600px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>Editar Usuario</h3>
                        <span style="cursor: pointer; font-size: 24px;" onclick="cerrarModal()">&times;</span>
                    </div>
                    <form id="form-editar" method="POST">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="edit-nombre">Nombre</label>
                                    <input type="text" id="edit-nombre" name="nombre" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="edit-correo">Correo</label>
                                    <input type="email" id="edit-correo" name="correo" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit-rol">Rol</label>
                            <select id="edit-rol" name="rol" required>
                                <option value="paciente">Paciente</option>
                                <option value="cuidador">Cuidador</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- TAB ASIGNACIONES -->
        <div id="asignaciones" class="tab-content <?= $tab_activa === 'asignaciones' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user-friends"></i> Crear Nueva Asignación</h2>
                </div>
                <form method="POST">
                    <input type="hidden" name="accion" value="asignar">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="cuidador_id">Cuidador</label>
                                <select id="cuidador_id" name="cuidador_id" required>
                                    <option value="">Seleccionar Cuidador</option>
                                    <?php foreach ($cuidadores as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?>  (correo: <?= $c['correo'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="paciente_id">Paciente</label>
                                <select id="paciente_id" name="paciente_id" required>
                                    <option value="">Seleccionar Paciente</option>
                                    <?php foreach ($pacientes as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (correo: <?= $p['correo'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-link"></i> Asignar
                    </button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-list"></i> Asignaciones Existentes</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Cuidador</th>
                                <th>Paciente</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asignaciones as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['cuidador']) ?></td>
                                    <td><?= htmlspecialchars($a['paciente']) ?></td>
                                    <td>
                                        <a href="?eliminar_asignacion=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar esta asignación?')">
                                            <i class="fas fa-unlink"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB LOGS -->
        <div id="logs" class="tab-content <?= $tab_activa === 'logs' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-history"></i> Registro de Actividades</h2>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Evento</th>
                                <th>Fecha y Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['nombre'] ?? 'Sistema') ?></td>
                                    <td><?= htmlspecialchars($log['evento']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($log['fecha_hora'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function mostrarTab(tab) {
            // Actualizar URL sin recargar la página
            history.pushState(null, null, `?tab=${tab}`);
            
            // Ocultar todos los tabs y remover clase active de los botones
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            // Mostrar el tab seleccionado y marcar el botón como activo
            document.getElementById(tab).classList.add('active');
            document.querySelector(`.tab-btn[onclick="mostrarTab('${tab}')"]`).classList.add('active');
        }
        
        function editarUsuario(id, nombre, correo, rol) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-nombre').value = nombre;
            document.getElementById('edit-correo').value = correo;
            document.getElementById('edit-rol').value = rol;
            
            document.getElementById('modal-editar').style.display = 'block';
        }
        
        function cerrarModal() {
            document.getElementById('modal-editar').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera del contenido
        window.onclick = function(event) {
            if (event.target == document.getElementById('modal-editar')) {
                cerrarModal();
            }
        }
        
        // Mostrar tab según parámetro URL al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab) {
                mostrarTab(tab);
            }
        });
    </script>
</body>
</html>