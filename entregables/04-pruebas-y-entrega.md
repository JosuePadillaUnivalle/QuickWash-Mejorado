# Pruebas y guía de entrega
QuickWash Campus | Revisión de recogida | 17/09/2026

## Resultado actual
71 pruebas y 408 aserciones aprobadas localmente en SQLite. XML: pruebas/phpunit.xml.
Incluye registro institucional, acceso por rol, validación, cupo, colisiones, cancelación, filtros, migraciones, catálogo y el nuevo flujo de recogida.
GitHub Actions ejecuta también la suite en PostgreSQL independiente; las carreras multiproceso conservan su base SQLite temporal.

## Límites temporales y permisos
Para reserva 16:00 a 17:00: a las 15:59:59 sigue Pendiente; a las 16:00 pasa a En proceso; a las 16:59:59 no se puede recoger; a las 17:00 pasa a Esperando recogida. A las 23:00 sigue esperando, no finaliza sola.
El comando se prueba sin navegador ni intervención del personal. La recogida exige estudiante propietario y lavado terminado. Visitantes, otros estudiantes y personal no pueden confirmarla.
Dos envíos de Recogido conservan la primera hora y no repiten efectos. Finalizado y Cancelada no se reabren.
Tres reservas en Esperando recogida siguen consumiendo el cupo. La máquina no acepta nuevas reservas hasta recoger. También se rechaza retirarla o ponerla en mantenimiento.

## Concurrencia
Cuatro procesos independientes intentan reservar el mismo turno: solo uno gana. Cuatro solicitudes del mismo estudiante en máquinas diferentes: solo tres se guardan.
Dos recogidas y dos sincronizadores compiten sobre la misma máquina: una única recogida efectiva, un solo siguiente lavado En proceso y el tercero permanece Pendiente. Se conservan el instante de liberación y sesenta minutos completos.
Otra prueba simula recogida a las 18:30 de un lavado 16:00–17:00: la reserva de 17:00 empieza realmente 18:30 y termina 19:30. La siguiente sigue esperando hasta recoger.

## Flujo real en navegador local
Se ingresó como Alex Rivera, se seleccionó Lavadora 01 y diez prendas, y se confirmó una reserva QW-0009 desde el formulario.
Solo para probar el paso del tiempo sin esperar una hora, se desplazó en la base local de demostración el intervalo de esa reserva a 12:03:55–13:03:55. Se conservó la duración de sesenta minutos; no se cambió el reloj del equipo ni datos de producción.
Sin pulsar Actualizar, la tabla mostró En proceso y después Esperando recogida, mediante consulta automática. El filtro de fecha permaneció seleccionado.
Se pulsó Recogido y luego Volver: no cambió el estado. En el segundo intento se confirmó y apareció Finalizado con la hora de recogida. Lavadora 01 volvió a estar seleccionable en Reservar máquina.
Se verificó la vista de 390×844: documento de 375 px, sin desbordamiento horizontal. No se registraron errores de JavaScript durante el flujo.

## Evidencias
26-inicio-automatico.png: estado En proceso sin acción del personal.
27-esperando-recogida.png: botón Recogido y aviso de máquina aún ocupada.
28-recogido-finalizado.png: confirmación, estado Finalizado y hora de recogida.
Las capturas 01 a 25 se conservan como historial de las entregas previas. En particular, 05, 24 y 25 muestran el antiguo flujo manual, sustituido en esta revisión. El PDF presenta el flujo vigente y las evidencias institucionales y del catálogo todavía aplicables.
Total: veintiocho PNG, todos dentro de Capturas del sistema.zip.

## Demostración al docente
Iniciar con INICIAR.cmd, registrar o usar el estudiante de demostración y crear una reserva futura con prendas.
Verificar Pendiente antes del horario y En proceso cuando comience. Después de sesenta minutos aparecerá Esperando recogida: retirar la ropa, pulsar Recogido y confirmar.
Mostrar que la reserva queda Finalizado, disminuye el cupo y la máquina se libera; si hay un turno esperando, comienza automáticamente.
Con Personal, mostrar el catálogo: crear, editar, habilitar y retirar equipos sin reservas activas. No se muestran controles de inicio o finalización manual.
Para demostrar los límites sin esperar, ejecutar probar.ps1: las pruebas controlan su propio reloj y usan bases aisladas.

## Ejecución y límites
El proceso de reloj debe permanecer activo. INICIAR.cmd lo abre junto al servidor local; Railway lo supervisa con el servidor. La tabla consulta cambios cada diez segundos y no reemplaza resultados mientras hay un diálogo abierto.
El proyecto original sigue separado y sin cambios. La aplicación mejorada está publicada en https://web-production-3897.up.railway.app y su repositorio es https://github.com/JosuePadillaUnivalle/QuickWash-Mejorado.
No hay avisos por correo/SMS ni conexión física a las lavadoras. La reducción de filas requiere un piloto real; no se midió carga sostenida ni satisfacción estudiantil.
