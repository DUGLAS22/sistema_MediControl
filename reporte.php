<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];

// Filtro por fechas
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Consulta de tomas por estado
$sql_estados = "SELECT estado, COUNT(*) as total 
                FROM tomas 
                WHERE fecha BETWEEN ? AND ? 
                GROUP BY estado";
$stmt = $conn->prepare($sql_estados);
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$res_estados = $stmt->get_result();
$data_estados = [];
while ($row = $res_estados->fetch_assoc()) {
    $data_estados[$row['estado']] = $row['total'];
}
$stmt->close();

// Consulta de tomas por paciente
$sql_pacientes = "SELECT u.nombre, COUNT(*) as total 
                  FROM tomas t
                  JOIN medicamentos m ON t.medicamento_id = m.id
                  JOIN usuarios u ON m.usuario_id = u.id
                  WHERE t.fecha BETWEEN ? AND ?
                  GROUP BY u.id, u.nombre
                  ORDER BY total DESC";
$stmt = $conn->prepare($sql_pacientes);
$stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
$stmt->execute();
$res_pacientes = $stmt->get_result();
$labels_pacientes = [];
$totales_pacientes = [];
while ($row = $res_pacientes->fetch_assoc()) {
    $labels_pacientes[] = $row['nombre'];
    $totales_pacientes[] = $row['total'];
}
$stmt->close();

// Totales generales
$total_tomas = array_sum($data_estados);
$porcentaje = function($valor) use ($total_tomas) {
    return $total_tomas > 0 ? round(($valor / $total_tomas) * 100, 1) : 0;
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediControl - Reportes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Form styles */
        .filter-form {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #2e384d;
        }
        
        .filter-group input {
            padding: 10px 15px;
            border: 1px solid #e0e6ed;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-primary {
            background: #4361ee;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: flex-end;
            height: 40px;
        }
        
        .btn-primary:hover {
            background: #3a56d4;
        }
        
        /* Chart container */
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-box {
            flex: 1;
            min-width: 300px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .chart-box h3 {
            margin-top: 0;
            color: #2e384d;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Summary table */
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .summary-table th {
            background: #4361ee;
            color: white;
            padding: 15px;
            font-weight: 500;
            text-align: left;
        }
        
        .summary-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .summary-table tr:last-child td {
            border-bottom: none;
        }
        
        .summary-table tr:hover {
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
                        <span class="badge badge-primary">
                            <?= ucfirst($rol) ?>
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
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1><i class="fas fa-chart-bar"></i> Reportes del Sistema</h1>
                <div class="header-actions">
                    <span class="current-time">
                        <i class="far fa-clock"></i>
                        <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </header>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-filter"></i> Filtros</h2>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label for="fecha_inicio">Fecha Inicio</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= $fecha_inicio ?>">
                        </div>
                        <div class="filter-group">
                            <label for="fecha_fin">Fecha Fin</label>
                            <input type="date" id="fecha_fin" name="fecha_fin" value="<?= $fecha_fin ?>">
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                    </form>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-box">
                    <h3><i class="fas fa-chart-pie"></i> Tomas por Estado</h3>
                    <canvas id="chartEstados"></canvas>
                </div>
                <div class="chart-box">
                    <h3><i class="fas fa-chart-bar"></i> Tomas por Paciente</h3>
                    <canvas id="chartPacientes"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-table"></i> Resumen Estadístico</h2>
                </div>
                <div class="card-body">
                    <table class="summary-table">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Porcentaje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge-estado badge-realizada">Realizadas</span></td>
                                <td><?= $data_estados['realizada'] ?? 0 ?></td>
                                <td><?= $porcentaje($data_estados['realizada'] ?? 0) ?>%</td>
                            </tr>
                            <tr>
                                <td><span class="badge-estado badge-omitida">Omitidas</span></td>
                                <td><?= $data_estados['omitida'] ?? 0 ?></td>
                                <td><?= $porcentaje($data_estados['omitida'] ?? 0) ?>%</td>
                            </tr>
                            <tr>
                                <td><span class="badge-estado badge-retrasada">Retrasadas</span></td>
                                <td><?= $data_estados['retrasada'] ?? 0 ?></td>
                                <td><?= $porcentaje($data_estados['retrasada'] ?? 0) ?>%</td>
                            </tr>
                            <tr>
                                <td><strong>Total General</strong></td>
                                <td><strong><?= $total_tomas ?></strong></td>
                                <td><strong>100%</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Datos PHP → JavaScript
    const estadosLabels = <?= json_encode(array_keys($data_estados)) ?>;
    const estadosData = <?= json_encode(array_values($data_estados)) ?>;
    const estadosColors = ['#4CAF50', '#F44336', '#FFC107'];

    const pacientesLabels = <?= json_encode($labels_pacientes) ?>;
    const pacientesData = <?= json_encode($totales_pacientes) ?>;
    const pacientesColors = ['#2196F3', '#FF9800', '#9C27B0', '#009688', '#E91E63', '#607D8B'];

    // Gráfica de dona (estados)
    new Chart(document.getElementById('chartEstados'), {
        type: 'doughnut',
        data: {
            labels: estadosLabels,
            datasets: [{
                data: estadosData,
                backgroundColor: estadosColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfica de barras (pacientes)
    new Chart(document.getElementById('chartPacientes'), {
        type: 'bar',
        data: {
            labels: pacientesLabels,
            datasets: [{
                label: 'Tomas registradas',
                data: pacientesData,
                backgroundColor: pacientesColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    </script>
</body>
</html>