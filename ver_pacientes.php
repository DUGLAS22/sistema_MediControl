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

// Verificar que solo cuidadores pueden acceder
if ($rol !== 'cuidador') {
    header("Location: dashboard.php");
    exit;
}

$sql = "SELECT u.id, u.nombre, u.correo
        FROM asignaciones a
        JOIN usuarios u ON a.paciente_id = u.id
        WHERE a.cuidador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$pacientes = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mensaje = '';
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
    <title>MediControl - Mis Pacientes</title>
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
        }
        
        /* Table styles */
        .table-pacientes {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .table-pacientes th {
            background: #4361ee;
            color: white;
            padding: 15px;
            font-weight: 500;
            text-align: left;
        }
        
        .table-pacientes td {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
            vertical-align: middle;
        }
        
        .table-pacientes tr:last-child td {
            border-bottom: none;
        }
        
        .table-pacientes tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Patient card styles */
        .patient-card {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .patient-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .patient-avatar .default-avatar {
            font-size: 1.5rem;
            color: #6c757d;
        }
        
        .patient-info {
            display: flex;
            flex-direction: column;
        }
        
        .patient-name {
            font-weight: 500;
            color: #2e384d;
        }
        
        .patient-email {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        /* Button styles */
        .btn-action {
            background: #4361ee;
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
            text-decoration: none;
        }
        
        .btn-action:hover {
            background: #3a56d4;
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
            .table-pacientes {
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
                        <span class="badge badge-success">
                            <?= ucfirst($rol) ?>
                        </span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
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
                <h1><i class="fas fa-users"></i> Mis Pacientes Asignados</h1>
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
                    <h2><i class="fas fa-list"></i> Lista de Pacientes</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($pacientes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <h3>No tienes pacientes asignados</h3>
                            <p>Actualmente no hay pacientes asignados a tu cuenta de cuidador.</p>
                        </div>
                    <?php else: ?>
                        <table class="table-pacientes">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Información</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <tr>
                                        <td>
                                            <div class="patient-card">
                                                <div class="patient-avatar">
                                                    <?php if (!empty($paciente['foto_perfil'])): ?>
                                                        <img src="uploads/<?= htmlspecialchars($paciente['foto_perfil']) ?>" alt="Foto de perfil">
                                                    <?php else: ?>
                                                        <i class="fas fa-user default-avatar"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="patient-name"><?= htmlspecialchars($paciente['nombre']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="patient-info">
                                                <span class="patient-email"><?= htmlspecialchars($paciente['correo']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="ver_medicamentos.php?paciente_id=<?= $paciente['id'] ?>" class="btn-action">
                                                <i class="fas fa-pills"></i> Ver Medicamentos
                                            </a>
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