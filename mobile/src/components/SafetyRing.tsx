import * as Haptics from 'expo-haptics';
import * as Location from 'expo-location';
import { router, useFocusEffect } from 'expo-router';
import { Check, ChevronRight } from 'lucide-react-native';
import { ReactNode, useCallback, useEffect, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Animated, { Easing, useAnimatedProps, useSharedValue, withTiming } from 'react-native-reanimated';
import Svg, { Circle } from 'react-native-svg';

import type { Me } from '@/lib/queries';
import { status, useTheme } from '@/lib/theme';

import { Card } from './Card';
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

// In a sheet (`bare`), `onStep` closes it before a step navigates away.
export function SafetyRing({ readiness, bare, onStep }: { readiness: Readiness; bare?: boolean; onStep?: () => void }) {
  const theme = useTheme();
  const { steps, done, ready } = readiness;

  const progress = useSharedValue(0);
  useEffect(() => {
    progress.set(withTiming(done / steps.length, { duration: 900, easing: Easing.out(Easing.cubic) }));
  }, [done, steps.length, progress]);
  const ringProps = useAnimatedProps(() => ({ strokeDashoffset: C * (1 - progress.value) }));

  return (
    <Wrap bare={bare}>
      <View style={styles.ring}>
        <Svg width={SIZE} height={SIZE}>
          <Circle cx={SIZE / 2} cy={SIZE / 2} r={R} stroke={theme.surfaceAlt} strokeWidth={STROKE} fill="none" />
          <AnimatedCircle
            cx={SIZE / 2}
            cy={SIZE / 2}
            r={R}
            stroke={ready ? status.good : theme.primary}
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
        {bare ? null : (
          <T variant="label" style={{ fontSize: 15.5 }}>
            {ready ? "You're SOS-ready" : 'Get SOS-ready'}
          </T>
        )}
        {steps.map((s) => (
          <Pressable
            key={s.label}
            onPress={
              s.done
                ? undefined
                : () => {
                    onStep?.();
                    s.fix();
                  }
            }
            disabled={s.done}
            style={styles.step}
            accessibilityRole={s.done ? undefined : 'button'}
            accessibilityLabel={s.done ? `${s.label}: done` : `${s.label}: set up`}>
            <View style={[styles.tick, s.done ? { backgroundColor: status.good } : { borderColor: theme.line, borderWidth: 1.5 }]}>
              {s.done ? <Check size={11} color="#fff" strokeWidth={3.5} /> : null}
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
    </Wrap>
  );
}

const MINI = 46;
const MINI_R = (MINI - 6) / 2;
const MINI_C = 2 * Math.PI * MINI_R;

// The header's compact version: a small ring with done/total, or a green check when ready.
export function ReadinessBadge({ readiness, onPress }: { readiness: Readiness; onPress: () => void }) {
  const theme = useTheme();
  const { steps, done, ready, known } = readiness;
  const progress = useSharedValue(0);
  useEffect(() => {
    progress.set(withTiming(done / steps.length, { duration: 900, easing: Easing.out(Easing.cubic) }));
  }, [done, steps.length, progress]);
  const ringProps = useAnimatedProps(() => ({ strokeDashoffset: MINI_C * (1 - progress.value) }));
  return (
    <Pressable
      onPress={() => {
        Haptics.selectionAsync();
        onPress();
      }}
      hitSlop={8}
      accessibilityRole="button"
      accessibilityLabel={ready ? 'SOS-ready. Show checklist' : `SOS readiness ${done} of ${steps.length}. Show checklist`}
      style={[styles.badge, { backgroundColor: theme.surface, borderColor: theme.line }]}>
      <Svg width={MINI} height={MINI}>
        <Circle cx={MINI / 2} cy={MINI / 2} r={MINI_R} stroke={theme.surfaceAlt} strokeWidth={4} fill="none" />
        <AnimatedCircle
          cx={MINI / 2}
          cy={MINI / 2}
          r={MINI_R}
          stroke={ready ? status.good : theme.primary}
          strokeWidth={4}
          strokeLinecap="round"
          fill="none"
          strokeDasharray={`${MINI_C} ${MINI_C}`}
          animatedProps={ringProps}
          transform={`rotate(-90 ${MINI / 2} ${MINI / 2})`}
        />
      </Svg>
      <View style={styles.ringCenter} pointerEvents="none">
        {ready ? (
          <Check size={18} color={status.good} strokeWidth={3} />
        ) : (
          <T variant="label" style={{ fontSize: 13 }}>
            {known ? `${done}/${steps.length}` : '…'}
          </T>
        )}
      </View>
    </Pressable>
  );
}

function Wrap({ bare, children }: { bare?: boolean; children: ReactNode }) {
  if (bare) return <View style={[styles.card, styles.bare]}>{children}</View>;
  return (
    <Card strong radius={28} style={styles.card}>
      {children}
    </Card>
  );
}

const styles = StyleSheet.create({
  card: { flexDirection: 'row', alignItems: 'center', gap: 16, padding: 16, height: 196 },
  ring: { width: SIZE, height: SIZE, alignItems: 'center', justifyContent: 'center' },
  ringCenter: { position: 'absolute', alignItems: 'center' },
  step: { flexDirection: 'row', alignItems: 'center', gap: 9, paddingVertical: 4 },
  tick: { width: 18, height: 18, borderRadius: 9, alignItems: 'center', justifyContent: 'center' },
  bare: { padding: 0, height: 'auto', paddingBottom: 6 },
  badge: { width: MINI + 8, height: MINI + 8, borderRadius: (MINI + 8) / 2, borderWidth: 1, alignItems: 'center', justifyContent: 'center' },
});
