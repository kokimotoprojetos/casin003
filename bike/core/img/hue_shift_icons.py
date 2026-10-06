from PIL import Image
import colorsys
import os

icons = [
    'core/img/withdraw-password.png',
    'core/img/income-record.png',
    'core/img/recharge-record.png',
    'core/img/withdraw-record.png',
    'core/img/custmer-service.png',
    'core/img/download-APK.png',
    'core/img/mine-recharge.png',
    'core/img/mine-withdraw.png',
    'core/img/mine-account.png',
    'core/img/mine-task.png'
]

target_hue = 20 / 360.0 # roughly orange

for icon_path in icons:
    if not os.path.exists(icon_path):
        print(f"Skipping {icon_path} (not found)")
        continue
        
    img = Image.open(icon_path).convert('RGBA')
    pixels = img.load()
    
    for y in range(img.height):
        for x in range(img.width):
            r, g, b, a = pixels[x, y]
            if a == 0:
                continue
                
            h, s, v = colorsys.rgb_to_hsv(r/255.0, g/255.0, b/255.0)
            
            if s > 0.1:
                h = target_hue
                
            nr, ng, nb = colorsys.hsv_to_rgb(h, s, v)
            pixels[x, y] = (int(nr * 255), int(ng * 255), int(nb * 255), a)
            
    img.save(icon_path)
    print(f"Done converting {icon_path}")
