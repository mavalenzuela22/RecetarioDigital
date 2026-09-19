"""Inventory shipped media; destinations are future suggestions, not writes."""
from pathlib import Path
from PIL import Image
import json,hashlib,re
R=Path(__file__).resolve().parents[1]
assets=[]
for p in sorted(R.rglob('*')):
 if p.suffix.lower() not in ['.png','.svg','.ico','.ttf'] and p.name not in ['LICENSE.txt','DM-Sans-OFL.txt','Fraunces-OFL.txt']:continue
 rel=p.relative_to(R).as_posix();dimensions=None
 if p.suffix in ['.png','.ico']:
  with Image.open(p) as im:dimensions=list(im.size)
 elif p.suffix=='.svg':
  m=re.search(r'viewBox="([^"]+)"',p.read_text())
  if m:dimensions=[float(x) for x in m.group(1).split()[2:]]
 purpose='Evidencia histórica de diseño; no instalar en app';destination=None;source=rel
 if rel.startswith('assets/brand/'):
  purpose='Marca tipográfica, icono de aplicación o export PWA';destination='public/icons/'+p.name;source='tokens/build.py + assets/fonts/DM-Sans.ttf'
  if p.name=='wordmark.svg':destination='resources/images/brand/wordmark.svg'
 elif rel.startswith('assets/fonts/'):
  purpose='Fuente local y licencia de redistribución';destination='resources/fonts/'+p.name;source='assets/fonts/sources.json'
 elif rel.startswith('icons/'):
  purpose='Iconografía de interfaz Lucide; semántica en icons/map.json';destination='resources/icons/'+p.name;source='icons/package-source.json + icons/LICENSE.txt'
 elif rel.startswith('imagery/cinnamon-rolls'):
  purpose='Fotografía sintética de referencia culinaria, no producto real';destination='resources/images/reference/cinnamon-rolls-master.png';source='imagery/cinnamon-rolls-source.md'
 elif rel.startswith('splashes/'):
  purpose='Composición/export de arranque de referencia; uso condicionado a plataforma';destination='public/splashes/'+p.name;source='splashes/export.py + splashes/export-matrix.json + assets/brand/app-icon-master.png'
 elif rel.startswith('screens/evidence/'):
  purpose='Captura real del prototipo de documentación o QA';source='prototypes/ + components/ + brandbook/; navegador local'
 assets.append({'filename':rel,'purpose':purpose,'dimensions_px':dimensions,'aspect_ratio':round(dimensions[0]/dimensions[1],5) if dimensions else None,'format':p.suffix[1:].upper(),'source_master':source,'intended_application_location':destination,'bytes':p.stat().st_size,'sha256':hashlib.sha256(p.read_bytes()).hexdigest()})
result={'version':'1.0.0','root':'docs/design/','write_boundary':'docs/design/**','destination_note':'Destinos propuestos para un contrato posterior. No se copió nada a producción. Maestros, evidencia y fuentes reproducibles permanecen aquí.','count':len(assets),'assets':assets,'no_additional_assets_required':['No se usa textura de fondo: canvas exacto.','No hay ilustración de vacío: icono funcional Lucide + texto.','No se entrega hoja decorativa del moodboard: no se adopta en UI.'],'regenerate':'python3 docs/design/implementation-guide/build_manifest.py'}
(R/'implementation-guide/asset-manifest.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n')
print(len(assets),'media/license entries')
