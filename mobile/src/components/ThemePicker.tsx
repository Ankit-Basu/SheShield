import * as Haptics from 'expo-haptics';
import { Check } from 'lucide-react-native';
import { useEffect } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import Svg, { Circle } from 'react-native-svg';

import { sosRed, Theme, ThemeName, themes, useTheme, useThemeStore } from '@/lib/theme';

import { T } from './Text';

const W = 112;
const H = 190;
const R = 10;
const C = 2 * Math.PI * R;

// A tiny Home screen in the theme's own colours: header, readiness card, two tool
// tiles (one switched on) and the dock, so you see the theme before you pick it.
function MiniPhone({ t }: { t: Theme }) {
  return (
    <View style={[styles.phone, { backgroundColor: t.base }]}>
      <View style={{ padding: 9, gap: 6 }}>
        <View style={[styles.line, { width: 30, backgroundColor: t.muted, opacity: 0.6, height: 3 }]} />
        <View style={[styles.line, { width: 50, backgroundColor: t.foreground, height: 7, borderRadius: 3 }]} />

        <View style={[styles.card, { backgroundColor: t.surface, borderColor: t.line }]}>
          <Svg width={26} height={26}>
            <Circle cx={13} cy={13} r={R} stroke={t.surfaceAlt} strokeWidth={4} fill="none" />
            <Circle cx={13} cy={13} r={R} stroke={t.primary} strokeWidth={4} fill="none" strokeDasharray={`${C * 0.66} ${C}`} strokeLinecap="round" transform="rotate(-90 13 13)" />
          </Svg>
          <View style={{ flex: 1, gap: 4 }}>
            <View style={[styles.line, { width: '80%', backgroundColor: t.foreground }]} />
            <View style={[styles.line, { width: '55%', backgroundColor: t.muted, opacity: 0.6 }]} />
          </View>
        </View>

        <View style={{ flexDirection: 'row', gap: 5 }}>
          <View style={[styles.tile, { backgroundColor: t.primary, borderColor: t.primary }]}>
            <View style={[styles.tileDot, { backgroundColor: t.primaryForeground, opacity: 0.85 }]} />
          </View>
          <View style={[styles.tile, { backgroundColor: t.surface, borderColor: t.line }]}>
            <View style={[styles.tileDot, { backgroundColor: t.surfaceAlt }]} />
          </View>
        </View>
        <View style={[styles.tile, { backgroundColor: t.surface, borderColor: t.line, flex: 0, height: 22 }]} />
      </View>

      <View style={[styles.dock, { backgroundColor: t.dock }]}>
        <View style={[styles.dockPill, { backgroundColor: t.primary }]} />
        <View style={[styles.dockDot, { backgroundColor: t.dockForeground }]} />
        <View style={[styles.sos, { backgroundColor: sosRed, borderColor: t.base }]} />
        <View style={[styles.dockDot, { backgroundColor: t.dockForeground }]} />
        <View style={[styles.dockDot, { backgroundColor: t.dockForeground }]} />
      </View>
    </View>
  );
}

function Option({ k, on }: { k: ThemeName; on: boolean }) {
  const current = useTheme();
  const t = themes[k];
  const setTheme = useThemeStore((s) => s.setTheme);
  const v = useSharedValue(on ? 1 : 0);
  useEffect(() => {
    v.set(withSpring(on ? 1 : 0, { damping: 15, stiffness: 220 }));
  }, [on, v]);
  const lift = useAnimatedStyle(() => ({ transform: [{ translateY: -4 * v.value }] }));
  const badge = useAnimatedStyle(() => ({ opacity: v.value, transform: [{ scale: 0.5 + 0.5 * v.value }] }));
  return (
    <Pressable
      onPress={() => {
        if (on) return;
        Haptics.selectionAsync();
        setTheme(k);
      }}
      accessibilityRole="radio"
      accessibilityState={{ checked: on }}
      accessibilityLabel={`${t.name} theme`}
      style={{ gap: 9, width: W + 6 }}>
      <Animated.View style={[styles.frame, { borderColor: on ? current.foreground : current.line }, lift]}>
        <MiniPhone t={t} />
        <Animated.View style={[styles.check, { backgroundColor: current.foreground }, badge]}>
          <Check size={12} color={current.base} strokeWidth={3} />
        </Animated.View>
      </Animated.View>
      <View style={styles.caption}>
        <T variant="label" color={on ? current.foreground : current.muted} style={{ fontSize: 13 }} numberOfLines={1}>
          {t.name}
        </T>
        <T variant="small" muted style={{ fontSize: 11 }}>
          {t.mode === 'light' ? 'Light' : 'Dark'}
        </T>
      </View>
    </Pressable>
  );
}

// Every theme as a preview you can swipe through. The whole app changes the moment you tap one.
export function ThemePicker() {
  const current = useThemeStore((s) => s.name);
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginHorizontal: -18 }} contentContainerStyle={{ paddingHorizontal: 18, paddingTop: 6, gap: 10 }}>
      {(Object.keys(themes) as ThemeName[]).map((k) => (
        <Option key={k} k={k} on={current === k} />
      ))}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  frame: { width: W + 6, height: H + 6, borderRadius: 24, borderWidth: 2, padding: 1 },
  phone: { width: W, height: H, borderRadius: 21, overflow: 'hidden' },
  line: { height: 4, borderRadius: 2 },
  card: { flexDirection: 'row', alignItems: 'center', gap: 7, padding: 7, borderRadius: 11, borderWidth: 1, marginTop: 4 },
  tile: { flex: 1, height: 34, borderRadius: 9, borderWidth: 1, padding: 5 },
  tileDot: { width: 9, height: 9, borderRadius: 3 },
  dock: { position: 'absolute', left: 8, right: 8, bottom: 8, height: 22, borderRadius: 11, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-around' },
  dockPill: { position: 'absolute', left: 4, top: 3, width: 20, height: 16, borderRadius: 8 },
  dockDot: { width: 5, height: 5, borderRadius: 3, opacity: 0.55 },
  sos: { width: 15, height: 15, borderRadius: 8, marginTop: -10, borderWidth: 2 },
  check: { position: 'absolute', top: 8, right: 8, width: 20, height: 20, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  caption: { gap: 1, paddingHorizontal: 4 },
});
