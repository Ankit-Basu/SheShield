import { router } from 'expo-router';
import { ChevronRight, CloudOff, Inbox, Phone, ShieldCheck } from 'lucide-react-native';
import { useEffect, useState } from 'react';
import { Linking, Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeIn, FadeInDown } from 'react-native-reanimated';

import { Avatar, Empty } from '@/components/bits';
import { CampusCard } from '@/components/CampusCard';
import { Card } from '@/components/Card';
import { ReportRow } from '@/components/ReportRow';
import { ReadinessBadge, SafetyRing, useReadiness } from '@/components/SafetyRing';
import { SafetyTools } from '@/components/SafetyTools';
import { Screen } from '@/components/Screen';
import { Segmented } from '@/components/Segmented';
import { Sheet } from '@/components/Sheet';
import { Skeleton } from '@/components/Skeleton';
import { SourceBanner } from '@/components/SourceBanner';
import { T } from '@/components/Text';
import { WalkHero } from '@/components/WalkHero';
import { helplines } from '@/lib/campus';
import { absoluteUrl } from '@/lib/config';
import { campusClock, greeting } from '@/lib/format';
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

// Short enough to fit beside the greeting on any day: "Sun, 15 Nov".
const today = () => new Date().toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'Asia/Kolkata' });
const reveal = (i: number) =>
  FadeInDown.delay(60 * i)
    .springify()
    .damping(20);

