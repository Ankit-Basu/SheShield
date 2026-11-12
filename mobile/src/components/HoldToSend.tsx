import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import { Siren } from 'lucide-react-native';
import { useEffect, useRef } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Animated, {
  cancelAnimation,
  Easing,
  runOnJS,
  useAnimatedProps,
  useAnimatedStyle,
  useSharedValue,
  withRepeat,
  withSequence,
  withTiming,
} from 'react-native-reanimated';
import Svg, { Circle } from 'react-native-svg';

import { T } from './Text';

const AnimatedCircle = Animated.createAnimatedComponent(Circle);
const SIZE = 230;
const STROKE = 10;
const R = (SIZE - STROKE) / 2;
const C = 2 * Math.PI * R;
const HOLD_MS = 1600;

// Press and hold to send: the ring fills over 1.6 s with rising haptic ticks,
// so an alert is never sent by a pocket tap, but is quick when you mean it.
export function HoldToSend({ onComplete, disabled, label }: { onComplete: () => void; disabled?: boolean; label: string }) {
  const progress = useSharedValue(0);
  const breathe = useSharedValue(0);
  const ticks = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    breathe.set(withRepeat(withSequence(withTiming(1, { duration: 1500 }), withTiming(0, { duration: 1500 })), -1));
    return () => {
      if (ticks.current) clearInterval(ticks.current);
    };
  }, [breathe]);

  const ringProps = useAnimatedProps(() => ({ strokeDashoffset: C * (1 - progress.value) }));
  const glow = useAnimatedStyle(() => ({ opacity: 0.35 + 0.25 * breathe.value + 0.4 * progress.value, transform: [{ scale: 1 + 0.06 * breathe.value + 0.08 * progress.value }] }));
  const core = useAnimatedStyle(() => ({ transform: [{ scale: 1 - 0.05 * progress.value }] }));

  const stopTicks = () => {
    if (ticks.current) clearInterval(ticks.current);
    ticks.current = null;
  };
  const finish = () => {
    stopTicks();
    Haptics.notificationAsync(Haptics.NotificationFeedbackType.Warning);
    onComplete();
  };

  const start = () => {
    if (disabled) return;
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
    let n = 0;
    ticks.current = setInterval(() => {
      n++;
      Haptics.impactAsync(n < 4 ? Haptics.ImpactFeedbackStyle.Light : n < 7 ? Haptics.ImpactFeedbackStyle.Medium : Haptics.ImpactFeedbackStyle.Heavy);
    }, HOLD_MS / 9);
    progress.set(
      withTiming(1, { duration: HOLD_MS, easing: Easing.linear }, (done) => {
        if (done) runOnJS(finish)();
      }),
    );
  };
  const cancel = () => {
    stopTicks();
    cancelAnimation(progress);
    progress.set(withTiming(0, { duration: 260 }));
  };

  return (
    <View style={styles.wrap}>
      <Animated.View style={[styles.glow, glow]} />
      <Svg width={SIZE} height={SIZE} style={StyleSheet.absoluteFill}>
        <Circle cx={SIZE / 2} cy={SIZE / 2} r={R} stroke="rgba(255,255,255,0.14)" strokeWidth={STROKE} fill="none" />
        <AnimatedCircle
          cx={SIZE / 2}
          cy={SIZE / 2}
          r={R}
          stroke="#fff"
          strokeWidth={STROKE}
          strokeLinecap="round"
          fill="none"
          strokeDasharray={`${C} ${C}`}
          animatedProps={ringProps}
          transform={`rotate(-90 ${SIZE / 2} ${SIZE / 2})`}
        />
      </Svg>
      <Pressable
        onPressIn={start}
        onPressOut={cancel}
        disabled={disabled}
        accessibilityRole="button"
        accessibilityLabel={label}
        accessibilityHint="Press and hold for about two seconds"
        style={styles.press}>
        <Animated.View style={[styles.core, core]}>
          <LinearGradient colors={['#ff5a6a', '#c1121f']} start={{ x: 0.2, y: 0 }} end={{ x: 0.8, y: 1 }} style={styles.coreFill}>
            <Siren size={46} color="#fff" strokeWidth={2} />
            <T variant="title" color="#fff" style={{ fontSize: 30, marginTop: 6 }}>
              SOS
            </T>
            <T variant="small" color="rgba(255,255,255,0.85)">
              {label}
            </T>
          </LinearGradient>
        </Animated.View>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { width: SIZE, height: SIZE, alignItems: 'center', justifyContent: 'center' },
  glow: { position: 'absolute', width: SIZE + 60, height: SIZE + 60, borderRadius: (SIZE + 60) / 2, backgroundColor: '#ff2d45' },
  press: { width: SIZE - 34, height: SIZE - 34, borderRadius: (SIZE - 34) / 2 },
  core: { flex: 1, borderRadius: (SIZE - 34) / 2, overflow: 'hidden', borderWidth: 3, borderColor: 'rgba(255,255,255,0.25)' },
  coreFill: { flex: 1, alignItems: 'center', justifyContent: 'center' },
});
