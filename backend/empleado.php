<?php
/**
 * Caso 12.1 - Devuelve los datos de un empleado (incluye saldo_vacaciones)
 * Uso: empleado.php?id_empleado=13
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

$id_empleado = isset($_GET['id_empleado']) ? (int) $_GET['id_empleado'] : null;

if (!$id_empleado) {
    http_response_code(400);
    echo json_encode(["status" => 400, "mensaje" => "Falta id_empleado"]);
    exit;
}

$pdo = getConnection();

$stmt = $pdo->prepare("
    SELECT e.id_empleado, e.nombre, e.apellido, e.saldo_vacaciones, e.id_sector, s.nombre_sector
    FROM empleados e
    JOIN sectores s ON s.id_sector = e.id_sector
    WHERE e.id_empleado = :id_empleado
");
$stmt->execute(['id_empleado' => $id_empleado]);
$empleado = $stmt->fetch();

if (!$empleado) {
    http_response_code(404);
    echo json_encode(["status" => 404, "mensaje" => "Empleado no encontrado"]);
    exit;
}

echo json_encode(["status" => 200, "empleado" => $empleado]);
