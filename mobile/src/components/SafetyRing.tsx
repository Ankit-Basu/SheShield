import * as Haptics from 'expo-haptics';
import * as Location from 'expo-location';
import { router, useFocusEffect } from 'expo-router';
import { Check, ChevronRight } from 'lucide-react-native';
import { useCallback, useEffect, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Animated, { Easing, useAnimatedProps, useSharedValue, withTiming } from 'react-native-reanimated';
import Svg, { Circle, Defs, LinearGradient, Stop } from 'react-native-svg';

import type { Me } from '@/lib/queries';
import { status, useTheme } from '@/lib/theme';

import { Glass } from './Glass';
import { T } from './Text';

const AnimatedCircle = Animated.createAnimatedComponent(Circle);
const SIZE = 112;
const STROKE = 10;
const R = (SIZE - STROKE) / 2;
const C = 2 * Math.PI * R;

export type Readiness = ReturnType<typeof useReadiness>;

// The three things that make SOS work for you. Each unfinished one can be fixed from here.
export function useReadiness(user: Me['user'] | undefined) {
  const [location, setLocation] = useState<boolean | null>(null);

  // Re-check when Home comes back into view, e.g. after changing it in settings.
  useFocusEffect(
    useCallback(() => {
      Location.getForegroundPermissionsAsync()
        .then((p) => setLocation(p.granted))
        .catch(() => setLocation(false));
    }, []),
  );

  const steps = [
    {
      label: 'Location access',
      done: location === true,
      fix: async () => {
        const p = await Location.requestForegroundPermissionsAsync();
        setLocation(p.granted);
        if (p.granted) Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
      },
    },
    { label: 'Emergency contact', done: !!user?.emergency_contact_phone, fix: () => router.navigate('/profile') },
    { label: 'Profile photo', done: !!user?.photo, fix: () => router.navigate('/profile') },
  ];
  const done = steps.filter((s) => s.done).length;
  return { steps, done, ready: done === steps.length, known: location !== null && !!user };
}

export function SafetyRing({ readiness }: { readiness: Readiness }) {
  const theme = useTheme();
  const { steps, done, ready } = readiness;

  const progress = useSharedValue(0);
  useEffect(() => {
    progress.set(withTiming(done / steps.length, { duration: 900, easing: Easing.out(Easing.cubic) }));
  }, [done, steps.length, progress]);
  const ringProps = useAnimatedProps(() => ({ strokeDashoffset: C * (1 - progress.value) }));

  return (
    <Glass strong radius={28} style={styles.card}>
      <View style={styles.ring}>
        <Svg width={SIZE} height={SIZE}>
          <Defs>
            <LinearGradient id="safetyRing" x1="0" y1="0" x2="1" y2="1">
              <Stop offset="0" stopColor={ready ? status.good : theme.accent} />
              <Stop offset="1" stopColor={ready ? '#2fb36f' : theme.primary} />
            </LinearGradient>
          </Defs>
          <Circle cx={SIZE / 2} cy={SIZE / 2} r={R} stroke="rgba(255,255,255,0.1)" strokeWidth={STROKE} fill="none" />
          <AnimatedCircle
            cx={SIZE / 2}
            cy={SIZE / 2}
            r={R}
            stroke="url(#safetyRing)"
            strokeWidth={STROKE}
            strokeLinecap="round"
            fill="none"
            strokeDasharray={`${C} ${C}`}
            animatedProps={ringProps}
            transform={`rotate(-90 ${SIZE / 2} ${SIZE / 2})`}
          />
        </Svg>
        <View style={styles.ringCenter} pointerEvents="none">
          <T variant="title" style={{ fontSize: 30, lineHeight: 32 }}>
            {done}
            <T variant="title" muted style={{ fontSize: 18 }}>
              /{steps.length}
            </T>
          </T>
          <T variant="small" muted style={{ fontSize: 11 }}>
            {ready ? 'ready' : 'set up'}
          </T>
        </View>
      </View>

      <View style={{ flex: 1, gap: 4 }}>
        <T variant="label" style={{ fontSize: 15.5 }}>
          {ready ? "You're SOS-ready" : 'Get SOS-ready'}
        </T>
        {steps.map((s) => (
          <Pressable
            key={s.label}
            onPress={s.done ? undefined : s.fix}
            disabled={s.done}
            style={styles.step}
            accessibilityRole={s.done ? undefined : 'button'}
            accessibilityLabel={s.done ? `${s.label}: done` : `${s.label}: set up`}>
            <View style={[styles.tick, s.done ? { backgroundColor: status.good } : { borderColor: 'rgba(255,255,255,0.35)', borderWidth: 1.5 }]}>
              {s.done ? <Check size={11} color="#0b2a17" strokeWidth={3.5} /> : null}
            </View>
            <View style={{ flex: 1 }}>
              <T variant="small" color={s.done ? theme.muted : theme.foreground} style={{ fontSize: 13 }}>
                {s.label}
              </T>
            </View>
            {!s.done ? <ChevronRight size={15} color={theme.primary} /> : null}
          </Pressable>
        ))}
      </View>
    </Glass>
  );
}

const styles = StyleSheet.create({
  card: { flexDirection: 'row', alignItems: 'center', gap: 16, padding: 16, height: 196 },
  ring: { width: SIZE, height: SIZE, alignItems: 'center', justifyContent: 'center' },
  ringCenter: { position: 'absolute', alignItems: 'center' },
  step: { flexDirection: 'row', alignItems: 'center', gap: 9, paddingVertical: 4 },
  tick: { width: 18, height: 18, borderRadius: 9, alignItems: 'center', justifyContent: 'center' },
});
