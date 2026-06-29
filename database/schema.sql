-- ============================================
-- Caso 12.1 - Gestion de RRHH (Vacaciones)
-- Script de creacion de base de datos
-- ============================================

CREATE DATABASE IF NOT EXISTS rrhh_vacaciones
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rrhh_vacaciones;

-- --------------------------------------------
-- Tabla: sectores
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS sectores (
  id_sector INT AUTO_INCREMENT PRIMARY KEY,
  nombre_sector VARCHAR(100) NOT NULL
);

-- --------------------------------------------
-- Tabla: empleados
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS empleados (
  id_empleado INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  apellido VARCHAR(100) NOT NULL,
  id_sector INT NOT NULL,
  saldo_vacaciones INT NOT NULL DEFAULT 14,
  FOREIGN KEY (id_sector) REFERENCES sectores(id_sector)
);

-- --------------------------------------------
-- Tabla: vacaciones
-- Tal como figura en el papel:
-- id_empleado, fecha_inicio, fecha_fin, dias, estado
-- (se agrega id_sector e id propio para poder
--  validar duplicados por sector y para tener PK)
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS vacaciones (
  id_vacacion INT AUTO_INCREMENT PRIMARY KEY,
  id_empleado INT NOT NULL,
  id_sector INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin DATE NOT NULL,
  dias INT NOT NULL,
  estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente', -- Pendiente / Aprobada / Rechazada
  fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado),
  FOREIGN KEY (id_sector) REFERENCES sectores(id_sector)
);

-- --------------------------------------------
-- Datos de ejemplo (segun el caso de papel)
-- --------------------------------------------
INSERT INTO sectores (id_sector, nombre_sector) VALUES
  (6, 'Sistemas'),
  (1, 'Administracion'),
  (2, 'Ventas');

INSERT INTO empleados (id_empleado, nombre, apellido, id_sector, saldo_vacaciones) VALUES
  (13, 'Juan', 'Perez', 6, 20),
  (14, 'Maria', 'Gomez', 6, 14),
  (15, 'Carlos', 'Lopez', 1, 10);
