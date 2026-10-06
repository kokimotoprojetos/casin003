from PIL import Image
import colorsys
import sys
import shutil
import os

# backup original
shutil.copy2('core/img/team-background.png', 'core/img/team-background-orig.png')

img = Image.open('core/img/team-background.png').convert('RGBA')
pixels = img.load()

# We want to change the target purple (#9400FF or similar) to the target orange (#fe5b09)
target_hue = 20 / 360.0 # roughly orange

for y in range(img.height):
    for x in range(img.width):
        r, g, b, a = pixels[x, y]
        if a == 0:
            continue
            
        h, s, v = colorsys.rgb_to_hsv(r/255.0, g/255.0, b/255.0)
        
        # If the pixel has some saturation (not grayscale), shift its hue
        if s > 0.1:
            h = target_hue
            
        nr, ng, nb = colorsys.hsv_to_rgb(h, s, v)
        pixels[x, y] = (int(nr * 255), int(ng * 255), int(nb * 255), a)
        
img.save('core/img/team-background.png')
print("Done converting team-background.png")
