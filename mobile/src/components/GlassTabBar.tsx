import { BlurView } from 'expo-blur';
import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Tabs } from 'expo-router';
import { Footprints, House, Map, Siren, UserRound } from 'lucide-react-native';
import { ComponentProps, useEffect, useRef, useState } from 'react';
import { LayoutChangeEvent, Platform, Pressable, StyleSheet, View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withRepeat, withSequence, withSpring, withTiming } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useBlurTarget } from '@/lib/blur';
import { glass, useTheme } from '@/lib/theme';

import { T } from './Text';

const icons: Record<string, typeof House> = { index: House, map: Map, walks: Footprints, profile: UserRound };
const labels: Record<string, string> = { index: 'Home', map: 'Map', walks: 'Walks', profile: 'You' };
const BAR = 70;
const SOS = 62;
const spring = { damping: 20, stiffness: 230, mass: 0.9 };

// Exactly what Expo Router's Tabs passes to a custom tab bar. In this version that is
// { state, descriptors, emitter, navigateToTab, insets }: there is no `navigation`.
export type TabBarProps = Parameters<NonNullable<ComponentProps<typeof Tabs>['tabBar']>>[0];

type Box = { x: number; w: number };

function Tab({ name, focused, onPress, onLayout }: { name: string; focused: boolean; onPress: () => void; onLayout: (b: Box) => void }) {
  const theme = useTheme();
  const v = useSharedValue(focused ? 1 : 0);
  useEffect(() => {
    v.set(withSpring(focused ? 1 : 0, { damping: 14, stiffness: 240 }));
  }, [focused, v]);
  const icon = useAnimatedStyle(() => ({ transform: [{ translateY: -1.5 * v.value }, { scale: 1 + 0.08 * v.value }] }));
  const Icon = icons[name] ?? House;
  return (
    <Pressable
      onPress={onPress}
      onLayout={(e: LayoutChangeEvent) => onLayout({ x: e.nativeEvent.layout.x, w: e.nativeEvent.layout.width })}
      style={styles.tab}
      accessibilityRole="tab"
      accessibilityState={{ selected: focused }}
      accessibilityLabel={labels[name]}>
      <Animated.View style={icon}>
        <Icon size={21} color={focused ? theme.primary : theme.muted} strokeWidth={focused ? 2.3 : 1.8} />
      </Animated.View>
      <T variant={focused ? 'label' : 'small'} color={focused ? theme.foreground : theme.muted} style={{ fontSize: 10.5, lineHeight: 13 }}>
        {labels[name]}
      </T>
    </Pressable>
  );
}

// One piece of brighter glass that slides to whichever tab is open.
function Lens({ box }: { box: Box | undefined }) {
  const theme = useTheme();
  const x = useSharedValue(box?.x ?? 0);
  const w = useSharedValue(box?.w ?? 0);
  // The first position is set directly; after that the lens glides.
  const placed = useRef(false);
  useEffect(() => {
    if (!box) return;
    if (!placed.current) {
      placed.current = true;
      x.set(box.x);
      w.set(box.w);
      return;
    }
    x.set(withSpring(box.x, spring));
    w.set(withSpring(box.w, spring));
  }, [box, x, w]);
  const style = useAnimatedStyle(() => ({ width: w.value - 8, transform: [{ translateX: x.value + 4 }] }));
  if (!box) return null;
  return (
    <Animated.View style={[styles.lens, { borderColor: theme.primary + '55' }, style]} pointerEvents="none">
      <LinearGradient colors={[theme.primary + '3d', theme.accent + '14']} start={{ x: 0.2, y: 0 }} end={{ x: 0.8, y: 1 }} style={StyleSheet.absoluteFill} />
      <LinearGradient
        colors={['rgba(255,255,255,0)', 'rgba(255,255,255,0.55)', 'rgba(255,255,255,0)']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={styles.lensRim}
      />
    </Animated.View>
  );
}

// The SOS button sits in a cut-out in the middle of the bar, breathing gently so it is easy to find.
function SosButton() {
  const theme = useTheme();
  const pulse = useSharedValue(0);
  useEffect(() => {
    pulse.set(withRepeat(withSequence(withTiming(1, { duration: 1300 }), withTiming(0, { duration: 1300 })), -1));
  }, [pulse]);
  const ring = useAnimatedStyle(() => ({ opacity: 0.5 - 0.45 * pulse.value, transform: [{ scale: 1 + 0.3 * pulse.value }] }));
  return (
    <View style={styles.sosSlot} pointerEvents="box-none">
      <View style={[styles.notch, { backgroundColor: theme.base, borderColor: glass.line }]} pointerEvents="none" />
      <Animated.View style={[styles.sosRing, ring]} pointerEvents="none" />
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Open SOS"
        onPress={() => {
          Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Heavy);
          router.push('/sos');
        }}
        style={({ pressed }) => [styles.sosPress, pressed && { transform: [{ scale: 0.94 }] }]}>
        <LinearGradient colors={['#ff6b78', '#e0112b']} start={{ x: 0.2, y: 0 }} end={{ x: 0.8, y: 1 }} style={styles.sos}>
          <Siren size={22} color="#fff" strokeWidth={2.3} />
          <T variant="eyebrow" color="#fff" style={{ fontSize: 9, letterSpacing: 1.4, lineHeight: 11, marginTop: 1 }}>
            SOS
          </T>
        </LinearGradient>
      </Pressable>
    </View>
  );
}

