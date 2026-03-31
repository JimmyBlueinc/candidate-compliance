#!/usr/bin/env node
import fs from 'fs';
import path from 'path';

const BUILD_DIR = 'public/build';
const MANIFEST_FILE = `${BUILD_DIR}/manifest.json`;

// Generate build version from timestamp
const BUILD_VERSION = new Date().toISOString().slice(0, 19).replace(/[:-]/g, '').replace('T', '-');

// Read manifest to get hashed asset names
const manifest = JSON.parse(fs.readFileSync(MANIFEST_FILE, 'utf-8'));

// Find the main JS and CSS files
let mainJs = null;
let mainCss = [];

for (const [key, value] of Object.entries(manifest)) {
    if (key.includes('resources/js/app.js')) {
        mainJs = value.file;
    }
    if (value.css) {
        mainCss.push(...value.css);
    }
}

if (!mainJs) {
    console.error('Could not find main JS file in manifest');
    process.exit(1);
}

// Generate index.html
const indexHtml = `<!DOCTYPE html>
<html lang="en" class="icons-loading">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>AgencyHQ</title>
        <meta name="build-version" content="${BUILD_VERSION}" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" />
${mainCss.map(css => `        <link rel="stylesheet" href="/assets/${css.split('/').pop()}" />`).join('\n')}
    </head>
    <body>
        <div id="app"></div>
        <script type="module" src="/assets/${mainJs.split('/').pop()}"></script>
    </body>
</html>
`;

fs.writeFileSync(`${BUILD_DIR}/index.html`, indexHtml);
console.log(`Generated index.html with build version: ${BUILD_VERSION}`);
console.log(`Main JS: ${mainJs}`);
console.log(`CSS files: ${mainCss.join(', ')}`);
