import { router } from 'expo-router';
import { ChevronRight, CloudOff, FilePlus, Footprints, History, Inbox, Map, Phone } from 'lucide-react-native';
import { useEffect, useState } from 'react';
import { Linking, Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';

import { Avatar, Badge, Empty } from '@/components/bits';
import { Button } from '@/components/Button';
import { CountUp } from '@/components/CountUp';
import { Glass } from '@/components/Glass';
import { ReportRow } from '@/components/ReportRow';
import { Screen } from '@/components/Screen';
import { Skeleton } from '@/components/Skeleton';
import { SourceBanner } from '@/components/SourceBanner';
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

const today = () => new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'Asia/Kolkata' });
const reveal = (i: number) => FadeInDown.delay(50 * i).springify().damping(20);

function SectionTitle({ title, action, onAction }: { title: string; action?: string; onAction?: () => void }) {
  const theme = useTheme();
  return (
    <View style={styles.section}>
      <T variant="label" muted style={{ fontSize: 13, letterSpacing: 0.2 }}>
        {title}
      </T>
      {action ? (
        <Pressable onPress={onAction} hitSlop={10} accessibilityRole="button" style={styles.sectionAction}>
          <T variant="small" color={theme.primary}>
            {action}
          </T>
          <ChevronRight size={14} color={theme.primary} />
        </Pressable>
      ) : null}
    </View>
  );
}

function QuickAction({ icon: Icon, label, onPress }: { icon: typeof Map; label: string; onPress: () => void }) {
  const theme = useTheme();
  return (
    <View style={styles.quick}>
      <Glass onPress={onPress} radius={20} flat style={styles.quickIcon} accessibilityLabel={label}>
        <Icon size={22} color={theme.foreground} strokeWidth={1.9} />
      </Glass>
      <T variant="small" style={{ fontSize: 12.5 }}>
        {label}
      </T>
    </View>
  );
}

