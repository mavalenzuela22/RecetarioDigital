"""Read-only validation of design package; writes reports only beside this file."""
from pathlib import Path
import json,hashlib,re,math
from decimal import Decimal
from PIL import Image
R=Path(__file__).resolve().parents[1]; repo=R.parents[1]
t=json.loads((R/'tokens/tokens.json').read_text());c=t['color']
def lum(h):
 rgb=[int(h[i:i+2],16)/255 for i in (1,3,5)]; linear=[v/12.92 if v<=.04045 else ((v+.055)/1.055)**2.4 for v in rgb];return sum(a*b for a,b in zip(linear,[.2126,.7152,.0722]))
pairs=[('body','ink','canvas',4.5),('muted','muted','canvas',4.5),('primary','paper','primary',4.5),('pressed','paper','primary-pressed',4.5),('positive','positive','positive-soft',4.5),('error','danger','danger-soft',4.5),('warning','warning','warning-soft',4.5),('info','info','info-soft',4.5),('input edge','input-border','paper',3),('focus','focus','canvas',3)]
ratios=[]
for n,a,b,minimum in pairs:
 x,y=sorted([lum(c[a]),lum(c[b])]);ratio=(y+.05)/(x+.05);ratios.append({'pair':n,'foreground':c[a],'background':c[b],'ratio':round(ratio,3),'minimum':minimum,'pass':ratio>=minimum})
base=json.loads((R/'audit/baseline-sha256.json').read_text());changed=[p for p,h in base.items() if not (repo/p).is_file() or hashlib.sha256((repo/p).read_bytes()).hexdigest()!=h]
f=json.loads((R/'screens/fixtures.json').read_text()); fixture_checks={'recipe':f['recipe']['ingredientCostMinor']+f['recipe']['batchExtrasMinor']==f['recipe']['batchCostMinor'],'recipeUnit':f['recipe']['batchCostMinor']//f['recipe']['yield']==f['recipe']['recipeUnitCostMinor'],'product':f['product']['recipeUnitCostMinor']+f['product']['packagingMinor']+f['product']['orderDeliveryAllocationMinor']==f['product']['unitCostMinor'],'todayBalance':f['today']['revenueMinor']-f['today']['paidMinor']==f['today']['balanceMinor'],'todayProfit':f['today']['revenueMinor']-f['today']['costMinor']==f['today']['estimatedProfitMinor'],'historyDrivers':sum(f['history']['costDriversPerUnitMinor'].values())==f['history']['currentUnitCostMinor']-f['history']['pastUnitCostMinor'],'scenarios':all(Decimal(s['multiplier'])*f['product']['unitCostMinor']==s['priceMinor'] and s['priceMinor']-f['product']['unitCostMinor']==s['profitMinor'] and s['profitMinor']*12==s['batchProfitMinor'] for s in f['scenarios'])}
im=Image.open(R/'assets/brand/maskable-512.png').convert('RGB');bg=im.getpixel((0,0));outside=0;far=0
for y in range(im.height):
 for x in range(im.width):
  pixel=im.getpixel((x,y))
  if sum(abs(pixel[i]-bg[i]) for i in range(3))>50:
   radius=math.hypot(x-256,y-256);far=max(far,radius);outside+=radius>204.8
missing=[]
for p in R.rglob('*'):
 if p.suffix not in ['.md','.html']:continue
 content=p.read_text();links=re.findall(r'\]\(([^)]+)\)',content) if p.suffix=='.md' else re.findall(r'(?:href|src)="([^"]+)"',content)
 for link in links:
  if link.startswith(('http:','https:','data:','#','mailto:')):continue
  target=link.split('?')[0].split('#')[0]
  resolved=repo/'public'/target.lstrip('/') if target.startswith('/build/') else p.parent/target
  if target and not resolved.exists():missing.append({'source':str(p.relative_to(R)),'target':link})
result={'contrast_pairs':ratios,'contrast_all_pass':all(x['pass'] for x in ratios),'non_design_files_unchanged':not changed,'outside_boundary_changed':changed,'baseline_files':len(base),'fixture_checks':fixture_checks,'maskable_safe_circle':{'radiusPx':204.8,'foregroundMaxRadiusPx':round(far,2),'foregroundPixelsOutside':outside,'pass':outside==0},'missing_links':missing,'core_screen_contracts':len(json.loads((R/'screens/flows.json').read_text())['flows']),'core_screen_captures':len([x for x in ['hoy','compra','receta','producto','pedido','cobro','produccion','historial'] if (R/'screens/evidence'/f'{x}-390.png').exists()]),'production_tests_run':False,'physical_phone_test_run':False,'accessibility_certification_claimed':False}
(R/'implementation-guide/verification.json').write_text(json.dumps(result,ensure_ascii=False,indent=2)+'\n');print(json.dumps(result,ensure_ascii=False,indent=2))
