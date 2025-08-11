<?php
$servidor = "localhost";
$usuario = "root";
$contrasena = "";
$basedatos = "sistema_medicamento"; // Asegúrate que el nombre esté correcto

$conn = new mysqli($servidor, $usuario, $contrasena, $basedatos);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>