// Builds the static part of SheShield for Vercel: the public pages (pro/) and
// their assets (public/). Everything else is PHP and runs on Render; vercel.json
// forwards those paths there.
//
// Only this folder is published, so PHP source and config never reach Vercel,
// which would otherwise serve .php files as plain text downloads.
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const out = path.join(root, 'vercel-dist');

fs.rmSync(out, { recursive: true, force: true });
for (const dir of ['pro', 'public']) {
  fs.cpSync(path.join(root, dir), path.join(out, dir), {
    recursive: true,
    filter: (src) => !src.endsWith('.php'),
  });
}

let files = 0;
(function count(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) count(path.join(dir, entry.name));
    else files++;
  }
})(out);
console.log(`vercel-dist ready: ${files} static files from pro/ and public/`);
