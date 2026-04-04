import os
from PIL import Image

# Current folder where the script is located
current_folder = os.path.dirname(os.path.abspath(__file__))

# Folder to save converted images
output_folder = os.path.join(current_folder, "converted_images")
os.makedirs(output_folder, exist_ok=True)

# Loop through all PNG files in current folder
for filename in os.listdir(current_folder):
    if filename.lower().endswith(".png"):
        input_path = os.path.join(current_folder, filename)
        img = Image.open(input_path).convert("RGBA")

        # Create white background
        white_bg = Image.new("RGBA", img.size, (255, 255, 255, 255))
        white_bg.paste(img, (0, 0), img)

        # Convert to RGB (remove alpha) and save as JPEG with compression
        output_path = os.path.join(output_folder, os.path.splitext(filename)[0] + ".jpg")
        white_bg.convert("RGB").save(output_path, "JPEG", quality=85, optimize=True)
        print(f"Converted {filename} -> {output_path}")

print("All PNG images converted to compressed JPEGs!")
