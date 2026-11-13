import { forwardRef, ReactNode, useImperativeHandle, useRef, useState } from 'react';
import { LayoutChangeEvent, Pressable, StyleSheet, View } from 'react-native';
import Animated, { interpolate, SharedValue, useAnimatedScrollHandler, useAnimatedStyle, useSharedValue } from 'react-native-reanimated';

import { useTheme } from '@/lib/theme';

const GAP = 12;

export type HeroCarouselHandle = { show: (index: number) => void };

function Dot({ i, x, step, label, onPress }: { i: number; x: SharedValue<number>; step: number; label: string; onPress: () => void }) {
  const theme = useTheme();
  const style = useAnimatedStyle(() => {
    const p = step ? x.value / step : 0;
    const d = Math.min(1, Math.abs(p - i));
    return { width: interpolate(d, [0, 1], [22, 7]), opacity: interpolate(d, [0, 1], [1, 0.35]) };
  });
  return (
    <Pressable onPress={onPress} hitSlop={8} accessibilityRole="button" accessibilityLabel={label}>
      <Animated.View style={[styles.dot, { backgroundColor: theme.foreground }, style]} />
    </Pressable>
  );
}

// Cards you swipe through sideways, with the next one peeking in and dots that stretch
// toward the current card. Slides fade and shrink a little as they leave.
export const HeroCarousel = forwardRef<HeroCarouselHandle, { slides: { key: string; label: string; node: ReactNode }[] }>(function HeroCarousel({ slides }, ref) {
  const [width, setWidth] = useState(0);
  const scroller = useRef<Animated.ScrollView>(null);
  const x = useSharedValue(0);
  const step = width + GAP;
  const widthRef = useRef(0);

  const show = (i: number) => {
    scroller.current?.scrollTo({ x: i * (widthRef.current + GAP), animated: true });
  };
  useImperativeHandle(ref, () => ({ show }));

  const onScroll = useAnimatedScrollHandler((e) => {
    x.set(e.contentOffset.x);
  });

  return (
    <View
      onLayout={(e: LayoutChangeEvent) => {
        widthRef.current = e.nativeEvent.layout.width;
        setWidth(e.nativeEvent.layout.width);
      }}
      style={{ gap: 12 }}>
      <Animated.ScrollView
        ref={scroller}
        horizontal
        showsHorizontalScrollIndicator={false}
        snapToInterval={step || undefined}
        decelerationRate="fast"
        disableIntervalMomentum
        onScroll={onScroll}
        scrollEventThrottle={16}
        style={{ marginHorizontal: -18 }}
        contentContainerStyle={{ paddingHorizontal: 18, gap: GAP }}>
        {width
          ? slides.map((s, i) => (
              <Slide key={s.key} i={i} x={x} step={step} width={width}>
                {s.node}
              </Slide>
            ))
          : null}
      </Animated.ScrollView>
      <View style={styles.dots}>
        {slides.map((s, i) => (
          <Dot key={s.key} i={i} x={x} step={step} label={`Show ${s.label}`} onPress={() => show(i)} />
        ))}
      </View>
    </View>
  );
});

function Slide({ i, x, step, width, children }: { i: number; x: SharedValue<number>; step: number; width: number; children: ReactNode }) {
  const style = useAnimatedStyle(() => {
    const d = Math.min(1, Math.abs(x.value / step - i));
    return { opacity: interpolate(d, [0, 1], [1, 0.55]), transform: [{ scale: interpolate(d, [0, 1], [1, 0.94]) }] };
  });
  return <Animated.View style={[{ width }, style]}>{children}</Animated.View>;
}

const styles = StyleSheet.create({
  dots: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 6 },
  dot: { height: 7, borderRadius: 4 },
});
