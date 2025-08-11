<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];
$mensaje = '';
$paciente_id = null;
$medicamentos = [];
$pacientes_asignados = [];

// Guardar toma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicamento_id = $_POST['medicamento_id'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $estado = $_POST['estado'];
    $observaciones = $_POST['observaciones'];

    $sql = "INSERT INTO tomas (medicamento_id, fecha, hora, estado, observaciones) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $medicamento_id, $fecha, $hora, $estado, $observaciones);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Toma registrada correctamente";
        header("Location: ver_tomas.php");
        exit;
    } else {
        $mensaje = "Error al registrar la toma";
    }
    $stmt->close();
}

// Obtener pacientes asignados si es cuidador
if ($rol === 'cuidador') {
    $sql_pacientes = "SELECT u.id, u. nombre, u.correo
                     FROM usuarios u
                     JOIN asignaciones a ON a.paciente_id = u.id
                     WHERE a.cuidador_id = ?";
    $stmt = $conn->prepare($sql_pacientes);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $pacientes_asignados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Si se seleccionó un paciente, obtener sus medicamentos
    if (isset($_GET['paciente_id'])) {
        $paciente_id = intval($_GET['paciente_id']);
        $sql_medicamentos = "SELECT id, nombre FROM medicamentos WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql_medicamentos);
        $stmt->bind_param("i", $paciente_id);
        $stmt->execute();
        $medicamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} 
// Para paciente
elseif ($rol === 'paciente') {
    $sql = "SELECT id, nombre FROM medicamentos WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $medicamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} 
// Para admin
elseif ($rol === 'admin') {
    $sql = "SELECT m.id, m.nombre, u.nombre AS paciente
            FROM medicamentos m
            JOIN usuarios u ON m.usuario_id = u.id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $medicamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediControl - Registrar Toma</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Estilos generales */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background-color: #f5f7fa;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e6ed;
        }
        
        .main-header h1 {
            font-size: 1.8rem;
            color: #2e384d;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Card styles */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #f8f9fa;
            background: #f8f9fa;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.4rem;
            color: #212529;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Form styles */
        .form-toma {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        @media (min-width: 768px) {
            .form-toma {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2e384d;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background-color: #fff;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        /* Button styles */
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-primary {
            background: #4361ee;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            background: #3a56d4;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 1rem;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        /* Alert styles */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            animation: fadeIn 0.4s ease-out;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .alert i {
            font-size: 1.3em;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Time picker enhancement */
        .time-picker-container {
            position: relative;
        }
        
        .time-picker-container::after {
            content: '\f017';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }
        
        /* Select styling */
        .select-container {
            position: relative;
        }
        
        .select-container::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }
        
        /* Patient selection */
        .patient-selection {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div>
                        <h3><?= htmlspecialchars($nombre_usuario) ?></h3>
                        <span class="badge badge-<?= $rol === 'admin' ? 'primary' : ($rol === 'cuidador' ? 'success' : 'info') ?>">
                            <?= ucfirst($rol) ?>
                        </span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <?php if ($rol === 'paciente'): ?>
                        <li>
                        <a href="crud_medicamentos.php">
                            <i class="fas fa-list"></i>
                            <span>Mis Medicamentos</span>
                        </a>
                    </li>
                    <li>
                        <a href="registrar_toma.php">
                            <i class="fas fa-check-circle"></i>
                            <span>Registrar Toma</span>
                        </a>
                    </li>
                    <li>
                        <a href="ver_tomas.php">
                            <i class="fas fa-history"></i>
                            <span>Historial de Tomas</span>
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
                        <a href="recordatorio_alert.php">
                            <i class="fas fa-bell"></i>
                            <span>Recordatorios Pendientes</span>
                        </a>
                    </li>
                    <li>
                        <a href="dashboard.php" class="active">
                            <i class="fas fa-home"></i>
                            <span>Inicio</span>
                        </a>
                        </li>
                    <?php elseif ($rol === 'cuidador'): ?>
                         <li><a href="ver_pacientes.php"><i class="fas fa-users"></i> Mis Pacientes</a></li>
                        <li><a href="ver_medicamentos.php"><i class="fas fa-pills"></i> Medicamentos</a></li>
                        <li><a href="registrar_toma.php"><i class="fas fa-check-circle"></i> Registrar Tomas</a></li>
                        <li><a href="ver_tomas.php" class="active"><i class="fas fa-history"></i> Historial</a></li>
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
                    <?php elseif ($rol === 'admin'): ?>
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
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1><i class="fas fa-check-circle"></i> Registrar Toma de Medicamento</h1>
                <div class="header-actions">
                    <span class="current-time">
                        <i class="far fa-clock"></i>
                        <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert <?= strpos($mensaje, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                    <i class="fas <?= strpos($mensaje, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i> 
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-pills"></i> Información de la Toma</h2>
                </div>
                <div class="card-body">
                    <?php if ($rol === 'cuidador' && empty($paciente_id)): ?>
                        <!-- Paso 1: Seleccionar paciente -->
                        <div class="patient-selection">
                            <h3><i class="fas fa-user-injured"></i> Seleccione un paciente</h3>
                            <form method="GET" class="form-toma">
                                <div class="form-group full-width">
                                    <div class="select-container">
                                        <select id="paciente_id" name="paciente_id" required onchange="this.form.submit()">
                                            <option value="">Seleccione un paciente</option>
                                            <?php foreach ($pacientes_asignados as $paciente): ?>
                                                <option value="<?= $paciente['id'] ?>">
                                                    <?= htmlspecialchars($paciente['nombre']) ?> (<?= htmlspecialchars($paciente['correo']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Paso 2: Formulario de toma -->
                        <form method="POST" class="form-toma">
                            <?php if ($rol === 'cuidador'): ?>
                                <div class="form-group full-width">
                                    <label><i class="fas fa-user-injured"></i> Paciente:</label>
                                    <input type="text" value="<?= htmlspecialchars(array_column($pacientes_asignados, 'nombre', 'id')[$paciente_id] ?? '') ?>" readonly>
                                    <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">
                                </div>
                            <?php endif; ?>

                            <div class="form-group <?= $rol !== 'paciente' ? 'full-width' : '' ?>">
                                <label for="medicamento_id"><i class="fas fa-medkit"></i> Medicamento:</label>
                                <div class="select-container">
                                    <select id="medicamento_id" name="medicamento_id" required>
                                        <?php if (empty($medicamentos)): ?>
                                            <option value="">No hay medicamentos registrados</option>
                                        <?php else: ?>
                                            <option value="">Seleccione un medicamento</option>
                                            <?php foreach ($medicamentos as $med): ?>
                                                <option value="<?= $med['id'] ?>">
                                                    <?= htmlspecialchars($med['nombre']) ?>
                                                    <?php if (isset($med['paciente'])): ?>
                                                        (Paciente: <?= htmlspecialchars($med['paciente']) ?>)
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="fecha"><i class="far fa-calendar-alt"></i> Fecha:</label>
                                <input type="date" id="fecha" name="fecha" required 
                                       value="<?= date('Y-m-d') ?>"
                                       max="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="form-group">
                                <label for="hora"><i class="far fa-clock"></i> Hora:</label>
                                <div class="time-picker-container">
                                    <input type="time" id="hora" name="hora" required 
                                           value="<?= date('H:i') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="estado"><i class="fas fa-info-circle"></i> Estado:</label>
                                <div class="select-container">
                                    <select id="estado" name="estado" required>
                                        <option value="realizada" selected>Realizada</option>
                                        <option value="omitida">Omitida</option>
                                        <option value="retrasada">Retrasada</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group full-width">
                                <label for="observaciones"><i class="fas fa-comment-alt"></i> Observaciones:</label>
                                <textarea id="observaciones" name="observaciones" placeholder="Ingrese cualquier observación relevante..."></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Registrar Toma
                                </button>
                                <?php if ($rol === 'cuidador'): ?>
                                    <a href="registrar_toma.php" class="btn-secondary">
                                        <i class="fas fa-sync-alt"></i> Cambiar Paciente
                                    </a>
                                <?php endif; ?>
                                <a href="dashboard.php" class="btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Establecer la hora actual como valor predeterminado
        const now = new Date();
        const currentHour = now.getHours().toString().padStart(2, '0');
        const currentMinute = now.getMinutes().toString().padStart(2, '0');
        if (document.getElementById('hora')) {
            document.getElementById('hora').value = `${currentHour}:${currentMinute}`;
        }
        
        // Eliminar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
        
        // Mejorar la experiencia de selección de hora
        if (document.getElementById('hora')) {
            const horaInput = document.getElementById('hora');
            horaInput.addEventListener('focus', function() {
                this.showPicker();
            });
        }
    });
    </script>
</body>
</html>