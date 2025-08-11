<?php
session_start();
require 'db.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];
$mensaje = '';
$editar_id = null;
$medicamento_editar = null;

// Procesar formulario de agregar/editar medicamento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($rol === 'paciente' || $rol === 'admin')) {
    if (isset($_POST['agregar'])) {
        $nombre = ucwords(strtolower($conn->real_escape_string($_POST['nombre'])));
        $dosis = $conn->real_escape_string($_POST['dosis']);
        $frecuencia = $conn->real_escape_string($_POST['frecuencia']);
        $horario = $conn->real_escape_string($_POST['horario']);
        $id_usuario_medicamento = ($rol === 'admin') ? intval($_POST['usuario_id']) : $usuario_id;

        $sql = "INSERT INTO medicamentos (usuario_id, nombre, dosis, frecuencia, horario) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $id_usuario_medicamento, $nombre, $dosis, $frecuencia, $horario);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Medicamento agregado correctamente";
            header("Location: crud_medicamentos.php");
            exit;
        } else {
            $mensaje = "Error al agregar medicamento";
        }
        $stmt->close();
    } elseif (isset($_POST['editar'])) {
        $editar_id = intval($_POST['editar_id']);
        $nombre = ucwords(strtolower($conn->real_escape_string($_POST['nombre'])));
        $dosis = $conn->real_escape_string($_POST['dosis']);
        $frecuencia = $conn->real_escape_string($_POST['frecuencia']);
        $horario = $conn->real_escape_string($_POST['horario']);
        
        if ($rol === 'paciente') {
            $sql = "UPDATE medicamentos SET nombre = ?, dosis = ?, frecuencia = ?, horario = ? WHERE id = ? AND usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $nombre, $dosis, $frecuencia, $horario, $editar_id, $usuario_id);
        } else {
            $sql = "UPDATE medicamentos SET nombre = ?, dosis = ?, frecuencia = ?, horario = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nombre, $dosis, $frecuencia, $horario, $editar_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Medicamento actualizado correctamente";
            header("Location: crud_medicamentos.php");
            exit;
        } else {
            $mensaje = "Error al actualizar medicamento";
        }
        $stmt->close();
    }
}

// Mostrar mensaje de sesión si existe
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

// Procesar eliminación de medicamento
if (isset($_GET['eliminar']) && ($rol === 'paciente' || $rol === 'admin')) {
    $id_med = intval($_GET['eliminar']);

    if ($rol === 'paciente') {
        $sql = "DELETE FROM medicamentos WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_med, $usuario_id);
    } else {
        $sql = "DELETE FROM medicamentos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_med);
    }

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Medicamento eliminado correctamente";
        header("Location: crud_medicamentos.php");
        exit;
    } else {
        $mensaje = "Error al eliminar medicamento";
    }
    $stmt->close();
}

// Procesar modo edición
if (isset($_GET['editar']) && ($rol === 'paciente' || $rol === 'admin')) {
    $editar_id = intval($_GET['editar']);
    
    if ($rol === 'paciente') {
        $sql = "SELECT * FROM medicamentos WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $editar_id, $usuario_id);
    } else {
        $sql = "SELECT * FROM medicamentos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $editar_id);
    }
    
    $stmt->execute();
    $resultado = $stmt->get_result();
    $medicamento_editar = $resultado->fetch_assoc();
    $stmt->close();
    
    if (!$medicamento_editar) {
        $editar_id = null;
        $mensaje = "Medicamento no encontrado";
    }
}

