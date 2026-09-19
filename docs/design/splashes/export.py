from pathlib import Path
import json
from PIL import Image,ImageDraw,ImageFont
R=Path(__file__).resolve().parents[1]
matrix=[{'id':'compact-portrait','css':[360,800],'dpr':3},{'id':'standard-portrait','css':[390,844],'dpr':3},{'id':'large-portrait','css':[430,932],'dpr':3}]
for row in matrix:
 w,h=[x*row['dpr'] for x in row['css']]; dpr=row['dpr']; im=Image.new('RGB',(w,h),'#FFF8ED'); d=ImageDraw.Draw(im); icon=Image.open(R/'assets/brand/app-icon-master.png').resize((80*dpr,80*dpr),Image.Resampling.LANCZOS); cy=round(h*.43); im.paste(icon,(w//2-40*dpr,cy-40*dpr)); f=ImageFont.truetype(str(R/'assets/fonts/DM-Sans.ttf'),18*dpr); d.text((w//2,cy+66*dpr),'EmprendimientoOS',font=f,fill='#38291F',anchor='mm'); f=ImageFont.truetype(str(R/'assets/fonts/DM-Sans.ttf'),14*dpr);d.text((w//2,cy+94*dpr),'Tu cocina. Tu negocio.',font=f,fill='#6C5B4F',anchor='mm'); filename='splash-'+row['id']+'.png';im.save(R/'splashes'/filename);row.update({'pixels':[w,h],'filename':filename,'purpose':'Reference export; OS support must be verified'})
(R/'splashes/export-matrix.json').write_text(json.dumps({'background':'#FFF8ED','master':'splash-master.png','composition':{'iconCss':80,'centerY':'43%','nameOffsetY':66,'taglineOffsetY':94,'nameSize':18,'taglineSize':14},'exports':matrix},indent=2)+'\n')

import shutil
shutil.copyfile(R/'splashes/splash-standard-portrait.png',R/'splashes/splash-master.png')
