import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';

// The same three tweakcn presets as the website, converted from OKLCH to hex.
export const themes = {
  dawn: {
    name: 'Sunset Horizon',
    background: '#160b12',
    foreground: '#f2e9e4',
    muted: '#cbbab0',
    primary: '#ff7e5f',
    primaryForeground: '#2a0f0a',
    accent: '#feb47b',
    destructive: '#e63946',
    aurora: ['#3a1a52', '#b23a6b', '#f08a5d'] as const,
    base: '#170d17',
  },
  cosmic: {
    name: 'Cosmic Night',
    background: '#0f0f1a',
    foreground: '#e2e2f5',
    muted: '#acaccd',
    primary: '#a48fff',
    primaryForeground: '#130d33',
    accent: '#ff79c6',
    destructive: '#ef4444',
    aurora: ['#1d1a5e', '#6a3fd1', '#d0569f'] as const,
    base: '#0e0d1d',
  },
  solar: {
    name: 'Solar Dusk',
    background: '#120f0d',
    foreground: '#f5f5f4',
    muted: '#b5aeaa',
    primary: '#f97316',
    primaryForeground: '#2b1203',
    accent: '#eab308',
    destructive: '#e93632',
    aurora: ['#3a1c0a', '#b0480f', '#e8a23a'] as const,
    base: '#120d0a',
  },
};

export type ThemeName = keyof typeof themes;
export type Theme = (typeof themes)[ThemeName];

export const status = {
  good: '#4ed589',
  warn: '#f4bf3f',
  info: '#62bbf5',
};

export const fonts = {
  display: 'ClashDisplay',
  body: 'PlusJakartaSans_400Regular',
  medium: 'PlusJakartaSans_500Medium',
  semibold: 'PlusJakartaSans_600SemiBold',
  bold: 'PlusJakartaSans_700Bold',
  mono: 'JetBrainsMono_500Medium',
};

// Glass is translucent white over the aurora, with a brighter top edge like the website.
export const glass = {
  fill: 'rgba(255,255,255,0.065)',
  fillStrong: 'rgba(255,255,255,0.1)',
  line: 'rgba(255,255,255,0.13)',
  lineTop: 'rgba(255,255,255,0.28)',
  lineSoft: 'rgba(255,255,255,0.07)',
  sheen: ['rgba(255,255,255,0.12)', 'rgba(255,255,255,0.02)'] as const,
  solid: 'rgba(28,18,26,0.88)',
};

type ThemeState = { name: ThemeName; setTheme: (name: ThemeName) => void };

export const useThemeStore = create<ThemeState>()(
  persist((set) => ({ name: 'dawn', setTheme: (name) => set({ name }) }), {
    name: 'ss-theme',
    storage: createJSONStorage(() => AsyncStorage),
  }),
);

export const useTheme = (): Theme => themes[useThemeStore((s) => s.name)];
