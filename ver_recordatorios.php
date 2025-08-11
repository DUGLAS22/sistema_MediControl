<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Procesar eliminación de recordatorio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_recordatorio'])) {
    $recordatorio_id = $_POST['recordatorio_id'];
    $rol = $_SESSION['rol'];
    $usuario_id = $_SESSION['usuario_id'];
    
    // Verificar permisos antes de eliminar
    if ($rol === 'admin') {
        $sql = "DELETE FROM recordatorios WHERE id = ?";
    } elseif ($rol === 'cuidador') {
        $sql = "DELETE r FROM recordatorios r
                JOIN usuarios u ON r.usuario_id = u.id
                JOIN asignaciones a ON a.paciente_id = u.id
                WHERE r.id = ? AND a.cuidador_id = ?";
    } elseif ($rol === 'paciente') {
        $sql = "DELETE FROM recordatorios WHERE id = ? AND usuario_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    if ($rol === 'admin') {
        $stmt->bind_param("i", $recordatorio_id);
    } else {
        $stmt->bind_param("ii", $recordatorio_id, $usuario_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Recordatorio eliminado correctamente";
        $_SESSION['mensaje_tipo'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar el recordatorio";
        $_SESSION['mensaje_tipo'] = "error";
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$rol = $_SESSION['rol'];
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];

$recordatorios = [];

if ($rol === 'admin') {
    $sql = "SELECT r.id, r.mensaje, r.fecha_envio, r.enviado, u.nombre AS paciente, m.nombre AS medicamento
            FROM recordatorios r
            JOIN usuarios u ON r.usuario_id = u.id
            JOIN medicamentos m ON r.medicamento_id = m.id
            ORDER BY r.fecha_envio DESC";
    $recordatorios = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
} elseif ($rol === 'cuidador') {
    $sql = "SELECT r.id, r.mensaje, r.fecha_envio, r.enviado, u.nombre AS paciente, m.nombre AS medicamento
            FROM recordatorios r
            JOIN usuarios u ON r.usuario_id = u.id
            JOIN medicamentos m ON r.medicamento_id = m.id
            JOIN asignaciones a ON a.paciente_id = u.id
            WHERE a.cuidador_id = ?
            ORDER BY r.fecha_envio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $recordatorios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} elseif ($rol === 'paciente') {
    $sql = "SELECT r.id, r.mensaje, r.fecha_envio, r.enviado, u.nombre AS paciente, m.nombre AS medicamento
            FROM recordatorios r
            JOIN usuarios u ON r.usuario_id = u.id
            JOIN medicamentos m ON r.medicamento_id = m.id
            WHERE r.usuario_id = ?
            ORDER BY r.fecha_envio DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $recordatorios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediControl | Recordatorios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-enviado {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pendiente {
            background-color: #fff3cd;
            color: #856404;
        }
        .btn-eliminar {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-eliminar:hover {
            background-color: #c82333;
        }
        .btn-eliminar i {
            margin-right: 5px;
        }
        .mensaje-alerta {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
        }
        .mensaje-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .no-data {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            color: #6c757d;
            font-size: 16px;
        }
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .current-time {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            color: #495057;
        }
        .btn-back {
            display: inline-block;
            margin-top: 20px;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        .btn-back:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar Actualizado -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="user-info">
                <div class="avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div>
                    <h3><?php echo htmlspecialchars($nombre_usuario); ?></h3>
                    <span class="badge badge-<?php echo $rol === 'admin' ? 'primary' : ($rol === 'cuidador' ? 'success' : 'info'); ?>">
                        <?php echo ucfirst($rol); ?>
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
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <header class="main-header">
            <h1><i class="fas fa-bell"></i> Lista de Recordatorios</h1>
            <div class="current-time">
                <i class="far fa-clock"></i>
                <?= date('d/m/Y H:i') ?>
            </div>
        </header>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje-alerta mensaje-<?= $_SESSION['mensaje_tipo'] ?>">
                <?= $_SESSION['mensaje'] ?>
            </div>
            <?php 
            unset($_SESSION['mensaje']);
            unset($_SESSION['mensaje_tipo']);
            ?>
        <?php endif; ?>

        <div class="table-container">
            <?php if (count($recordatorios) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Paciente</th>
                            <th><i class="fas fa-pills"></i> Medicamento</th>
                            <th><i class="fas fa-comment-alt"></i> Mensaje</th>
                            <th><i class="fas fa-calendar-alt"></i> Fecha de Envío</th>
                            <th><i class="fas fa-info-circle"></i> Estado</th>
                            <th><i class="fas fa-cog"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recordatorios as $rec): ?>
                            <tr>
                                <td><?= htmlspecialchars($rec['paciente']) ?></td>
                                <td><?= htmlspecialchars($rec['medicamento']) ?></td>
                                <td><?= htmlspecialchars($rec['mensaje']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($rec['fecha_envio'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $rec['enviado'] ? 'status-enviado' : 'status-pendiente' ?>">
                                        <?= $rec['enviado'] ? 'Enviado' : 'Pendiente' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este recordatorio?');" style="display: inline;">
                                        <input type="hidden" name="recordatorio_id" value="<?= $rec['id'] ?>">
                                        <button type="submit" name="eliminar_recordatorio" class="btn-eliminar">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 36px; margin-bottom: 15px;"></i>
                    No hay recordatorios registrados.
                </div>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </a>
    </main>
</div>

<script src="assets/script.js"></script>
</body>
</html>