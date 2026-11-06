import { BlurView } from 'expo-blur';
import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { Footprints, House, Map, Siren, UserRound } from 'lucide-react-native';
import { useEffect } from 'react';
import { Platform, Pressable, StyleSheet, View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withRepeat, withSequence, withSpring, withTiming } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { glass, useTheme } from '@/lib/theme';

import { T } from './Text';

const icons: Record<string, typeof House> = { index: House, map: Map, walks: Footprints, profile: UserRound };
const labels: Record<string, string> = { index: 'Home', map: 'Map', walks: 'Walks', profile: 'You' };

type TabBarProps = {
  state: { index: number; routes: { key: string; name: string }[] };
  navigation: { emit: (e: any) => any; navigate: (name: string) => void };
};

function Tab({ name, focused, onPress }: { name: string; focused: boolean; onPress: () => void }) {
  const theme = useTheme();
  const v = useSharedValue(focused ? 1 : 0);
  useEffect(() => {
    v.set(withSpring(focused ? 1 : 0, { damping: 16, stiffness: 220 }));
  }, [focused, v]);
  const pill = useAnimatedStyle(() => ({ opacity: v.value, transform: [{ scale: 0.7 + 0.3 * v.value }] }));
  const lift = useAnimatedStyle(() => ({ transform: [{ translateY: -2 * v.value }] }));
  const Icon = icons[name] ?? House;
  return (
    <Pressable onPress={onPress} style={styles.tab} accessibilityRole="tab" accessibilityState={{ selected: focused }} accessibilityLabel={labels[name]}>
      <Animated.View style={[styles.pill, { backgroundColor: theme.primary + '26' }, pill]} />
      <Animated.View style={[{ alignItems: 'center', gap: 3 }, lift]}>
        <Icon size={21} color={focused ? theme.primary : theme.muted} strokeWidth={focused ? 2.3 : 1.9} />
        <T variant="small" color={focused ? theme.foreground : theme.muted} style={{ fontSize: 10.5 }}>
          {labels[name]}
        </T>
      </Animated.View>
    </Pressable>
  );
}

// The SOS button is always in the middle of the bar, breathing gently so it is easy to find.
function SosButton() {
  const pulse = useSharedValue(0);
  useEffect(() => {
    pulse.set(withRepeat(withSequence(withTiming(1, { duration: 1300 }), withTiming(0, { duration: 1300 })), -1));
  }, [pulse]);
  const ring = useAnimatedStyle(() => ({ opacity: 0.55 - 0.45 * pulse.value, transform: [{ scale: 1 + 0.28 * pulse.value }] }));
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel="Open SOS"
      onPress={() => {
        Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Heavy);
        router.push('/sos');
      }}
      style={styles.sosWrap}>
      <Animated.View style={[styles.sosRing, ring]} />
      <LinearGradient colors={['#ff5a6a', '#d90429']} start={{ x: 0.2, y: 0 }} end={{ x: 0.8, y: 1 }} style={styles.sos}>
        <Siren size={26} color="#fff" strokeWidth={2.2} />
      </LinearGradient>
    </Pressable>
  );
}

export function GlassTabBar({ state, navigation }: TabBarProps) {
  const insets = useSafeAreaInsets();
  const routes = state.routes;
  const half = Math.ceil(routes.length / 2);
  const go = (route: { key: string; name: string }, index: number) => {
    const event = navigation.emit({ type: 'tabPress', target: route.key, canPreventDefault: true });
    if (state.index !== index && !event.defaultPrevented) {
      Haptics.selectionAsync();
      navigation.navigate(route.name);
    }
  };
  const render = (r: { key: string; name: string }) => {
    const i = routes.indexOf(r);
    return <Tab key={r.key} name={r.name} focused={state.index === i} onPress={() => go(r, i)} />;
  };
  return (
    <View style={[styles.wrap, { bottom: Math.max(insets.bottom, 10) + 6 }]} pointerEvents="box-none">
      <View style={styles.bar}>
        {Platform.OS === 'ios' ? (
          <BlurView intensity={45} tint="dark" style={StyleSheet.absoluteFill} />
        ) : (
          <View style={[StyleSheet.absoluteFill, { backgroundColor: 'rgba(24,14,22,0.86)' }]} />
        )}
        <LinearGradient colors={['rgba(255,255,255,0.12)', 'rgba(255,255,255,0.02)']} style={StyleSheet.absoluteFill} pointerEvents="none" />
        {routes.slice(0, half).map(render)}
        <View style={{ width: 74 }} />
        {routes.slice(half).map(render)}
      </View>
      <SosButton />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { position: 'absolute', left: 14, right: 14, alignItems: 'center' },
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 68,
    width: '100%',
    borderRadius: 26,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: glass.line,
    borderTopColor: glass.lineTop,
    paddingHorizontal: 6,
  },
  tab: { flex: 1, alignItems: 'center', justifyContent: 'center', height: '100%' },
  pill: { position: 'absolute', width: 58, height: 50, borderRadius: 18 },
  sosWrap: { position: 'absolute', top: -20, width: 72, height: 72, alignItems: 'center', justifyContent: 'center' },
  sosRing: { position: 'absolute', width: 72, height: 72, borderRadius: 36, backgroundColor: '#ff3b4f' },
  sos: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 3,
    borderColor: 'rgba(255,255,255,0.22)',
    shadowColor: '#ff2d45',
    shadowOpacity: 0.6,
    shadowRadius: 16,
    shadowOffset: { width: 0, height: 6 },
    elevation: 12,
  },
});
