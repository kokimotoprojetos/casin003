from PIL import Image
import colorsys

img = Image.open('core/img/active-plan.png').convert('RGBA')
pixels = img.load()

target_hue = 20 / 360.0 # roughly orange

for y in range(img.height):
    for x in range(img.width):
        r, g, b, a = pixels[x, y]
        if a == 0:
            continue
            
        h, s, v = colorsys.rgb_to_hsv(r/255.0, g/255.0, b/255.0)
        
        # Erase black text in the top left ("ACTIVE PLAN")
        if x < 150 and y < 40 and v < 0.2:
            pixels[x, y] = (0, 0, 0, 0)
            continue
            
        # If the pixel has some saturation (not grayscale), shift its hue
        if s > 0.1:
            h = target_hue
            
        nr, ng, nb = colorsys.hsv_to_rgb(h, s, v)
        pixels[x, y] = (int(nr * 255), int(ng * 255), int(nb * 255), a)
        
img.save('core/img/active-plan.png')
print("Done converting active-plan.png")
