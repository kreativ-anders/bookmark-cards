const fs = require('fs').promises;
const path = require('path');
const sharp = require('sharp');
const ColorThief = require('colorthief');

// Configuration
const imagesFolder = './assets/brand-names';
const outputCSSFile = './assets/css/output.css';

// Supported image extensions (including both vector and raster formats)
const supportedExtensions = ['.svg', '.png', '.jpg', '.jpeg', '.gif'];

// Function to convert SVG to PNG using sharp
const convertSvgToPng = async (svgPath) => {
  const pngPath = svgPath.replace('.svg', '.png');
  await sharp(svgPath)
    .png()
    .toFile(pngPath);
  return pngPath;
};

// Function to extract the dominant color using colorthief
const extractDominantColor = async (imagePath) => {
  try {
    let pathToUse = imagePath;
    
    // If the image is SVG, convert it to PNG first
    if (path.extname(imagePath).toLowerCase() === '.svg') {
      pathToUse = await convertSvgToPng(imagePath);
    }
    
    const color = await ColorThief.getColor(pathToUse);
    const rgbColor = `rgb(${color[0]}, ${color[1]}, ${color[2]})`;
    
    // If we created a temporary PNG, delete it
    if (pathToUse !== imagePath) {
      await fs.unlink(pathToUse);
    }
    
    return rgbColor;
  } catch (err) {
    console.error(`Error extracting color from ${imagePath}:`, err);
    return null;
  }
};

// Function to generate CSS rules for images
const generateCSSForImages = async (images) => {
  let cssContent = '';

  for (const image of images) {
    const imageName = path.parse(image).name;
    const imagePath = path.join(imagesFolder, image).replace(/\\/g, '/');
    const imgPath = path.resolve(process.cwd(), imagePath);

    const bgColor = await extractDominantColor(imgPath);

    if (bgColor) {
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
const generateCSSFile = async () => {
  try {
    const files = await fs.readdir(imagesFolder);
    
    const imageFiles = files.filter((file) =>
      supportedExtensions.includes(path.extname(file).toLowerCase())
    );

    if (imageFiles.length === 0) {
      console.log('No supported images found in the folder.');
      return;
    }

    const cssContent = await generateCSSForImages(imageFiles);

    await fs.writeFile(outputCSSFile, cssContent);
    console.log(`CSS file generated successfully: ${outputCSSFile}`);
  } catch (err) {
    console.error('Error generating CSS file:', err);
  }
};

// Run the script
generateCSSFile();