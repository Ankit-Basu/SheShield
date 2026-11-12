import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';

import { ApiError } from './api';

// Render's free plan sleeps when idle and takes up to a minute to wake. Rather than
// show empty screens, reads fall back to the last data this phone loaded, or to
// sample data on a first run, and say so in a banner while they keep retrying.
export type Source = 'live' | 'saved' | 'sample';

export const useDataSource = create<{ sources: Record<string, Source>; set: (key: string, s: Source) => void }>((set) => ({
  sources: {},
  set: (key, s) => set((st) => (st.sources[key] === s ? st : { sources: { ...st.sources, [key]: s } })),
}));

const PREFIX = 'ss.cache.';
export const CACHE_KEYS = ['me', 'reports', 'walks', 'safe-spaces', 'profile'].map((k) => PREFIX + k);

export async function saveCache(key: string, value: unknown) {
  try {
    await AsyncStorage.setItem(PREFIX + key, JSON.stringify({ at: Date.now(), value }));
  } catch {}
}

async function readCache<T>(key: string): Promise<T | null> {
  try {
    const raw = await AsyncStorage.getItem(PREFIX + key);
    return raw ? (JSON.parse(raw).value as T) : null;
  } catch {
    return null;
  }
}

export const clearCache = () => AsyncStorage.multiRemove(CACHE_KEYS).catch(() => {});

// Reads wait at most this long before showing saved data; the request that timed out
// still wakes the server, and the next retry usually gets live data.
export const READ_TIMEOUT_MS = 10000;
export const RETRY_WHILE_FALLBACK_MS = 15000;

export async function withFallback<T>(key: string, load: () => Promise<T>, sample: () => T | Promise<T>): Promise<T> {
  try {
    const value = await load();
    useDataSource.getState().set(key, 'live');
    saveCache(key, value);
    return value;
  } catch (e) {
    // Only fall back when the server is unreachable or waking; real errors (such as
    // "not signed in") must still surface.
    if (!(e instanceof ApiError && e.offline)) throw e;
    const saved = await readCache<T>(key);
    if (saved !== null) {
      useDataSource.getState().set(key, 'saved');
      return saved;
    }
    useDataSource.getState().set(key, 'sample');
    return sample();
  }
}

export const isFallback = (key: string) => (useDataSource.getState().sources[key] ?? 'live') !== 'live';

// The name to use in sample data: saved when you sign in, so the sample still greets you.
export type SavedProfile = { first_name: string; last_name: string; email: string };
export const saveProfile = (p: SavedProfile) => saveCache('profile', p);
export const readProfile = () => readCache<SavedProfile>('profile');
