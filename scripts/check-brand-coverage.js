#!/usr/bin/env node

/**
 * Brand Coverage Checker
 * 
 * This script analyzes bookmarks and checks which ones don't have matching brand logos.
 * It reads user data from Kirby's file structure and compares bookmark titles against
 * available brands defined in brands.css.
 * 
 * Usage:
 *   node scripts/check-brand-coverage.js
 *   npm run check-brands
 */

const fs = require('fs');
const path = require('path');

// Configuration
const BRANDS_CSS_PATH = './assets/css/brands.css';
const ACCOUNTS_DIR = './site/accounts';

/**
 * Simple YAML parser for bookmark data
 * Note: This is a simplified parser tailored for Kirby's bookmark structure
 */
function parseYamlValue(yamlString) {
  if (!yamlString) return [];
  
  const bookmarks = [];
  const lines = yamlString.split('\n');
  let currentBookmark = null;
  
  for (let line of lines) {
    line = line.trim();
    
    // Start of new bookmark entry
    if (line.startsWith('-')) {
      if (currentBookmark && currentBookmark.title) {
        bookmarks.push(currentBookmark);
      }
      currentBookmark = {};
    }
    
    // Parse fields
    if (line.startsWith('title:')) {
      const value = line.substring(6).trim();
      if (currentBookmark) {
        currentBookmark.title = value;
      }
    } else if (line.startsWith('link:')) {
      const value = line.substring(5).trim();
      if (currentBookmark) {
        currentBookmark.link = value;
      }
    } else if (line.startsWith('tags:')) {
      const value = line.substring(5).trim();
      if (currentBookmark) {
        currentBookmark.tags = value;
      }
    }
  }
  
  // Add last bookmark
  if (currentBookmark && currentBookmark.title) {
    bookmarks.push(currentBookmark);
  }
  
  return bookmarks;
}

/**
 * Parse brands.css to extract available brand names
 */
function getAvailableBrands() {
  try {
    const content = fs.readFileSync(BRANDS_CSS_PATH, 'utf8');
    const regex = /\*\[brand~='([^']+)'\]/g;
    const brands = [];
    let match;
    
    while ((match = regex.exec(content)) !== null) {
      brands.push(match[1]);
    }
    
    return brands;
  } catch (error) {
    console.error('Error reading brands.css:', error.message);
    return [];
  }
}

/**
 * Get all user bookmark data
 */
function getUserBookmarks() {
  const allBookmarks = [];
  
  try {
    if (!fs.existsSync(ACCOUNTS_DIR)) {
      console.error(`Accounts directory not found: ${ACCOUNTS_DIR}`);
      return [];
    }
    
    const files = fs.readdirSync(ACCOUNTS_DIR);
    
    files.forEach(file => {
      if (file.endsWith('.yml')) {
        const filePath = path.join(ACCOUNTS_DIR, file);
        try {
          const content = fs.readFileSync(filePath, 'utf8');
          
          // Extract bookmarks field value
          const bookmarksMatch = content.match(/Bookmarks:\s*>\s*([\s\S]*?)(?=\n[A-Z]|\n*$)/);
          if (bookmarksMatch) {
            const bookmarksYaml = bookmarksMatch[1];
            const bookmarks = parseYamlValue(bookmarksYaml);
            
            bookmarks.forEach(bookmark => {
              allBookmarks.push({
                user: file.replace('.yml', ''),
                title: bookmark.title,
                link: bookmark.link,
                tags: bookmark.tags
              });
            });
          }
        } catch (error) {
          // Skip files that can't be parsed
          console.warn(`Warning: Could not parse ${file}`);
        }
      }
    });
  } catch (error) {
    console.error('Error reading user accounts:', error.message);
  }
  
  return allBookmarks;
}

/**
 * Check if a bookmark has a matching brand
 */
function hasMatchingBrand(bookmarkTitle, availableBrands) {
  const title = bookmarkTitle.toLowerCase();
  const titleNoSpaces = title.replace(/\s+/g, '');
  
  return availableBrands.some(brand => 
    brand === title || brand === titleNoSpaces
  );
}

/**
 * Main analysis function
 */
function analyzeBrandCoverage() {
  console.log('='.repeat(60));
  console.log('Brand Coverage Analysis Report');
  console.log('='.repeat(60));
  console.log();
  
  // Get available brands
  const availableBrands = getAvailableBrands();
  console.log(`📊 Available brands in brands.css: ${availableBrands.length}`);
  
  // Get all bookmarks
  const bookmarks = getUserBookmarks();
  console.log(`📚 Total bookmarks found: ${bookmarks.length}`);
  console.log();
  
  // Analyze coverage
  const bookmarksWithBrands = [];
  const bookmarksWithoutBrands = [];
  
  bookmarks.forEach(bookmark => {
    if (hasMatchingBrand(bookmark.title, availableBrands)) {
      bookmarksWithBrands.push(bookmark);
    } else {
      bookmarksWithoutBrands.push(bookmark);
    }
  });
  
  // Calculate statistics
  const coveragePercentage = bookmarks.length > 0 
    ? ((bookmarksWithBrands.length / bookmarks.length) * 100).toFixed(1)
    : 100;
  
  console.log('='.repeat(60));
  console.log('Summary');
  console.log('='.repeat(60));
  console.log(`✅ Bookmarks with brands: ${bookmarksWithBrands.length}`);
  console.log(`❌ Bookmarks without brands: ${bookmarksWithoutBrands.length}`);
  console.log(`📈 Coverage: ${coveragePercentage}%`);
  console.log();
  
  // List bookmarks without brands
  if (bookmarksWithoutBrands.length > 0) {
    console.log('='.repeat(60));
    console.log('Bookmarks Without Matching Brands');
    console.log('='.repeat(60));
    
    // Group by title to avoid duplicates
    const uniqueTitles = {};
    bookmarksWithoutBrands.forEach(bookmark => {
      if (!uniqueTitles[bookmark.title]) {
        uniqueTitles[bookmark.title] = {
          title: bookmark.title,
          count: 0,
          users: []
        };
      }
      uniqueTitles[bookmark.title].count++;
      uniqueTitles[bookmark.title].users.push(bookmark.user);
    });
    
    // Sort by count (most common first)
    const sortedTitles = Object.values(uniqueTitles)
      .sort((a, b) => b.count - a.count);
    
    sortedTitles.forEach((item, index) => {
      console.log(`${index + 1}. "${item.title}" (used ${item.count}x)`);
      console.log(`   Suggested brand names: "${item.title.toLowerCase()}" or "${item.title.toLowerCase().replace(/\s+/g, '')}"`);
    });
    
    console.log();
    console.log('='.repeat(60));
    console.log('💡 Recommendation');
    console.log('='.repeat(60));
    console.log('Consider creating brand logos for the most commonly used bookmarks.');
    console.log('Add SVG files to assets/brand-names/ and run:');
    console.log('  npm run generateBrandsCSS');
  } else {
    console.log('🎉 All bookmarks have matching brands!');
  }
  
  console.log();
  console.log('='.repeat(60));
}

// Run the analysis
try {
  analyzeBrandCoverage();
} catch (error) {
  console.error('Error running analysis:', error);
  process.exit(1);
}
