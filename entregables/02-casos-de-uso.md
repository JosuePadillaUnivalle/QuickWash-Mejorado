# Casos de uso y actores
QuickWash Campus | V1 Mejorado

## Actores
Estudiante: registra su cuenta, consulta disponibilidad, reserva y consulta/cancela solicitudes propias.
Personal de lavandería: consulta todas las solicitudes, modifica sus estados y administra el catálogo de lavadoras.
La base de datos es interna, no un actor.

## CU-01 · Registrar estudiante
Actor: Estudiante. Precondición: sin sesión.
Flujo: abrir Registro; ingresar nombre, correo @est.univalle.edu, contraseña y confirmación; validar; guardar rol Estudiante; iniciar sesión; abrir panel.
Alternativas: dominio distinto a @est.univalle.edu, duplicados o datos inválidos; informar sin crear cuenta.
Postcondición: cuenta y sesión disponibles. HU-01.

## CU-02 · Iniciar sesión
Actores: Estudiante y Personal. Precondición: cuenta existente.
Flujo: ingresar credenciales; validar contraseña y dominio @est.univalle.edu para estudiantes; renovar sesión; abrir panel del rol. Personal utiliza su correo asignado.
Alternativas: credenciales incorrectas o exceso de intentos; denegar.
Postcondición: sesión autenticada. HU-02.

## CU-03 · Consultar disponibilidad
Actor: Estudiante. Precondición: sesión de estudiante.
Flujo: elegir fecha/turno; consultar; mostrar lavadoras libres, ocupadas y en mantenimiento.
Alternativas: datos inválidos, turno iniciado o todas ocupadas; informar y permitir otra selección.
Postcondición: disponibilidad consultada, sin reserva. HU-03.

## CU-04 · Registrar reserva
Actor: Estudiante. Precondición: sesión de estudiante.
Flujo: ejecutar CU-03; seleccionar lavadora; ingresar prendas; confirmar; validar datos, turno y cupo; guardar Pendiente y ocupar turno en una transacción; mostrar código.
Alternativas: ocupación concurrente, tres activas, datos inválidos o mantenimiento; rechazar sin registros parciales.
Postcondición: reserva persistida y turno exclusivo. HU-04.
Incluye CU-03: la consulta de disponibilidad es obligatoria en el flujo de reserva. La revalidación atómica al guardar es además una regla interna.

## CU-05 · Consultar mis reservas
Actor: Estudiante. Precondición: sesión de estudiante.
Flujo: abrir Mis reservas; listar exclusivamente las propias; aplicar filtros opcionales y consultar.
Alternativa: sin resultados; mostrar mensaje.
Postcondición: información consultada. HU-05.
Punto de extensión: selección de cancelación de una reserva elegible.

## CU-06 · Cancelar reserva
Actor: Estudiante. Precondiciones: sesión; reserva propia, Pendiente y con inicio futuro.
Flujo: seleccionar Cancelar desde Mis reservas; confirmar; revalidar permisos/estado/hora; cambiar a Cancelada y liberar turno.
Alternativas: desistir o reserva ya cambiada/iniciada; no cancelar.
Postcondición: turno y cupo liberados. HU-06.
Extiende CU-05 opcionalmente en el punto “cancelación elegible”.

## CU-07 · Consultar todas las reservas
Actor: Personal. Precondición: sesión de personal.
Flujo: abrir listado global; consultar estudiantes y solicitudes; filtrar.
Alternativa: no hay coincidencias; informar sin modificar.
Postcondición: reservas consultadas. HU-07.
Punto de extensión: selección de actualización de una reserva.

## CU-08 · Cambiar estado
Actor: Personal. Precondiciones: sesión de personal; reserva con transición disponible.
Flujo: elegir nuevo estado desde el listado; pulsar Actualizar; confirmar; validar rol/transición; guardar exclusivamente estado; mostrar resultado.
Alternativas: salto inválido, inicio anticipado, conflicto de estado o cambio de otro campo; rechazar.
Postcondición: el estudiante puede consultar el nuevo estado. HU-08.
Extiende CU-07: consultar no exige modificar.

## CU-09 · Cerrar sesión
Actores: Estudiante y Personal. Precondición: sesión activa.
Flujo: pulsar Cerrar sesión; invalidar sesión; regresar a Ingresar.
Postcondición: rutas protegidas inaccesibles sin autenticarse. HU-09.

## CU-10 · Crear lavadora
Actor: Personal. Precondición: sesión del personal.
Flujo: abrir catálogo; Crear lavadora; ingresar nombre, capacidad, ubicación y estado; guardar; validar; mostrar equipo.
Alternativas: datos inválidos o nombre repetido; informar sin guardar. Postcondición: lavadora registrada. HU-10.

## CU-11 · Editar lavadora
Actor: Personal. Precondición: lavadora existente y sesión del personal.
Flujo: abrir Editar desde el catálogo; modificar datos; guardar; validar; mostrar resultado.
Alternativas: datos inválidos o paso a mantenimiento con reservas activas; rechazar. Postcondición: equipo actualizado. HU-11.

## CU-12 · Eliminar lavadora
Actor: Personal. Precondición: lavadora existente sin reservas activas.
Flujo: pulsar Eliminar; confirmar; revalidar permisos y reservas; retirar del catálogo mediante eliminación lógica.
Alternativas: hay reservas activas o el equipo ya fue eliminado; no retirar. Postcondición: no admite nuevas reservas y conserva su historial. HU-12.

## Diagrama y semántica
Ver diagrama-casos-de-uso.svg y su fuente editable .puml.
Asociaciones: línea continua. Include/extend: línea discontinua dirigida al caso incluido/base.
Iniciar sesión es precondición de operaciones protegidas, no include repetido: una sesión permite varias operaciones.
No se modelan notificaciones ni cobros. CU-10 a CU-12 incorporan la ampliación de administración del catálogo solicitada; no cambian la regla de edición exclusiva del estado en las reservas.
