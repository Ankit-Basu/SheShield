// The backend stores campus times in IST as "YYYY-MM-DD HH:MM:SS".
export const parseServerDate = (s?: string | null) => {
  if (!s) return null;
  const d = new Date(s.replace(' ', 'T') + '+05:30');
  return Number.isNaN(d.getTime()) ? null : d;
};

export function timeAgo(s?: string | null) {
  const d = parseServerDate(s);
  if (!d) return '';
  const sec = Math.round((Date.now() - d.getTime()) / 1000);
  if (sec < 45) return 'just now';
  if (sec < 3600) return `${Math.round(sec / 60)} min ago`;
  if (sec < 86400) return `${Math.round(sec / 3600)} h ago`;
  if (sec < 86400 * 7) return `${Math.round(sec / 86400)} d ago`;
  return d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
}

export const shortDateTime = (s?: string | null) =>
  parseServerDate(s)?.toLocaleString('en-IN', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit', timeZone: 'Asia/Kolkata' }) ?? '';

// "2026-11-06T14:05" in IST, the format the report endpoint accepts.
export function istLocalString(d = new Date()) {
  const ist = new Date(d.getTime() + 5.5 * 3600 * 1000);
  return ist.toISOString().slice(0, 16);
}

export function greeting(d = new Date()) {
  const h = Number(d.toLocaleString('en-US', { hour: 'numeric', hour12: false, timeZone: 'Asia/Kolkata' }));
  return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
}

// Minutes after midnight IST for sunrise and sunset at LPU (31.25 N, 75.70 E), NOAA approximation.
export function sunTimes(date = new Date()) {
  const lat = 31.2536, lng = 75.7037, rad = Math.PI / 180;
  const start = Date.UTC(date.getUTCFullYear(), 0, 0);
  const day = Math.floor((date.getTime() - start) / 86400000);
  const g = ((2 * Math.PI) / 365) * (day - 1);
  const eq = 229.18 * (0.000075 + 0.001868 * Math.cos(g) - 0.032077 * Math.sin(g) - 0.014615 * Math.cos(2 * g) - 0.040849 * Math.sin(2 * g));
  const decl = 0.006918 - 0.399912 * Math.cos(g) + 0.070257 * Math.sin(g) - 0.006758 * Math.cos(2 * g) + 0.000907 * Math.sin(2 * g);
  const ha = Math.acos(Math.cos(90.833 * rad) / (Math.cos(lat * rad) * Math.cos(decl)) - Math.tan(lat * rad) * Math.tan(decl)) / rad;
  const noon = 720 - 4 * lng - eq + 330;
  return { sunrise: noon - 4 * ha, sunset: noon + 4 * ha };
}

export function campusClock(now = new Date()) {
  const time = now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Kolkata' });
  const [h, m] = time.split(':').map(Number);
  const mins = h * 60 + m;
  const { sunrise, sunset } = sunTimes(now);
  const fmt = (x: number) => `${Math.floor(x / 60)}h ${Math.round(x % 60)}m`;
  const isDay = mins >= sunrise && mins < sunset;
  const next = isDay ? `Sunset in ${fmt(sunset - mins)}` : `Sunrise in ${fmt((sunrise - mins + 1440) % 1440)}`;
  return { time, isDay, next };
}
