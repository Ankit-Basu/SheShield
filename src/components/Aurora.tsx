import { useEffect, useId } from 'react';
import { StyleSheet, useWindowDimensions, View } from 'react-native';
import Animated, { Easing, useAnimatedStyle, useReducedMotion, useSharedValue, withRepeat, withTiming } from 'react-native-reanimated';
import Svg, { Defs, RadialGradient, Rect, Stop } from 'react-native-svg';

import { useTheme } from '@/lib/theme';

// The website's aurora, rebuilt for phones: three soft radial glows that drift
// slowly on the UI thread (Reanimated), with no per-frame JavaScript.
function Glow({ color, size, x, y, dx, dy, duration, opacity }: { color: string; size: number; x: number; y: number; dx: number; dy: number; duration: number; opacity: number }) {
  const t = useSharedValue(0);
  const reduced = useReducedMotion();
  useEffect(() => {
    if (!reduced) t.set(withRepeat(withTiming(1, { duration, easing: Easing.inOut(Easing.sin) }), -1, true));
  }, [duration, reduced, t]);
  const style = useAnimatedStyle(() => ({
    transform: [{ translateX: x + dx * t.value }, { translateY: y + dy * t.value }, { scale: 1 + 0.12 * t.value }],
  }));
  // Unique per instance: on the web, gradient ids are document-wide.
  const id = `aurora${useId().replace(/[^a-zA-Z0-9]/g, '')}`;
  return (
    <Animated.View style={[{ position: 'absolute', width: size, height: size }, style]} pointerEvents="none">
      <Svg width={size} height={size}>
        <Defs>
          <RadialGradient id={id} cx="50%" cy="50%" r="50%">
            <Stop offset="0" stopColor={color} stopOpacity={opacity} />
            <Stop offset="0.55" stopColor={color} stopOpacity={opacity * 0.35} />
            <Stop offset="1" stopColor={color} stopOpacity={0} />
          </RadialGradient>
        </Defs>
        <Rect width={size} height={size} fill={`url(#${id})`} />
      </Svg>
    </Animated.View>
  );
}

export function Aurora({ intensity = 1 }: { intensity?: number }) {
  const theme = useTheme();
  const { width, height } = useWindowDimensions();
  const [a1, a2, a3] = theme.aurora;
  const s = Math.max(width, height);
  return (
    <View style={[StyleSheet.absoluteFill, { backgroundColor: theme.base, overflow: 'hidden' }]} pointerEvents="none">
      <Glow color={a2} size={s * 1.1} x={-s * 0.45} y={-s * 0.5} dx={s * 0.12} dy={s * 0.08} duration={14000} opacity={0.75 * intensity} />
      <Glow color={a1} size={s * 1.0} x={width - s * 0.45} y={-s * 0.35} dx={-s * 0.1} dy={s * 0.12} duration={17000} opacity={0.9 * intensity} />
      <Glow color={a3} size={s * 0.9} x={width * 0.1} y={height - s * 0.35} dx={s * 0.1} dy={-s * 0.08} duration={19000} opacity={0.38 * intensity} />
    </View>
  );
}
