# Caso 12.1 — Gestión de RRHH: Solicitud de Vacaciones

Aplicación web para que un empleado solicite vacaciones, validando saldo disponible
y evitando superposición de vacaciones aprobadas dentro del mismo sector.

## Estructura del proyecto

```
caso12.1-rrhh/
├── backend/
│   ├── db.php                     # Conexión PDO a MySQL
│   ├── solicitar_vacaciones.php   # Endpoint principal (POST)
│   ├── empleado.php                # Endpoint de lectura (GET) - datos + saldo del empleado
│   └── vacaciones.php              # Endpoint de lectura (GET) - listado de solicitudes
├── frontend/
│   ├── index.html
│   ├── style.css
│   └── app.js
├── database/
│   └── schema.sql                 # Creación de la BD + datos de ejemplo
└── README.md
```

## 1. Capa de interfaz de usuario (UI)

**Acción concreta:** el empleado entra a "Solicitar vacaciones", completa un formulario
y presiona el botón **"Enviar solicitud"**.

**Datos enviados al backend:**

```json
{
  "id_empleado": 13,
  "fecha_inicio": "2026-07-01",
  "fecha_fin": "2026-07-15",
  "dias_solicitados": 14,
  "id_sector": 6
}
```

El campo `dias_solicitados` se calcula automáticamente en el frontend a partir del
rango de fechas elegido.

## 2. Capa lógica y validación (Backend)

**Datos recibidos:** `id_empleado`, `fecha_inicio`, `fecha_fin`, `dias_solicitados`, `id_sector`.

**Validación 1 — Saldo suficiente**
`dias_solicitados` debe ser menor o igual a `saldo_vacaciones` del empleado.
Si falla → Error: *"No tenes saldo suficiente"*.

**Validación 2 — Duplicado en el sector**
Se verifica que ningún otro empleado del mismo `id_sector` tenga vacaciones
**aprobadas** que se superpongan con el rango de fechas pedido.
Si falla → Error: *"Ya hay un empleado del mismo sector de vacaciones en ese rango de fechas"*.

**Procesamiento matemático/lógico:**

```
dias_solicitados <= saldo_vacaciones
```

## 3. Capa de persistencia (Base de datos)

**Consultas previas (lectura):**
- Buscar el saldo de vacaciones disponible del empleado.
- Verificar si existe alguna vacación aprobada en el mismo sector que se
  superponga con las fechas pedidas.

**Operación de escritura:**

Tabla `vacaciones`:

```
id_empleado = 13, fecha_inicio = "2026-07-01", fecha_fin = "2026-07-15",
dias = 14, estado = "Pendiente"
```

Además se descuenta el saldo del empleado en la tabla `empleados`
(`saldo_vacaciones = saldo_vacaciones - dias_solicitados`).

## 4. Retorno y feedback

**Respuesta técnica:**

```json
{ "status": 201, "mensaje": "Solicitud enviada", "saldo_restante": 6 }
```

**Actualización visual en pantalla:** aparece un cartel verde
*"Solicitud enviada correctamente"*, la lista de solicitudes muestra el nuevo
pedido marcado en **amarillo (Pendiente)**, y el contador de saldo se actualiza.
Cuando una solicitud pasa a estado `Aprobada` (actualización manual en la base
o futuro panel de RRHH), la tarjeta cambia a **verde**.

## Instalación y ejecución

### Requisitos
- PHP 8+
- MySQL / MariaDB
- Servidor local tipo XAMPP, WAMP, Laragon o `php -S`

### Pasos

1. **Base de datos**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. **Backend**
   - Editar `backend/db.php` con las credenciales de tu MySQL si no usás `root` sin contraseña.
   - Colocar la carpeta `backend/` dentro de tu servidor PHP (por ejemplo `htdocs/caso12.1-rrhh/backend`).

3. **Frontend**
   - Abrir `frontend/index.html` en el navegador, o servirlo desde el mismo
     servidor PHP (`htdocs/caso12.1-rrhh/frontend`).
   - Si el backend corre en otra URL/puerto, actualizar la constante `API_BASE`
     en `frontend/app.js`.

4. Probar con el legajo de ejemplo `id_empleado = 13` (sector 6, saldo inicial 20 días).

## Endpoints

| Método | Endpoint                       | Descripción                                  |
|--------|---------------------------------|-----------------------------------------------|
| POST   | `/backend/solicitar_vacaciones.php` | Crea una solicitud de vacaciones (con validaciones) |
| GET    | `/backend/empleado.php?id_empleado=13` | Devuelve nombre, sector y saldo del empleado |
| GET    | `/backend/vacaciones.php?id_empleado=13` | Lista las solicitudes del empleado |
