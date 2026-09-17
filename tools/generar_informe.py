from pathlib import Path
from xml.sax.saxutils import escape
import re, xml.etree.ElementTree as ET
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, PageBreak, Image, KeepTogether
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.colors import HexColor, white
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.graphics.shapes import Drawing, Rect, Ellipse, Circle, Line, PolyLine, String
from reportlab.graphics import renderSVG

ROOT=Path(__file__).resolve().parents[1]
OUT=ROOT/'entregables'
RED=HexColor('#A42643'); INK=HexColor('#27313C'); GRAY=HexColor('#737E89'); LINE=HexColor('#C9CFD5')
d=Drawing(1100,1350)
def y(v): return 1350-v
def text(x,top,s,size=17,color=INK,anchor='middle'):
    d.add(String(x,y(top),s,fontName='Helvetica',fontSize=size,fillColor=color,textAnchor=anchor))
def path(points,dashed=False):
    kwargs={'strokeColor':RED if dashed else GRAY,'strokeWidth':1.5,'fillColor':None}
    if dashed: kwargs['strokeDashArray']=[7,5]
    d.add(PolyLine([(a,y(b)) for a,b in points],**kwargs))
def arrow(points):
    path(points,True)
    import math
    x1,y1=points[-2]; x2,y2=points[-1]
    angle=math.atan2(y2-y1,x2-x1)
    a=(x2-12*math.cos(angle-.45), y2-12*math.sin(angle-.45))
    b=(x2-12*math.cos(angle+.45), y2-12*math.sin(angle+.45))
    path([a,(x2,y2),b],True)
def actor(x,top,name):
    d.add(Circle(x,y(top),18,strokeColor=INK,fillColor=white,strokeWidth=2))
    path([(x,top+18),(x,top+80)])
    path([(x-30,top+40),(x+30,top+40)])
    path([(x,top+80),(x-28,top+115)])
    path([(x,top+80),(x+28,top+115)])
    for i,line in enumerate(name): text(x,top+145+i*22,line,18)
def uc(x,top,code,lines,rx=130):
    d.add(Ellipse(x,y(top),rx,38,strokeColor=RED,fillColor=HexColor('#FCF5F7'),strokeWidth=1.6))
    text(x,top-10,code,14,RED)
    for i,line in enumerate(lines): text(x,top+11+i*18,line,16)
d.add(Rect(180,y(1250),740,1180,strokeColor=LINE,fillColor=white,strokeWidth=1.5))
text(550,103,'QuickWash Campus | V1 Mejorado',23,RED)
actor(65,360,['Estudiante'])
actor(1035,360,['Personal de','lavandería'])
# Actor associations, separate from UML dependency arrows.
for target in [(250,255),(250,375),(250,495),(250,615)]:
    path([(95,400),target])
path([(95,400),(140,150),(420,150)])
path([(95,400),(130,865),(420,865)])
path([(95,400),(145,700),(710,700),(710,653)])
path([(1005,400),(960,150),(680,150)])
path([(1005,400),(845,290)])
path([(1005,400),(845,450)])
path([(1005,400),(975,865),(680,865)])
uc(550,150,'CU-02',['Iniciar sesión'])
uc(380,255,'CU-01',['Registrar estudiante'])
uc(380,375,'CU-03',['Consultar disponibilidad'])
uc(380,495,'CU-04',['Registrar reserva'])
uc(380,615,'CU-05',['Consultar mis reservas'])
uc(710,615,'CU-06',['Cancelar reserva'])
uc(710,290,'CU-07',['Consultar todas','las reservas'],135)
uc(710,450,'CU-08',['Cancelar reserva','futura'],135)
uc(550,865,'CU-09',['Cerrar sesión'])
for top, code, title in [(980,'CU-10','Crear lavadora'),(1080,'CU-11','Editar lavadora'),(1180,'CU-12','Eliminar lavadora')]:
    path([(1005,400),(990,top),(710,top)])
    uc(550,top,code,[title],160)
