import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import { useEffect, useState } from 'react';
import { LayoutChangeEvent, Pressable, StyleSheet, View } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';

import { glass, useTheme } from '@/lib/theme';

import { T } from './Text';

type Props<K extends string> = { options: readonly { key: K; label: string; count?: number }[]; value: K; onChange: (k: K) => void };

// A segmented switch whose highlight slides to the chosen option.
export function Segmented<K extends string>({ options, value, onChange }: Props<K>) {
  const theme = useTheme();
  const [width, setWidth] = useState(0);
  const index = Math.max(0, options.findIndex((o) => o.key === value));
  // Inside the 1 px border and 4 px padding on each side.
  const seg = width ? (width - 10) / options.length : 0;
  const x = useSharedValue(0);
  useEffect(() => {
    x.set(withSpring(index * seg, { damping: 18, stiffness: 220 }));
  }, [index, seg, x]);
  const thumb = useAnimatedStyle(() => ({ transform: [{ translateX: x.value }] }));

  return (
    <View style={styles.track} onLayout={(e: LayoutChangeEvent) => setWidth(e.nativeEvent.layout.width)} accessibilityRole="tablist">
      {seg ? (
        <Animated.View style={[styles.thumb, { width: seg }, thumb]}>
          <LinearGradient colors={[theme.primary + '40', theme.accent + '24']} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} style={[StyleSheet.absoluteFill, styles.thumbFill, { borderColor: theme.primary + '66' }]} />
        </Animated.View>
      ) : null}
      {options.map((o) => {
        const on = o.key === value;
        return (
          <Pressable
            key={o.key}
            style={styles.option}
            onPress={() => {
              if (on) return;
              Haptics.selectionAsync();
              onChange(o.key);
            }}
            accessibilityRole="tab"
            accessibilityState={{ selected: on }}
            accessibilityLabel={o.label}>
            <T variant="label" color={on ? theme.foreground : theme.muted} style={{ fontSize: 13.5 }}>
              {o.label}
            </T>
            {o.count ? (
              <View style={[styles.count, { backgroundColor: on ? theme.primary : 'rgba(255,255,255,0.12)' }]}>
                <T variant="small" color={on ? theme.primaryForeground : theme.muted} style={{ fontSize: 10.5, lineHeight: 13 }}>
                  {o.count}
                </T>
              </View>
            ) : null}
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  track: { flexDirection: 'row', height: 46, padding: 4, borderRadius: 23, backgroundColor: glass.fill, borderWidth: 1, borderColor: glass.line },
  thumb: { position: 'absolute', top: 4, left: 4, bottom: 4 },
  thumbFill: { borderRadius: 19, borderWidth: 1 },
  option: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6 },
  count: { minWidth: 18, height: 18, paddingHorizontal: 5, borderRadius: 9, alignItems: 'center', justifyContent: 'center' },
});
