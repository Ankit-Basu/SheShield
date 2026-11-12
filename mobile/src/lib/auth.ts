import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';
import { create } from 'zustand';

// The sign-in token lives in the OS keychain / keystore, never in plain storage.
const KEY = 'ss.token';
const store = {
  get: () => (Platform.OS === 'web' ? Promise.resolve(globalThis.localStorage?.getItem(KEY) ?? null) : SecureStore.getItemAsync(KEY)),
  set: (v: string) => (Platform.OS === 'web' ? Promise.resolve(globalThis.localStorage?.setItem(KEY, v)) : SecureStore.setItemAsync(KEY, v)),
  clear: () => (Platform.OS === 'web' ? Promise.resolve(globalThis.localStorage?.removeItem(KEY)) : SecureStore.deleteItemAsync(KEY)),
};

type AuthState = {
  token: string | null;
  ready: boolean;
  restore: () => Promise<void>;
  signIn: (token: string) => Promise<void>;
  signOut: () => Promise<void>;
};

export const useAuth = create<AuthState>((set) => ({
  token: null,
  ready: false,
  restore: async () => {
    try {
      set({ token: await store.get(), ready: true });
    } catch {
      set({ token: null, ready: true });
    }
  },
  signIn: async (token) => {
    await store.set(token);
    set({ token });
  },
  signOut: async () => {
    await store.clear();
    set({ token: null });
  },
}));
