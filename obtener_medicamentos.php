<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['paciente_id'])) {
    header("HTTP/1.1 400 Bad Request");
    exit;
}

$paciente_id = $_GET['paciente_id'];
$rol = $_SESSION['rol'];
$usuario_id = $_SESSION['usuario_id'];

// Verificar permisos (solo admin/cuidador pueden ver medicamentos de otros)
if ($rol === 'cuidador') {
    $stmt = $conn->prepare("SELECT 1 FROM asignaciones WHERE cuidador_id = ? AND paciente_id = ?");
    $stmt->bind_param("ii", $usuario_id, $paciente_id);
    $stmt->execute();
    if (!$stmt->get_result()->num_rows) {
        header("HTTP/1.1 403 Forbidden");
        exit;
    }
} elseif ($rol === 'paciente' && $paciente_id != $usuario_id) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$stmt = $conn->prepare("SELECT id, nombre FROM medicamentos WHERE usuario_id = ?");
$stmt->bind_param("i", $paciente_id);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: application/json');
echo json_encode($result->fetch_all(MYSQLI_ASSOC));
?>