export function GlassTabBar({ state, emitter, navigateToTab }: TabBarProps) {
  const insets = useSafeAreaInsets();
  const blurTarget = useBlurTarget((s) => s.target);
  const [boxes, setBoxes] = useState<Record<string, Box>>({});
  const routes = state.routes;
  const half = Math.ceil(routes.length / 2);
  // Same contract as the built-in tab bar: announce the press, then switch unless a screen cancelled it.
  const go = (route: { key: string; name: string }, index: number) => {
    const event = emitter.emit({ type: 'tabPress', target: route.key, canPreventDefault: true });
    if (state.index !== index && !event.defaultPrevented) {
      Haptics.selectionAsync();
      navigateToTab(route.key);
    }
  };
  const render = (r: { key: string; name: string }) => {
    const i = routes.indexOf(r);
    return (
      <Tab
        key={r.key}
        name={r.name}
        focused={state.index === i}
        onPress={() => go(r, i)}
        onLayout={(b) => setBoxes((prev) => (prev[r.key]?.x === b.x && prev[r.key]?.w === b.w ? prev : { ...prev, [r.key]: b }))}
      />
    );
  };
  return (
    <View style={[styles.wrap, { bottom: Math.max(insets.bottom, 10) + 4 }]} pointerEvents="box-none">
      <View style={styles.bar}>
        {Platform.OS === 'ios' ? (
          <BlurView intensity={45} tint="dark" style={StyleSheet.absoluteFill} />
        ) : blurTarget ? (
          // Real frosted glass on Android 12+: blurs the focused screen's content.
          // Older Android versions skip the blur and keep the tinted fill below.
          <>
            <View style={[StyleSheet.absoluteFill, { backgroundColor: 'rgba(20,12,18,0.45)' }]} />
            <BlurView blurTarget={blurTarget} blurMethod="dimezisBlurViewSdk31Plus" intensity={60} tint="dark" style={StyleSheet.absoluteFill} />
          </>
        ) : (
          <View style={[StyleSheet.absoluteFill, { backgroundColor: 'rgba(24,14,22,0.88)' }]} />
        )}
        <LinearGradient colors={['rgba(255,255,255,0.13)', 'rgba(255,255,255,0.02)']} style={StyleSheet.absoluteFill} pointerEvents="none" />
        <Lens box={boxes[routes[state.index]?.key]} />
        {routes.slice(0, half).map(render)}
        <View style={{ width: SOS + 18 }} />
        {routes.slice(half).map(render)}
      </View>
      <SosButton />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { position: 'absolute', left: 16, right: 16, alignItems: 'center' },
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    height: BAR,
    width: '100%',
    borderRadius: BAR / 2,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: glass.line,
    borderTopColor: glass.lineTop,
    paddingHorizontal: 6,
  },
  tab: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 3, height: '100%' },
  lens: { position: 'absolute', left: 0, top: 7, height: BAR - 16, borderRadius: (BAR - 16) / 2, borderWidth: 1, overflow: 'hidden' },
  lensRim: { position: 'absolute', top: 0, left: 12, right: 12, height: 1 },
  sosSlot: { position: 'absolute', top: -(SOS / 2) + 6, width: SOS + 14, height: SOS + 14, alignItems: 'center', justifyContent: 'center' },
  notch: { position: 'absolute', width: SOS + 14, height: SOS + 14, borderRadius: (SOS + 14) / 2, borderWidth: 1 },
  sosRing: { position: 'absolute', width: SOS, height: SOS, borderRadius: SOS / 2, backgroundColor: '#ff3b4f' },
  sosPress: { width: SOS, height: SOS, borderRadius: SOS / 2 },
  sos: {
    width: SOS,
    height: SOS,
    borderRadius: SOS / 2,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: 'rgba(255,255,255,0.35)',
    shadowColor: '#ff2d45',
    shadowOpacity: 0.6,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 6 },
    elevation: 12,
  },
});
