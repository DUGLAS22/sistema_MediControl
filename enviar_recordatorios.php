<?php
session_start();
require 'db.php';

// Obtener todos los recordatorios no enviados (sin filtro de fecha)
$sql = "SELECT r.*, u.nombre AS paciente, m.nombre AS medicamento 
        FROM recordatorios r
        JOIN usuarios u ON r.usuario_id = u.id
        JOIN medicamentos m ON r.medicamento_id = m.id
        WHERE r.enviado = 0";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Recordatorios Pendientes</h2>";
    
    while($recordatorio = $result->fetch_assoc()) {
        // Mostrar info del recordatorio
        echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid #ccc;'>";
        echo "<p><strong>Paciente:</strong> ".$recordatorio['paciente']."</p>";
        echo "<p><strong>Medicamento:</strong> ".$recordatorio['medicamento']."</p>";
        echo "<p><strong>Mensaje:</strong> ".$recordatorio['mensaje']."</p>";
        echo "<p><strong>Fecha Programada:</strong> ".$recordatorio['fecha_envio']."</p>";
        
        // Botón para marcar como enviado
        echo "<form method='POST' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='id' value='".$recordatorio['id']."'>";
        echo "<button type='submit' name='enviar' style='padding: 5px 10px; background: #4361ee; color: white; border: none; border-radius: 4px; cursor: pointer;'>";
        echo "Marcar como Enviado";
        echo "</button>";
        echo "</form>";
        echo "</div>";
    }
} else {
    echo "<p>No hay recordatorios pendientes.</p>";
}

// Procesar cuando se hace clic en "Marcar como Enviado"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {
    $id = $_POST['id'];
    
    // 1. Insertar notificación
    $recordatorio = $conn->query("SELECT * FROM recordatorios WHERE id = $id")->fetch_assoc();
    $mensaje = "Recordatorio para ".$recordatorio['usuario_id'].": ".$recordatorio['mensaje'];
    $conn->query("INSERT INTO notificaciones (usuario_id, mensaje, fecha) VALUES (".$recordatorio['usuario_id'].", '$mensaje', NOW())");
    
    // 2. Marcar como enviado
    $conn->query("UPDATE recordatorios SET enviado = 1 WHERE id = $id");
    
    // Recargar la página
    header("Location: enviar_recordatorios.php");
    exit;
}
?>