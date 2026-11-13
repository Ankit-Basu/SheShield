import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import { Check } from 'lucide-react-native';
import { useEffect } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import Svg, { Circle, Defs, RadialGradient, Rect, Stop } from 'react-native-svg';

import { Theme, ThemeName, themes, useThemeStore } from '@/lib/theme';

import { T } from './Text';

const W = 116;
const H = 196;

// A tiny Home screen painted in the theme: aurora, a glass card with the readiness
// ring, two tool tiles and the tab bar, so you see the theme before you pick it.
function MiniPhone({ t, id }: { t: Theme; id: string }) {
  return (
    <View style={[styles.phone, { backgroundColor: t.base }]}>
      <Svg width={W} height={H} style={StyleSheet.absoluteFill}>
        <Defs>
          <RadialGradient id={`a${id}`} cx="20%" cy="10%" r="70%">
            <Stop offset="0" stopColor={t.aurora[1]} stopOpacity={0.95} />
            <Stop offset="1" stopColor={t.aurora[1]} stopOpacity={0} />
          </RadialGradient>
          <RadialGradient id={`b${id}`} cx="95%" cy="45%" r="60%">
            <Stop offset="0" stopColor={t.aurora[2]} stopOpacity={0.55} />
            <Stop offset="1" stopColor={t.aurora[2]} stopOpacity={0} />
          </RadialGradient>
        </Defs>
        <Rect width={W} height={H} fill={t.aurora[0]} opacity={0.5} />
        <Rect width={W} height={H} fill={`url(#a${id})`} />
        <Rect width={W} height={H} fill={`url(#b${id})`} />
      </Svg>

      <View style={{ padding: 9, gap: 6 }}>
        <View style={[styles.line, { width: 30, backgroundColor: t.muted, opacity: 0.6, height: 3 }]} />
        <View style={[styles.line, { width: 52, backgroundColor: t.foreground, height: 7, borderRadius: 3 }]} />

        <View style={styles.glassCard}>
          <Svg width={30} height={30}>
            <Circle cx={15} cy={15} r={11} stroke="rgba(255,255,255,0.15)" strokeWidth={4} fill="none" />
            <Circle cx={15} cy={15} r={11} stroke={t.primary} strokeWidth={4} fill="none" strokeDasharray={`${2 * Math.PI * 11 * 0.66} 100`} strokeLinecap="round" transform="rotate(-90 15 15)" />
          </Svg>
          <View style={{ flex: 1, gap: 4 }}>
            <View style={[styles.line, { width: '80%', backgroundColor: t.foreground, opacity: 0.85 }]} />
            <View style={[styles.line, { width: '55%', backgroundColor: t.muted, opacity: 0.6 }]} />
            <View style={[styles.line, { width: '65%', backgroundColor: t.muted, opacity: 0.6 }]} />
          </View>
        </View>

        <View style={{ flexDirection: 'row', gap: 5 }}>
          <LinearGradient colors={[t.primary + '66', t.accent + '22']} style={[styles.tile, { borderColor: t.primary + '88' }]}>
            <View style={[styles.tileDot, { backgroundColor: t.primary }]} />
          </LinearGradient>
          <View style={[styles.tile, { backgroundColor: 'rgba(255,255,255,0.08)', borderColor: 'rgba(255,255,255,0.12)' }]}>
            <View style={[styles.tileDot, { backgroundColor: 'rgba(255,255,255,0.3)' }]} />
          </View>
        </View>
        <View style={[styles.button, { backgroundColor: t.primary }]} />
      </View>

      <View style={styles.tabbar}>
        <View style={[styles.tabLens, { backgroundColor: t.primary + '44', borderColor: t.primary + '77' }]} />
        <View style={[styles.tabDot, { backgroundColor: t.primary }]} />
        <View style={styles.tabDot} />
        <View style={[styles.sos]} />
        <View style={styles.tabDot} />
        <View style={styles.tabDot} />
      </View>
    </View>
  );
}

