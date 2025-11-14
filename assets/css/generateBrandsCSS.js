const fs = require('fs').promises;
const path = require('path');

// Configuration
const imagesFolder = './assets/brand-names';
const outputCSSFile = './assets/css/brands.css';

// Supported image extensions
const supportedExtensions = ['.svg'];

// Function to generate CSS rules for images
const generateCSSForImages = async (images) => {
  let cssContent = '';

  for (const image of images) {
    const imageName = path.parse(image).name;
    // normalized token: lowercase and keep only a-z letters
    const token = String(imageName).toLowerCase().replace(/[^a-z]+/g, '');

    // skip images that don't produce a token (defensive)
    if (!token) continue;

    // Use substring attribute selector (*=) for permissive matching
    // Keep the original image filename for the URL but URL-encode it to be safe
    const safeFile = encodeURI(imageName + '.svg');
    cssContent += `
*[brand*='${token}'] {
  background-image: url('../../assets/brand-names/${safeFile}');
}
`;
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