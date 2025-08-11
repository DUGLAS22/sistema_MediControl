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

// Procesar eliminación si se envió POST con toma_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toma_id'])) {
    $toma_id = intval($_POST['toma_id']);

    // Validar permiso según rol
    if ($rol === 'paciente') {
        $sql = "DELETE t FROM tomas t
                JOIN medicamentos m ON t.medicamento_id = m.id
                WHERE t.id = ? AND m.usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $toma_id, $usuario_id);
    } elseif ($rol === 'cuidador') {
        $sql = "DELETE t FROM tomas t
                JOIN medicamentos m ON t.medicamento_id = m.id
                JOIN asignaciones a ON a.paciente_id = m.usuario_id
                WHERE t.id = ? AND a.cuidador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $toma_id, $usuario_id);
    } elseif ($rol === 'admin') {
        $sql = "DELETE FROM tomas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $toma_id);
    }

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Toma eliminada correctamente";
        header("Location: ver_tomas.php");
        exit;
    } else {
        $mensaje = "Error al eliminar la toma";
    }
    $stmt->close();
}

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

// Consultar tomas según rol
if ($rol === 'paciente') {
    $sql = "SELECT t.*, m.nombre AS medicamento
            FROM tomas t
            JOIN medicamentos m ON t.medicamento_id = m.id
            WHERE m.usuario_id = ?
            ORDER BY t.fecha DESC, t.hora DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
} elseif ($rol === 'cuidador') {
    $sql = "SELECT t.*, m.nombre AS medicamento, u.nombre AS paciente
            FROM tomas t
            JOIN medicamentos m ON t.medicamento_id = m.id
            JOIN usuarios u ON m.usuario_id = u.id
            JOIN asignaciones a ON a.paciente_id = u.id
            WHERE a.cuidador_id = ?
            ORDER BY t.fecha DESC, t.hora DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
} elseif ($rol === 'admin') {
    $sql = "SELECT t.*, m.nombre AS medicamento, u.nombre AS paciente
            FROM tomas t
            JOIN medicamentos m ON t.medicamento_id = m.id
            JOIN usuarios u ON m.usuario_id = u.id
            ORDER BY t.fecha DESC, t.hora DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$resultado = $stmt->get_result();
$tomas = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediControl - Historial de Tomas</title>
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
            overflow-x: auto;
        }
        
        /* Table styles */
        .table-tomas {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table-tomas th {
            background: #4361ee;
            color: white;
            padding: 15px;
            font-weight: 500;
            text-align: left;
        }
        
        .table-tomas td {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
            vertical-align: middle;
        }
        
        .table-tomas tr:last-child td {
            border-bottom: none;
        }
        
        .table-tomas tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Status badges */
        .badge-estado {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-realizada {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-omitida {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-retrasada {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Button styles */
        .btn-eliminar {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        
        .btn-eliminar:hover {
            background: #c0392b;
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
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #adb5bd;
        }
        
        /* Responsive table */
        @media (max-width: 768px) {
            .table-tomas {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
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
                <h1><i class="fas fa-history"></i> Historial de Tomas</h1>
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
                    <h2><i class="fas fa-list"></i> Registro de Tomas</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($tomas)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>No hay tomas registradas</h3>
                            <p>Aún no se han registrado tomas de medicamentos.</p>
                        </div>
                    <?php else: ?>
                        <table class="table-tomas">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-pills"></i> Medicamento</th>
                                    <th><i class="far fa-calendar-alt"></i> Fecha</th>
                                    <th><i class="far fa-clock"></i> Hora</th>
                                    <th><i class="fas fa-info-circle"></i> Estado</th>
                                    <th><i class="fas fa-comment-alt"></i> Observaciones</th>
                                    <?php if ($rol !== 'paciente'): ?>
                                        <th><i class="fas fa-user"></i> Paciente</th>
                                    <?php endif; ?>
                                    <th><i class="fas fa-cog"></i> Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tomas as $t): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($t['medicamento']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                                        <td><?= date('H:i', strtotime($t['hora'])) ?></td>
                                        <td>
                                            <span class="badge-estado badge-<?= $t['estado'] ?>">
                                                <?= ucfirst($t['estado']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($t['observaciones']) ?></td>
                                        <?php if ($rol !== 'paciente'): ?>
                                            <td><?= htmlspecialchars($t['paciente']) ?></td>
                                        <?php endif; ?>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta toma?');">
                                                <input type="hidden" name="toma_id" value="<?= $t['id'] ?>">
                                                <button type="submit" class="btn-eliminar">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Eliminar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    </script>
</body>
</html>