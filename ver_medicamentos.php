<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'cuidador') {
    header("Location: login.php");
    exit;
}

$cuidador_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$nombre_usuario = $_SESSION['nombre'];

$sql = "SELECT m.nombre, m.dosis, m.frecuencia, m.horario, u.nombre AS paciente
        FROM medicamentos m
        JOIN usuarios u ON m.usuario_id = u.id
        JOIN asignaciones a ON a.paciente_id = u.id
        WHERE a.cuidador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cuidador_id);
$stmt->execute();
$resultado = $stmt->get_result();
$medicamentos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>MediControl | Medicamentos</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="assets/style.css" />
<style>
    /* --- Sidebar y layout base adaptados del segundo código --- */

    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        background-color: #f5f7fa;
        color: #2e384d;
    }

    .dashboard-container {
        display: flex;
        min-height: 100vh;
        background-color: #f5f7fa;
    }

    aside.sidebar {
        width: 250px;
        background: #fff;
        box-shadow: 2px 0 12px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100%;
        overflow-y: auto;
    }

    .sidebar-header {
        padding: 25px;
        border-bottom: 1px solid #e0e6ed;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .avatar {
        font-size: 2.8rem;
        color: #4361ee;
    }

    .user-info h3 {
        margin: 0;
        font-weight: 600;
        font-size: 1.2rem;
    }

    .badge {
        display: inline-block;
        margin-top: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 12px;
        background-color: #4361ee;
        color: white;
        text-transform: capitalize;
    }

    nav.sidebar-nav ul {
        list-style: none;
        padding: 0;
        margin: 0;
        flex-grow: 1;
    }

    nav.sidebar-nav ul li {
        border-bottom: 1px solid #f0f2f5;
    }

    nav.sidebar-nav ul li a {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 25px;
        color: #495057;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.3s;
    }

    nav.sidebar-nav ul li a:hover,
    nav.sidebar-nav ul li a.active {
        background-color: #4361ee;
        color: white;
    }

    nav.sidebar-nav ul li a i {
        font-size: 1.1rem;
        min-width: 20px;
    }

    .sidebar-footer {
        padding: 20px 25px;
        border-top: 1px solid #e0e6ed;
    }

    .logout-link {
        color: #dc3545;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logout-link:hover {
        text-decoration: underline;
    }

    main.main-content {
        margin-left: 250px;
        padding: 30px;
        flex: 1;
        overflow-y: auto;
    }

    header.main-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e6ed;
    }

    header.main-header h1 {
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Tabla de medicamentos */

    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        padding: 25px;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        min-width: 600px;
    }

    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
    }

    th {
        background: #4361ee;
        color: white;
        font-weight: 600;
    }

    tr:hover {
        background-color: #f8f9fa;
    }

    .no-data {
        background: white;
        padding: 40px 20px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        text-align: center;
        color: #6c757d;
        font-size: 1.1rem;
    }

    .btn-back {
        display: inline-block;
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

    /* Responsive */

    @media (max-width: 768px) {
        aside.sidebar {
            position: relative;
            width: 100%;
            height: auto;
            box-shadow: none;
        }
        main.main-content {
            margin-left: 0;
            padding: 15px;
        }
        table {
            min-width: unset;
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
                    <span class="badge"><?= ucfirst($rol) ?></span>
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
            
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <h1><i class="fas fa-pills"></i> Medicamentos de Pacientes Asignados</h1>
            <div class="current-time">
                <i class="far fa-clock"></i>
                <?= date('d/m/Y H:i') ?>
            </div>
        </header>

        <div class="table-container">
            <?php if (count($medicamentos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Paciente</th>
                            <th><i class="fas fa-medkit"></i> Medicamento</th>
                            <th><i class="fas fa-syringe"></i> Dosis</th>
                            <th><i class="fas fa-redo"></i> Frecuencia</th>
                            <th><i class="fas fa-clock"></i> Horario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicamentos as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['paciente']) ?></td>
                                <td><?= htmlspecialchars($m['nombre']) ?></td>
                                <td><?= htmlspecialchars($m['dosis']) ?></td>
                                <td><?= htmlspecialchars($m['frecuencia']) ?></td>
                                <td><?= htmlspecialchars($m['horario']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 36px; margin-bottom: 15px;"></i>
                    No hay medicamentos registrados para tus pacientes.
                </div>
            <?php endif; ?>
        </div>

        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
    </main>
</div>

</body>
</html>
