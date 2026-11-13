import * as Location from 'expo-location';

export type Fix = { lat: number; lng: number; accuracy: number | null };

export const withTimeout = <T,>(p: Promise<T>, ms: number) =>
  Promise.race([p, new Promise<never>((_, r) => setTimeout(() => r(new Error('timeout')), ms))]);

// Last known position first (instant), then a fresh fix if it arrives within 8 s.
export async function getFix(): Promise<Fix | null> {
  const { granted } = await Location.requestForegroundPermissionsAsync();
  if (!granted) return null;
  const last = await Location.getLastKnownPositionAsync({ maxAge: 120000 }).catch(() => null);
  try {
    const fresh = await withTimeout(Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High }), 8000);
    return { lat: fresh.coords.latitude, lng: fresh.coords.longitude, accuracy: fresh.coords.accuracy };
  } catch {
    return last ? { lat: last.coords.latitude, lng: last.coords.longitude, accuracy: last.coords.accuracy } : null;
  }
}

export const mapsLink = (f: Fix) => `https://maps.google.com/?q=${f.lat.toFixed(6)},${f.lng.toFixed(6)}`;
