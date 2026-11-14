import * as Haptics from 'expo-haptics';
import { ReactNode } from 'react';
import { Pressable, StyleProp, StyleSheet, View, ViewStyle } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';

import { useTheme } from '@/lib/theme';

type Props = {
  children?: ReactNode;
  style?: StyleProp<ViewStyle>;
  radius?: number;
  /** Raised: a touch more shadow on light themes. */
  strong?: boolean;
  /** A solid fill instead of the surface colour, e.g. a status tint or the accent. */
  fill?: string;
  onPress?: () => void;
  accessibilityLabel?: string;
  /** No shadow, for cards inside other cards or in dense grids. */
  flat?: boolean;
};

// A solid card: the theme's surface, a hairline edge and, on light themes, a soft
// shadow. Pressable cards sink slightly under the finger.
export function Card({ children, style, radius = 22, strong, fill, onPress, accessibilityLabel, flat }: Props) {
  const theme = useTheme();
  const press = useSharedValue(0);
  const pressStyle = useAnimatedStyle(() => ({ transform: [{ scale: 1 - 0.02 * press.value }] }));
  const light = theme.mode === 'light';
  const body = (
    <View
      style={[
        styles.card,
        { borderRadius: radius, backgroundColor: fill ?? theme.surface, borderColor: fill ? 'transparent' : theme.line },
        light && !flat && (strong ? styles.shadowStrong : styles.shadow),
        style,
      ]}>
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
  card: { borderWidth: 1, overflow: 'hidden' },
  shadow: { shadowColor: '#2b2118', shadowOpacity: 0.06, shadowRadius: 14, shadowOffset: { width: 0, height: 4 }, elevation: 1 },
  shadowStrong: { shadowColor: '#2b2118', shadowOpacity: 0.1, shadowRadius: 20, shadowOffset: { width: 0, height: 8 }, elevation: 3 },
});
