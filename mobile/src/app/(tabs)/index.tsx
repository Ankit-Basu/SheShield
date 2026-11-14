import { router } from 'expo-router';
import { ChevronRight, CloudOff, Footprints, Inbox, Phone } from 'lucide-react-native';
import { useEffect, useRef, useState } from 'react';
import { Linking, Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeIn, FadeInDown } from 'react-native-reanimated';

import { Avatar, Badge, Empty } from '@/components/bits';
import { CampusCard } from '@/components/CampusCard';
import { Card } from '@/components/Card';
import { HeroCarousel, HeroCarouselHandle } from '@/components/HeroCarousel';
import { ReportRow } from '@/components/ReportRow';
import { SafetyRing, useReadiness } from '@/components/SafetyRing';
import { SafetyTools } from '@/components/SafetyTools';
import { Screen } from '@/components/Screen';
import { Segmented } from '@/components/Segmented';
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

type Tab = 'reports' | 'helplines';

export default function Home() {
  const theme = useTheme();
  const { data, isLoading, isRefetching, refetch, error } = useMe();
  const queued = useReportQueue((s) => s.queue.length);
  const clock = useClock();
  const readiness = useReadiness(data?.user);
  const hero = useRef<HeroCarouselHandle>(null);
  const [tab, setTab] = useState<Tab>('reports');
  const name = data?.user.first_name ?? '';
  const walk = data?.active_walk;
  const reports = data?.latest_reports ?? [];
  const stepsLeft = readiness.steps.length - readiness.done;

  // One line on what needs you, most important first.
  const subtitle = error
    ? { text: 'Could not reach SheShield. Pull down to try again.' }
    : data && data.stats.pending > 0
      ? { text: `${data.stats.pending} of your report${data.stats.pending === 1 ? ' is' : 's are'} waiting on review.` }
      : readiness.known && stepsLeft > 0
        ? { text: `${stepsLeft} step${stepsLeft === 1 ? '' : 's'} left to be SOS-ready`, link: true }
        : { text: "You're all set. Stay safe out there." };

  return (
    <Screen refreshing={isRefetching} onRefresh={refetch}>
      {/* Header: who you are and what needs attention. */}
      <Animated.View entering={reveal(0)} style={styles.header}>
        <View style={{ flex: 1, gap: 2 }}>
          <T variant="small" muted>
            {greeting()} · {today()}
          </T>
          {name ? (
            <T variant="title" style={{ fontSize: 32, lineHeight: 37 }}>
              {name}
            </T>
          ) : (
            <Skeleton width={140} height={32} radius={10} style={{ marginVertical: 2 }} />
          )}
          {isLoading && !name ? null : 'link' in subtitle ? (
            <Pressable onPress={() => hero.current?.show(1)} hitSlop={6} style={styles.subtitleLink} accessibilityRole="button">
              <View style={[styles.subtitleDot, { backgroundColor: status.warn }]} />
              <T variant="small" color={theme.foreground}>
                {subtitle.text}
              </T>
              <ChevronRight size={13} color={theme.primary} />
            </Pressable>
          ) : (
            <T variant="small" muted style={{ marginTop: 2 }}>
              {subtitle.text}
            </T>
          )}
        </View>
        <Pressable onPress={() => router.navigate('/profile')} accessibilityLabel="Your profile" hitSlop={6}>
          {data ? (
            <Avatar uri={absoluteUrl(data.user.photo)} name={`${data.user.first_name} ${data.user.last_name}`} size={48} />
          ) : (
            <Skeleton width={48} height={48} radius={24} />
          )}
        </Pressable>
      </Animated.View>

      <SourceBanner keys={['me']} />

      {queued > 0 && (
        <Card style={styles.notice} radius={18} flat fill={status.warn + '1f'}>
          <CloudOff size={18} color={status.warn} />
          <T variant="small" style={{ flex: 1 }}>
            {queued} report{queued === 1 ? ' is' : 's are'} saved on this phone and will send when you&apos;re back online.
          </T>
        </Card>
      )}

      {/* The hero: the campus right now, and how ready SOS is. Swipe between them. */}
      <Animated.View entering={reveal(1)}>
        <HeroCarousel
          ref={hero}
          slides={[
            {
              key: 'campus',
              label: 'campus',
              node: <CampusCard time={clock.time} next={clock.next} isDay={clock.isDay} walkers={data?.stats.active_walkers} safeSpaces={data?.stats.safe_spaces} />,
            },
            { key: 'ready', label: 'SOS readiness', node: <SafetyRing readiness={readiness} /> },
          ]}
        />
      </Animated.View>

      {/* Your walk, or a quiet invitation to request one. */}
      <Animated.View entering={reveal(2)}>
        {walk ? (
          <Card strong radius={26} onPress={() => router.navigate('/walks')} style={styles.focus} accessibilityLabel="Your walk">
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
          </Card>
        ) : (
          <Card radius={22} onPress={() => router.navigate('/walks')} style={styles.invite} accessibilityLabel="Request a walk">
            <View style={[styles.inviteIcon, { backgroundColor: theme.soft }]}>
              <Footprints size={20} color={theme.primary} />
            </View>
            <View style={{ flex: 1 }}>
              <T variant="label">Heading somewhere late?</T>
              <T variant="small" muted>
                A verified walker can meet you on campus.
              </T>
            </View>
            <View style={[styles.inviteGo, { backgroundColor: theme.primary }]}>
              <ChevronRight size={18} color={theme.primaryForeground} />
            </View>
          </Card>
        )}
      </Animated.View>

      <SectionTitle title="Safety tools" />
      <Animated.View entering={reveal(3)}>
        <SafetyTools contactName={data?.user.emergency_contact_name} />
      </Animated.View>

      <Animated.View entering={reveal(4)} style={{ gap: 12, marginTop: 4 }}>
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
                  <Pressable onPress={() => router.push('/reports')} style={styles.seeAll} accessibilityRole="button">
                    <T variant="small" color={theme.primary}>
                      See all reports
                    </T>
                    <ChevronRight size={14} color={theme.primary} />
                  </Pressable>
                </>
              ) : (
                <Empty icon={<Inbox size={24} color={theme.muted} />} text={isLoading ? 'Loading…' : "You haven't filed a report. If something happens, Report is in Safety tools above."} />
              )}
            </Card>
          </Animated.View>
        ) : (
          <Animated.View key="helplines" entering={FadeIn.duration(220)}>
            <Card radius={22} style={{ paddingHorizontal: 16 }}>
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
                    style={[styles.call, { backgroundColor: theme.soft }]}
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
            </Card>
          </Animated.View>
        )}
      </Animated.View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  subtitleLink: { flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 3, alignSelf: 'flex-start' },
  subtitleDot: { width: 7, height: 7, borderRadius: 4 },
  invite: { flexDirection: 'row', alignItems: 'center', gap: 12, padding: 14 },
  inviteIcon: { width: 44, height: 44, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  inviteGo: { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  notice: { flexDirection: 'row', alignItems: 'center', gap: 10, padding: 14 },
  focus: { padding: 18, gap: 14 },
  focusTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  route: { flexDirection: 'row', gap: 14 },
  routeLine: { alignItems: 'center', paddingVertical: 6 },
  routeDot: { width: 10, height: 10, borderRadius: 5 },
  routeDash: { width: 2, flex: 1, marginVertical: 4, borderRadius: 1, backgroundColor: 'rgba(127,127,127,0.22)' },
  hairline: { height: StyleSheet.hairlineWidth, backgroundColor: 'rgba(127,127,127,0.22)' },
  section: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 6, marginBottom: -4, paddingHorizontal: 4 },
  sectionAction: { flexDirection: 'row', alignItems: 'center', gap: 2 },
  seeAll: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 2, paddingVertical: 14, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: 'rgba(127,127,127,0.22)' },
  line: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 14 },
  lineDivider: { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: 'rgba(127,127,127,0.22)' },
  call: { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 12, height: 32, borderRadius: 16 },
});
