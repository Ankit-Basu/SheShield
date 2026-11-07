import * as Haptics from 'expo-haptics';
import { useLocalSearchParams } from 'expo-router';
import { ArrowUpDown, Clock, Footprints, MapPin, Navigation, Phone, Route } from 'lucide-react-native';
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
  const [editing, setEditing] = useState<'from' | 'to'>(params.from ? 'to' : 'from');
  const [minutes, setMinutes] = useState(0);
  const { data: walks, isLoading, refetch, isRefetching } = useWalks();
  const request = useRequestWalk();
  const cancel = useCancelWalk();

  // A landmark chosen on the map arrives as ?from=; adopt it when it changes.
  const [lastFrom, setLastFrom] = useState(params.from);
  if (params.from !== lastFrom) {
    setLastFrom(params.from);
    if (params.from) {
      setFrom(params.from);
      setEditing('to');
    }
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
          setEditing('to');
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
          {/* Route: tap an end to edit it, pick a place below, swap with one tap. */}
          <View style={styles.route}>
            <View style={styles.routeLine}>
              <View style={[styles.routeDot, { backgroundColor: theme.primary }]} />
              <View style={styles.routeDash} />
              <View style={[styles.routeDot, { backgroundColor: theme.accent }]} />
            </View>
            <View style={{ flex: 1, gap: 8 }}>
              {(['from', 'to'] as const).map((end) => {
                const value = end === 'from' ? from : to;
                const on = editing === end;
                return (
                  <Pressable
                    key={end}
                    onPress={() => {
                      Haptics.selectionAsync();
                      setEditing(end);
                    }}
                    style={[styles.routeEnd, on && { borderColor: theme.primary, backgroundColor: theme.primary + '1c' }]}
                    accessibilityRole="button"
                    accessibilityLabel={`${end === 'from' ? 'Pickup' : 'Destination'}: ${value ?? 'not chosen'}`}>
                    <T variant="eyebrow" style={{ fontSize: 9.5 }}>
                      {end === 'from' ? 'From' : 'To'}
                    </T>
                    <T variant="label" color={value ? theme.foreground : theme.muted} numberOfLines={1}>
                      {value ?? (end === 'from' ? 'Choose pickup' : 'Choose destination')}
                    </T>
                  </Pressable>
                );
              })}
            </View>
            <Pressable
              onPress={() => {
                Haptics.selectionAsync();
                setFrom(to);
                setTo(from);
              }}
              disabled={!from && !to}
              style={[styles.swap, { opacity: from || to ? 1 : 0.4 }]}
              accessibilityRole="button"
              accessibilityLabel="Swap pickup and destination">
              <ArrowUpDown size={18} color={theme.foreground} />
            </Pressable>
          </View>

          <View style={styles.stepHead}>
            {editing === 'from' ? <MapPin size={16} color={theme.primary} /> : <Navigation size={16} color={theme.accent} />}
            <T variant="label">{editing === 'from' ? 'Where are you?' : 'Where are you going?'}</T>
          </View>
          <View style={styles.chips}>
            {landmarks.map((l) => {
              const taken = editing === 'from' ? to === l.name : from === l.name;
              return (
                <Chip
                  key={l.name}
                  label={l.name}
                  selected={(editing === 'from' ? from : to) === l.name}
                  onPress={() => {
                    if (taken) return toast('That is already the other end of your route.', 'error');
                    if (editing === 'from') {
                      setFrom(l.name);
                      if (!to) setEditing('to');
                    } else {
                      setTo(l.name);
                    }
                  }}
                />
              );
            })}
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
  route: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  routeLine: { alignItems: 'center', paddingVertical: 16 },
  routeDot: { width: 11, height: 11, borderRadius: 6 },
  routeDash: { width: 2, flex: 1, minHeight: 34, marginVertical: 4, backgroundColor: 'rgba(255,255,255,0.18)', borderRadius: 1 },
  routeEnd: { paddingHorizontal: 14, paddingVertical: 9, borderRadius: 16, borderWidth: 1, borderColor: 'rgba(255,255,255,0.12)', backgroundColor: 'rgba(0,0,0,0.2)', gap: 2 },
  swap: { width: 42, height: 42, borderRadius: 21, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(255,255,255,0.08)', borderWidth: 1, borderColor: 'rgba(255,255,255,0.12)' },
  walkTop: { flexDirection: 'row', alignItems: 'center', gap: 11 },
  walker: { flexDirection: 'row', alignItems: 'center', padding: 10, borderRadius: 12, backgroundColor: 'rgba(0,0,0,0.2)' },
  call: { flexDirection: 'row', alignItems: 'center', gap: 5 },
});
