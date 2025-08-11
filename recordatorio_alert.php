<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$nombre_usuario = $_SESSION['nombre'];
$rol = $_SESSION['rol'];

// Obtener recordatorios pendientes relacionados con medicamentos
$sql = "SELECT r.id, r.mensaje, r.fecha_envio, m.nombre AS medicamento, m.dosis, m.frecuencia
        FROM recordatorios r
        JOIN medicamentos m ON r.medicamento_id = m.id
        WHERE r.usuario_id = ? AND r.enviado = 0 AND r.fecha_envio <= NOW()
        ORDER BY r.fecha_envio ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$recordatorios_alerta = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorios de Medicamentos - MediControl</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .main-content {
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-description {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        /* Estilos para la lista de recordatorios */
        .recordatorios-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 20px;
        }
        
        .recordatorio-item {
            padding: 15px;
            border-bottom: 1px solid #e0e6ed;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .recordatorio-item:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        
        .recordatorio-item:last-child {
            border-bottom: none;
        }
        
        .recordatorio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .recordatorio-medicamento {
            font-weight: 600;
            color: #4361ee;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }
        
        .recordatorio-detalles {
            display: flex;
            gap: 15px;
            margin-top: 8px;
            font-size: 0.9rem;
        }
        
        .recordatorio-detalle {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #495057;
        }
        
        .recordatorio-fecha {
            color: #6c757d;
            font-size: 0.9rem;
            background: #f1f3f9;
            padding: 3px 8px;
            border-radius: 12px;
        }
        
        .recordatorio-mensaje {
            color: #495057;
            line-height: 1.5;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #4361ee;
        }
        
        .no-recordatorios {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        
        .no-recordatorios i {
            font-size: 3rem;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        /* Estilos para el modal */
        .modal-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .modal-title {
            color: #4361ee;
            margin-top: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-title i {
            font-size: 1.8rem;
        }
        
        .modal-content {
            margin: 20px 0;
            line-height: 1.6;
        }
        
        .modal-info-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .modal-detalles {
            margin-top: 15px;
        }
        
        .modal-detalle {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #495057;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #4361ee;
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #3a56d4;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #dee2e6;
            color: #495057;
        }
        
        .btn-outline:hover {
            background: #f8f9fa;
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
            .modal {
                width: 95%;
                padding: 20px;
            }
            
            .modal-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .recordatorio-detalles {
                flex-direction: column;
                gap: 8px;
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
                            <i class="fas fa-bell"></i>
                            <span>Crear Recordatorio</span>
                        </a>
                    </li>
                    <li>
                        <a href="ver_recordatorios.php" class="active">
                            <i class="fas fa-bell"></i>
                            <span>Recordatorios</span>
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
            <h1><i class="fas fa-bell"></i> Recordatorios de Medicamentos</h1>
            <div class="current-time">
                <i class="far fa-clock"></i>
                <?= date('d/m/Y H:i') ?>
            </div>
        </header>

        <div class="page-description">
            <p>Aquí puedes ver y gestionar todos los recordatorios relacionados con tus medicamentos.</p>
        </div>

        <div class="recordatorios-container">
            <?php if (count($recordatorios_alerta) > 0): ?>
                <?php foreach ($recordatorios_alerta as $recordatorio): ?>
                    <div class="recordatorio-item" data-id="<?= $recordatorio['id'] ?>">
                        <div class="recordatorio-header">
                            <div class="recordatorio-medicamento">
                                <i class="fas fa-pills"></i>
                                <?= htmlspecialchars($recordatorio['medicamento']) ?>
                            </div>
                            <div class="recordatorio-fecha">
                                <?= date('d/m/Y H:i', strtotime($recordatorio['fecha_envio'])) ?>
                            </div>
                        </div>
                        <div class="recordatorio-detalles">
                            <div class="recordatorio-detalle">
                                <i class="fas fa-syringe"></i>
                                <span>Dosis: <?= htmlspecialchars($recordatorio['dosis']) ?></span>
                            </div>
                            <div class="recordatorio-detalle">
                                <i class="fas fa-redo"></i>
                                <span>Frecuencia: <?= htmlspecialchars($recordatorio['frecuencia']) ?></span>
                            </div>
                        </div>
                        <div class="recordatorio-mensaje">
                            <?= htmlspecialchars($recordatorio['mensaje']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-recordatorios">
                    <i class="fas fa-check-circle"></i>
                    <h3>No tienes recordatorios pendientes</h3>
                    <p>Todos tus recordatorios de medicamentos están al día.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal para ver el recordatorio -->
        <div id="modal-bg" class="modal-bg" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="modal">
                <h3 class="modal-title"><i class="fas fa-bell"></i> Recordatorio de Medicamento</h3>
                <div class="modal-content">
                    <p id="mensaje-recordatorio"></p>
                    
                    <div class="modal-info-box">
                        <i class="fas fa-pills"></i>
                        <div>
                            <strong id="medicamento-recordatorio"></strong>
                            <div class="modal-detalles">
                                <div class="modal-detalle">
                                    <i class="fas fa-syringe"></i>
                                    <span id="dosis-recordatorio"></span>
                                </div>
                                <div class="modal-detalle">
                                    <i class="fas fa-redo"></i>
                                    <span id="frecuencia-recordatorio"></span>
                                </div>
                                <div class="modal-detalle">
                                    <i class="far fa-clock"></i>
                                    <span id="fecha-recordatorio"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button id="btn-cancelar" class="btn btn-outline">
                        <i class="fas fa-times"></i> Cerrar
                    </button>
                    <button id="marcar-enviado" class="btn btn-primary">
                        <i class="fas fa-check"></i> Marcar como completado
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const modalBg = document.getElementById('modal-bg');
const mensajeEl = document.getElementById('mensaje-recordatorio');
const medicamentoEl = document.getElementById('medicamento-recordatorio');
const dosisEl = document.getElementById('dosis-recordatorio');
const frecuenciaEl = document.getElementById('frecuencia-recordatorio');
const fechaEl = document.getElementById('fecha-recordatorio');
const btnMarcar = document.getElementById('marcar-enviado');
const btnCancelar = document.getElementById('btn-cancelar');

let recordatorioActualId = null;

// Mostrar modal al hacer clic en un recordatorio
document.querySelectorAll('.recordatorio-item').forEach(item => {
    item.addEventListener('click', () => {
        const recordatorio = {
            id: item.dataset.id,
            mensaje: item.querySelector('.recordatorio-mensaje').textContent,
            medicamento: item.querySelector('.recordatorio-medicamento').textContent.trim(),
            dosis: item.querySelector('.recordatorio-detalle:nth-child(1) span').textContent.replace('Dosis: ', ''),
            frecuencia: item.querySelector('.recordatorio-detalle:nth-child(2) span').textContent.replace('Frecuencia: ', ''),
            fecha: item.querySelector('.recordatorio-fecha').textContent
        };
        
        mostrarRecordatorio(recordatorio);
    });
});

function mostrarRecordatorio(recordatorio) {
    recordatorioActualId = recordatorio.id;
    mensajeEl.textContent = recordatorio.mensaje;
    medicamentoEl.textContent = recordatorio.medicamento;
    dosisEl.textContent = recordatorio.dosis;
    frecuenciaEl.textContent = recordatorio.frecuencia;
    fechaEl.textContent = recordatorio.fecha;
    modalBg.style.display = 'flex';
    btnMarcar.focus();
}

function ocultarModal() {
    modalBg.style.display = 'none';
    recordatorioActualId = null;
}

// Marcar como completado
btnMarcar.addEventListener('click', () => {
    if (!recordatorioActualId) return;
    
    fetch('marcar_enviado.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${recordatorioActualId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Eliminar el recordatorio de la lista
            const item = document.querySelector(`.recordatorio-item[data-id="${recordatorioActualId}"]`);
            if (item) item.remove();
            
            // Verificar si quedan recordatorios
            if (document.querySelectorAll('.recordatorio-item').length === 0) {
                document.querySelector('.recordatorios-container').innerHTML = `
                    <div class="no-recordatorios">
                        <i class="fas fa-check-circle"></i>
                        <h3>No tienes recordatorios pendientes</h3>
                        <p>Todos tus recordatorios de medicamentos están al día.</p>
                    </div>
                `;
            }
            
            ocultarModal();
        } else {
            console.error("Error al marcar como completado");
        }
    })
    .catch(error => console.error("Error:", error));
});

// Cerrar modal
btnCancelar.addEventListener('click', ocultarModal);

// Cerrar modal al hacer clic fuera del contenido
modalBg.addEventListener('click', (e) => {
    if (e.target === modalBg) {
        ocultarModal();
    }
});
</script>
</body>
</html>