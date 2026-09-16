# Pruebas y guía de entrega
QuickWash Campus | V1 Mejorado

## Verificación automatizada
La ejecución final de PHPUnit se adjunta como XML en pruebas/phpunit.xml.
Incluye registro, duplicados de correo, acceso por rol, credenciales, cierre de sesión, limitación de intentos, campos obligatorios, cantidades inválidas, creación de reserva, disponibilidad, fechas, mantenimiento, exclusión de secadoras, propiedad de reservas, cupo, estados, filtros, migración y restricción única en la base.
Cancelación temporal: 09:59:59 permitida; 10:00:00 y 10:00:01 rechazadas para un turno a las 10:00.

## Concurrencia real
Se lanzan cuatro procesos PHP contra una base SQLite temporal.
Escenario 1: cuatro estudiantes, misma lavadora y turno. Resultado esperado y observado: una reserva guardada y tres rechazadas.
Escenario 2: un estudiante, cuatro lavadoras. Resultado esperado y observado: tres guardadas y una rechazada por cupo.
Se detectó y corrigió que Laravel en PHP 8.3 no aplica transaction_mode de SQLite. El servicio adquiere un bloqueo de escritura antes de leer el cupo.

## Flujos ejecutados en navegador
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

## Capturas
01-ingreso.png: acceso al sistema.
02-reserva-prendas.png: formulario con máquina y cantidad.
03-reserva-confirmada.png: alta con 12 prendas.
04-cancelacion.png: cancelación del estudiante.
05-personal-en-proceso.png: cambio de estado por personal.
06-panel-finalizada.png: estado final visible al estudiante.
07-movil.png: adaptación de pantalla pequeña.
Las imágenes son capturas de la aplicación; no son maquetas.

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
