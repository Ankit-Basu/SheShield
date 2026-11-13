import * as Haptics from 'expo-haptics';
import { Image } from 'expo-image';
import { LinearGradient } from 'expo-linear-gradient';
import { router } from 'expo-router';
import { ArrowUpRight, Footprints, ShieldCheck, Sunrise, Sunset } from 'lucide-react-native';
import { Pressable, StyleSheet, View } from 'react-native';

import { status, useTheme } from '@/lib/theme';

import { T } from './Text';

type Props = { time: string; next: string; isDay: boolean; walkers?: number; safeSpaces?: number };

// The campus right now: a satellite view of LPU with the time, the next sunrise or
// sunset, and who is around to help. Opens the map.
export function CampusCard({ time, next, isDay, walkers, safeSpaces }: Props) {
  const theme = useTheme();
  const SunIcon = isDay ? Sunset : Sunrise;
  return (
    <Pressable
      onPress={() => {
        Haptics.selectionAsync();
        router.navigate('/map');
      }}
      accessibilityRole="button"
      accessibilityLabel={`Campus map. ${walkers ?? 0} walkers on duty, ${safeSpaces ?? 0} safe spaces.`}
      style={({ pressed }) => [styles.card, pressed && { transform: [{ scale: 0.985 }] }]}>
      <Image source={require('../../assets/images/campus.jpg')} style={StyleSheet.absoluteFill} contentFit="cover" transition={250} />
      <LinearGradient colors={['rgba(10,6,12,0.15)', 'rgba(10,6,12,0.55)', 'rgba(10,6,12,0.92)']} locations={[0, 0.45, 1]} style={StyleSheet.absoluteFill} />

      <View style={styles.top}>
        <View style={styles.live}>
          <View style={[styles.liveDot, { backgroundColor: status.good }]} />
          <T variant="eyebrow" color="#fff" style={{ fontSize: 9.5 }}>
            LPU · Live
          </T>
        </View>
        <View style={styles.open}>
          <T variant="small" color="#fff" style={{ fontSize: 12 }}>
            Map
          </T>
          <ArrowUpRight size={14} color="#fff" />
        </View>
      </View>

      <View style={styles.bottom}>
        <View style={{ flex: 1 }}>
          <T variant="display" color="#fff" style={{ fontSize: 44, lineHeight: 46, fontVariant: ['tabular-nums'] }}>
            {time}
          </T>
          <View style={styles.sun}>
            <SunIcon size={13} color={theme.accent} />
            <T variant="small" color="rgba(255,255,255,0.85)">
              {next}
            </T>
          </View>
        </View>
        <View style={{ gap: 6, alignItems: 'flex-end' }}>
          <View style={styles.pill}>
            <Footprints size={13} color={theme.accent} />
            <T variant="small" color="#fff" style={{ fontSize: 12 }}>
              {walkers ?? '–'} walkers on duty
            </T>
          </View>
          <View style={styles.pill}>
            <ShieldCheck size={13} color={status.good} />
            <T variant="small" color="#fff" style={{ fontSize: 12 }}>
              {safeSpaces ?? '–'} safe spaces
            </T>
          </View>
        </View>
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: { height: 196, borderRadius: 28, overflow: 'hidden', padding: 16, justifyContent: 'space-between', borderWidth: 1, borderColor: 'rgba(255,255,255,0.14)' },
  top: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  live: { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 10, height: 26, borderRadius: 13, backgroundColor: 'rgba(0,0,0,0.45)' },
  liveDot: { width: 6, height: 6, borderRadius: 3 },
  open: { flexDirection: 'row', alignItems: 'center', gap: 3, paddingHorizontal: 10, height: 26, borderRadius: 13, backgroundColor: 'rgba(255,255,255,0.16)' },
  bottom: { flexDirection: 'row', alignItems: 'flex-end', gap: 10 },
  sun: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 2 },
  pill: { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 10, height: 28, borderRadius: 14, backgroundColor: 'rgba(0,0,0,0.45)' },
});
