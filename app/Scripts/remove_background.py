import os
import sys

def main():
    if len(sys.argv) < 4:
        print("Usage: python3 remove_background.py <input_path> <output_path> <cache_dir>")
        sys.exit(1)
        
    input_path = sys.argv[1]
    output_path = sys.argv[2]
    cache_dir = sys.argv[3]
    
    # Configure Hugging Face home directories to application-writable storage path
    os.environ["HF_HOME"] = cache_dir
    os.environ["TORCH_HOME"] = cache_dir
    
    try:
        from transformers import pipeline
        from PIL import Image
        
        # Load the image segmentation pipeline
        pipe = pipeline("image-segmentation", model="briaai/RMBG-1.4", trust_remote_code=True)
        
        # Remove background
        result_img = pipe(input_path)
        
        # Save output in PNG format with transparency
        result_img.save(output_path, "PNG")
        print("SUCCESS")
        
    except Exception as e:
        print(f"ERROR: {str(e)}")
        sys.exit(2)

if __name__ == "__main__":
    main()
