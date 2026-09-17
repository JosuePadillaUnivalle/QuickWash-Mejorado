# Base de datos y migración
Motor verificado: SQLite. Otros motores configurables en Laravel no están validados en esta entrega.

## USERS
id PK; name; email único; password hash; role (estudiante/personal); remember_token; timestamps.
El registro normaliza correo, exige el dominio exacto @est.univalle.edu y asigna rol Estudiante. El acceso aplica la misma regla al estudiante; el personal puede usar otro dominio.
DemoSeeder actualiza solo la cuenta ficticia Alex Rivera de estudiante@quickwash.test a estudiante@est.univalle.edu conservando su ID y reservas. Las demás cuentas históricas no se renombran automáticamente.

## MACHINES
id PK; name único; type; capacity en kg; location; status (disponible/mantenimiento); timestamps; deleted_at.
Se conservan equipos históricos; las nuevas reservas solo ofrecen lavadoras no eliminadas.

## RESERVATIONS
id PK; user_id FK; machine_id FK; starts_at; ends_at; garment_count; status; timestamps.
Código visual: QW-0001, etc. Fechas en America/La_Paz.
garment_count es entero obligatorio en altas nuevas y NULL en registros históricos sin dato.
Estados: pendiente, en_proceso, finalizada, cancelada.
Índice user_id/status para consultas de cupo.

## RESERVATION_SLOTS
id PK; reservation_id FK único; machine_id FK; starts_at.
Índice único machine_id/starts_at impide doble ocupación.
Cancelar elimina la ocupación, no la reserva. Finalizar conserva la ocupación histórica.

## Relaciones
Usuario 1 a N Reservas. Máquina 1 a N Reservas. Reserva 1 a 0..1 Ocupación.
Las FK protegen referencias; el borrado lógico de máquinas conserva historial.
Tablas técnicas Laravel: migrations, sessions, cache, cache_locks, jobs, job_batches, failed_jobs y password_reset_tokens. No existe recuperación de contraseña expuesta.

## Concurrencia
La reserva se crea en transacción. En SQLite con PHP 8.3 se obtiene el bloqueo de escritura antes de leer el cupo; se evita que dos solicitudes consuman el último lugar simultáneamente. Después se revalida máquina/cupo/turno y se inserta reserva + ocupación.
La restricción única es una segunda protección. Un fallo revierte ambas inserciones.
El estado se comprueba otra vez dentro de la transacción. Se probaron carreras con cuatro procesos PHP.

## Migración
La copia tiene .env, clave, cookie y base independientes.
La migración agrega garment_count nullable y transforma listo/entregado a finalizada y cancelado a cancelada. Conserva pendiente/en_proceso.
Los históricos muestran “No registrada / Reserva de versión inicial”; no se inventan cantidades ni se pierden reservas.
Un rollback no recupera la antigua distinción listo/entregado. Restaurar una copia previa para una reversión fiel.

## Restauración
Base operativa local migrada: database/database.sqlite.
Exportación reproducible con datos ficticios: entregables/base-datos/quickwash-demo.sql y quickwash-demo.sqlite.
El SQL es SQLite, no MySQL. Importar en una base vacía con una herramienta SQLite; no sobre una base existente.
La instalación recomendada usa migraciones + DemoSeeder para generar fechas relativas al día.
La base operativa, .env, sesiones y usuarios reales no se publican en el repositorio.

## Administración del catálogo
El personal puede crear, editar y retirar lavadoras. La baja actualiza deleted_at; las reservas conservan su relación mediante withTrashed. El estado de mantenimiento no se deriva de las reservas. La edición que retira de servicio y la eliminación revalidan que no haya Pendientes ni En proceso dentro de una transacción con bloqueo de la máquina, compatible con el bloqueo de las nuevas reservas.
No se requiere migración adicional: los campos y la eliminación lógica ya existían.
