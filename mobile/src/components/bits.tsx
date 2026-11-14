import * as Haptics from 'expo-haptics';
import { Image } from 'expo-image';
import { ReactNode } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Svg, { Circle, Path } from 'react-native-svg';

import { status, useTheme } from '@/lib/theme';

import { T } from './Text';

export function Logo({ size = 34 }: { size?: number }) {
  const theme = useTheme();
  return (
    <Svg width={size} height={size * 1.1} viewBox="0 0 100 110" fill="none">
      <Path d="M50 6 88 22v34q0 24-38 40Q12 80 12 56V22Z" fill={theme.primary} />
      <Circle cx="50" cy="40" r="12" stroke={theme.primaryForeground} strokeWidth={5} />
      <Path d="M50 52v21M41 64h18" stroke={theme.primaryForeground} strokeWidth={5} strokeLinecap="round" />
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
          ? { backgroundColor: theme.selected, borderColor: theme.selected }
          : { backgroundColor: theme.surfaceAlt, borderColor: theme.line },
      ]}>
      {icon}
      <T variant="small" color={selected ? theme.selectedForeground : theme.foreground} style={{ fontSize: 13 }}>
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
  cancelled: '#8d8b91',
  closed: '#8d8b91',
  rejected: '#e0484f',
  high: '#e0484f',
  medium: status.warn,
  low: status.info,
};

export function Badge({ value }: { value: string; live?: boolean }) {
  const theme = useTheme();
  const c = statusColors[value] ?? theme.muted;
  return (
    <View style={[styles.badge, { backgroundColor: c + '1f' }]}>
      <View style={[styles.dot, { backgroundColor: c }]} />
      <T variant="small" color={theme.foreground} style={{ fontSize: 11.5 }}>
        {value.replace(/_/g, ' ').replace(/^\w/, (x) => x.toUpperCase())}
      </T>
    </View>
  );
}

export function IconTile({ children, color, size = 44 }: { children: ReactNode; color?: string; size?: number }) {
  const theme = useTheme();
  const c = color ?? theme.primary;
  return (
    <View style={[styles.tile, { width: size, height: size, borderRadius: size * 0.32, backgroundColor: c + '1f', borderColor: c + '33' }]}>
      {children}
    </View>
  );
}

export function Avatar({ uri, name, size = 52 }: { uri?: string | null; name: string; size?: number }) {
  const theme = useTheme();
  const initials = name.split(' ').filter(Boolean).slice(0, 2).map((s) => s[0]?.toUpperCase()).join('') || '?';
  return (
    <View style={[styles.avatar, { width: size, height: size, borderRadius: size / 2, backgroundColor: theme.soft, borderColor: theme.surface }]}>
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
  avatar: { overflow: 'hidden', alignItems: 'center', justifyContent: 'center', borderWidth: 2 },
  section: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 6, marginBottom: -6, paddingHorizontal: 4 },
  empty: { alignItems: 'center', gap: 10, paddingVertical: 26 },
});
