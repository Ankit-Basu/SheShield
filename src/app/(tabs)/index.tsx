import { router } from 'expo-router';
import { ArrowUpRight, CircleCheck, CloudOff, FilePlus, FileText, Footprints, Inbox, Map, Phone, ShieldCheck, Sun, Sunset } from 'lucide-react-native';
import { useEffect, useState } from 'react';
import { Linking, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';

import { Avatar, Badge, Empty, IconTile, SectionHeader } from '@/components/bits';
import { CountUp } from '@/components/CountUp';
import { Glass } from '@/components/Glass';
import { ReportRow } from '@/components/ReportRow';
import { Skeleton } from '@/components/Skeleton';
import { Screen } from '@/components/Screen';
import { T } from '@/components/Text';
import { helplines } from '@/lib/campus';
import { absoluteUrl } from '@/lib/config';
import { campusClock, greeting, shortDateTime } from '@/lib/format';
import { useReportQueue } from '@/lib/offline';
import { useMe } from '@/lib/queries';
import { status, useTheme } from '@/lib/theme';

function useClock() {
  const [now, setNow] = useState(() => campusClock());
  useEffect(() => {
    const t = setInterval(() => setNow(campusClock()), 30_000);
    return () => clearInterval(t);
  }, []);
  return now;
}

const reveal = (i: number) => FadeInDown.delay(60 * i).springify().damping(18);

export default function Home() {
  const theme = useTheme();
  const { data, isLoading, isRefetching, refetch, error } = useMe();
  const queued = useReportQueue((s) => s.queue.length);
  const clock = useClock();
  const name = data?.user.first_name ?? '';

  const actions = [
    { icon: FilePlus, title: 'File a report', note: 'Confidential', go: () => router.push('/report') },
    { icon: Footprints, title: 'Request a walk', note: 'Company on your route', go: () => router.navigate('/walks') },
    { icon: Map, title: 'Campus map', note: 'LPU, by satellite', go: () => router.navigate('/map') },
    { icon: FileText, title: 'My reports', note: 'Track their status', go: () => router.push('/reports') },
  ];

  const stats = [
    { label: 'Your reports', value: data?.stats.reports ?? 0, icon: FileText, color: theme.primary },
    { label: 'Resolved', value: data?.stats.resolved ?? 0, icon: CircleCheck, color: status.good },
    { label: 'Active walkers', value: data?.stats.active_walkers ?? 0, icon: Footprints, color: theme.accent },
    { label: 'Safe spaces', value: data?.stats.safe_spaces ?? 0, icon: ShieldCheck, color: status.info },
  ];

  return (
    <Screen refreshing={isRefetching} onRefresh={refetch}>
      <Animated.View entering={reveal(0)} style={styles.header}>
        <View style={{ flex: 1 }}>
          <View style={styles.clock}>
            {clock.isDay ? <Sun size={13} color={theme.accent} /> : <Sunset size={13} color={theme.accent} />}
            <T variant="mono" style={{ fontSize: 11.5 }}>
              {clock.time} IST · {clock.next}
            </T>
          </View>
        </View>
        <Pressable onPress={() => router.navigate('/profile')} accessibilityLabel="Your profile">
          {data ? (
            <Avatar uri={absoluteUrl(data.user.photo)} name={`${data.user.first_name} ${data.user.last_name}`} size={44} />
          ) : (
            <Skeleton width={44} height={44} radius={22} />
          )}
        </Pressable>
      </Animated.View>

      <Animated.View entering={reveal(1)}>
        <T variant="display">
          {greeting()}
          {name || isLoading ? ',' : '.'}
          {name ? (
            <T variant="display" color={theme.primary}>
              {'\n'}
              {name}.
            </T>
          ) : null}
        </T>
        {isLoading && !name ? <Skeleton width={150} height={36} radius={12} style={{ marginTop: 6 }} /> : null}
        <T muted style={{ marginTop: 8 }}>
          {isLoading
            ? 'Getting your day ready…'
            : error
              ? 'Could not reach SheShield. Pull down to try again.'
              : data && data.stats.pending > 0
                ? `You have ${data.stats.pending} report${data.stats.pending === 1 ? '' : 's'} waiting on review.`
                : 'Nothing is waiting on you right now.'}
        </T>
      </Animated.View>

      {queued > 0 && (
        <Animated.View entering={reveal(2)}>
          <Glass style={styles.banner} radius={18} tint={['rgba(244,191,63,0.18)', 'rgba(244,191,63,0.04)']}>
            <CloudOff size={18} color={status.warn} />
            <T variant="small" style={{ flex: 1 }}>
              {queued} report{queued === 1 ? ' is' : 's are'} saved on this phone and will send when you are back online.
            </T>
          </Glass>
        </Animated.View>
      )}

      {data?.active_walk && (
        <Animated.View entering={reveal(2)}>
          <Glass onPress={() => router.navigate('/walks')} style={styles.walk} radius={24} tint={[theme.primary + '40', theme.primary + '0a']}>
            <IconTile color={theme.primary}>
              <Footprints size={20} color={theme.primary} />
            </IconTile>
            <View style={{ flex: 1, gap: 3 }}>
              <T variant="eyebrow">Your walk</T>
              <T variant="label" numberOfLines={1}>
                {data.active_walk.pickup_location} → {data.active_walk.destination}
              </T>
              <T variant="small" muted>
                {data.active_walk.walker_name ? `Walker: ${data.active_walk.walker_name}` : 'Finding a walker'} · {shortDateTime(data.active_walk.request_time)}
              </T>
            </View>
            <Badge value={data.active_walk.status} live />
          </Glass>
        </Animated.View>
      )}

      <View style={styles.grid}>
        {actions.map((a, i) => (
          <Animated.View key={a.title} entering={reveal(3 + i)} style={styles.half}>
            <Glass onPress={a.go} style={styles.action} radius={24} accessibilityLabel={a.title}>
              <View style={styles.actionTop}>
                <IconTile>
                  <a.icon size={20} color={theme.primary} />
                </IconTile>
                <ArrowUpRight size={18} color={theme.muted} />
              </View>
              <View>
                <T variant="label">{a.title}</T>
                <T variant="small" muted>
                  {a.note}
                </T>
              </View>
            </Glass>
          </Animated.View>
        ))}
      </View>

      <View style={styles.grid}>
        {stats.map((s, i) => (
          <Animated.View key={s.label} entering={reveal(7 + i)} style={styles.half}>
            <Glass style={styles.stat} radius={22}>
              <View style={styles.actionTop}>
                <T variant="small" muted>
                  {s.label}
                </T>
                <s.icon size={16} color={s.color} />
              </View>
              {data ? <CountUp value={s.value} /> : <Skeleton width={46} height={34} style={{ marginVertical: 2 }} />}
            </Glass>
          </Animated.View>
        ))}
      </View>

      <SectionHeader title="Latest reports" action="See all" onAction={() => router.push('/reports')} />
      <Animated.View entering={reveal(11)}>
        <Glass style={{ paddingHorizontal: 16, paddingVertical: 4 }} radius={24}>
          {data?.latest_reports.length ? (
            data.latest_reports.slice(0, 3).map((r, i, arr) => <ReportRow key={r.id} r={r} last={i === arr.length - 1} />)
          ) : (
            <Empty icon={<Inbox size={26} color={theme.muted} />} text={isLoading ? 'Loading…' : 'You have not filed a report yet.'} />
          )}
        </Glass>
      </Animated.View>

      <SectionHeader title="Helplines" />
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 10, paddingRight: 18 }} style={{ marginHorizontal: -18, paddingLeft: 18 }}>
        {helplines.map((h) => (
          <Glass key={h.number} onPress={() => Linking.openURL(`tel:${h.number}`)} style={styles.helpline} radius={20} accessibilityLabel={`Call ${h.label}, ${h.number}`}>
            <Phone size={16} color={theme.primary} />
            <T variant="title" style={{ fontSize: 26 }}>
              {h.number}
            </T>
            <T variant="small" muted numberOfLines={1}>
              {h.label}
            </T>
          </Glass>
        ))}
      </ScrollView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  clock: { flexDirection: 'row', alignItems: 'center', gap: 7, alignSelf: 'flex-start', paddingHorizontal: 11, height: 30, borderRadius: 99, backgroundColor: 'rgba(0,0,0,0.25)' },
  banner: { flexDirection: 'row', alignItems: 'center', gap: 10, padding: 14 },
  walk: { flexDirection: 'row', alignItems: 'center', gap: 12, padding: 15 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  half: { width: '48%', flexGrow: 1 },
  action: { padding: 15, gap: 22, minHeight: 138, justifyContent: 'space-between' },
  actionTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  stat: { padding: 15, gap: 8 },
  helpline: { padding: 15, width: 132, gap: 4 },
});
