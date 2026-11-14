import { useEffect } from 'react';
import { DimensionValue, StyleProp, ViewStyle } from 'react-native';
import { useTheme } from '@/lib/theme';
import Animated, { useAnimatedStyle, useReducedMotion, useSharedValue, withRepeat, withSequence, withTiming } from 'react-native-reanimated';

// A soft pulsing placeholder shown while data loads, instead of zeros or "?".
export function Skeleton({ width, height, radius = 10, style }: { width: DimensionValue; height: number; radius?: number; style?: StyleProp<ViewStyle> }) {
  const theme = useTheme();
  const pulse = useSharedValue(0);
  const reduced = useReducedMotion();
  useEffect(() => {
    if (!reduced) pulse.set(withRepeat(withSequence(withTiming(1, { duration: 750 }), withTiming(0, { duration: 750 })), -1));
  }, [pulse, reduced]);
  const anim = useAnimatedStyle(() => ({ opacity: 0.45 + 0.35 * pulse.value }));
  return <Animated.View style={[{ width, height, borderRadius: radius, backgroundColor: theme.surfaceAlt }, anim, style]} />;
}
