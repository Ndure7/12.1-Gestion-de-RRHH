<?php
/**
 * Conexion a la base de datos - Caso 12.1 (RRHH - Vacaciones)
 */

// ----- Configuracion de conexion -----
// Ajustar estos datos segun tu entorno (XAMPP/WAMP/Laragon/servidor propio)
define('DB_HOST', 'localhost');
define('DB_NAME', 'rrhh_vacaciones');
define('DB_USER', 'root');
define('DB_PASS', '');

function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => 500,
            "mensaje" => "Error de conexion a la base de datos: " . $e->getMessage()
        ]);
        exit;
    }
}
