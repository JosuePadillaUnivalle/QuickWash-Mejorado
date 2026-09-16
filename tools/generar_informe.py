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
d=Drawing(1100,900)
def y(v): return 900-v
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
d.add(Rect(180,y(815),740,745,strokeColor=LINE,fillColor=white,strokeWidth=1.5))
text(550,103,'QuickWash Campus | V1 Mejorado',23,RED)
actor(65,360,['Estudiante'])
actor(1035,360,['Personal de','lavandería'])
# Actor associations, separate from UML dependency arrows.
for target in [(250,255),(250,375),(250,495),(250,615)]:
    path([(95,400),target])
path([(95,400),(140,150),(420,150)])
path([(95,400),(130,765),(420,765)])
path([(95,400),(145,700),(710,700),(710,653)])
path([(1005,400),(960,150),(680,150)])
path([(1005,400),(845,290)])
path([(1005,400),(845,450)])
path([(1005,400),(975,765),(680,765)])
uc(550,150,'CU-02',['Iniciar sesión'])
uc(380,255,'CU-01',['Registrar estudiante'])
uc(380,375,'CU-03',['Consultar disponibilidad'])
uc(380,495,'CU-04',['Registrar reserva'])
uc(380,615,'CU-05',['Consultar mis reservas'])
uc(710,615,'CU-06',['Cancelar reserva'])
uc(710,290,'CU-07',['Consultar todas','las reservas'],135)
uc(710,450,'CU-08',['Cambiar estado'],135)
uc(550,765,'CU-09',['Cerrar sesión'])
arrow([(380,457),(380,413)])
text(437,440,'«include»',14,RED)
arrow([(580,615),(510,615)])
text(548,595,'«extend»',14,RED)
arrow([(710,412),(710,328)])
text(758,375,'«extend»',14,RED)
text(550,848,'Cancelación estudiantil: propia + Pendiente + antes del inicio.',16)
text(550,875,'La sesión es precondición de las operaciones protegidas.',16,GRAY)
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
para('Dos actores y nueve casos. Flechas include: comportamiento obligatorio. Flechas extend: acciones opcionales desde la consulta. El límite del sistema encierra sus funciones.')
d.scale(0.46,0.46); d.width=506; d.height=414
story.append(d)
para('CU-06 extiende la consulta propia cuando la reserva es elegible. CU-08 extiende la consulta global cuando el personal elige actualizar. Iniciar sesión se mantiene como precondición, sin exigir reautenticación por operación.','QSmall')
para('La fuente PlantUML y el SVG se adjuntan para editar o imprimir el diagrama por separado.','QSmall')
story.append(PageBreak())
markdown('02-casos-de-uso.md')
markdown('03-base-de-datos.md')
para('Resultados de pruebas','QH1')
xml=ET.parse(OUT/'pruebas/phpunit.xml').getroot().find('testsuite')
para(f"{xml.get('tests')} pruebas · {xml.get('assertions')} aserciones · {xml.get('failures')} fallos · {xml.get('errors')} errores",'QH2')
para('Resultado tomado del XML generado por PHPUnit en esta entrega. Las bases de prueba están aisladas de la base operativa.')
markdown('04-pruebas-y-entrega.md')
para('Evidencia del sistema','QH1')
para('Panel del estudiante después de que el personal finalizó la reserva histórica QW-0001. La cantidad antigua se conserva como No registrada.')
im=Image(str(OUT/'capturas/06-panel-finalizada.png'))
ratio=im.imageHeight/im.imageWidth
im.drawWidth=505; im.drawHeight=505*ratio
story.append(im)
para('Captura real del navegador local; no representa una medición de reducción de filas. Las demás capturas se adjuntan en la carpeta capturas/.','QSmall')
story.append(Spacer(1,15))
para('Acceso a la aplicación','QH2')
para('Local: http://127.0.0.1:8010. Inicio: INICIAR.cmd. Para instalación nueva: preparar.ps1 -Demo. Credenciales de demostración e instrucciones en README.md.')
para('El repositorio contiene el código, documentación, migraciones, exportación de demostración y pruebas. La publicación web de esta versión necesita un destino independiente del prototipo.')
doc=SimpleDocTemplate(str(OUT/'QuickWash-Campus-Informe.pdf'),pagesize=A4,rightMargin=42,leftMargin=42,topMargin=51,bottomMargin=53,title='QuickWash Campus - V1 Mejorado',author='Proyecto QuickWash Campus')
doc.build(story,onFirstPage=page_header,onLaterPages=page_header)
print('PDF y SVG generados.')
