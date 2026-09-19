"""Generate exact design references inside docs/design only. Python stdlib + Pillow."""
from pathlib import Path
import json,base64
from PIL import Image,ImageDraw,ImageFont
R=Path(__file__).resolve().parents[1]
colors={'canvas':'#FFF8ED','paper':'#FFFFFF','ink':'#38291F','muted':'#6C5B4F','primary':'#93482F','primary-pressed':'#713620','positive':'#466344','positive-soft':'#EAF0E5','warning':'#735211','warning-soft':'#FFF0C7','danger':'#922E25','danger-soft':'#FFF0EB','info':'#315C68','info-soft':'#E9F0F3','line':'#DCCFC0','input-border':'#8A7565','disabled-bg':'#E8E0D6','disabled-fg':'#6C5B4F','focus':'#38291F','scrim':'#38291F66','nav-active':'#F5E4D8'}
t={'version':'1.0.0','direction':'A Cocina cálida artesanal','locale':'es-MX','currency':'MXN','color':colors,'font':{'body':{'family':'DM Sans','file':'assets/fonts/DM-Sans.ttf','weights':[400,500,600,700],'axes':{'opsz':14}},'display':{'family':'Fraunces','file':'assets/fonts/Fraunces.ttf','weights':[600],'axes':{'opsz':32,'SOFT':0,'WONK':0}}},'type':{'hero':[32,38,600,'display'],'title':[28,34,600,'display'],'section':[22,28,600,'display'],'money':[28,34,600,'display'],'body':[16,24,400,'body'],'label':[16,24,500,'body'],'help':[14,20,400,'body'],'nav':[12,16,600,'body']},'space':[0,4,8,12,16,20,24,32,40,48,64],'radius':{'small':8,'control':12,'card':16,'sheet':24,'pill':999},'border':{'hairline':1,'focus':2,'focusOffset':3},'shadow':{'none':'none','raised':'0 4px 16px #38291F0D','overlay':'0 -8px 32px #38291F26'},'breakpoint':{'narrow':360,'tablet':768,'wide':1200},'layout':{'mobilePadding':20,'narrowPadding':16,'flowMax':480,'contentMax':1120,'touchMin':48,'buttonHeight':52,'inputHeight':52,'navigationHeight':72,'sheetMaxHeight':'85dvh','safeArea':'env(safe-area-inset-bottom, 0px)'},'zIndex':{'base':0,'sticky':10,'navigation':20,'scrim':30,'dialog':40,'toast':50},'motion':{'press':80,'state':160,'sheet':220,'ease':'cubic-bezier(0.2,0,0,1)','reducedDuration':0}}
(R/'tokens/tokens.json').write_text(json.dumps(t,ensure_ascii=False,indent=2)+'\n')
lines=['/* Generated from tokens.json by build.py. Design reference, not installed in production. */',':root {']
for k,v in colors.items(): lines.append(f'  --eo-{k}: {v};')
for k,v in t['radius'].items(): lines.append(f'  --eo-radius-{k}: {v}px;')
for v in t['space']: lines.append(f'  --eo-space-{v}: {v}px;')
for k,v in t['type'].items(): lines += [f'  --eo-type-{k}-size: {v[0]/16}rem;',f'  --eo-type-{k}-line: {v[1]/16}rem;']
for k,v in t['shadow'].items():lines.append(f'  --eo-shadow-{k}: {v};')
for k,v in t['zIndex'].items():lines.append(f'  --eo-z-{k}: {v};')
for k in ['press','state','sheet']:lines.append(f'  --eo-motion-{k}: {t["motion"][k]}ms;')
lines += ['  --eo-font-body: "DM Sans", system-ui, sans-serif;','  --eo-font-display: "Fraunces", Georgia, serif;','}']
(R/'tokens/tokens.css').write_text('\n'.join(lines)+'\n')
fontfile=R/'assets/fonts/DM-Sans.ttf'; font=ImageFont.truetype(str(fontfile),210)
axes=font.get_variation_axes(); font.set_variation_by_axes([700 if a['name']==b'Weight' else a['default'] for a in axes])
# Typographic EO monogram: actual supplied font, not a drawn culinary pictogram.
im=Image.new('RGB',(1024,1024),colors['ink']); draw=ImageDraw.Draw(im); draw.text((512,512),'EO',font=ImageFont.truetype(str(fontfile),380),fill=colors['canvas'],anchor='mm',stroke_width=1)
# Freeze weight explicitly for all raster exports.
f=ImageFont.truetype(str(fontfile),380); f.set_variation_by_axes([700 if a['name']==b'Weight' else a['default'] for a in f.get_variation_axes()]); draw.rectangle((0,0,1024,1024),fill=colors['ink']); draw.text((512,492),'EO',font=f,fill=colors['canvas'],anchor='mm')
im.save(R/'assets/brand/app-icon-master.png')
for size in [32,48,180,192,512]: im.resize((size,size),Image.Resampling.LANCZOS).save(R/f'assets/brand/app-icon-{size}.png')
im.resize((512,512),Image.Resampling.LANCZOS).save(R/'assets/brand/maskable-512.png')
im.save(R/'assets/brand/favicon.ico',sizes=[(16,16),(32,32),(48,48)])
font64=base64.b64encode(fontfile.read_bytes()).decode()
style=f'@font-face{{font-family:EO;src:url(data:font/ttf;base64,{font64})}}text{{font-family:EO;font-weight:700;fill:{colors["canvas"]}}}'
svg=f'<svg xmlns="http://www.w3.org/2000/svg" width="1024" height="1024" viewBox="0 0 1024 1024"><title>EmprendimientoOS</title><style>{style}</style><rect width="1024" height="1024" fill="{colors["ink"]}"/><text x="512" y="630" text-anchor="middle" font-size="380">EO</text></svg>'
(R/'assets/brand/app-icon-master.svg').write_text(svg)
style2=f'@font-face{{font-family:EO;src:url(data:font/ttf;base64,{font64})}}text{{font-family:EO;font-weight:600;fill:{colors["ink"]}}}'
(R/'assets/brand/wordmark.svg').write_text(f'<svg xmlns="http://www.w3.org/2000/svg" width="520" height="64" viewBox="0 0 520 64"><title>EmprendimientoOS</title><style>{style2}</style><text x="0" y="46" font-size="43">EmprendimientoOS</text></svg>')
# Raster splash is a composition from supplied typographic mark and licensed fonts.
splash=Image.new('RGB',(1170,2532),colors['canvas']); d=ImageDraw.Draw(splash); mark=im.resize((240,240)); splash.paste(mark,(465,960)); f=ImageFont.truetype(str(fontfile),54); d.text((585,1280),'EmprendimientoOS',font=f,fill=colors['ink'],anchor='mm'); f2=ImageFont.truetype(str(fontfile),42); d.text((585,1360),'Tu cocina. Tu negocio.',font=f2,fill=colors['muted'],anchor='mm'); splash.save(R/'splashes/splash-master.png')
print('tokens and typographic brand exports generated')