function SectionTitle({ title, action, onAction }: { title: string; action?: string; onAction?: () => void }) {
  const theme = useTheme();
  return (
    <View style={styles.section}>
      <T variant="heading" style={{ fontSize: 17 }}>
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

type Tab = 'reports' | 'helplines';

// Home, top to bottom: who you are and how ready SOS is; where you're walking;
// the campus right now; the safety tools; then your reports or the helplines.
export default function Home() {
  const theme = useTheme();
  const { data, isLoading, isRefetching, refetch, error } = useMe();
  const queued = useReportQueue((s) => s.queue.length);
  const clock = useClock();
  const readiness = useReadiness(data?.user);
  const [tab, setTab] = useState<Tab>('reports');
  const [checklist, setChecklist] = useState(false);
  const name = data?.user.first_name ?? '';
  const reports = data?.latest_reports ?? [];
  const stepsLeft = readiness.steps.length - readiness.done;

  // One line on what needs you, most important first.
  const subtitle = error
    ? { text: 'Could not reach SheShield. Pull down to try again.' }
    : data && data.stats.pending > 0
      ? { text: `${data.stats.pending} of your report${data.stats.pending === 1 ? ' is' : 's are'} waiting on review` }
      : readiness.known && stepsLeft > 0
        ? { text: `${stepsLeft} step${stepsLeft === 1 ? '' : 's'} left to be SOS-ready`, link: true }
        : { text: "You're SOS-ready. Stay safe out there." };

  return (
    <Screen refreshing={isRefetching} onRefresh={refetch}>
      {/* Header: you, today, and SOS readiness at a glance. */}
      <Animated.View entering={reveal(0)} style={styles.header}>
        <Pressable onPress={() => router.navigate('/profile')} accessibilityLabel="Your profile" hitSlop={6}>
          {data ? <Avatar uri={absoluteUrl(data.user.photo)} name={`${data.user.first_name} ${data.user.last_name}`} size={50} /> : <Skeleton width={50} height={50} radius={25} />}
        </Pressable>
        <View style={{ flex: 1 }}>
          <T variant="small" muted numberOfLines={1}>
            {greeting()} · {today()}
          </T>
          {name ? (
            <T variant="title" numberOfLines={1} style={{ fontSize: 28, lineHeight: 33 }}>
              {name}
            </T>
          ) : (
            <Skeleton width={120} height={28} radius={9} style={{ marginTop: 3 }} />
          )}
        </View>
        <ReadinessBadge readiness={readiness} onPress={() => setChecklist(true)} />
      </Animated.View>

      {isLoading && !name ? null : (
        <Animated.View entering={reveal(1)}>
          {'link' in subtitle ? (
            <Pressable onPress={() => setChecklist(true)} style={[styles.status, { backgroundColor: status.warn + '1f' }]} accessibilityRole="button">
              <View style={[styles.statusDot, { backgroundColor: status.warn }]} />
              <T variant="small" style={{ flex: 1 }}>
                {subtitle.text}
              </T>
              <T variant="small" color={theme.primary}>
                Finish setup
              </T>
              <ChevronRight size={14} color={theme.primary} />
            </Pressable>
          ) : (
            <View style={[styles.status, { backgroundColor: theme.surfaceAlt }]}>
              <View style={[styles.statusDot, { backgroundColor: error ? theme.destructive : data && data.stats.pending > 0 ? status.info : status.good }]} />
              <T variant="small" style={{ flex: 1 }}>
                {subtitle.text}
              </T>
            </View>
          )}
        </Animated.View>
      )}

      <SourceBanner keys={['me']} />

      {queued > 0 && (
        <Card style={styles.notice} radius={18} flat fill={status.warn + '1f'}>
          <CloudOff size={18} color={status.warn} />
          <T variant="small" style={{ flex: 1 }}>
            {queued} report{queued === 1 ? ' is' : 's are'} saved on this phone and will send when you&apos;re back online.
          </T>
        </Card>
      )}

      {/* The hero: where you're headed, or the walk you're on. */}
      <Animated.View entering={reveal(2)}>
        <WalkHero walk={data?.active_walk} walkers={data?.stats.active_walkers} isDay={clock.isDay} />
      </Animated.View>

      <SectionTitle title="Safety tools" />
      <Animated.View entering={reveal(3)}>
        <SafetyTools contactName={data?.user.emergency_contact_name} />
      </Animated.View>

      <SectionTitle title="On campus now" action="Open map" onAction={() => router.navigate('/map')} />
      <Animated.View entering={reveal(4)}>
        <CampusCard time={clock.time} next={clock.next} isDay={clock.isDay} walkers={data?.stats.active_walkers} safeSpaces={data?.stats.safe_spaces} />
      </Animated.View>

      <Animated.View entering={reveal(5)} style={{ gap: 12, marginTop: 8 }}>
        <Segmented<Tab>
          value={tab}
          onChange={setTab}
          options={[
            { key: 'reports', label: 'Your reports', count: data?.stats.pending || undefined },
            { key: 'helplines', label: 'Helplines' },
          ]}
        />
        {tab === 'reports' ? (
          <Animated.View key="reports" entering={FadeIn.duration(220)}>
            <Card radius={22} style={{ paddingHorizontal: 16 }}>
              {reports.length ? (
                <>
                  {reports.slice(0, 3).map((r, i, arr) => (
                    <ReportRow key={r.id} r={r} last={i === arr.length - 1} onPress={() => router.push('/reports')} />
                  ))}
                  <Pressable onPress={() => router.push('/reports')} style={[styles.seeAll, { borderTopColor: theme.line }]} accessibilityRole="button">
                    <T variant="small" color={theme.primary}>
                      See all reports
                    </T>
                    <ChevronRight size={14} color={theme.primary} />
                  </Pressable>
                </>
              ) : (
                <Empty icon={<Inbox size={24} color={theme.muted} />} text={isLoading ? 'Loading…' : 'No reports yet. If something happens, Report an incident is in Safety tools.'} />
              )}
            </Card>
          </Animated.View>
        ) : (
          <Animated.View key="helplines" entering={FadeIn.duration(220)} style={styles.lines}>
            {helplines.map((h) => (
              <View key={h.number} style={styles.lineCell}>
                <Card radius={20} onPress={() => Linking.openURL(`tel:${h.number}`)} accessibilityLabel={`Call ${h.label}, ${h.number}`} style={styles.line}>
                  <T variant="title" style={{ fontSize: 24, lineHeight: 28, fontVariant: ['tabular-nums'] }}>
                    {h.number}
                  </T>
                  <T variant="small" muted numberOfLines={2} style={{ minHeight: 34 }}>
                    {h.label}
                  </T>
                  <View style={[styles.call, { backgroundColor: theme.soft }]}>
                    <Phone size={13} color={theme.primary} />
                    <T variant="small" color={theme.primary}>
                      Call
                    </T>
                  </View>
                </Card>
              </View>
            ))}
          </Animated.View>
        )}
      </Animated.View>

      <Sheet
        open={checklist}
        onClose={() => setChecklist(false)}
        title={readiness.ready ? "You're SOS-ready" : 'Get SOS-ready'}
        subtitle="Three things make an SOS work for you. Tap one to set it up."
        icon={<ShieldCheck size={20} color={theme.primary} />}>
        <SafetyRing readiness={readiness} bare onStep={() => setChecklist(false)} />
      </Sheet>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  status: { flexDirection: 'row', alignItems: 'center', gap: 8, paddingHorizontal: 14, height: 42, borderRadius: 14, marginTop: -4 },
  statusDot: { width: 8, height: 8, borderRadius: 4 },
  notice: { flexDirection: 'row', alignItems: 'center', gap: 10, padding: 14 },
  section: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 10, marginBottom: -4, paddingHorizontal: 2 },
  sectionAction: { flexDirection: 'row', alignItems: 'center', gap: 2 },
  seeAll: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 2, paddingVertical: 14, borderTopWidth: StyleSheet.hairlineWidth },
  lines: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', rowGap: 12 },
  lineCell: { width: '48.3%' },
  line: { padding: 14, gap: 6 },
  call: { flexDirection: 'row', alignItems: 'center', gap: 5, alignSelf: 'flex-start', paddingHorizontal: 11, height: 30, borderRadius: 15, marginTop: 2 },
});