function Option({ k, on }: { k: ThemeName; on: boolean }) {
  const t = themes[k];
  const setTheme = useThemeStore((s) => s.setTheme);
  const v = useSharedValue(on ? 1 : 0);
  useEffect(() => {
    v.set(withSpring(on ? 1 : 0, { damping: 15, stiffness: 220 }));
  }, [on, v]);
  const lift = useAnimatedStyle(() => ({ transform: [{ scale: 0.95 + 0.05 * v.value }, { translateY: -4 * v.value }] }));
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
      style={{ gap: 10, width: W }}>
      <Animated.View style={[styles.frame, { borderColor: on ? t.primary : 'rgba(255,255,255,0.1)' }, on && { shadowColor: t.primary }, lift]}>
        <MiniPhone t={t} id={k} />
        <Animated.View style={[styles.check, { backgroundColor: t.primary }, badge]}>
          <Check size={13} color={t.primaryForeground} strokeWidth={3} />
        </Animated.View>
      </Animated.View>
      <View style={{ gap: 5 }}>
        <T variant="label" color={on ? t.foreground : undefined} muted={!on} style={{ fontSize: 13 }} numberOfLines={1}>
          {t.name}
        </T>
        <View style={{ flexDirection: 'row', gap: 4 }}>
          {[t.primary, t.accent, t.aurora[1], t.foreground].map((c) => (
            <View key={c} style={[styles.swatch, { backgroundColor: c }]} />
          ))}
        </View>
      </View>
    </Pressable>
  );
}

// Every theme as a live preview you can swipe through. The whole app changes the moment you tap one.
export function ThemePicker() {
  const current = useThemeStore((s) => s.name);
  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginHorizontal: -18 }} contentContainerStyle={{ paddingHorizontal: 18, paddingTop: 6, gap: 12 }}>
      {(Object.keys(themes) as ThemeName[]).map((k) => (
        <Option key={k} k={k} on={current === k} />
      ))}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  frame: { width: W, height: H + 4, borderRadius: 24, borderWidth: 2, padding: 0, shadowOpacity: 0.5, shadowRadius: 14, shadowOffset: { width: 0, height: 6 } },
  phone: { width: W - 4, height: H, borderRadius: 22, overflow: 'hidden' },
  line: { height: 4, borderRadius: 2 },
  glassCard: { flexDirection: 'row', alignItems: 'center', gap: 7, padding: 7, borderRadius: 11, backgroundColor: 'rgba(255,255,255,0.09)', borderWidth: 1, borderColor: 'rgba(255,255,255,0.14)', marginTop: 4 },
  tile: { flex: 1, height: 34, borderRadius: 9, borderWidth: 1, padding: 5, overflow: 'hidden' },
  tileDot: { width: 9, height: 9, borderRadius: 3 },
  button: { height: 12, borderRadius: 6, marginTop: 2 },
  tabbar: { position: 'absolute', left: 8, right: 8, bottom: 8, height: 22, borderRadius: 11, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-around', backgroundColor: 'rgba(0,0,0,0.35)', borderWidth: 1, borderColor: 'rgba(255,255,255,0.12)' },
  tabLens: { position: 'absolute', left: 3, top: 2, width: 22, height: 16, borderRadius: 8, borderWidth: 1 },
  tabDot: { width: 5, height: 5, borderRadius: 3, backgroundColor: 'rgba(255,255,255,0.35)' },
  sos: { width: 13, height: 13, borderRadius: 7, backgroundColor: '#ef3b4f', marginTop: -10, borderWidth: 1.5, borderColor: 'rgba(0,0,0,0.5)' },
  check: { position: 'absolute', top: 8, right: 8, width: 22, height: 22, borderRadius: 11, alignItems: 'center', justifyContent: 'center' },
  swatch: { width: 12, height: 12, borderRadius: 6, borderWidth: 1, borderColor: 'rgba(0,0,0,0.3)' },
});
