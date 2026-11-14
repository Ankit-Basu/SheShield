import { LinearGradient } from 'expo-linear-gradient';
import { ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useTheme } from '@/lib/theme';

// A screen's background: the theme's solid base colour. A strip in the same colour
// sits under the status bar, so content scrolling up never runs beneath the clock.
export function Scene({ children }: { children: ReactNode }) {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  return (
    <View style={{ flex: 1, backgroundColor: theme.base }}>
      {children}
      <View style={[styles.bar, { height: insets.top, backgroundColor: theme.base }]} pointerEvents="none" />
      <LinearGradient colors={[theme.base, theme.base + '00']} style={[styles.fade, { top: insets.top }]} pointerEvents="none" />
    </View>
  );
}

const styles = StyleSheet.create({
  bar: { position: 'absolute', top: 0, left: 0, right: 0 },
  fade: { position: 'absolute', left: 0, right: 0, height: 14 },
});
