# Historias de usuario
QuickWash Campus | V1 Mejorado | UNIVALLE

## Alcance y fuente
Fuente: enunciado del examen entregado por el solicitante. Se implementa la organización digital de turnos; la reducción efectiva de filas deberá medirse en un piloto.

## HU-01 · Registro de estudiante
Como estudiante, quiero registrarme con mi nombre, correo y contraseña para acceder a las reservas.
Prioridad: alta. Caso: CU-01.
- Con correo nuevo y datos válidos, se crea una cuenta Estudiante y se inicia su sesión.
- Correo duplicado, campo vacío o confirmación incorrecta produce errores sin crear la cuenta.
- La contraseña tiene mínimo ocho caracteres y se guarda como hash.
- El registro público no permite obtener el rol Personal.

## HU-02 · Inicio de sesión
Como estudiante o miembro del personal, quiero iniciar sesión para acceder a las funciones de mi rol.
Prioridad: alta. Caso: CU-02.
- Credenciales correctas abren el panel del rol; credenciales incorrectas no autentican.
- Se renueva la sesión y se limita la repetición de intentos.
- Sin sesión no se accede a reservas.

## HU-03 · Disponibilidad de lavadoras
Como estudiante, quiero consultar lavadoras por fecha y horario para elegir un turno disponible antes de acudir al campus.
Prioridad: alta. Caso: CU-03.
- La consulta muestra disponibilidad para el turno seleccionado.
- Mantenimiento, ocupación o turno iniciado impiden seleccionar la máquina.
- Al guardar se revalida la disponibilidad por si otra persona reservó mientras se consultaba.

## HU-04 · Crear reserva con prendas
Como estudiante, quiero reservar una lavadora con fecha, horario y cantidad de prendas para asegurar mi turno sin hacer fila.
Prioridad: alta. Caso: CU-04.
- Los cuatro datos son obligatorios; prendas debe ser un entero válido.
- Con datos válidos y menos de tres activas, se guarda una reserva Pendiente con código.
- Se rechaza un cuarto turno activo o un turno de lavadora ya ocupado.
- La validación y ocupación son atómicas, incluso con solicitudes simultáneas.
- La cantidad se conserva y aparece en los listados.

## HU-05 · Consultar mis reservas
Como estudiante, quiero consultar mis reservas y sus estados para organizar mis lavados y saber cuándo ha finalizado mi ropa.
Prioridad: alta. Caso: CU-05.
- Solo veo mis reservas, con código, máquina, fecha, intervalo, prendas y estado.
- Puedo filtrar por fecha, estado y búsqueda; el historial se pagina.
- Al actualizar la página veo el estado guardado por el personal.

## HU-06 · Cancelar reserva
Como estudiante, quiero cancelar una reserva pendiente antes de su inicio para liberar el turno.
Prioridad: alta. Caso: CU-06.
- La reserva debe ser propia, Pendiente y con hora de inicio estrictamente futura.
- Al confirmar pasa a Cancelada y libera turno y cupo.
- Exactamente al inicio, y después, la cancelación se rechaza aunque siga Pendiente.
- No puedo cancelar reservas ajenas, En proceso, Finalizadas o Canceladas.

## HU-07 · Consultar todas las reservas
Como personal, quiero visualizar las reservas de todos los estudiantes para organizar la atención.
Prioridad: alta. Caso: CU-07.
- Veo estudiante, máquina, fecha, horario, prendas y estado; puedo filtrar.
- Un estudiante no accede a información ajena mediante este listado.
- El personal no edita estudiante, máquina, prendas ni horario.

## HU-08 · Cambiar estado
Como personal, quiero actualizar el estado de una reserva para que el estudiante conozca el avance.
Prioridad: alta. Caso: CU-08.
- Toda reserva nueva comienza Pendiente.
- A partir de su inicio puede pasar a En proceso y luego a Finalizada.
- El personal puede cancelar desde Pendiente o En proceso.
- No se reabren estados terminales ni se salta de Pendiente a Finalizada.
- Se rechazan intentos de modificar otros campos junto al estado.

## HU-09 · Cerrar sesión
Como usuario autenticado, quiero cerrar sesión para proteger mi cuenta al terminar.
Prioridad: media. Caso: CU-09.
- Se invalida la sesión y se renueva la protección CSRF.
- Volver a una ruta protegida solicita ingreso.
- Cerrar sesión usa POST protegido.

## Reglas de negocio
RN-01: una lavadora no puede asignarse a más de un estudiante en el mismo turno.
RN-02: máximo tres reservas activas por estudiante.
RN-03: el estudiante solo cancela Pendientes.
RN-04: el personal consulta todas las reservas y únicamente modifica su estado.
RN-05: cancelación estudiantil solo antes del inicio, en conjunto con RN-03.

## Supuestos explícitos
Activas: Pendiente y En proceso. Turnos: 60 minutos, 08:00 a 20:00, todos los días; último inicio 19:00. Anticipación: 30 días. Zona: America/La_Paz. Prendas: entero de 1 a 100, sin inferir peso.
Se permite al personal cancelar En proceso por una incidencia. No hay notificaciones, registro de recogida, cobros ni automatizaciones.
El catálogo se prepara en la instalación; el CRUD de equipos de la primera versión queda fuera de la interfaz del personal.
