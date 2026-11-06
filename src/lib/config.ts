// Where the SheShield backend lives. Vercel serves the site and forwards /api to Render.
// Override for local testing with EXPO_PUBLIC_API_URL=http://<your-ip>:8088/sheshield/
const raw = process.env.EXPO_PUBLIC_API_URL || 'https://sheshield-ab.vercel.app/';
export const API_URL = raw.endsWith('/') ? raw : `${raw}/`;

export const absoluteUrl = (path?: string | null) => {
  if (!path) return null;
  if (/^https?:\/\//i.test(path)) return path;
  return API_URL + path.replace(/^\/+/, '');
};