path([(95,400),(130,735),(250,735)])
uc(380,735,'CU-13',['Confirmar recogida'])
arrow([(380,697),(380,653)])
text(440,681,'«extend»',14,RED)
arrow([(380,457),(380,413)])
text(437,440,'«include»',14,RED)
arrow([(580,615),(510,615)])
text(548,595,'«extend»',14,RED)
arrow([(710,412),(710,328)])
text(758,375,'«extend»',14,RED)
text(550,1280,'Cancelación estudiantil: propia + Pendiente + antes del inicio.',16)
text(550,1310,'La sesión es precondición de las operaciones protegidas.',16,GRAY)
renderSVG.drawToFile(d,str(OUT/'diagrama-casos-de-uso.svg'))

styles=getSampleStyleSheet()
styles.add(ParagraphStyle(name='QBody',fontName='Helvetica',fontSize=9.5,leading=14,textColor=INK,spaceAfter=6))
styles.add(ParagraphStyle(name='QTitle',fontName='Helvetica-Bold',fontSize=28,leading=34,textColor=RED,spaceAfter=17))
styles.add(ParagraphStyle(name='QH1',fontName='Helvetica-Bold',fontSize=21,leading=26,textColor=RED,spaceBefore=8,spaceAfter=15,keepWithNext=True))
styles.add(ParagraphStyle(name='QH2',fontName='Helvetica-Bold',fontSize=12,leading=17,textColor=INK,spaceBefore=11,spaceAfter=6,keepWithNext=True))
styles.add(ParagraphStyle(name='QBullet',parent=styles['QBody'],leftIndent=11,firstLineIndent=-8,spaceAfter=5))
styles.add(ParagraphStyle(name='QSmall',parent=styles['QBody'],fontSize=8,leading=12,textColor=GRAY))
story=[]
def para(s,style='QBody'):
    story.append(Paragraph(escape(s).replace('→',' -&gt; '),styles[style]))
def page_header(canvas,doc):
    canvas.setFillColor(RED);canvas.rect(42,805,511,3,fill=1,stroke=0)
    canvas.setFont('Helvetica',8);canvas.setFillColor(GRAY)
    canvas.drawString(42,819,'UNIVALLE  /  QUICKWASH CAMPUS')
    canvas.drawRightString(553,819,'V1 MEJORADO')
    canvas.setStrokeColor(LINE);canvas.line(42,39,553,39)
    canvas.drawString(42,26,'Entrega académica | Septiembre 2026')
    canvas.drawRightString(553,26,str(doc.page))
def markdown(filename):
    blocks=[]; block=[]
    for line in (OUT/filename).read_text(encoding='utf-8').splitlines():
        if not line.strip(): continue
        if line.startswith('## ') and block:
            blocks.append(block); block=[]
        if line.startswith('# '): s,sty=line[2:],'QH1'
        elif line.startswith('## '): s,sty=line[3:],'QH2'
        elif line.startswith('- '): s,sty='• '+line[2:],'QBullet'
        else: s,sty=line,'QBody'
        block.append(Paragraph(escape(s).replace('→',' -&gt; '),styles[sty]))
    if block: blocks.append(block)
    for block in blocks: story.append(KeepTogether(block))
    story.append(PageBreak())
story.append(Spacer(1,45))
story.append(Image(str(ROOT/'public/images/univalle.png'),96,96))
story.append(Spacer(1,30))
para('QuickWash Campus','QTitle')
para('V1 Mejorado · Aplicación de reservas de lavandería','QH1')
para('Universidad del Valle (UNIVALLE)','QH2')
para('Historias de usuario, casos de uso, implementación, base de datos y evidencia de pruebas.')
para('Versión independiente creada a partir del prototipo inicial y alineada con el enunciado real del examen. El proyecto original y su despliegue se conservan.')
story.append(Spacer(1,25))
para('Contenido','QH2')
for s in ['1. Historias de usuario y criterios de aceptación','2. Diagrama UML y especificación de casos de uso','3. Diseño y migración de la base de datos','4. Pruebas, capturas y guía de demostración']:
    para(s)