export default function Home() {
  const theme = useTheme();
  const { data, isLoading, isRefetching, refetch, error } = useMe();
  const queued = useReportQueue((s) => s.queue.length);
  const clock = useClock();
  const name = data?.user.first_name ?? '';
  const walk = data?.active_walk;

  const subtitle = isLoading
    ? ' '
    : error
      ? 'Could not reach SheShield. Pull down to try again.'
      : data && data.stats.pending > 0
        ? `${data.stats.pending} of your report${data.stats.pending === 1 ? ' is' : 's are'} waiting on review.`
        : 'Nothing needs your attention right now.';

  const summary = [
    { label: 'Reports', value: data?.stats.reports ?? 0 },
    { label: 'Resolved', value: data?.stats.resolved ?? 0 },
    { label: 'Walkers on duty', value: data?.stats.active_walkers ?? 0 },
  ];

  return (
    <Screen refreshing={isRefetching} onRefresh={refetch}>
      {/* Header: date and campus time, then a plain large title. */}
      <Animated.View entering={reveal(0)} style={styles.header}>
        <View style={{ flex: 1, gap: 2 }}>
          <T variant="small" muted>
            {today()} · {clock.time}
          </T>
          <T variant="small" muted style={{ fontSize: 12 }}>
            {clock.next} at LPU
          </T>
        </View>
        <Pressable onPress={() => router.navigate('/profile')} accessibilityLabel="Your profile" hitSlop={6}>
          {data ? (
            <Avatar uri={absoluteUrl(data.user.photo)} name={`${data.user.first_name} ${data.user.last_name}`} size={42} />
          ) : (
            <Skeleton width={42} height={42} radius={21} />
          )}
        </Pressable>
      </Animated.View>

      <Animated.View entering={reveal(1)} style={{ gap: 6 }}>
        <T variant="title" style={{ fontSize: 32, lineHeight: 37 }}>
          {greeting()}
          {name ? ', ' : ''}
          {name ? <T variant="title" color={theme.primary} style={{ fontSize: 32, lineHeight: 37 }}>{name}</T> : null}
        </T>
        {isLoading && !name ? <Skeleton width={220} height={16} /> : <T muted>{subtitle}</T>}
      </Animated.View>

      <SourceBanner keys={['me']} />

      {queued > 0 && (
        <Glass style={styles.notice} radius={18} flat tint={[status.warn + '24', 'rgba(255,255,255,0.02)']}>
          <CloudOff size={18} color={status.warn} />
          <T variant="small" style={{ flex: 1 }}>
            {queued} report{queued === 1 ? ' is' : 's are'} saved on this phone and will send when you&apos;re back online.
          </T>
        </Glass>
      )}

      {/* The one focal panel: your walk, or an invitation to request one. */}
      <Animated.View entering={reveal(2)}>
        {walk ? (
          <Glass strong radius={26} onPress={() => router.navigate('/walks')} style={styles.focus} accessibilityLabel="Your walk">
            <View style={styles.focusTop}>
              <T variant="small" muted>
                Your walk
              </T>
              <Badge value={walk.status} live />
            </View>
            <View style={styles.route}>
              <View style={styles.routeLine}>
                <View style={[styles.routeDot, { backgroundColor: theme.primary }]} />
                <View style={styles.routeDash} />
                <View style={[styles.routeDot, { backgroundColor: theme.accent }]} />
              </View>
              <View style={{ flex: 1, gap: 14 }}>
                <T variant="heading" numberOfLines={1}>
                  {walk.pickup_location}
                </T>
                <T variant="heading" numberOfLines={1}>
                  {walk.destination}
                </T>
              </View>
            </View>
            <View style={styles.hairline} />
            <T variant="small" muted>
              {walk.walker_name ? `${walk.walker_name} is walking with you` : 'Finding a verified walker'} · {shortDateTime(walk.request_time)}
            </T>
          </Glass>
        ) : (
          <Glass strong radius={26} style={styles.focus}>
            <T variant="heading">Heading somewhere?</T>
            <T muted style={{ marginTop: -4 }}>
              A verified student walker can meet you at any campus landmark and stay with you.
            </T>
            <Button label="Request a walk" icon={<Footprints size={17} color={theme.primaryForeground} />} onPress={() => router.navigate('/walks')} style={{ marginTop: 4 }} />
          </Glass>
        )}
      </Animated.View>

      <Animated.View entering={reveal(3)} style={styles.quickRow}>
        <QuickAction icon={FilePlus} label="Report" onPress={() => router.push('/report')} />
        <QuickAction icon={Footprints} label="Walk" onPress={() => router.navigate('/walks')} />
        <QuickAction icon={Map} label="Map" onPress={() => router.navigate('/map')} />
        <QuickAction icon={History} label="History" onPress={() => router.push('/reports')} />
      </Animated.View>

      {/* One summary strip instead of a grid of number cards. */}
      <Animated.View entering={reveal(4)}>
        <Glass radius={22} flat style={styles.strip}>
          {summary.map((s, i) => (
            <View key={s.label} style={[styles.stripCell, i > 0 && styles.stripDivider]}>
              {data ? <CountUp value={s.value} style={{ fontSize: 26, lineHeight: 30 }} /> : <Skeleton width={30} height={26} />}
              <T variant="small" muted numberOfLines={1} style={{ fontSize: 12 }}>
                {s.label}
              </T>
            </View>
          ))}
        </Glass>
      </Animated.View>

      <SectionTitle title="Recent reports" action="See all" onAction={() => router.push('/reports')} />
      <Animated.View entering={reveal(5)}>
        <Glass radius={22} style={{ paddingHorizontal: 16 }}>
          {data?.latest_reports.length ? (
            data.latest_reports.slice(0, 3).map((r, i, arr) => <ReportRow key={r.id} r={r} last={i === arr.length - 1} />)
          ) : (
            <Empty icon={<Inbox size={24} color={theme.muted} />} text={isLoading ? 'Loading…' : "You haven't filed a report."} />
          )}
        </Glass>
      </Animated.View>

      <SectionTitle title="Helplines" />
      <Animated.View entering={reveal(6)}>
        <Glass radius={22} style={{ paddingHorizontal: 16 }}>
          {helplines.map((h, i) => (
            <View key={h.number} style={[styles.line, i < helplines.length - 1 && styles.lineDivider]}>
              <T variant="heading" style={{ width: 58, fontVariant: ['tabular-nums'] }}>
                {h.number}
              </T>
              <T style={{ flex: 1 }} numberOfLines={1}>
                {h.label}
              </T>
              <Pressable
                onPress={() => Linking.openURL(`tel:${h.number}`)}
                style={[styles.call, { backgroundColor: theme.primary + '22' }]}
                hitSlop={6}
                accessibilityRole="button"
                accessibilityLabel={`Call ${h.label}, ${h.number}`}>
                <Phone size={14} color={theme.primary} />
                <T variant="small" color={theme.primary}>
                  Call
                </T>
              </Pressable>
            </View>
          ))}
        </Glass>
      </Animated.View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  notice: { flexDirection: 'row', alignItems: 'center', gap: 10, padding: 14 },
  focus: { padding: 18, gap: 14 },
  focusTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  route: { flexDirection: 'row', gap: 14 },
  routeLine: { alignItems: 'center', paddingVertical: 6 },
  routeDot: { width: 10, height: 10, borderRadius: 5 },
  routeDash: { width: 2, flex: 1, marginVertical: 4, borderRadius: 1, backgroundColor: 'rgba(255,255,255,0.2)' },
  hairline: { height: StyleSheet.hairlineWidth, backgroundColor: 'rgba(255,255,255,0.14)' },
  quickRow: { flexDirection: 'row', justifyContent: 'space-between', paddingHorizontal: 4 },
  quick: { alignItems: 'center', gap: 8, width: 72 },
  quickIcon: { width: 60, height: 60, alignItems: 'center', justifyContent: 'center' },
  strip: { flexDirection: 'row', paddingVertical: 16 },
  stripCell: { flex: 1, alignItems: 'center', gap: 4, paddingHorizontal: 6 },
  stripDivider: { borderLeftWidth: StyleSheet.hairlineWidth, borderLeftColor: 'rgba(255,255,255,0.16)' },
  section: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 6, marginBottom: -6, paddingHorizontal: 4 },
  sectionAction: { flexDirection: 'row', alignItems: 'center', gap: 2 },
  line: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 14 },
  lineDivider: { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: 'rgba(255,255,255,0.12)' },
  call: { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 12, height: 32, borderRadius: 16 },
});
