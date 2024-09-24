const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

// Configuration
const imagesFolder = './assets/brand-names'; // Path to the folder containing images
const outputCSSFile = './assets/css/output.css'; // The output CSS file path

// Supported image extensions
const supportedExtensions = ['.svg']; // Only SVG for now

// Function to extract the dominant color
const extractDominantColor = async (imagePath) => {

//console.log(imagePath);

  try {
      const { dominant } = await sharp(imagePath).normalise(80).stats();
      const { r, g, b } = dominant;

    console.log(dominant);
    return `rgb(${r}, ${g}, ${b})`;
  } catch (err) {
    console.error(`Error extracting color from ${imagePath}:`, err);
    return null;
  }
};

// Function to generate CSS rules for images
const generateCSSForImages = async (images) => {
  let cssContent = '';

  for (const image of images) {
    const imageName = path.parse(image).name; // Get the image name without the extension
    const imagePath = path.join(imagesFolder, image).replace(/\\/g, '/'); // Make the path compatible for CSS usage
    const imgPath = path.resolve(process.cwd(), imagePath); // Resolve the full path to the image

    const bgColor = await extractDominantColor(imgPath);

    if (bgColor) {
      // Generate the CSS rule for this image
      cssContent += `
*[brand*='${imageName}'] {
  background-image: url('${imagePath}');
  background-color: ${bgColor};
}
`;
    }
  }

  return cssContent;
};

// Function to scan the folder and generate CSS
const generateCSSFile = () => {
  fs.readdir(imagesFolder, async (err, files) => {
    if (err) {
      console.error('Error reading the images folder:', err);
      return;
    }

    const imageFiles = files.filter((file) =>
      supportedExtensions.includes(path.extname(file).toLowerCase())
    );

    if (imageFiles.length === 0) {
      console.log('No images found in the folder.');
      return;
    }

    const cssContent = await generateCSSForImages(imageFiles);

    fs.writeFileSync(outputCSSFile, cssContent); // Using writeFileSync to block until the file is written
    console.log(`CSS file generated successfully: ${outputCSSFile}`);
  });
};

// Run the script
generateCSSFile();
