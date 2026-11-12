// The SheShield backend on Render. The app calls it directly: going through Vercel's
// proxy adds nothing for a phone and fails early (502) while Render is still waking.
// Override for local testing with EXPO_PUBLIC_API_URL=http://<your-ip>:8088/sheshield/
const raw = process.env.EXPO_PUBLIC_API_URL || 'https://sheshield-y9jp.onrender.com/';
export const API_URL = raw.endsWith('/') ? raw : `${raw}/`;

export const absoluteUrl = (path?: string | null) => {
  if (!path) return null;
  if (/^https?:\/\//i.test(path)) return path;
  return API_URL + path.replace(/^\/+/, '');
};
