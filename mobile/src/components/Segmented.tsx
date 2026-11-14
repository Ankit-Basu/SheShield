import * as Haptics from 'expo-haptics';
import { useEffect, useState } from 'react';
import { LayoutChangeEvent, Pressable, StyleSheet, View } from 'react-native';
import Animated, { Easing, useAnimatedStyle, useSharedValue, withTiming } from 'react-native-reanimated';

import { useTheme } from '@/lib/theme';

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
    x.set(withTiming(index * seg, { duration: 260, easing: Easing.out(Easing.cubic) }));
  }, [index, seg, x]);
  const thumb = useAnimatedStyle(() => ({ transform: [{ translateX: x.value }] }));

  return (
    <View style={[styles.track, { backgroundColor: theme.surfaceAlt, borderColor: theme.line }]} onLayout={(e: LayoutChangeEvent) => setWidth(e.nativeEvent.layout.width)} accessibilityRole="tablist">
      {seg ? (
        <Animated.View style={[styles.thumb, { width: seg, backgroundColor: theme.selected }, thumb]} />
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
            <T variant="label" color={on ? theme.selectedForeground : theme.muted} style={{ fontSize: 13.5 }}>
              {o.label}
            </T>
            {o.count ? (
              <View style={[styles.count, { backgroundColor: on ? theme.primary : theme.line }]}>
                <T variant="small" color={on ? theme.primaryForeground : theme.foreground} style={{ fontSize: 10.5, lineHeight: 13 }}>
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
  track: { flexDirection: 'row', height: 46, padding: 4, borderRadius: 23, borderWidth: 1 },
  thumb: { position: 'absolute', top: 4, left: 4, bottom: 4, borderRadius: 19 },
  option: { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 6 },
  count: { minWidth: 18, height: 18, paddingHorizontal: 5, borderRadius: 9, alignItems: 'center', justifyContent: 'center' },
});
