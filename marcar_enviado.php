<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$sql = "SELECT * FROM recordatorios WHERE id = ? AND usuario_id = ? AND enviado = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false]);
    exit;
}

$sqlUpdate = "UPDATE recordatorios SET enviado = 1 WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("i", $id);
$success = $stmtUpdate->execute();

echo json_encode(['success' => $success]);
