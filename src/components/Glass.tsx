import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import { ReactNode, useId } from 'react';
import { Pressable, StyleProp, StyleSheet, View, ViewStyle } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import Svg, { Defs, RadialGradient, Rect, Stop } from 'react-native-svg';

import { glass } from '@/lib/theme';

type Props = {
  children?: ReactNode;
  style?: StyleProp<ViewStyle>;
  radius?: number;
  strong?: boolean;
  tint?: readonly [string, string, ...string[]];
  onPress?: () => void;
  accessibilityLabel?: string;
  /** Skip the corner glow on small or very numerous cards. */
  flat?: boolean;
};

// The light sources of the glass: a specular glow in the top-left corner,
// a bright rim along the top edge, and a slight shade towards the bottom for depth.
function Light({ radius, flat }: { radius: number; flat?: boolean }) {
  const id = `glow${useId().replace(/[^a-zA-Z0-9]/g, '')}`;
  return (
    <View style={[StyleSheet.absoluteFill, { borderRadius: radius, overflow: 'hidden' }]} pointerEvents="none">
      {!flat && (
        <Svg width={220} height={170} style={styles.glow}>
          <Defs>
            <RadialGradient id={id} cx="35%" cy="30%" r="60%">
              <Stop offset="0" stopColor="#ffffff" stopOpacity={0.16} />
              <Stop offset="1" stopColor="#ffffff" stopOpacity={0} />
            </RadialGradient>
          </Defs>
          <Rect width={220} height={170} fill={`url(#${id})`} />
        </Svg>
      )}
      <LinearGradient colors={['rgba(0,0,0,0)', 'rgba(0,0,0,0.14)']} start={{ x: 0.5, y: 0.45 }} end={{ x: 0.5, y: 1 }} style={StyleSheet.absoluteFill} />
      <LinearGradient
        colors={['rgba(255,255,255,0)', 'rgba(255,255,255,0.75)', 'rgba(255,255,255,0.18)', 'rgba(255,255,255,0)']}
        locations={[0, 0.3, 0.7, 1]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={[styles.rim, { left: radius * 0.6, right: radius * 0.6 }]}
      />
    </View>
  );
}

// Glass card: translucent fill, a diagonal sheen, light from the top-left and a
// brighter top edge. Pressable cards sink and tilt slightly under the finger.
export function Glass({ children, style, radius = 24, strong, tint, onPress, accessibilityLabel, flat }: Props) {
  const press = useSharedValue(0);
  const pressStyle = useAnimatedStyle(() => ({
    transform: [{ perspective: 800 }, { scale: 1 - 0.03 * press.value }, { rotateX: `${2.5 * press.value}deg` }],
  }));
  const body = (
    <View style={[styles.card, { borderRadius: radius, backgroundColor: strong ? glass.fillStrong : glass.fill }, style]}>
      <LinearGradient colors={tint ?? glass.sheen} start={{ x: 0.1, y: 0 }} end={{ x: 0.9, y: 1 }} style={[StyleSheet.absoluteFill, { borderRadius: radius }]} pointerEvents="none" />
      <Light radius={radius} flat={flat} />
      {children}
    </View>
  );
  if (!onPress) return body;
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel}
      onPressIn={() => press.set(withSpring(1, { damping: 18, stiffness: 320 }))}
      onPressOut={() => press.set(withSpring(0, { damping: 14, stiffness: 240 }))}
      onPress={() => {
        Haptics.selectionAsync();
        onPress();
      }}>
      <Animated.View style={pressStyle}>{body}</Animated.View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: glass.line,
    borderTopColor: glass.lineTop,
    borderLeftColor: 'rgba(255,255,255,0.2)',
    borderBottomColor: glass.lineSoft,
  },
  glow: { position: 'absolute', top: -50, left: -40 },
  rim: { position: 'absolute', top: 0, height: 1 },
});
