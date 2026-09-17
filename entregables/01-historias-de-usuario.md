# Historias de usuario
QuickWash Campus | V1 Mejorado | UNIVALLE

## Alcance y fuente
Fuente: enunciado del examen entregado por el solicitante. Se implementa la organización digital de turnos; la reducción efectiva de filas deberá medirse en un piloto.

## HU-01 · Registro de estudiante
Como estudiante, quiero registrarme con mi nombre, correo institucional @est.univalle.edu y contraseña para acceder a las reservas.
Prioridad: alta. Caso: CU-01.
- Con correo institucional nuevo y datos válidos, se crea una cuenta Estudiante y se inicia su sesión.
- Correo duplicado, campo vacío o confirmación incorrecta produce errores sin crear la cuenta.
- La contraseña tiene mínimo ocho caracteres y se guarda como hash.
- El registro público no permite obtener el rol Personal.
- Se rechazan otros dominios, subdominios y sufijos similares; se normalizan mayúsculas y espacios exteriores.

## HU-02 · Inicio de sesión
Como estudiante o miembro del personal, quiero iniciar sesión para acceder a las funciones de mi rol.
Prioridad: alta. Caso: CU-02.
- Credenciales correctas abren el panel del rol; credenciales incorrectas no autentican.
- El estudiante debe usar @est.univalle.edu incluso si su cuenta fue creada antes de esta regla. El personal usa su correo asignado.
- Las sesiones anteriores de estudiantes con correo externo se cierran al acceder a una ruta protegida.
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
- El listado actualiza los estados cada diez segundos sin perder los filtros. El inicio ocurre por horario y el fin requiere mi confirmación de recogida.

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

## HU-08 · Supervisar y cancelar reservas futuras
Como personal, quiero supervisar los estados automáticos y cancelar reservas pendientes futuras ante una incidencia para organizar el servicio.
Prioridad: alta. Caso: CU-08. Requisito revisado por el solicitante el 17/09/2026.
- El personal consulta todas las reservas, pero no inicia ni finaliza lavados manualmente.
- Solo puede cancelar una reserva Pendiente antes del horario reservado.
- No puede cambiar el estudiante, la máquina, las prendas ni el horario de la reserva.
- El sistema inicia al llegar la hora y pasa a Esperando recogida al terminar; solo el estudiante confirma la recogida.

## HU-09 · Cerrar sesión
Como usuario autenticado, quiero cerrar sesión para proteger mi cuenta al terminar.
Prioridad: media. Caso: CU-09.
- Se invalida la sesión y se renueva la protección CSRF.
- Volver a una ruta protegida solicita ingreso.
- Cerrar sesión usa POST protegido.

## HU-10 · Crear lavadora
Como personal, quiero crear lavadoras con nombre, capacidad, ubicación y estado de servicio para ampliar el catálogo.
Prioridad: alta. Caso: CU-10. Ampliación solicitada después de la entrega inicial.
- Solo Personal puede crear; los datos se validan y no se admiten nombres repetidos.
- Las lavadoras habilitadas aparecen en la consulta estudiantil; mantenimiento impide reservar.

## HU-11 · Editar lavadora
Como personal, quiero editar los datos y habilitar o poner en mantenimiento un equipo para reflejar su condición real.
Prioridad: alta. Caso: CU-11.
- Mantenimiento significa fuera de servicio y no depende de la ocupación de turnos.
- No se permite pasar a mantenimiento si existen reservas Pendientes, En proceso o Esperando recogida.
- Habilitar una lavadora vuelve a ofrecer sus turnos libres.

## HU-12 · Eliminar lavadora
Como personal, quiero retirar una lavadora del catálogo para que deje de recibir reservas.
Prioridad: alta. Caso: CU-12.
- Se solicita confirmación y se rechaza la operación mientras existan reservas activas.
- Se aplica eliminación lógica: las reservas históricas y su relación con el equipo se conservan.
- Solo Personal puede ejecutar la acción; se revalidan las restricciones al guardar.

## HU-13 · Confirmar recogida
Como estudiante, quiero pulsar Recogido después de retirar mi ropa para finalizar la reserva y liberar la máquina.
Prioridad: alta. Caso: CU-13.
- El botón aparece solo en mis reservas en Esperando recogida, después del fin real del lavado.
- Un diálogo pide confirmar que ya retiré toda la ropa; Volver conserva el estado.
- Confirmar guarda Finalizado y la fecha de recogida, libera mi cupo y permite iniciar el siguiente lavado.
- Personal y otros estudiantes no pueden confirmar en mi nombre; tampoco se puede adelantar la recogida.
- Repetir una confirmación ya completada no cambia la hora ni reinicia el siguiente lavado.

## Reglas de negocio vigentes
RN-01: un turno por máquina y estudiante; nunca se inicia otro lavado mientras quede ropa sin recoger.
RN-02: máximo tres activas: Pendiente, En proceso y Esperando recogida.
RN-03: solo se cancela Pendiente antes del horario original, tanto estudiante como personal; el estudiante solo cancela la propia.
RN-04: personal consulta todas las reservas y administra máquinas; no edita datos de las reservas ni confirma recogidas.
RN-05: Pendiente pasa automáticamente a En proceso al inicio, y a Esperando recogida al fin real. No existe finalización automática.
RN-06: solo la recogida confirmada por el estudiante produce Finalizado y libera la ocupación física.
RN-07: una máquina con recogida pendiente no admite nuevas reservas. Los turnos ya reservados esperan en orden y empiezan al liberarse; mantienen sus sesenta minutos y muestran inicio y fin reales.

## Supuestos explícitos
Turnos: sesenta minutos, inicio entre 08:00 y 19:00, todos los días; horizonte de treinta días. Zona: America/La_Paz. Prendas: entero de 1 a 100, sin inferir peso.
Un retraso de recogida puede prolongar un lavado más allá de las 20:00; no se acorta su duración. No se implementa expulsión automática de reservas ni avisos por correo o SMS.
El sistema automatiza el registro del estado; no controla físicamente el motor de la lavadora. Se supone que el estudiante entrega la ropa a tiempo.
Lavadora 04 se inicializa en mantenimiento como dato de demostración: Personal puede habilitarla desde Editar. Mantenimiento y ocupación son conceptos independientes.
La revisión de recogida sustituye el cambio manual de estados del enunciado inicial por solicitud expresa del usuario.
