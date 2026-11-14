import * as Haptics from 'expo-haptics';
import { router } from 'expo-router';
import { ArrowRight, Footprints, Moon, Sun } from 'lucide-react-native';
import { useEffect, useState } from 'react';
import { LayoutChangeEvent, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import Animated, { Easing, useAnimatedStyle, useReducedMotion, useSharedValue, withRepeat, withTiming } from 'react-native-reanimated';

import type { Me } from '@/lib/queries';
import { useTheme } from '@/lib/theme';

import { Badge } from './bits';
import { Card } from './Card';
import { T } from './Text';

// The places students most often walk to at night, first.
const destinations = ['Girls Hostel', 'Boys Hostel', 'Library', 'Main Gate', 'Uni Mall', 'Block 34'];

type Props = {
  walk: Me['active_walk'] | undefined;
  walkers?: number;
  isDay: boolean;
};

// A dot that travels the route from pickup to destination while a walk is on.
function RouteLine({ color, track }: { color: string; track: string }) {
  const [w, setW] = useState(0);
  const t = useSharedValue(0);
  const reduced = useReducedMotion();
  useEffect(() => {
    if (!reduced) t.set(withRepeat(withTiming(1, { duration: 2600, easing: Easing.inOut(Easing.cubic) }), -1));
  }, [reduced, t]);
  const dot = useAnimatedStyle(() => ({
    transform: [{ translateX: (w - 12) * t.value }],
    opacity: 1 - 0.6 * t.value * t.value,
  }));
  return (
    <View style={styles.routeTrack} onLayout={(e: LayoutChangeEvent) => setW(e.nativeEvent.layout.width)}>
      <View style={[styles.routeDash, { borderColor: track }]} />
      {w ? <Animated.View style={[styles.routeDot, { backgroundColor: color }, dot]} /> : null}
    </View>
  );
}

// The top of Home: where you're going tonight. One tap on a place opens Walks with it
// as the destination. While a walk is on, it shows the walk instead.
export function WalkHero({ walk, walkers, isDay }: Props) {
  const theme = useTheme();
  const on = theme.primaryForeground;
  const TimeIcon = isDay ? Sun : Moon;

  if (walk) {
    return (
      <Card radius={28} fill={theme.primary} onPress={() => router.navigate('/walks')} accessibilityLabel="Your walk" style={styles.card}>
        <View style={styles.top}>
          <T variant="eyebrow" color={on} style={{ opacity: 0.75 }}>
            Your walk
          </T>
          <View style={[styles.badgeWrap, { backgroundColor: theme.surface }]}>
            <Badge value={walk.status} live />
          </View>
        </View>
        <View style={styles.routeRow}>
          <T variant="heading" color={on} numberOfLines={1} style={{ flexShrink: 1, fontSize: 18 }}>
            {walk.pickup_location}
          </T>
          <RouteLine color={on} track={on + '55'} />
          <T variant="heading" color={on} numberOfLines={1} style={{ flexShrink: 1, fontSize: 18 }}>
            {walk.destination}
          </T>
        </View>
        <T variant="small" color={on} style={{ opacity: 0.85 }}>
          {walk.walker_name ? `${walk.walker_name} is walking with you` : 'Finding a verified walker near you…'}
        </T>
        <Footprints size={150} color={on} strokeWidth={1.2} style={styles.art} />
      </Card>
    );
  }

  return (
    <Card radius={28} fill={theme.primary} style={styles.card}>
      <Footprints size={150} color={on} strokeWidth={1.2} style={styles.art} />
      <View style={styles.top}>
        <T variant="eyebrow" color={on} style={{ opacity: 0.75 }}>
          Walk with someone
        </T>
        <View style={[styles.pill, { backgroundColor: on + '22' }]}>
          <TimeIcon size={12} color={on} />
          <T variant="small" color={on} style={{ fontSize: 11.5 }}>
            {walkers ? `${walkers} walker${walkers === 1 ? '' : 's'} on duty` : isDay ? 'Daytime' : 'Late night'}
          </T>
        </View>
      </View>
      <T variant="title" color={on} style={{ fontSize: 27, lineHeight: 31 }}>
        Where are you{'\n'}headed?
      </T>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginHorizontal: -18 }} contentContainerStyle={styles.chips}>
        {destinations.map((d) => (
          <Pressable
            key={d}
            onPress={() => {
              Haptics.selectionAsync();
              router.navigate({ pathname: '/walks', params: { to: d } });
            }}
            style={({ pressed }) => [
              styles.chip,
              {
                backgroundColor: pressed ? on + '38' : on + '1f',
                borderColor: on + '33',
              },
            ]}
            accessibilityRole="button"
            accessibilityLabel={`Walk to ${d}`}>
            <T variant="label" color={on} style={{ fontSize: 13.5 }}>
              {d}
            </T>
          </Pressable>
        ))}
      </ScrollView>
      <Pressable
        onPress={() => {
          Haptics.selectionAsync();
          router.navigate('/walks');
        }}
        style={({ pressed }) => [styles.cta, { backgroundColor: on, opacity: pressed ? 0.85 : 1 }]}
        accessibilityRole="button"
        accessibilityLabel="Plan a walk">
        <T variant="label" color={theme.primary}>
          Plan a walk
        </T>
        <ArrowRight size={16} color={theme.primary} />
      </Pressable>
    </Card>
  );
}

const styles = StyleSheet.create({
  card: { padding: 18, gap: 14, overflow: 'hidden' },
  top: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  pill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 10,
    height: 26,
    borderRadius: 13,
  },
  badgeWrap: { borderRadius: 9 },
  chips: { paddingHorizontal: 18, gap: 8 },
  chip: {
    height: 38,
    paddingHorizontal: 14,
    borderRadius: 19,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    alignSelf: 'flex-start',
    paddingHorizontal: 16,
    height: 40,
    borderRadius: 20,
  },
  art: {
    position: 'absolute',
    right: -26,
    bottom: -30,
    opacity: 0.1,
    transform: [{ rotate: '-18deg' }],
  },
  routeRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  routeTrack: { flex: 1, minWidth: 40, height: 12, justifyContent: 'center' },
  routeDash: { borderTopWidth: 2, borderStyle: 'dashed' },
  routeDot: {
    position: 'absolute',
    left: 0,
    width: 12,
    height: 12,
    borderRadius: 6,
  },
});
