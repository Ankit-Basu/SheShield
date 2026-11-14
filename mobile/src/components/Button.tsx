import * as Haptics from 'expo-haptics';
import { ReactNode } from 'react';
import { ActivityIndicator, Pressable, StyleProp, StyleSheet, View, ViewStyle } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';

import { sosRed, useTheme } from '@/lib/theme';

import { T } from './Text';

type Props = {
  label: string;
  onPress?: () => void;
  variant?: 'primary' | 'ghost' | 'danger' | 'onDark';
  icon?: ReactNode;
  loading?: boolean;
  disabled?: boolean;
  style?: StyleProp<ViewStyle>;
  size?: 'md' | 'lg';
};

export function Button({ label, onPress, variant = 'primary', icon, loading, disabled, style, size = 'md' }: Props) {
  const theme = useTheme();
  const scale = useSharedValue(1);
  const anim = useAnimatedStyle(() => ({ transform: [{ scale: scale.value }] }));
  const height = size === 'lg' ? 58 : 50;
  const off = disabled || loading;

  // onDark: for the always-dark SOS screen, whatever the theme.
  const bg = variant === 'ghost' ? theme.surfaceAlt : variant === 'onDark' ? 'rgba(255,255,255,0.12)' : variant === 'danger' ? sosRed : theme.primary;
  const fg = variant === 'ghost' ? theme.foreground : variant === 'danger' || variant === 'onDark' ? '#fff' : theme.primaryForeground;

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled: !!off, busy: !!loading }}
      disabled={off}
      onPressIn={() => scale.set(withSpring(0.96, { damping: 18, stiffness: 340 }))}
      onPressOut={() => scale.set(withSpring(1, { damping: 14, stiffness: 260 }))}
      onPress={() => {
        Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
        onPress?.();
      }}
      style={style}>
      <Animated.View style={[anim, { opacity: disabled ? 0.5 : 1 }]}>
        <View style={[styles.base, { height, borderRadius: height / 2, backgroundColor: bg }, variant === 'ghost' && { borderWidth: 1, borderColor: theme.line }, variant === 'onDark' && { borderWidth: 1, borderColor: 'rgba(255,255,255,0.22)' }]}>
          {loading ? (
            <ActivityIndicator color={fg} />
          ) : (
            <>
              {icon}
              <T variant="label" color={fg} numberOfLines={1} adjustsFontSizeToFit minimumFontScale={0.8} style={{ fontSize: size === 'lg' ? 16 : 15, flexShrink: 1 }}>
                {label}
              </T>
            </>
          )}
        </View>
      </Animated.View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, paddingHorizontal: 18, overflow: 'hidden' },
});
