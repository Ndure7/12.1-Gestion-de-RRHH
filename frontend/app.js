/* ============================================
   Caso 12.1 - Portal de Vacaciones (RRHH)
   Logica de la Capa de Interfaz (UI)
   ============================================ */

// Ajustar esta URL base segun donde quede publicado el backend PHP
const API_BASE = "http://localhost/caso12.1-rrhh/backend";

const form = document.getElementById("formVacaciones");
const inputEmpleado = document.getElementById("id_empleado");
const inputSector = document.getElementById("id_sector");
const inputInicio = document.getElementById("fecha_inicio");
const inputFin = document.getElementById("fecha_fin");
const inputDias = document.getElementById("dias_solicitados");
const saldoValor = document.getElementById("saldoValor");
const cajaUsuario = document.getElementById("cajaUsuario");
const mensajeResultado = document.getElementById("mensajeResultado");
const btnEnviar = document.getElementById("btnEnviar");
const listaVacaciones = document.getElementById("listaVacaciones");

// -------------------------------------------------
// Calcula dias_solicitados automaticamente
// segun fecha_inicio y fecha_fin (inclusive)
// -------------------------------------------------
function calcularDias() {
  const inicio = new Date(inputInicio.value);
  const fin = new Date(inputFin.value);
  if (inputInicio.value && inputFin.value && fin >= inicio) {
    const diffMs = fin - inicio;
    const dias = Math.round(diffMs / (1000 * 60 * 60 * 24)) + 1;
    inputDias.value = dias;
  } else {
    inputDias.value = "";
  }
}

inputInicio.addEventListener("change", calcularDias);
inputFin.addEventListener("change", calcularDias);

// -------------------------------------------------
// Carga los datos del empleado (nombre + saldo)
// -------------------------------------------------
async function cargarEmpleado() {
  const id = inputEmpleado.value;
  if (!id) return;

  try {
    const res = await fetch(`${API_BASE}/empleado.php?id_empleado=${id}`);
    const data = await res.json();

    if (data.status === 200) {
      const e = data.empleado;
      cajaUsuario.textContent = `Legajo ${e.id_empleado} · ${e.nombre} ${e.apellido} · ${e.nombre_sector}`;
      saldoValor.textContent = e.saldo_vacaciones;
      inputSector.value = e.id_sector;
    } else {
      cajaUsuario.textContent = "Legajo no encontrado";
      saldoValor.textContent = "—";
    }
  } catch (err) {
    cajaUsuario.textContent = "Sin conexión con el servidor";
  }
}

// -------------------------------------------------
// Carga la lista de solicitudes del empleado
// (pinta tarjetas en lugar de un calendario completo,
//  marcando pendiente = amarillo, aprobada = verde)
// -------------------------------------------------
async function cargarVacaciones() {
  const id = inputEmpleado.value;
  if (!id) return;

  try {
    const res = await fetch(`${API_BASE}/vacaciones.php?id_empleado=${id}`);
    const data = await res.json();

    listaVacaciones.innerHTML = "";

    if (data.status === 200 && data.vacaciones.length > 0) {
      data.vacaciones.forEach((v) => {
        const esAprobada = v.estado === "Aprobada";
        const tarjeta = document.createElement("div");
        tarjeta.className = "tarjeta-vacacion" + (esAprobada ? " tarjeta-vacacion--aprobada" : "");
        tarjeta.innerHTML = `
          <div class="tarjeta-vacacion__fechas">${v.fecha_inicio} → ${v.fecha_fin}</div>
          <div class="tarjeta-vacacion__dias">${v.dias} días</div>
          <span class="tarjeta-vacacion__estado">${v.estado}</span>
        `;
        listaVacaciones.appendChild(tarjeta);
      });
    } else {
      listaVacaciones.innerHTML = `<p class="lista-vacaciones__vacio">Todavía no hay solicitudes cargadas.</p>`;
    }
  } catch (err) {
    listaVacaciones.innerHTML = `<p class="lista-vacaciones__vacio">No se pudieron cargar las solicitudes.</p>`;
  }
}

// -------------------------------------------------
// Envia la solicitud de vacaciones (Accion concreta
// del papel: boton "Enviar Solicitud")
// -------------------------------------------------
form.addEventListener("submit", async (e) => {
  e.preventDefault();

  mensajeResultado.hidden = true;
  btnEnviar.disabled = true;
  btnEnviar.textContent = "Enviando…";

  const payload = {
    id_empleado: parseInt(inputEmpleado.value, 10),
    fecha_inicio: inputInicio.value,
    fecha_fin: inputFin.value,
    dias_solicitados: parseInt(inputDias.value, 10),
    id_sector: parseInt(inputSector.value, 10),
  };

  try {
    const res = await fetch(`${API_BASE}/solicitar_vacaciones.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    mensajeResultado.hidden = false;

    if (data.status === 201) {
      mensajeResultado.className = "mensaje mensaje--ok";
      mensajeResultado.textContent = `Solicitud enviada correctamente. Saldo restante: ${data.saldo_restante} días.`;
      saldoValor.textContent = data.saldo_restante;
      form.reset();
      inputEmpleado.value = payload.id_empleado;
      inputSector.value = payload.id_sector;
      cargarVacaciones();
    } else {
      mensajeResultado.className = "mensaje mensaje--error";
      mensajeResultado.textContent = data.mensaje || "No se pudo enviar la solicitud.";
    }
  } catch (err) {
    mensajeResultado.hidden = false;
    mensajeResultado.className = "mensaje mensaje--error";
    mensajeResultado.textContent = "Error de conexión con el servidor.";
  } finally {
    btnEnviar.disabled = false;
    btnEnviar.textContent = "Enviar solicitud";
  }
});

inputEmpleado.addEventListener("change", () => {
  cargarEmpleado();
  cargarVacaciones();
});

// Carga inicial
cargarEmpleado();
cargarVacaciones();
