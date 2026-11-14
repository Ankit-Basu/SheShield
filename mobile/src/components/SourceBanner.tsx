import { useQueryClient } from '@tanstack/react-query';
import * as Haptics from 'expo-haptics';
import { CloudOff, RefreshCw } from 'lucide-react-native';
import { useEffect } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown, FadeOutUp, useAnimatedStyle, useSharedValue, withRepeat, withTiming } from 'react-native-reanimated';

import { useDataSource } from '@/lib/fallback';
import { status, useTheme } from '@/lib/theme';

import { Card } from './Card';
import { T } from './Text';

const queryKeyFor: Record<string, string> = { me: 'me', reports: 'reports', walks: 'walks', 'safe-spaces': 'safe-spaces' };

// Says plainly when a screen is showing saved or sample data because the server is
// asleep, keeps a live "reconnecting" dot, and offers an immediate retry.
export function SourceBanner({ keys }: { keys: string[] }) {
  const theme = useTheme();
  const qc = useQueryClient();
  const sources = useDataSource((s) => s.sources);
  const states = keys.map((k) => sources[k] ?? 'live');
  const sample = states.includes('sample');
  const saved = states.includes('saved');
  const pulse = useSharedValue(0);
  useEffect(() => {
    pulse.set(withRepeat(withTiming(1, { duration: 900 }), -1, true));
  }, [pulse]);
  const dot = useAnimatedStyle(() => ({ opacity: 0.35 + 0.65 * pulse.value }));

  if (!sample && !saved) return null;
  return (
    <Animated.View entering={FadeInDown.springify().damping(18)} exiting={FadeOutUp}>
      <Card radius={18} flat style={styles.banner} fill={status.warn + '1f'}>
        <CloudOff size={18} color={status.warn} />
        <View style={{ flex: 1, gap: 1 }}>
          <T variant="label" style={{ fontSize: 13.5 }}>
            {sample ? 'Showing sample data' : 'Showing your saved data'}
          </T>
          <View style={styles.row}>
            <Animated.View style={[styles.dot, { backgroundColor: status.warn }, dot]} />
            <T variant="small" muted style={{ fontSize: 12 }}>
              SheShield is waking up · reconnecting
            </T>
          </View>
        </View>
        <Pressable
          onPress={() => {
            Haptics.selectionAsync();
            keys.forEach((k) => qc.invalidateQueries({ queryKey: [queryKeyFor[k] ?? k] }));
          }}
          hitSlop={10}
          style={[styles.retry, { borderColor: theme.primary + '66' }]}
          accessibilityRole="button"
          accessibilityLabel="Retry now">
          <RefreshCw size={14} color={theme.primary} />
          <T variant="small" color={theme.primary}>
            Retry
          </T>
        </Pressable>
      </Card>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  banner: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 12, paddingHorizontal: 14 },
  row: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  dot: { width: 6, height: 6, borderRadius: 3 },
  retry: { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 10, height: 30, borderRadius: 10, borderWidth: 1 },
});