para('Fuente de requisitos: enunciado proporcionado por el solicitante. Las decisiones operativas no especificadas se identifican como supuestos.','QSmall')
story.append(PageBreak())
markdown('01-historias-de-usuario.md')
para('Diagrama de casos de uso','QH1')
para('Dos actores y trece casos. Flechas include: comportamiento obligatorio. Flechas extend: acciones opcionales desde la consulta. El límite del sistema encierra sus funciones.')
d.scale(0.41,0.41); d.width=451; d.height=554
story.append(d)
para('CU-06 extiende la consulta propia cuando la reserva es elegible. CU-13 extiende la consulta propia para recoger. CU-08 extiende la consulta global para cancelar una futura. Iniciar sesión se mantiene como precondición, sin exigir reautenticación por operación.','QSmall')
para('La fuente PlantUML y el SVG se adjuntan para editar o imprimir el diagrama por separado.','QSmall')
story.append(PageBreak())
markdown('02-casos-de-uso.md')
markdown('03-base-de-datos.md')
para('Resultados de pruebas','QH1')
xml=ET.parse(OUT/'pruebas/phpunit.xml').getroot().find('testsuite')
para(f"{xml.get('tests')} pruebas · {xml.get('assertions')} aserciones · {xml.get('failures')} fallos · {xml.get('errors')} errores",'QH2')
para('Resultado tomado del XML generado por PHPUnit en esta entrega. Las bases de prueba están aisladas de la base operativa.')
markdown('04-pruebas-y-entrega.md')
evidences = [
    ('08-ingreso-institucional.png', 'Acceso por tipo de cuenta',
     'La pantalla indica el dominio @est.univalle.edu para estudiantes y el uso del correo asignado para personal.',
     'HU-02 / CU-02. La validación también se aplica en el servidor.'),
    ('09-rechazo-correo-externo.png', 'Rechazo de correo externo',
     'Al enviar elena.prueba@example.com, el registro informa que se requiere el dominio institucional. La cuenta no se crea.',
     'HU-01 / CU-01. Resultado esperado y observado: error de dominio.'),
    ('10-registro-institucional.png', 'Registro válido de estudiante',
     'Con elena.prueba@est.univalle.edu se crea Elena Prueba y se abre su panel con el mensaje de bienvenida.',
     'HU-01 / CU-01. Resultado: cuenta con rol Estudiante y sesión iniciada.'),
    ('11-disponibilidad-lavadoras.png', 'Disponibilidad para el turno',
     'Consulta del 16/09/2026 a las 08:00. Se muestran lavadoras disponibles y una en mantenimiento que no puede seleccionarse.',
     'HU-03 / CU-03. La ocupación se verifica nuevamente al confirmar.'),
    ('12-confirmar-prendas.png', 'Confirmación de datos',
     'El diálogo resume Lavadora 02, 15 prendas, fecha y horario antes de guardar la reserva.',
     'HU-04 / CU-04. Los cuatro datos exigidos están presentes.'),
    ('13-reserva-institucional-creada.png', 'Reserva guardada',
     'QW-0007 aparece en el historial propio con 15 prendas, fecha 16/09/2026, turno 08:00 a 09:00 y estado Pendiente.',
     'HU-04 y HU-05 / CU-04 y CU-05. El mensaje confirma la operación.'),
    ('14-cancelacion-institucional.png', 'Cancelación antes del inicio',
     'Elena cancela QW-0007 antes de comenzar el turno. El estado cambia a Cancelada y aparece el mensaje de disponibilidad liberada.',
     'HU-06 / CU-06. La fila deja de ofrecer acciones de cancelación.'),
    ('15-personal-reservas-globales.png', 'Consulta global del personal',
     'El personal ingresa con personal@quickwash.test y consulta reservas de varios estudiantes, incluyendo sus correos y cantidades.',
     'HU-07 / CU-07. El correo anterior de Camila se conserva como dato histórico; ya no permite ingreso.'),
    ('16-filtro-finalizadas.png', 'Filtro de estados',
     'Al elegir Finalizada, el listado muestra las tres reservas que coinciden. La cuenta demo de Alex ya usa el dominio institucional.',
     'HU-07 / CU-07. Las reservas finalizadas conservan sus datos y no pueden reabrirse.'),
    ('17-catalogo-personal.png', 'Catálogo de lavadoras',
     'Captura de la entrega anterior: catálogo de consulta. La ampliación actual incorpora las acciones de administración mostradas en las evidencias siguientes.',
     'Se conserva como evidencia de evolución; la interfaz actual permite crear, editar y eliminar.'),
    ('18-cuenta-demo-institucional.png', 'Nuevo correo de demostración',
     'La cuenta demo ingresa como estudiante@est.univalle.edu. Conserva su contraseña de demostración y su historial.',
     'HU-02 / CU-02. El ingreso fue aceptado y abrió el panel de Alex.'),
    ('19-panel-historial-conservado.png', 'Historial después del cambio',
     'El panel de Alex mantiene las reservas históricas. Las cantidades que no existían en la versión inicial se muestran como No registrada.',
     'HU-05 / CU-05. El cambio del correo demo conserva el identificador del estudiante y sus reservas.'),
    ('20-panel-movil-institucional.png', 'Panel en pantalla móvil',
     'Verificación en 390 por 844 píxeles. El panel conserva la navegación, el logo y sus indicadores en pantalla pequeña.',
     'Resultado: sin desbordamiento horizontal del documento. Las tablas se desplazan dentro de su propio contenedor.'),
    ('21-catalogo-administrable.png', 'Administración del catálogo',
     'El personal dispone de Crear lavadora, Editar y Eliminar, con el número de reservas activas por equipo.',
     'HU-10 a HU-12. El mantenimiento es independiente de la ocupación.'),
    ('22-edicion-mantenimiento.png', 'Edición de una lavadora',
     'Prueba local: se creó Lavadora prueba de flujo, se editó su capacidad de 10 a 12 kg y se guardó Mantenimiento.',
     'HU-11 / CU-11. Los datos y el estado del equipo se guardaron correctamente.'),
    ('23-eliminacion-confirmada.png', 'Eliminación confirmada',
     'La lavadora de prueba se retiró del catálogo después de confirmar. El mensaje acredita la operación.',
     'HU-12 / CU-12. La eliminación lógica conserva el historial.'),
    ('26-inicio-automatico.png', 'Lavado iniciado automáticamente',
     'Alex reservó Lavadora 01 con diez prendas desde el formulario. En la base local se desplazó únicamente el intervalo de prueba, manteniendo sesenta minutos, para observar el fin sin esperar una hora.',
     'La tabla pasó a En proceso sin intervención del personal ni recarga manual; se conservó el filtro de fecha.'),
    ('27-esperando-recogida.png', 'Lavado terminado y máquina ocupada',
     'Al terminar el intervalo real, la tabla se actualizó a Esperando recogida y habilitó Recogido para el estudiante propietario.',
     'HU-13 / CU-13. La máquina permanece ocupada hasta confirmar. Volver en el diálogo conserva el estado.'),
    ('28-recogido-finalizado.png', 'Recogida confirmada por el estudiante',
     'Alex pulsó Recogido y confirmó el retiro de las prendas. QW-0009 cambió a Finalizado y registró la hora de recogida.',
     'HU-13 / CU-13. Lavadora 01 volvió a estar seleccionable en la consulta de disponibilidad. La prueba usa datos ficticios locales.'),
]
for index, (filename, title, description, result) in enumerate(evidences, 1):
    para(f'Evidencia {index:02d} | {title}', 'QH1')
    para(description)
    im = Image(str(OUT/'capturas'/filename))
    scale = min(505/im.imageWidth, 500/im.imageHeight)
    im.drawWidth = im.imageWidth*scale
    im.drawHeight = im.imageHeight*scale
    story.append(Spacer(1,12))
    story.append(im)
    story.append(Spacer(1,12))
    para(result, 'QBody')
    para(f'Archivo: capturas/{filename}. Captura real de la aplicación local, de las verificaciones del 15 al 17/09/2026.', 'QSmall')
    if index < len(evidences):
        story.append(PageBreak())
doc=SimpleDocTemplate(str(OUT/'QuickWash-Campus-Informe.pdf'),pagesize=A4,rightMargin=42,leftMargin=42,topMargin=51,bottomMargin=53,title='QuickWash Campus - V1 Mejorado',author='Proyecto QuickWash Campus')
doc.build(story,onFirstPage=page_header,onLaterPages=page_header)
print('PDF y SVG generados.')
