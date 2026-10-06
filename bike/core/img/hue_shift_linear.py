from PIL import Image
import colorsys
import shutil

# backup original
shutil.copy2('core/img/linear-bg.png', 'core/img/linear-bg-orig.png')

img = Image.open('core/img/linear-bg.png').convert('RGBA')
pixels = img.load()

target_hue = 20 / 360.0 # roughly orange

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
        
img.save('core/img/linear-bg.png')
print("Done converting linear-bg.png")
