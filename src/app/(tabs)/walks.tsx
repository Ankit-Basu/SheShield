import * as Haptics from 'expo-haptics';
import { useLocalSearchParams } from 'expo-router';
import { ArrowDown, Clock, Footprints, MapPin, Navigation, Phone, Route } from 'lucide-react-native';
import { useState } from 'react';
import { Linking, Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown, LinearTransition } from 'react-native-reanimated';

import { Badge, Chip, Empty, IconTile, SectionHeader } from '@/components/bits';
import { Button } from '@/components/Button';
import { Glass } from '@/components/Glass';
import { Screen } from '@/components/Screen';
import { T } from '@/components/Text';
import { toast } from '@/components/Toast';
import { landmarks } from '@/lib/campus';
import { shortDateTime } from '@/lib/format';
import { useCancelWalk, useRequestWalk, useWalks } from '@/lib/queries';
import { useTheme } from '@/lib/theme';

const when = [
  { label: 'Now', minutes: 0 },
  { label: 'In 15 min', minutes: 15 },
  { label: 'In 30 min', minutes: 30 },
  { label: 'In 1 hour', minutes: 60 },
];

function ist(minutesFromNow: number) {
  const d = new Date(Date.now() + minutesFromNow * 60000 + 5.5 * 3600000);
  return d.toISOString().slice(0, 19).replace('T', ' ');
}

export default function Walks() {
  const theme = useTheme();
  const params = useLocalSearchParams<{ from?: string }>();
  const [from, setFrom] = useState<string | null>(params.from ?? null);
  const [to, setTo] = useState<string | null>(null);
  const [minutes, setMinutes] = useState(0);
  const { data: walks, isLoading, refetch, isRefetching } = useWalks();
  const request = useRequestWalk();
  const cancel = useCancelWalk();

  // A landmark chosen on the map arrives as ?from=; adopt it when it changes.
  const [lastFrom, setLastFrom] = useState(params.from);
  if (params.from !== lastFrom) {
    setLastFrom(params.from);
    if (params.from) setFrom(params.from);
  }

  const submit = () => {
    if (!from || !to) return toast('Choose where you are and where you are going.', 'error');
    if (from === to) return toast('Pickup and destination need to be different.', 'error');
    request.mutate(
      { pickupLocation: from, destination: to, requestTime: ist(minutes) },
      {
        onSuccess: (r) => {
          Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
          toast(r.message || 'Walk requested');
          setTo(null);
        },
        onError: (e) => toast(e.message, 'error'),
      },
    );
  };

  const active = walks?.filter((w) => w.status === 'pending' || w.status === 'accepted') ?? [];
  const past = walks?.filter((w) => !(w.status === 'pending' || w.status === 'accepted')) ?? [];

  return (
    <Screen refreshing={isRefetching} onRefresh={refetch}>
      <Animated.View entering={FadeInDown.springify().damping(18)}>
        <T variant="eyebrow">Walk With Us</T>
        <T variant="display" style={{ fontSize: 36, lineHeight: 40 }}>
          Never walk{'\n'}
          <T variant="display" color={theme.primary} style={{ fontSize: 36, lineHeight: 40 }}>
            alone.
          </T>
        </T>
        <T muted style={{ marginTop: 6 }}>
          A verified student walker meets you and stays with you to your destination.
        </T>
      </Animated.View>

      <Animated.View entering={FadeInDown.delay(80).springify().damping(18)}>
        <Glass radius={28} strong style={{ padding: 16, gap: 14 }}>
          <View style={styles.stepHead}>
            <MapPin size={16} color={theme.primary} />
            <T variant="label">From</T>
            <T variant="small" muted style={{ marginLeft: 'auto' }}>
              {from ?? 'Choose pickup'}
            </T>
          </View>
          <View style={styles.chips}>
            {landmarks.map((l) => (
              <Chip key={l.name} label={l.name} selected={from === l.name} onPress={() => setFrom(l.name)} />
            ))}
          </View>

          <View style={styles.arrow}>
            <ArrowDown size={16} color={theme.muted} />
          </View>

          <View style={styles.stepHead}>
            <Navigation size={16} color={theme.accent} />
            <T variant="label">To</T>
            <T variant="small" muted style={{ marginLeft: 'auto' }}>
              {to ?? 'Choose destination'}
            </T>
          </View>
          <View style={styles.chips}>
            {landmarks.map((l) => (
              <Chip key={l.name} label={l.name} selected={to === l.name} onPress={() => setTo(l.name)} />
            ))}
          </View>

          <View style={styles.stepHead}>
            <Clock size={16} color={theme.muted} />
            <T variant="label">When</T>
          </View>
          <View style={styles.chips}>
            {when.map((w) => (
              <Chip key={w.label} label={w.label} selected={minutes === w.minutes} onPress={() => setMinutes(w.minutes)} />
            ))}
          </View>

          <Button label="Request a walker" size="lg" icon={<Footprints size={18} color={theme.primaryForeground} />} loading={request.isPending} onPress={submit} />
        </Glass>
      </Animated.View>

      <SectionHeader title="Your walks" />
      {active.length === 0 && past.length === 0 ? (
        <Glass radius={24} style={{ padding: 8 }}>
          <Empty icon={<Route size={26} color={theme.muted} />} text={isLoading ? 'Loading…' : 'No walks yet. Request one above.'} />
        </Glass>
      ) : (
        [...active, ...past].map((w, i) => (
          <Animated.View key={w.walk_id} entering={FadeInDown.delay(40 * i)} layout={LinearTransition.springify().damping(18)}>
            <Glass radius={22} style={{ padding: 15, gap: 10 }}>
              <View style={styles.walkTop}>
                <IconTile size={38}>
                  <Footprints size={17} color={theme.primary} />
                </IconTile>
                <View style={{ flex: 1 }}>
                  <T variant="label" numberOfLines={1}>
                    {w.pickup_location} → {w.destination}
                  </T>
                  <T variant="small" muted>
                    {shortDateTime(w.request_time)} · {w.walk_id}
                  </T>
                </View>
                <Badge value={w.status} live={w.status === 'accepted' || w.status === 'pending'} />
              </View>
              {w.walker_name ? (
                <View style={styles.walker}>
                  <T variant="small" style={{ flex: 1 }}>
                    Walker: <T variant="small" color={theme.primary}>{w.walker_name}</T>
                  </T>
                  {w.walker_phone ? (
                    <Pressable onPress={() => Linking.openURL(`tel:${w.walker_phone}`)} hitSlop={8} style={styles.call} accessibilityLabel={`Call ${w.walker_name}`}>
                      <Phone size={14} color={theme.primary} />
                      <T variant="small" color={theme.primary}>
                        Call
                      </T>
                    </Pressable>
                  ) : null}
                </View>
              ) : null}
              {(w.status === 'pending' || w.status === 'accepted') && (
                <Button label="Cancel walk" variant="ghost" loading={cancel.isPending && cancel.variables === w.walk_id} onPress={() => cancel.mutate(w.walk_id, { onError: (e) => toast(e.message, 'error') })} />
              )}
            </Glass>
          </Animated.View>
        ))
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  stepHead: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  arrow: { alignItems: 'center', marginVertical: -4 },
  walkTop: { flexDirection: 'row', alignItems: 'center', gap: 11 },
  walker: { flexDirection: 'row', alignItems: 'center', padding: 10, borderRadius: 12, backgroundColor: 'rgba(0,0,0,0.2)' },
  call: { flexDirection: 'row', alignItems: 'center', gap: 5 },
});
