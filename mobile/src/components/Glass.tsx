import { BlurView } from 'expo-blur';
import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import { ReactNode, useId } from 'react';
import { Platform, Pressable, StyleProp, StyleSheet, View, ViewStyle } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import Svg, { Defs, RadialGradient, Rect, Stop } from 'react-native-svg';

import { useBackdrop } from '@/lib/blur';
import { glass } from '@/lib/theme';

type Props = {
  children?: ReactNode;
  style?: StyleProp<ViewStyle>;
  radius?: number;
  strong?: boolean;
  tint?: readonly [string, string, ...string[]];
  onPress?: () => void;
  accessibilityLabel?: string;
  /** Skip the corner light on small or very numerous cards. */
  flat?: boolean;
  /** Turn off the frosted blur, e.g. for overlays on the map. */
  noBlur?: boolean;
};

// Light on the glass: a faint glow from the top-left, a fine rim along the top edge,
// and a little shade at the bottom. Kept subtle on purpose.
function Light({ radius, flat }: { radius: number; flat?: boolean }) {
  const id = `glow${useId().replace(/[^a-zA-Z0-9]/g, '')}`;
  return (
    <View style={[StyleSheet.absoluteFill, { borderRadius: radius, overflow: 'hidden' }]} pointerEvents="none">
      {!flat && (
        <Svg width={240} height={180} style={styles.glow}>
          <Defs>
            <RadialGradient id={id} cx="30%" cy="25%" r="65%">
              <Stop offset="0" stopColor="#ffffff" stopOpacity={0.1} />
              <Stop offset="1" stopColor="#ffffff" stopOpacity={0} />
            </RadialGradient>
          </Defs>
          <Rect width={240} height={180} fill={`url(#${id})`} />
        </Svg>
      )}
      <LinearGradient colors={['rgba(0,0,0,0)', 'rgba(0,0,0,0.1)']} start={{ x: 0.5, y: 0.5 }} end={{ x: 0.5, y: 1 }} style={StyleSheet.absoluteFill} />
      <LinearGradient
        colors={['rgba(255,255,255,0)', 'rgba(255,255,255,0.5)', 'rgba(255,255,255,0.12)', 'rgba(255,255,255,0)']}
        locations={[0, 0.3, 0.7, 1]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 0 }}
        style={[styles.rim, { left: radius * 0.6, right: radius * 0.6 }]}
      />
    </View>
  );
}

// Frosted glass: a real blur of the screen's background (Android 12+ through the
// screen's backdrop, iOS natively), a light tint, and soft light on top.
// Pressable cards sink slightly under the finger.
export function Glass({ children, style, radius = 22, strong, tint, onPress, accessibilityLabel, flat, noBlur }: Props) {
  const backdrop = useBackdrop();
  const press = useSharedValue(0);
  const pressStyle = useAnimatedStyle(() => ({ transform: [{ scale: 1 - 0.025 * press.value }] }));
  const frosted = !noBlur && (Platform.OS === 'ios' || (Platform.OS === 'android' && !!backdrop));
  const body = (
    <View
      style={[
        styles.card,
        { borderRadius: radius, backgroundColor: frosted ? (strong ? glass.fill : glass.frost) : strong ? glass.fillStrong : glass.fill },
        style,
      ]}>
      {frosted && (
        <BlurView
          blurTarget={Platform.OS === 'android' ? backdrop ?? undefined : undefined}
          blurMethod="dimezisBlurViewSdk31Plus"
          intensity={Platform.OS === 'android' ? 70 : 40}
          tint="dark"
          style={StyleSheet.absoluteFill}
        />
      )}
      <LinearGradient colors={tint ?? glass.sheen} start={{ x: 0.1, y: 0 }} end={{ x: 0.9, y: 1 }} style={StyleSheet.absoluteFill} pointerEvents="none" />
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
    borderWidth: StyleSheet.hairlineWidth * 2,
    borderColor: glass.line,
    borderTopColor: glass.lineTop,
    borderBottomColor: glass.lineSoft,
  },
  glow: { position: 'absolute', top: -60, left: -50 },
  rim: { position: 'absolute', top: 0, height: 1 },
});
