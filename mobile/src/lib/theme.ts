import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';

// Flat, solid themes: one background, solid cards with a hairline edge, ink text and
// a single accent. Colour is kept for what matters (the accent, status, SOS red).
type Palette = {
  name: string;
  mode: 'light' | 'dark';
  /** Screen background. */
  base: string;
  /** Cards and sheets. */
  surface: string;
  /** Inputs, chips, tracks: a step away from the surface. */
  surfaceAlt: string;
  /** Hairline borders and dividers. */
  line: string;
  foreground: string;
  muted: string;
  primary: string;
  primaryForeground: string;
  /** The accent at low strength, for selected and tinted backgrounds. */
  soft: string;
  /** A second, warmer highlight used sparingly. */
  accent: string;
  /** The tab bar: a solid, high-contrast dock. */
  dock: string;
  dockForeground: string;
  /** Selected chips and switches: ink on light themes, near-white on dark ones. */
  selected: string;
  selectedForeground: string;
  destructive: string;
};

const palette = <T extends Record<string, Palette>>(t: T) => t;

export const themes = palette({
  paper: {
    name: 'Paper',
    mode: 'light',
    base: '#f4f1ea',
    surface: '#ffffff',
    surfaceAlt: '#efebe3',
    line: '#e3ddd1',
    foreground: '#17171b',
    muted: '#6e6a64',
    primary: '#1f6f5c',
    primaryForeground: '#ffffff',
    soft: '#dcebe5',
    accent: '#e9a23b',
    dock: '#17171b',
    dockForeground: '#f4f1ea',
    selected: '#17171b',
    selectedForeground: '#f4f1ea',
    destructive: '#d63b3b',
  },
  ink: {
    name: 'Ink',
    mode: 'dark',
    base: '#0e0f12',
    surface: '#17191d',
    surfaceAlt: '#212429',
    line: '#2a2d33',
    foreground: '#f3f2ee',
    muted: '#9a9da5',
    primary: '#f2c14e',
    primaryForeground: '#17171b',
    soft: '#3a3221',
    accent: '#7fd1c1',
    dock: '#212429',
    dockForeground: '#f3f2ee',
    selected: '#f3f2ee',
    selectedForeground: '#0e0f12',
    destructive: '#ef5350',
  },
  sand: {
    name: 'Sand',
    mode: 'light',
    base: '#f1e8db',
    surface: '#fffcf7',
    surfaceAlt: '#ece1d1',
    line: '#e0d3c0',
    foreground: '#2a1f17',
    muted: '#7a6a5c',
    primary: '#b4502c',
    primaryForeground: '#ffffff',
    soft: '#f3dccf',
    accent: '#2f5d50',
    dock: '#2a1f17',
    dockForeground: '#f1e8db',
    selected: '#2a1f17',
    selectedForeground: '#f1e8db',
    destructive: '#c62f2f',
  },
  midnight: {
    name: 'Midnight',
    mode: 'dark',
    base: '#0b1322',
    surface: '#121c2e',
    surfaceAlt: '#1a263c',
    line: '#24314a',
    foreground: '#eef2f8',
    muted: '#93a0b8',
    primary: '#ff8a6b',
    primaryForeground: '#1a0d08',
    soft: '#3a2826',
    accent: '#6ea8ff',
    dock: '#1a263c',
    dockForeground: '#eef2f8',
    selected: '#eef2f8',
    selectedForeground: '#0b1322',
    destructive: '#ff5c5c',
  },
  lilac: {
    name: 'Lilac',
    mode: 'light',
    base: '#eeebf6',
    surface: '#ffffff',
    surfaceAlt: '#e6e2f1',
    line: '#dbd6ea',
    foreground: '#1c1a29',
    muted: '#6c6780',
    primary: '#4b3fc4',
    primaryForeground: '#ffffff',
    soft: '#e2defa',
    accent: '#f0a830',
    dock: '#1c1a29',
    dockForeground: '#eeebf6',
    selected: '#1c1a29',
    selectedForeground: '#eeebf6',
    destructive: '#d23d3d',
  },
});

export type ThemeName = keyof typeof themes;
export type Theme = Palette;

// Mid-tone status colours that read on both light and dark surfaces. Used for
// dots, icons and tinted backgrounds; text next to them stays in the ink colour.
export const status = {
  good: '#22a05b',
  warn: '#d98e0b',
  info: '#2f86d8',
};

/** SOS keeps one red everywhere, whatever the theme. */
export const sosRed = '#e5303f';

export const fonts = {
  display: 'ClashDisplay',
  body: 'PlusJakartaSans_400Regular',
  medium: 'PlusJakartaSans_500Medium',
  semibold: 'PlusJakartaSans_600SemiBold',
  bold: 'PlusJakartaSans_700Bold',
  mono: 'JetBrainsMono_500Medium',
};

type ThemeState = { name: ThemeName; setTheme: (name: ThemeName) => void };

export const useThemeStore = create<ThemeState>()(
  persist((set) => ({ name: 'paper', setTheme: (name) => set({ name }) }), {
    name: 'ss-theme',
    storage: createJSONStorage(() => AsyncStorage),
    // Phones that saved one of the old gradient themes start on Paper.
    version: 2,
    migrate: () => ({ name: 'paper' as ThemeName }),
  }),
);

export const useTheme = (): Theme => themes[useThemeStore((s) => s.name)] ?? themes.paper;
