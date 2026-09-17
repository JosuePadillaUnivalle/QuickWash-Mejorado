# Pruebas y guía de entrega
QuickWash Campus | V1 Mejorado

## Verificación automatizada
La ejecución final de PHPUnit se adjunta como XML en pruebas/phpunit.xml.
Incluye registro, duplicados de correo, acceso por rol, credenciales, cierre de sesión, limitación de intentos, campos obligatorios, cantidades inválidas, creación de reserva, disponibilidad, fechas, mantenimiento, exclusión de secadoras, propiedad de reservas, cupo, estados, filtros, migración y restricción única en la base.
Ampliación institucional: 51 pruebas y 273 aserciones aprobadas. Incluye seis variantes de dominio no permitido tanto en registro como en ingreso, normalización de mayúsculas/espacios, acceso del personal con su correo asignado, cierre de sesiones antiguas y conservación del historial de la cuenta demo.
Cancelación temporal: 09:59:59 permitida; 10:00:00 y 10:00:01 rechazadas para un turno a las 10:00.

## Concurrencia real
Se lanzan cuatro procesos PHP contra una base SQLite temporal.
Escenario 1: cuatro estudiantes, misma lavadora y turno. Resultado esperado y observado: una reserva guardada y tres rechazadas.
Escenario 2: un estudiante, cuatro lavadoras. Resultado esperado y observado: tres guardadas y una rechazada por cupo.
Se detectó y corrigió que Laravel en PHP 8.3 no aplica transaction_mode de SQLite. El servicio adquiere un bloqueo de escritura antes de leer el cupo.

## Flujos iniciales ejecutados en navegador
Entorno: aplicación local en 127.0.0.1:8010; navegador Chromium integrado.
1. Registro de Camila Prueba, correo ficticio camila.flujo@quickwash.test: cuenta Estudiante y panel correctos.
2. Consulta del 16/09/2026 a las 08:00: lavadoras disponibles y una en mantenimiento no seleccionable.
3. Selección de Lavadora 01 y 12 prendas; confirmación; creación QW-0006 con cantidad y estado Pendiente.
4. Cancelación de QW-0006 antes de su horario: estado Cancelada, mensaje correcto y acción retirada.
5. Ingreso como personal; listado de reservas de Alex y Camila visible.
6. QW-0001 (histórica, horario ya iniciado) cambió Pendiente → En proceso → Finalizada. Se usó este registro para probar el ciclo después del horario de atención, sin alterar el reloj del equipo.
7. Nuevo ingreso de Alex: Finalizada visible en su panel. Los registros históricos muestran cantidad No registrada.
8. Vista móvil 390×844: logo visible y sin desbordamiento horizontal del documento; las tablas pueden desplazarse dentro de su contenedor. Se corrigió el ancho mínimo del contenedor antes de la captura final.
No se probó reducción de filas con usuarios reales ni rendimiento bajo carga sostenida.

## Nuevos flujos: correo institucional
Verificados el 15 y 16/09/2026 en la versión mejorada local.
1. Registro con elena.prueba@example.com: rechazado con el mensaje que exige @est.univalle.edu.
2. Registro con elena.prueba@est.univalle.edu: cuenta Elena Prueba creada y panel de Estudiante abierto.
3. Disponibilidad del 16/09/2026 a las 08:00; selección de Lavadora 02 y 15 prendas; diálogo de confirmación y reserva QW-0007 en Pendiente.
4. Cancelación de QW-0007 antes del inicio: mensaje de turno liberado, estado Cancelada y sin acciones adicionales.
5. Ingreso con personal@quickwash.test: permitido; listado global con reservas de varios estudiantes.
6. Filtro Finalizada: solo aparecen tres reservas finalizadas. Catálogo de lavadoras consultable, con mantenimiento visible.
7. Ingreso con estudiante@est.univalle.edu: permitido; se conservan el usuario Alex Rivera y sus cinco reservas históricas.
8. Panel en pantalla de 390×844: sin desbordamiento horizontal del documento.
La cuenta histórica de Camila conserva su correo anterior para no cambiar su identidad automáticamente; ese correo ya no permite acceso. Las pruebas iniciales y sus capturas describen el comportamiento previo a la restricción.

