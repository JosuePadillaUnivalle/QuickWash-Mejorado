# QuickWash Campus · V1 Mejorado

Aplicación Laravel/PHP para la lavandería de UNIVALLE, adaptada al enunciado real del examen. Carpeta, base y repositorio independientes; la versión original y su despliegue se conservan.

Repositorio de entrega (público): https://github.com/JosuePadillaUnivalle/QuickWash-Mejorado
El docente puede consultar el código en GitHub o usar el ZIP de entrega.

## Ejecutar en este equipo
1. Haz doble clic en **INICIAR.cmd**.
2. Abre http://127.0.0.1:8010.
3. Mantén abierta la consola; Ctrl+C detiene el servidor.
Si el servidor ya está funcionando en 8010, basta con abrir la dirección. Puerto alternativo: .\iniciar.ps1 -Puerto 8011.

Cuentas locales:
- Estudiante: estudiante@est.univalle.edu
- Personal: personal@quickwash.test
- Contraseña de ambas: QuickWash2026!

La base local contiene registros migrados y datos de pruebas manuales.
El registro y el acceso de estudiantes exigen el dominio exacto @est.univalle.edu. El personal ingresa con su correo asignado. Los correos se normalizan a minúsculas y sin espacios exteriores.
Al ejecutar DemoSeeder, la cuenta demo antigua de Alex conserva su ID, contraseña y reservas con el nuevo correo. Otras cuentas históricas con dominio externo conservan sus datos, pero no pueden ingresar; tampoco mantienen acceso mediante sesiones antiguas.

## Instalación nueva en Windows
PHP 8.3+ en PATH con SQLite, mbstring, OpenSSL, fileinfo, curl, DOM y XML.
Dentro del proyecto, ejecutar:

    powershell -ExecutionPolicy Bypass -File preparar.ps1 -Demo

Después ejecutar INICIAR.cmd. El preparador configura PHP para este proyecto, instala dependencias, crea una clave nueva y migra sin borrar datos.

En Linux/macOS con PHP/Composer configurados:
1. composer install
2. Copiar .env.example a .env.
3. Crear el archivo vacío database/database.sqlite.
4. php artisan key:generate
5. php artisan migrate --seed
6. php artisan db:seed --class=DemoSeeder (opcional, solo local)
7. php artisan serve --host=127.0.0.1 --port=8010

No requiere Node, Vite, colas ni tareas programadas. La tipografía web tiene respaldo local sin Internet.

## Funciones y reglas
- Registro e ingreso de estudiantes exclusivamente con @est.univalle.edu; inicio y cierre de sesión por rol.
- Disponibilidad por fecha/turno; reserva de lavadora con cantidad de prendas.
- Historial propio, filtros y cancelación antes del horario.
- Personal: consulta global y modificación exclusiva del estado.
- Turno único por máquina y máximo tres reservas activas.
- Pendiente y En proceso son activas; Finalizada y Cancelada no consumen cupo.
- El estudiante cancela únicamente Pendiente antes del inicio.
- Transiciones: Pendiente → En proceso → Finalizada. Personal también puede cancelar desde Pendiente o En proceso.
- Finalizada y Cancelada son terminales; no se inicia antes de la hora reservada.
- Catálogo de equipos solo de consulta para personal.

Decisiones no definidas por el examen: turnos de 60 minutos de 08:00 a 20:00, próximos 30 días, horario Bolivia y cantidad entera de 1 a 100 prendas. La cantidad no equivale a peso; debe respetarse la capacidad en kg.
No se implementan notificaciones ni procesos automáticos.

## Datos
- Base operativa local: database/database.sqlite, excluida de Git.
- Migraciones: database/migrations.
- Exportación SQL de datos ficticios: entregables/base-datos/quickwash-demo.sql.
- Equivalente SQLite local: entregables/base-datos/quickwash-demo.sqlite.
- Diccionario: entregables/03-base-de-datos.md.

La migración convierte Listo/Entregado a Finalizada y Cancelado a Cancelada. Los registros antiguos conservan cantidad NULL, presentada como “No registrada”. Se preservan reservas y máquinas históricas, incluidas secadoras; las altas nuevas solo ofrecen lavadoras.
El rollback no recupera la distinción Listo/Entregado: conservar una copia antes de revertir.

Crear personal propio: php artisan quickwash:personal.
En este Windows, antes de comandos PHP define: $env:PHPRC="$PWD\tools".

## Pruebas
    powershell -ExecutionPolicy Bypass -File probar.ps1

Incluye flujos, validaciones, permisos, migración y dos pruebas con cuatro procesos PHP simultáneos. Las pruebas usan bases temporales independientes.
Resultados: entregables/pruebas. Capturas reales: entregables/capturas.
Última verificación: 51 pruebas y 273 aserciones aprobadas. El PDF incluye trece nuevas capturas de registro, acceso, reservas, cancelación, personal y vista móvil; hay veinte PNG en total.

## Entrega académica
- QuickWash-Campus-Informe.pdf: informe para revisar/imprimir.
- 01-historias-de-usuario.md: historias y criterios de aceptación.
- 02-casos-de-uso.md: actores, flujos y relaciones.
- diagrama-casos-de-uso.svg y .puml: diagrama vectorial y fuente editable.
- 03-base-de-datos.md: entidades y migración.
- 04-pruebas-y-entrega.md: resultados y guía de demostración.
Todos estos archivos se encuentran en entregables/.

## Despliegue independiente
El servidor local no es alojamiento público. Se requiere PHP 8.3+, document root en public/, HTTPS, almacenamiento persistente para SQLite, permisos de escritura en storage/ y bootstrap/cache, APP_ENV=production, APP_DEBUG=false, clave y cuentas propias.
No ejecutar DemoSeeder en producción ni reutilizar base, clave, cookies o despliegue del proyecto original.
La reducción de filas debe validarse con un piloto; las pruebas técnicas no miden ese resultado.
