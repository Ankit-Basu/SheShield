#!/usr/bin/env node
// Copies runtime libraries into public/lib and builds the lucide icon sprite
// (the icon set shadcn/ui uses). Serving these locally removes CDN round trips.
// Run after upgrading a dependency: node scripts/build-vendor.js
const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const lib = path.join(root, 'public', 'lib');
const nm = path.join(root, 'node_modules');
fs.mkdirSync(path.join(lib, 'images'), { recursive: true });

const files = {
  'gsap/dist/gsap.min.js': 'gsap.min.js',
  'gsap/dist/ScrollTrigger.min.js': 'ScrollTrigger.min.js',
  'lenis/dist/lenis.min.js': 'lenis.min.js',
  'leaflet/dist/leaflet.js': 'leaflet.js',
  'leaflet/dist/leaflet.css': 'leaflet.css',
  'leaflet/dist/images/marker-icon.png': 'images/marker-icon.png',
  'leaflet/dist/images/marker-icon-2x.png': 'images/marker-icon-2x.png',
  'leaflet/dist/images/marker-shadow.png': 'images/marker-shadow.png',
  'leaflet/dist/images/layers.png': 'images/layers.png',
  'leaflet/dist/images/layers-2x.png': 'images/layers-2x.png',
  'leaflet-draw/dist/leaflet.draw.js': 'leaflet.draw.js',
  'leaflet-draw/dist/leaflet.draw.css': 'leaflet.draw.css',
  'leaflet-draw/dist/images/spritesheet.svg': 'images/spritesheet.svg',
  'leaflet-draw/dist/images/spritesheet.png': 'images/spritesheet.png',
  'leaflet-draw/dist/images/spritesheet-2x.png': 'images/spritesheet-2x.png',
};
for (const [from, to] of Object.entries(files)) {
  fs.copyFileSync(path.join(nm, from), path.join(lib, to));
}

const icons = [
  'shield', 'shield-check', 'shield-alert', 'siren', 'phone', 'phone-call', 'map-pin', 'map', 'navigation',
  'locate-fixed', 'footprints', 'file-text', 'file-plus', 'files', 'chart-column', 'house', 'settings', 'log-out',
  'bell', 'search', 'menu', 'x', 'arrow-right', 'arrow-left', 'arrow-up-right', 'arrow-down', 'check', 'circle-check',
  'circle-alert', 'triangle-alert', 'info', 'clock', 'copy', 'message-circle', 'message-square', 'send', 'lock',
  'mail', 'user', 'users', 'eye', 'eye-off', 'upload', 'camera', 'palette', 'sparkles', 'heart-handshake',
  'hand-heart', 'graduation-cap', 'building', 'store', 'door-open', 'trophy', 'rotate-cw', 'plus', 'layers',
  'trash-2', 'chevron-right', 'chevron-down', 'route', 'user-round-check', 'pentagon', 'circle', 'database', 'activity',
  'inbox', 'scale', 'moon', 'sun', 'sunrise', 'sunset', 'satellite', 'circle-x', 'badge-check', 'smartphone', 'radar', 'check-check', 'video', 'wifi', 'battery-full', 'signal',
];
const symbols = icons.map((name) => {
  const svg = fs.readFileSync(path.join(nm, 'lucide-static', 'icons', `${name}.svg`), 'utf8');
  const inner = svg.replace(/<!--[\s\S]*?-->/g, '').replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>\s*$/, '')
    .replace(/\s*\n\s*/g, '');
  return `<symbol id="${name}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${inner}</symbol>`;
});
fs.writeFileSync(path.join(root, 'public', 'icons.svg'),
  `<svg xmlns="http://www.w3.org/2000/svg">${symbols.join('')}</svg>\n`);
console.log(`lib: ${Object.keys(files).length} files, sprite: ${icons.length} icons`);
