import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import { router, Tabs } from 'expo-router';
import { Footprints, House, Map, Siren, UserRound } from 'lucide-react-native';
import { ComponentProps, useEffect, useRef, useState } from 'react';
import { LayoutChangeEvent, Pressable, StyleSheet, View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withRepeat, withSequence, withSpring, withTiming } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { sosRed, useTheme } from '@/lib/theme';

import { T } from './Text';

const icons: Record<string, typeof House> = { index: House, map: Map, walks: Footprints, profile: UserRound };
const labels: Record<string, string> = { index: 'Home', map: 'Map', walks: 'Walks', profile: 'You' };
const BAR = 68;
const SOS = 60;
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
  const icon = useAnimatedStyle(() => ({ transform: [{ scale: 1 + 0.06 * v.value }] }));
  const Icon = icons[name] ?? House;
  const color = focused ? theme.primaryForeground : theme.dockForeground;
  return (
    <Pressable
      onPress={onPress}
      onLayout={(e: LayoutChangeEvent) => onLayout({ x: e.nativeEvent.layout.x, w: e.nativeEvent.layout.width })}
      style={styles.tab}
      accessibilityRole="tab"
      accessibilityState={{ selected: focused }}
      accessibilityLabel={labels[name]}>
      <Animated.View style={[icon, { opacity: focused ? 1 : 0.6 }]}>
        <Icon size={21} color={color} strokeWidth={focused ? 2.3 : 1.9} />
      </Animated.View>
      <T variant={focused ? 'label' : 'small'} color={color} style={{ fontSize: 10.5, lineHeight: 13, opacity: focused ? 1 : 0.6 }}>
        {labels[name]}
      </T>
    </Pressable>
  );
}

// A solid pill in the accent colour that slides to whichever tab is open.
function Indicator({ box }: { box: Box | undefined }) {
  const theme = useTheme();
  const x = useSharedValue(box?.x ?? 0);
  const w = useSharedValue(box?.w ?? 0);
  // The first position is set directly; after that the pill glides.
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
  return <Animated.View style={[styles.indicator, { backgroundColor: theme.primary }, style]} pointerEvents="none" />;
}

// SOS sits in a cut-out in the middle of the dock, breathing gently so it is easy to find.
function SosButton() {
  const theme = useTheme();
  const pulse = useSharedValue(0);
  useEffect(() => {
    pulse.set(withRepeat(withSequence(withTiming(1, { duration: 1300 }), withTiming(0, { duration: 1300 })), -1));
  }, [pulse]);
  const ring = useAnimatedStyle(() => ({ opacity: 0.35 - 0.33 * pulse.value, transform: [{ scale: 1 + 0.28 * pulse.value }] }));
  return (
    <View style={styles.sosSlot} pointerEvents="box-none">
      <Animated.View style={[styles.sosRing, { backgroundColor: sosRed }, ring]} pointerEvents="none" />
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Open SOS"
        onPress={() => {
          Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Heavy);
          router.push('/sos');
        }}
        style={({ pressed }) => [styles.sos, { backgroundColor: sosRed, borderColor: theme.base }, pressed && { transform: [{ scale: 0.94 }] }]}>
        <Siren size={21} color="#fff" strokeWidth={2.3} />
        <T variant="eyebrow" color="#fff" style={{ fontSize: 9, letterSpacing: 1.4, lineHeight: 11, marginTop: 1 }}>
          SOS
        </T>
      </Pressable>
    </View>
  );
}

export function TabBar({ state, emitter, navigateToTab }: TabBarProps) {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
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
  const bottom = Math.max(insets.bottom, 10) + 4;
  return (
    <>
    {/* Content fades out under the floating dock instead of showing through the gap below it. */}
    <LinearGradient colors={[theme.base + '00', theme.base + 'e6', theme.base]} locations={[0, 0.45, 1]} style={[styles.fade, { height: bottom + BAR + 28 }]} pointerEvents="none" />
    <View style={[styles.wrap, { bottom }]} pointerEvents="box-none">
      <View style={[styles.bar, { backgroundColor: theme.dock, borderColor: theme.mode === 'dark' ? theme.line : theme.dock }]}>
        <Indicator box={boxes[routes[state.index]?.key]} />
        {routes.slice(0, half).map(render)}
        <View style={{ width: SOS + 16 }} />
        {routes.slice(half).map(render)}
      </View>
      <SosButton />
    </View>
    </>
  );
}

const styles = StyleSheet.create({
  wrap: { position: 'absolute', left: 16, right: 16, alignItems: 'center' },
  fade: { position: 'absolute', left: 0, right: 0, bottom: 0 },
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    height: BAR,
    width: '100%',
    borderRadius: BAR / 2,
    borderWidth: 1,
    paddingHorizontal: 6,
    shadowColor: '#000',
    shadowOpacity: 0.18,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 8 },
    elevation: 10,
  },
  tab: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 3, height: '100%' },
  indicator: { position: 'absolute', left: 0, top: 7, height: BAR - 16, borderRadius: (BAR - 16) / 2 },
  sosSlot: { position: 'absolute', top: -SOS / 2 + 4, width: SOS + 12, height: SOS + 12, alignItems: 'center', justifyContent: 'center' },
  sosRing: { position: 'absolute', width: SOS + 8, height: SOS + 8, borderRadius: (SOS + 8) / 2 },
  sos: { width: SOS + 12, height: SOS + 12, borderRadius: (SOS + 12) / 2, borderWidth: 6, alignItems: 'center', justifyContent: 'center', elevation: 12 },
});
