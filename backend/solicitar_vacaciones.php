<?php
/**
 * Caso 12.1 - Gestion de RRHH (Vacaciones)
 *
 * 2. CAPA LOGICA Y VALIDACION (BACKEND)
 *
 * Datos recibidos: id_empleado, fecha_inicio, fecha_fin, dias_solicitados, id_sector
 *
 * Validacion 1: Saldo suficiente
 *   dias_solicitados debe ser menor o igual a saldo_vacaciones del empleado.
 *   Si falla -> Error: "No tenes saldo suficiente"
 *
 * Validacion 2: Duplicado en algun sector
 *   Verificar que ningun otro empleado del mismo id_sector tenga vacaciones
 *   aprobadas en ese rango de fecha.
 *   Si falla -> Error: "Ya hay un empleado del mismo sector de vacaciones
 *                        en ese rango de fechas"
 *
 * Procesamiento matematico/logico:
 *   dias_solicitados <= saldo_vacaciones
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => 405, "mensaje" => "Metodo no permitido"]);
    exit;
}

// ----- 1. CAPA DE INTERFAZ: datos enviados desde el formulario -----
$input = json_decode(file_get_contents('php://input'), true);

$id_empleado     = isset($input['id_empleado']) ? (int) $input['id_empleado'] : null;
$fecha_inicio    = $input['fecha_inicio'] ?? null;
$fecha_fin       = $input['fecha_fin'] ?? null;
$dias_solicitados = isset($input['dias_solicitados']) ? (int) $input['dias_solicitados'] : null;
$id_sector       = isset($input['id_sector']) ? (int) $input['id_sector'] : null;

// Validacion basica de datos recibidos
if (!$id_empleado || !$fecha_inicio || !$fecha_fin || !$dias_solicitados || !$id_sector) {
    http_response_code(400);
    echo json_encode([
        "status" => 400,
        "mensaje" => "Faltan datos obligatorios: id_empleado, fecha_inicio, fecha_fin, dias_solicitados, id_sector"
    ]);
    exit;
}

$pdo = getConnection();

try {
    // Verificar que el empleado exista y pertenezca al sector indicado
    $stmt = $pdo->prepare("SELECT * FROM empleados WHERE id_empleado = :id_empleado");
    $stmt->execute(['id_empleado' => $id_empleado]);
    $empleado = $stmt->fetch();

    if (!$empleado) {
        http_response_code(404);
        echo json_encode(["status" => 404, "mensaje" => "El empleado no existe"]);
        exit;
    }

    $saldo_vacaciones = (int) $empleado['saldo_vacaciones'];

    // ----------------------------------------------------
    // VALIDACION 1: Saldo suficiente
    // dias_solicitados <= saldo_vacaciones del empleado
    // ----------------------------------------------------
    if ($dias_solicitados > $saldo_vacaciones) {
        http_response_code(400);
        echo json_encode([
            "status" => 400,
            "mensaje" => "No tenes saldo suficiente"
        ]);
        exit;
    }

    // ----------------------------------------------------
    // VALIDACION 2: Duplicado en algun sector
    // Ningun otro empleado del mismo id_sector debe tener
    // vacaciones APROBADAS que se superpongan con el rango pedido.
    // ----------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS cantidad
        FROM vacaciones
        WHERE id_sector = :id_sector
          AND id_empleado != :id_empleado
          AND estado = 'Aprobada'
          AND fecha_inicio <= :fecha_fin
          AND fecha_fin >= :fecha_inicio
    ");
    $stmt->execute([
        'id_sector'   => $id_sector,
        'id_empleado' => $id_empleado,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin'    => $fecha_fin,
    ]);
    $duplicado = $stmt->fetch();

    if ((int) $duplicado['cantidad'] > 0) {
        http_response_code(400);
        echo json_encode([
            "status" => 400,
            "mensaje" => "Ya hay un empleado del mismo sector de vacaciones en ese rango de fechas"
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 3. CAPA DE PERSISTENCIA: operacion de escritura
    // Tabla: vacaciones
    //   id_empleado, fecha_inicio, fecha_fin, dias, estado="Pendiente"
    // ----------------------------------------------------
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO vacaciones (id_empleado, id_sector, fecha_inicio, fecha_fin, dias, estado)
        VALUES (:id_empleado, :id_sector, :fecha_inicio, :fecha_fin, :dias, 'Pendiente')
    ");
    $stmt->execute([
        'id_empleado'  => $id_empleado,
        'id_sector'    => $id_sector,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin'    => $fecha_fin,
        'dias'         => $dias_solicitados,
    ]);

    // Se descuenta el saldo del empleado (queda reservado al quedar Pendiente)
    $saldo_restante = $saldo_vacaciones - $dias_solicitados;

    $stmt = $pdo->prepare("
        UPDATE empleados SET saldo_vacaciones = :saldo WHERE id_empleado = :id_empleado
    ");
    $stmt->execute([
        'saldo'       => $saldo_restante,
        'id_empleado' => $id_empleado,
    ]);

    $pdo->commit();

    // ----------------------------------------------------
    // 4. RETORNO Y FEEDBACK
    // Respuesta tecnica: { "status": 201, "mensaje": "Solicitud enviada",
    //                       "saldo_restante": 6 }
    // ----------------------------------------------------
    http_response_code(201);
    echo json_encode([
        "status" => 201,
        "mensaje" => "Solicitud enviada",
        "saldo_restante" => $saldo_restante
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "status" => 500,
        "mensaje" => "Error al procesar la solicitud: " . $e->getMessage()
    ]);
}
