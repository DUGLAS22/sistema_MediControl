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

// Obtener lista de pacientes según rol
$pacientes = [];
if ($rol === 'admin') {
    $res = $conn->query("SELECT id, nombre FROM usuarios WHERE rol='paciente'");
    while ($row = $res->fetch_assoc()) {
        $pacientes[] = $row;
    }
} elseif ($rol === 'cuidador') {
    $stmt = $conn->prepare("SELECT u.id, u.nombre 
                           FROM asignaciones a 
                           JOIN usuarios u ON a.paciente_id = u.id
                           WHERE a.cuidador_id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $pacientes[] = $row;
    }
}

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = ($rol === 'paciente') ? $usuario_id : $_POST['paciente_id'];
    $medicamento_id = $_POST['medicamento_id'];
    $mensaje = $_POST['mensaje'];
    $fecha_envio = $_POST['fecha_envio'];

    $stmt = $conn->prepare("INSERT INTO recordatorios (usuario_id, medicamento_id, mensaje, fecha_envio) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $paciente_id, $medicamento_id, $mensaje, $fecha_envio);

    if ($stmt->execute()) {
        // Registrar en logs
        $evento = "Creó recordatorio para usuario ID $paciente_id";
        $log = $conn->prepare("INSERT INTO logs (usuario_id, evento) VALUES (?, ?)");
        $log->bind_param("is", $usuario_id, $evento);
        $log->execute();

        // Mostrar notificación de éxito
        $_SESSION['notificacion'] = [
            'tipo' => 'exito',
            'mensaje' => 'Recordatorio creado correctamente. Se enviará en la fecha programada.'
        ];
        header("Location: recordatorio.php");
        exit;
    } else {
        $error = "Error al crear recordatorio: " . $conn->error;
    }
}

// Obtener medicamentos del paciente seleccionado (inicialmente vacío)
$medicamentos = [];

// Si hay un paciente seleccionado (para cuidadores/admin) o es paciente
if (($rol !== 'paciente' && isset($_GET['paciente_id'])) || $rol === 'paciente') {
    $paciente_id = ($rol === 'paciente') ? $usuario_id : $_GET['paciente_id'];
    
    $stmt = $conn->prepare("SELECT id, nombre FROM medicamentos WHERE usuario_id = ?");
    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $medicamentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediControl | Crear Recordatorio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 25px;
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2e384d;
        }

        .form-group select,
        .form-group textarea,
        .form-group input[type="datetime-local"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            transition: border 0.3s;
        }

        .form-group select:focus,
        .form-group textarea:focus,
        .form-group input[type="datetime-local"]:focus {
            border-color: #4361ee;
            outline: none;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background-color: #4361ee;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background-color: #3a56d4;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #4361ee;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 25px;
            transition: background-color 0.3s;
        }

        .btn-back:hover {
            background-color: #3a56d4;
        }

        .current-time {
            font-weight: 500;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
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
                    <li><a href="ver_tomas.php"><i class="fas fa-history"></i> Historial</a></li>
                    <li>
                        <a href="recordatorio.php" class="active">
                            <i class="fas fa-check-circle"></i>
                            <span>Crear Recordatorio</span>
                        </a>
                    </li>
                    <li>
                        <a href="ver_recordatorios.php">
                            <i class="fas fa-bell"></i>
                            <span>ver  recordatorio</span>
                        </a>
                    </li>
                    <li>
                        <a href="dashboard.php">
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
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <header class="main-header">
            <h1><i class="fas fa-bell"></i> Crear Nuevo Recordatorio</h1>
            <div class="current-time">
                <i class="far fa-clock"></i>
                <?= date('d/m/Y H:i') ?>
            </div>
        </header>

        <div class="form-container">
            <?php if (!empty($_SESSION['notificacion'])): ?>
                <div class="alert alert-<?= $_SESSION['notificacion']['tipo'] ?>">
                    <i class="fas fa-<?= $_SESSION['notificacion']['tipo'] === 'exito' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= $_SESSION['notificacion']['mensaje'] ?>
                </div>
                <?php unset($_SESSION['notificacion']); ?>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="form-recordatorio">
                <?php if ($rol !== 'paciente' && !empty($pacientes)): ?>
                    <div class="form-group">
                        <label for="paciente_id"><i class="fas fa-user-injured"></i> Seleccionar Paciente:</label>
                        <select id="paciente_id" name="paciente_id" required onchange="cargarMedicamentos(this.value)">
                            <option value="">-- Seleccione un paciente --</option>
                            <?php foreach ($pacientes as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= isset($_GET['paciente_id']) && $_GET['paciente_id'] == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="medicamento_id"><i class="fas fa-pills"></i> Medicamento Relacionado:</label>
                    <select id="medicamento_id" name="medicamento_id" required <?= empty($medicamentos) ? 'disabled' : '' ?>>
                        <?php if (!empty($medicamentos)): ?>
                            <?php foreach ($medicamentos as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">-- Seleccione primero un paciente --</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="mensaje"><i class="fas fa-comment-alt"></i> Mensaje:</label>
                    <textarea id="mensaje" name="mensaje" required placeholder="Escribe aquí el mensaje del recordatorio..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="fecha_envio"><i class="fas fa-calendar-alt"></i> Fecha y Hora de Envío:</label>
                    <input type="datetime-local" id="fecha_envio" name="fecha_envio" required>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Guardar Recordatorio
                </button>
            </form>

            <a href="recordatorio.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver a Recordatorios
            </a>
        </div>
    </main>
</div>

<script src="assets/script.js"></script>
<script>
// Función para cargar medicamentos según paciente seleccionado
function cargarMedicamentos(pacienteId) {
    if (!pacienteId) {
        document.getElementById('medicamento_id').innerHTML = '<option value="">-- Seleccione primero un paciente --</option>';
        document.getElementById('medicamento_id').disabled = true;
        return;
    }
    
    fetch(`obtener_medicamentos.php?paciente_id=${pacienteId}`)
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('medicamento_id');
            select.innerHTML = '';
            
            if (data.length > 0) {
                data.forEach(medicamento => {
                    const option = document.createElement('option');
                    option.value = medicamento.id;
                    option.textContent = medicamento.nombre;
                    select.appendChild(option);
                });
                select.disabled = false;
            } else {
                select.innerHTML = '<option value="">-- No hay medicamentos para este paciente --</option>';
                select.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('medicamento_id').innerHTML = '<option value="">-- Error al cargar medicamentos --</option>';
        });
}

// Si es paciente, cargar medicamentos automáticamente al cargar la página
<?php if ($rol === 'paciente'): ?>
document.addEventListener('DOMContentLoaded', function() {
    cargarMedicamentos(<?= $usuario_id ?>);
});
<?php endif; ?>
</script>
</body>
</html>