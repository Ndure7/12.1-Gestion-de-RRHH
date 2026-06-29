<?php
/**
 * Caso 12.1 - Lista las solicitudes de vacaciones de un empleado
 * Se usa para pintar el calendario en pantalla (fechas marcadas
 * en amarillo = Pendiente, verde = Aprobada).
 * Uso: vacaciones.php?id_empleado=13
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
    SELECT id_vacacion, fecha_inicio, fecha_fin, dias, estado, fecha_solicitud
    FROM vacaciones
    WHERE id_empleado = :id_empleado
    ORDER BY fecha_inicio DESC
");
$stmt->execute(['id_empleado' => $id_empleado]);
$vacaciones = $stmt->fetchAll();

echo json_encode(["status" => 200, "vacaciones" => $vacaciones]);