## Capturas iniciales conservadas
01-ingreso.png: acceso al sistema.
02-reserva-prendas.png: formulario con máquina y cantidad.
03-reserva-confirmada.png: alta con 12 prendas.
04-cancelacion.png: cancelación del estudiante.
05-personal-en-proceso.png: cambio de estado por personal.
06-panel-finalizada.png: estado final visible al estudiante.
07-movil.png: adaptación de pantalla pequeña.
Las imágenes son capturas de la aplicación; no son maquetas.

## Nuevas capturas incluidas en el PDF
08-ingreso-institucional.png: aviso de dominio institucional y acceso del personal.
09-rechazo-correo-externo.png: validación del registro en el servidor.
10-registro-institucional.png: bienvenida después del registro válido de Elena.
11-disponibilidad-lavadoras.png: consulta del turno y máquinas elegibles.
12-confirmar-prendas.png: confirmación de lavadora, fecha, horario y 15 prendas.
13-reserva-institucional-creada.png: QW-0007 guardada en Pendiente.
14-cancelacion-institucional.png: cancelación permitida y turno liberado.
15-personal-reservas-globales.png: reservas de distintos estudiantes.
16-filtro-finalizadas.png: filtro aplicado y resultados correspondientes.
17-catalogo-personal.png: catálogo consultable y equipo en mantenimiento.
18-cuenta-demo-institucional.png: ingreso con el nuevo correo demo.
19-panel-historial-conservado.png: historial de Alex después del cambio de correo.
20-panel-movil-institucional.png: panel del estudiante en pantalla pequeña.
Se conservan 20 archivos PNG: siete iniciales y trece nuevos. El PDF integra los trece nuevos con títulos y explicación de la comprobación.

## Demostración sugerida al docente
Abrir INICIAR.cmd; ingresar como estudiante o registrar una cuenta.
Elegir turno futuro, lavadora y cantidad; confirmar y mostrar el historial.
Crear hasta tres activas y comprobar el bloqueo del cuarto intento.
Cancelar una pendiente futura y repetir la reserva para ver el cupo liberado.
Ingresar como personal y mostrar el listado global; cambiar el estado de una reserva cuyo turno ya comenzó.
Volver a estudiante y actualizar el historial. Las pruebas automáticas cubren el límite exacto de hora sin esperar en tiempo real.

## Límites y pendientes externos
El repositorio entrega el código y los entregables. El puerto 8010 es local, no un enlace público.
La versión inicial publicada se conserva. Un despliegue público de la mejorada requiere un destino independiente.
No hay avisos por correo, SMS, pagos, lectura del peso ni cambios automáticos.
Objetivo pendiente de validación: medir tiempo de espera, número de visitas sin disponibilidad y satisfacción en un piloto de campus.

## Ampliación del catálogo y aclaración de estados
Resultado vigente: 59 pruebas y 340 aserciones aprobadas. La cifra de 51 anterior corresponde a la entrega institucional previa.
Se probaron alta, edición, habilitación, validación de datos, permisos, eliminación lógica, conservación del historial y bloqueo de mantenimiento/eliminación con reservas activas. También se verificó un turno con más de cinco horas de atraso: no cambia solo, pero Personal puede registrar En proceso y después Finalizada.
En navegador local se creó Lavadora prueba de flujo, se editó de 10 a 12 kg, se puso en mantenimiento y se eliminó mediante confirmación. Para probar estados sin esperar se preparó QW-0008 con fecha anterior usando el servicio de reservas y reloj de prueba solo durante la preparación. En el navegador se guardaron En proceso y Finalizada mediante Actualizar y confirmación; Alex vio Finalizada al ingresar. No se cambió el reloj del equipo ni la reserva real de Railway.
Se corrigió el aviso Disponible en este turno para horarios ya iniciados. El selector ahora comienza en Seleccionar nuevo estado para evitar confundir la opción con el estado guardado.
Las capturas 21-catalogo-administrable.png, 22-edicion-mantenimiento.png, 23-eliminacion-confirmada.png, 24-estado-en-proceso-guardado.png y 25-finalizacion-guardada.png documentan esta ampliación. Total vigente: 25 PNG, todos en el ZIP de capturas. Las capturas iniciales reflejan el alcance anterior.
