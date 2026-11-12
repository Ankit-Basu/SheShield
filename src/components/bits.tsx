import * as Haptics from 'expo-haptics';
import { Image } from 'expo-image';
import { ReactNode, useId } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Svg, { Circle, Defs, LinearGradient, Path, Stop } from 'react-native-svg';

import { glass, status, useTheme } from '@/lib/theme';

import { T } from './Text';

export function Logo({ size = 34 }: { size?: number }) {
  const theme = useTheme();
  const id = `logo${useId().replace(/[^a-zA-Z0-9]/g, '')}`;
  return (
    <Svg width={size} height={size * 1.1} viewBox="0 0 100 110" fill="none">
      <Defs>
        <LinearGradient id={id} x1="0" y1="0" x2="1" y2="1">
          <Stop offset="0" stopColor={theme.primary} />
          <Stop offset="1" stopColor={theme.accent} />
        </LinearGradient>
      </Defs>
      <Path d="M50 6 88 22v34q0 24-38 40Q12 80 12 56V22Z" fill={`url(#${id})`} />
      <Circle cx="50" cy="40" r="12" stroke="#1d121b" strokeWidth={5} />
      <Path d="M50 52v21M41 64h18" stroke="#1d121b" strokeWidth={5} strokeLinecap="round" />
    </Svg>
  );
}

export function Chip({ label, selected, onPress, icon }: { label: string; selected?: boolean; onPress?: () => void; icon?: ReactNode }) {
  const theme = useTheme();
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ selected }}
      onPress={() => {
        Haptics.selectionAsync();
        onPress?.();
      }}
      style={[
        styles.chip,
        selected
          ? { backgroundColor: theme.primary + '2e', borderColor: theme.primary }
          : { backgroundColor: glass.fill, borderColor: glass.line },
      ]}>
      {icon}
      <T variant="small" color={selected ? theme.foreground : theme.muted} style={{ fontSize: 13 }}>
        {label}
      </T>
    </Pressable>
  );
}

const statusColors: Record<string, string> = {
  resolved: status.good,
  completed: status.good,
  accepted: status.good,
  in_progress: status.info,
  pending: status.warn,
  cancelled: '#9b8f96',
  closed: '#9b8f96',
  rejected: '#ff6b6b',
  high: '#ff6b6b',
  medium: status.warn,
  low: status.info,
};

export function Badge({ value, live }: { value: string; live?: boolean }) {
  const c = statusColors[value] ?? '#cbbab0';
  return (
    <View style={[styles.badge, { backgroundColor: c + '22' }]}>
      {live ? <View style={[styles.dot, { backgroundColor: c }]} /> : null}
      <T variant="small" color={c} style={{ fontSize: 11.5 }}>
        {value.replace(/_/g, ' ').replace(/^\w/, (x) => x.toUpperCase())}
      </T>
    </View>
  );
}

export function IconTile({ children, color, size = 44 }: { children: ReactNode; color?: string; size?: number }) {
  const theme = useTheme();
  const c = color ?? theme.primary;
  return (
    <View style={[styles.tile, { width: size, height: size, borderRadius: size * 0.32, backgroundColor: c + '24', borderColor: c + '55' }]}>
      {children}
    </View>
  );
}

export function Avatar({ uri, name, size = 52 }: { uri?: string | null; name: string; size?: number }) {
  const theme = useTheme();
  const initials = name.split(' ').filter(Boolean).slice(0, 2).map((s) => s[0]?.toUpperCase()).join('') || '?';
  return (
    <View style={[styles.avatar, { width: size, height: size, borderRadius: size / 2, borderColor: theme.primary + '88' }]}>
      {uri ? (
        <Image source={{ uri }} style={{ width: '100%', height: '100%' }} contentFit="cover" transition={200} />
      ) : (
        <T variant="heading" color={theme.primary} style={{ fontSize: size * 0.36 }}>
          {initials}
        </T>
      )}
    </View>
  );
}

export function SectionHeader({ title, action, onAction }: { title: string; action?: string; onAction?: () => void }) {
  const theme = useTheme();
  return (
    <View style={styles.section}>
      <T variant="label" muted style={{ fontSize: 13, letterSpacing: 0.2 }}>
        {title}
      </T>
      {action ? (
        <Pressable onPress={onAction} hitSlop={10} accessibilityRole="button">
          <T variant="label" color={theme.primary}>
            {action}
          </T>
        </Pressable>
      ) : null}
    </View>
  );
}

export function Empty({ icon, text }: { icon: ReactNode; text: string }) {
  return (
    <View style={styles.empty}>
      {icon}
      <T variant="small" muted style={{ textAlign: 'center' }}>
        {text}
      </T>
    </View>
  );
}

const styles = StyleSheet.create({
  chip: { flexDirection: 'row', alignItems: 'center', gap: 7, height: 38, paddingHorizontal: 14, borderRadius: 12, borderWidth: 1 },
  badge: { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 9, height: 24, borderRadius: 8, alignSelf: 'flex-start' },
  dot: { width: 6, height: 6, borderRadius: 3 },
  tile: { alignItems: 'center', justifyContent: 'center', borderWidth: 1 },
  avatar: { overflow: 'hidden', alignItems: 'center', justifyContent: 'center', borderWidth: 2, backgroundColor: 'rgba(255,255,255,0.06)' },
  section: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 6, marginBottom: -6, paddingHorizontal: 4 },
  empty: { alignItems: 'center', gap: 10, paddingVertical: 26 },
});
