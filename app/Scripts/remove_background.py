import os
import sys

def main():
    if len(sys.argv) < 4:
        print("Usage: python3 remove_background.py <input_path> <output_path> <cache_dir> [mode]")
        sys.exit(1)
        
    input_path = sys.argv[1]
    output_path = sys.argv[2]
    cache_dir = sys.argv[3]
    mode = sys.argv[4] if len(sys.argv) > 4 else "smooth"
    
    # Configure Hugging Face home directories to application-writable storage path
    os.environ["HF_HOME"] = cache_dir
    os.environ["TORCH_HOME"] = cache_dir
    
    try:
        from transformers import pipeline
        from PIL import Image, ImageFilter
        import numpy as np
        
        # Load the image segmentation pipeline
        pipe = pipeline("image-segmentation", model="briaai/RMBG-1.4", trust_remote_code=True)
        
        # Get raw mask instead of standard composited image to allow threshold/alpha fine-tuning
        mask = pipe(input_path, return_mask=True)
        
        # Open original image to compose alpha channel onto
        original_img = Image.open(input_path).convert("RGBA")
        
        # Load mask as numpy array for precision operations
        mask_arr = np.array(mask)
        
        if mode == "sharp":
            # Apply edge contrast boost for sharp subject cutouts (e.g. products)
            mask_arr_normalized = mask_arr / 255.0
            boosted = np.power(mask_arr_normalized, 0.7)
            # Sigmoid-like contrast enhancement curve
            boosted = 1.0 / (1.0 + np.exp(-10.0 * (boosted - 0.45)))
            mask_arr = np.clip(boosted * 255.0, 0, 255).astype(np.uint8)
            
        elif mode == "text":
            # Aggressively preserve thin structures, details, overlay text, and badges
            mask_arr_normalized = mask_arr / 255.0
            # High gamma boost (0.35) raises transparent / faint pixels to visible levels
            boosted = np.power(mask_arr_normalized, 0.35)
            # Threshold cutoff: anything above 12% probability becomes opaque to keep text characters solid
            boosted = np.where(boosted > 0.12, 1.0, 0.0)
            mask_arr = (boosted * 255.0).astype(np.uint8)
            
            # Smooth binary mask edges with BoxBlur to prevent aliased / jagged steps
            temp_mask_img = Image.fromarray(mask_arr)
            temp_mask_img = temp_mask_img.filter(ImageFilter.BoxBlur(1))
            mask_arr = np.array(temp_mask_img)
            
        # Composite the final tweaked alpha mask onto the original image
        final_mask = Image.fromarray(mask_arr)
        result_img = original_img.copy()
        result_img.putalpha(final_mask)
        
        # Save output in PNG format with transparency
        result_img.save(output_path, "PNG")
        print("SUCCESS")
        
    except Exception as e:
        print(f"ERROR: {str(e)}")
        sys.exit(2)

if __name__ == "__main__":
    main()