// Obtener lista de medicamentos según rol
if ($rol === 'paciente') {
    $sql = "SELECT * FROM medicamentos WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
} elseif ($rol === 'cuidador') {
    $sql = "SELECT m.*, u.nombre AS paciente
            FROM medicamentos m
            JOIN usuarios u ON m.usuario_id = u.id
            JOIN pacientes_cuidadores a ON a.paciente_id = u.id
            WHERE a.cuidador_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
} elseif ($rol === 'admin') {
    $sql = "SELECT m.*, u.nombre AS paciente FROM medicamentos m
            JOIN usuarios u ON m.usuario_id = u.id";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$resultado = $stmt->get_result();
$medicamentos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediControl - Gestión de Medicamentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Estilos generales */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f8f9fa;
            background: #f8f9fa;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #212529;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* Formulario */
        .form-medicamento {
            background: transparent;
            padding: 0;
            box-shadow: none;
            margin-bottom: 0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #212529;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .btn-agregar {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-agregar:hover {
            background: #27ae60;
        }
        
        .btn-cancelar {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 10px;
            text-decoration: none;
        }
        
        .btn-cancelar:hover {
            background: #7f8c8d;
        }

        /* Tabla */
        .table-medicamentos {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .table-medicamentos th {
            background: #4361ee;
            color: white;
            padding: 15px;
            font-weight: 500;
            text-align: left;
        }
        
        .table-medicamentos td {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
            vertical-align: middle;
        }
        
        .table-medicamentos tr:last-child td {
            border-bottom: none;
        }
        
        .table-medicamentos tr:hover {
            background-color: #f8f9fa;
        }
        
        .medicamento-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .icono-medicamento {
            color: #4361ee;
            font-size: 1.2rem;
        }
        
        .hora-medicamento {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Botones de acción */
        .acciones {
            display: flex;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }
        
        .btn-accion {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            transition: all 0.2s;
            background: transparent;
            border: none;
            cursor: pointer;
        }
        
        .btn-accion.editar {
            color: #2ecc71;
            background: rgba(46, 204, 113, 0.1);
        }
        
        .btn-accion.eliminar {
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
        }
        
        .btn-accion:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
       /* Alertas mejoradas */
.alert-empty, 
.alert-success, 
.alert-error {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 15px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 15px;
    font-weight: 500;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    animation: fadeIn 0.4s ease-out;
    border-left: 4px solid;
}

.alert-empty {
    background-color: #f8f9fa;
    color: #6c757d;
    border-color: #6c757d;
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

.alert-empty i,
.alert-success i,
.alert-error i {
    font-size: 1.3em;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeOut {
    0% { opacity: 1; }
    100% { opacity: 0; height: 0; padding: 0; margin: 0; overflow: hidden; }
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
                        <li><a href="pacientes.php"><i class="fas fa-users"></i> Mis Pacientes</a></li>
                        <li><a href="medicamentos.php" class="active"><i class="fas fa-pills"></i> Medicamentos</a></li>
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
                <h1><i class="fas fa-pills"></i> Gestión de Medicamentos</h1>
                <div class="header-actions">
                    <span class="current-time">
                        <i class="far fa-clock"></i>
                        <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </header>

            <?php if ($mensaje): ?>
                <div class="<?= strpos($mensaje, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                    <i class="fas <?= strpos($mensaje, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i> 
                    <?= $mensaje ?>
                </div>
            <?php endif; ?>

            <div class="content-grid">
                <?php if ($rol === 'paciente' || $rol === 'admin'): ?>
                <section class="card">
                    <div class="card-header">
                        <h2><i class="fas <?= $editar_id ? 'fa-edit' : 'fa-plus-circle' ?>"></i> 
                            <?= $editar_id ? 'Editar Medicamento' : ($rol === 'admin' ? 'Agregar Medicamento a Paciente' : 'Agregar Nuevo Medicamento') ?>
                        </h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="form-medicamento">
                            <?php if ($editar_id): ?>
                                <input type="hidden" name="editar_id" value="<?= $editar_id ?>">
                            <?php endif; ?>
                            
                            <?php if ($rol === 'admin'): ?>
                            <div class="form-group">
                                <label for="usuario_id">Paciente:</label>
                                <select id="usuario_id" name="usuario_id" required <?= $editar_id ? 'disabled' : '' ?>>
                                    <option value="">Seleccione un paciente</option>
                                    <?php
                                    $res = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'paciente'");
                                    while ($row = $res->fetch_assoc()): ?>
                                        <option value="<?= $row['id'] ?>" 
                                            <?= ($editar_id && $medicamento_editar['usuario_id'] == $row['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($row['nombre']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if ($editar_id): ?>
                                    <input type="hidden" name="usuario_id" value="<?= $medicamento_editar['usuario_id'] ?>">
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="nombre">Nombre del Medicamento:</label>
                                <input type="text" id="nombre" name="nombre" required
                                    value="<?= $editar_id ? htmlspecialchars($medicamento_editar['nombre']) : '' ?>">
                            </div>

                            <div class="form-group">
                                <label for="dosis">Dosis:</label>
                                <input type="text" id="dosis" name="dosis" required
                                    value="<?= $editar_id ? htmlspecialchars($medicamento_editar['dosis']) : '' ?>">
                            </div>

                            <div class="form-group">
                                <label for="frecuencia">Frecuencia:</label>
                                <input type="text" id="frecuencia" name="frecuencia" required
                                    value="<?= $editar_id ? htmlspecialchars($medicamento_editar['frecuencia']) : '' ?>">
                            </div>

                            <div class="form-group">
                                <label for="horario">Horario:</label>
                                <input type="time" id="horario" name="horario" required
                                    value="<?= $editar_id ? htmlspecialchars(date('H:i', strtotime($medicamento_editar['horario']))) : '' ?>">
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="<?= $editar_id ? 'editar' : 'agregar' ?>" class="btn-agregar">
                                    <i class="fas fa-save"></i> <?= $editar_id ? 'Actualizar' : 'Guardar' ?> Medicamento
                                </button>
                                <?php if ($editar_id): ?>
                                    <a href="crud_medicamentos.php" class="btn-cancelar">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </section>
                <?php endif; ?>

                <section class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-list"></i> Lista de Medicamentos</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($medicamentos)): ?>
                            <div class="alert-empty">
                                <i class="fas fa-info-circle"></i> No hay medicamentos registrados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table-medicamentos">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-pills"></i> Nombre</th>
                                            <th><i class="fas fa-prescription-bottle-alt"></i> Dosis</th>
                                            <th><i class="fas fa-clock"></i> Frecuencia</th>
                                            <th><i class="far fa-clock"></i> Horario</th>
                                            <?php if ($rol === 'admin' || $rol === 'cuidador'): ?>
                                                <th><i class="fas fa-user"></i> Paciente</th>
                                            <?php endif; ?>
                                            <?php if ($rol !== 'cuidador'): ?>
                                                <th style="width: 120px; text-align: center;"><i class="fas fa-cog"></i> Acciones</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($medicamentos as $med): ?>
                                            <tr>
                                                <td>
                                                    <div class="medicamento-info">
                                                        <i class="fas fa-pills icono-medicamento"></i>
                                                        <span><?= htmlspecialchars($med['nombre']) ?></span>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($med['dosis']) ?></td>
                                                <td><?= htmlspecialchars($med['frecuencia']) ?></td>
                                                <td>
                                                    <span class="hora-medicamento">
                                                        <i class="far fa-clock"></i>
                                                        <?= date('H:i', strtotime($med['horario'])) ?>
                                                    </span>
                                                </td>
                                                <?php if ($rol === 'admin' || $rol === 'cuidador'): ?>
                                                    <td><?= htmlspecialchars($med['paciente']) ?></td>
                                                <?php endif; ?>
                                                <?php if ($rol !== 'cuidador'): ?>
                                                    <td class="acciones">
                                                        <button class="btn-accion eliminar" onclick="if(confirm('¿Eliminar este medicamento?')) window.location.href='?eliminar=<?= $med['id'] ?>'" title="Eliminar">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                        <button class="btn-accion editar" onclick="window.location.href='?editar=<?= $med['id'] ?>'" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Formatear automáticamente el nombre del medicamento
        document.getElementById('nombre').addEventListener('blur', function() {
            this.value = this.value.toLowerCase().replace(/\b\w/g, function(l) {
                return l.toUpperCase();
            });
        });
        
        // Eliminar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success, .alert-error');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    });
    </script>
</body>
</html> 