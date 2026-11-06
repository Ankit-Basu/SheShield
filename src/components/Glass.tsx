import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import { ReactNode } from 'react';
import { Pressable, StyleProp, StyleSheet, View, ViewStyle } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';

import { glass } from '@/lib/theme';

type Props = {
  children?: ReactNode;
  style?: StyleProp<ViewStyle>;
  radius?: number;
  strong?: boolean;
  tint?: readonly [string, string, ...string[]];
  onPress?: () => void;
  accessibilityLabel?: string;
};

// Glass card: translucent fill, a sheen across the top, and a brighter top edge.
// Pressable cards sink slightly under the finger and give a light haptic tick.
export function Glass({ children, style, radius = 24, strong, tint, onPress, accessibilityLabel }: Props) {
  const scale = useSharedValue(1);
  const pressStyle = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));
  const body = (
    <View
      style={[
        styles.card,
        { borderRadius: radius, backgroundColor: strong ? glass.fillStrong : glass.fill },
        style,
      ]}>
      <LinearGradient
        colors={tint ?? glass.sheen}
        start={{ x: 0.1, y: 0 }}
        end={{ x: 0.9, y: 1 }}
        style={[StyleSheet.absoluteFill, { borderRadius: radius }]}
        pointerEvents="none"
      />
      {children}
    </View>
  );
  if (!onPress) return body;
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel}
      onPressIn={() => scale.set(withSpring(0.97, { damping: 18, stiffness: 320 }))}
      onPressOut={() => scale.set(withSpring(1, { damping: 14, stiffness: 260 }))}
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
    borderBottomColor: glass.lineSoft,
  },
});
