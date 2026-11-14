import * as Haptics from 'expo-haptics';
import { router } from 'expo-router';
import { ArrowUpRight, FilePlus, LocateFixed, PhoneIncoming, Timer } from 'lucide-react-native';
import { ReactNode, useEffect, useState } from 'react';
import { ActivityIndicator, Share, StyleProp, StyleSheet, View, ViewStyle } from 'react-native';
import Animated, { useAnimatedStyle, useSharedValue, withSpring } from 'react-native-reanimated';
import Svg, { Circle } from 'react-native-svg';

import { getFix, mapsLink } from '@/lib/location';
import { mmss, useCheckIn, useFakeCall } from '@/lib/tools';
import { status, useTheme } from '@/lib/theme';

import { Chip } from './bits';
import { Button } from './Button';
import { Card } from './Card';
import { Sheet } from './Sheet';
import { T } from './Text';
import { toast } from './Toast';

// Ticks once a second while something is counting down.
export function useNow(active: boolean) {
  const [now, setNow] = useState(() => Date.now());
  useEffect(() => {
    if (!active) return;
    const tick = () => setNow(Date.now());
    const first = setTimeout(tick, 0);
    const t = setInterval(tick, 1000);
    return () => {
      clearTimeout(first);
      clearInterval(t);
    };
  }, [active]);
  return now;
}

// On an active tile (filled with the accent) the switch inverts to stay visible.
function Switch({ on }: { on: boolean }) {
  const theme = useTheme();
  const v = useSharedValue(on ? 1 : 0);
  useEffect(() => {
    v.set(withSpring(on ? 1 : 0, { damping: 16, stiffness: 260 }));
  }, [on, v]);
  const knob = useAnimatedStyle(() => ({ transform: [{ translateX: 14 * v.value }] }));
  return (
    <View style={[styles.switch, { backgroundColor: on ? theme.primaryForeground : theme.line }]}>
      <Animated.View style={[styles.knob, { backgroundColor: on ? theme.primary : theme.surface }, knob]} />
    </View>
  );
}

type TileProps = {
  icon: ReactNode;
  title: string;
  detail: string;
  active?: boolean;
  corner?: ReactNode;
  onPress: () => void;
  label: string;
  style?: StyleProp<ViewStyle>;
  children?: ReactNode;
};

function Tile({ icon, title, detail, active, corner, onPress, label, style, children }: TileProps) {
  const theme = useTheme();
  return (
    <Card radius={24} onPress={onPress} accessibilityLabel={label} style={[styles.tile, style]} fill={active ? theme.primary : undefined}>
      <View style={styles.tileTop}>
        <View style={[styles.tileIcon, { backgroundColor: active ? theme.primaryForeground + '26' : theme.surfaceAlt }]}>{icon}</View>
        {corner ?? <ArrowUpRight size={16} color={active ? theme.primaryForeground : theme.muted} />}
      </View>
      {children}
      <View style={{ gap: 1 }}>
        <T variant="label" color={active ? theme.primaryForeground : theme.foreground} style={{ fontSize: 15 }}>
          {title}
        </T>
        <T variant="small" color={active ? theme.primaryForeground : theme.muted} numberOfLines={1} style={{ fontSize: 12, fontVariant: ['tabular-nums'], opacity: active ? 0.85 : 1 }}>
          {detail}
        </T>
      </View>
    </Card>
  );
}

const RING = 96;
const RING_R = (RING - 8) / 2;
const RING_C = 2 * Math.PI * RING_R;

// The check-in tile's dial: time left while a check-in runs, the last length used otherwise.
function Dial({ active, progress, label, unit }: { active: boolean; progress: number; label: string; unit: string }) {
  const theme = useTheme();
  const track = active ? theme.primaryForeground + '33' : theme.surfaceAlt;
  const ink = active ? theme.primaryForeground : theme.foreground;
  return (
    <View style={styles.dial}>
      <Svg width={RING} height={RING}>
        <Circle cx={RING / 2} cy={RING / 2} r={RING_R} stroke={track} strokeWidth={7} fill="none" />
        <Circle
          cx={RING / 2}
          cy={RING / 2}
          r={RING_R}
          stroke={active ? theme.primaryForeground : theme.primary}
          strokeWidth={7}
          strokeLinecap="round"
          fill="none"
          strokeDasharray={`${RING_C * Math.max(0.001, progress)} ${RING_C}`}
          transform={`rotate(-90 ${RING / 2} ${RING / 2})`}
        />
      </Svg>
      <View style={styles.dialCenter} pointerEvents="none">
        <T variant="title" color={ink} style={{ fontSize: 22, lineHeight: 25, fontVariant: ['tabular-nums'] }}>
          {label}
        </T>
        <T variant="small" color={ink} style={{ fontSize: 10.5, opacity: 0.7 }}>
          {unit}
        </T>
      </View>
    </View>
  );
}

const callers = ['Mum', 'Papa', 'Didi', 'Roommate'];
const delays = [
  { s: 0, label: 'Now' },
  { s: 10, label: 'In 10 s' },
  { s: 30, label: 'In 30 s' },
  { s: 60, label: 'In 1 min' },
];
const durations = [10, 20, 30, 45];

// Four things you can do in a few seconds when a walk or a situation feels off,
// laid out as a bento: the check-in dial large, the quick actions beside it.
export function SafetyTools({ contactName }: { contactName?: string | null }) {
  const theme = useTheme();
  const checkIn = useCheckIn();
  const fake = useFakeCall();
  const now = useNow(!!checkIn.endsAt || !!fake.at);
  const [sheet, setSheet] = useState<'call' | 'checkin' | null>(null);
  const [sharing, setSharing] = useState(false);

  const names = contactName && !callers.includes(contactName) ? [contactName, ...callers.slice(0, 3)] : callers;
  const [caller, setCaller] = useState(names[0]);
  const [delay, setDelay] = useState(10);
  const [minutes, setMinutes] = useState(checkIn.minutes);

  const left = checkIn.endsAt ? checkIn.endsAt - now : 0;
  const checking = !!checkIn.endsAt && left > 0;
  const ringsIn = fake.at ? fake.at - now : 0;

  const share = async () => {
    setSharing(true);
    try {
      const f = await getFix();
      if (!f) return toast('Location is off. Allow it to share where you are.', 'error');
      await Share.share({ message: `I'm sharing my location from SheShield: ${mapsLink(f)}` });
    } catch {
      toast('Could not get your location. Try again outside.', 'error');
    } finally {
      setSharing(false);
    }
  };

  const scheduleCall = () => {
    setSheet(null);
    Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
    fake.schedule(caller, delay);
    if (delay > 0) toast(`${caller} will call in ${delay < 60 ? `${delay} s` : '1 min'}`);
  };

  return (
    <View style={{ gap: 12 }}>
      <View style={styles.row}>
        <View style={{ flex: 1 }}>
          <Tile
            label={checking ? `Check-in, ${mmss(left)} left` : 'Check-in timer'}
            icon={<Timer size={19} color={checking ? theme.primaryForeground : theme.foreground} />}
            title="Check-in"
            detail={checking ? 'Tap to check in' : "We'll ask if you're OK"}
            active={checking}
            corner={<Switch on={checking} />}
            onPress={() => setSheet('checkin')}
            style={styles.tall}>
            <Dial
              active={checking}
              progress={checking ? 1 - left / (checkIn.minutes * 60_000) : 0}
              label={checking ? mmss(left) : String(checkIn.minutes)}
              unit={checking ? 'left' : 'min'}
            />
          </Tile>
        </View>
        <View style={{ flex: 1, gap: 12 }}>
          <Tile
            label="Fake call"
            icon={<PhoneIncoming size={19} color={fake.at ? theme.primaryForeground : theme.foreground} />}
            title="Fake call"
            detail={fake.at ? `${fake.caller} in ${mmss(ringsIn)}` : 'Your excuse to leave'}
            active={!!fake.at}
            onPress={() => setSheet('call')}
            style={styles.small}
          />
          <Tile
            label="Share my location"
            icon={sharing ? <ActivityIndicator size="small" color={theme.foreground} /> : <LocateFixed size={19} color={theme.foreground} />}
            title="Share location"
            detail={sharing ? 'Finding you…' : 'Send a map pin'}
            onPress={share}
            style={styles.small}
          />
        </View>
      </View>
      <Card radius={22} onPress={() => router.push('/report')} accessibilityLabel="Report an incident" style={styles.wide}>
        <View style={[styles.tileIcon, { backgroundColor: theme.soft }]}>
          <FilePlus size={19} color={theme.primary} />
        </View>
        <View style={{ flex: 1 }}>
          <T variant="label" style={{ fontSize: 15 }}>
            Report an incident
          </T>
          <T variant="small" muted style={{ fontSize: 12 }}>
            Anonymous option · works offline
          </T>
        </View>
        <View style={[styles.go, { backgroundColor: theme.selected }]}>
          <ArrowUpRight size={16} color={theme.selectedForeground} />
        </View>
      </Card>

      <Sheet
        open={sheet === 'call'}
        onClose={() => setSheet(null)}
        title="Fake call"
        subtitle="Your phone rings with a real-looking call, so you have a reason to step away."
        icon={<PhoneIncoming size={20} color={theme.primary} />}>
        {fake.at ? (
          <View style={{ gap: 14 }}>
            <T variant="display" style={{ fontSize: 40, lineHeight: 44, textAlign: 'center', fontVariant: ['tabular-nums'] }}>
              {mmss(ringsIn)}
            </T>
            <T muted style={{ textAlign: 'center' }}>
              until {fake.caller} calls
            </T>
            <Button
              label="Cancel the call"
              variant="ghost"
              onPress={() => {
                fake.cancel();
                setSheet(null);
              }}
            />
          </View>
        ) : (
          <>
            <View style={{ gap: 9 }}>
              <T variant="eyebrow">Who&apos;s calling</T>
              <View style={styles.chips}>
                {names.map((n) => (
                  <Chip key={n} label={n} selected={caller === n} onPress={() => setCaller(n)} />
                ))}
              </View>
            </View>
            <View style={{ gap: 9 }}>
              <T variant="eyebrow">When</T>
              <View style={styles.chips}>
                {delays.map((d) => (
                  <Chip key={d.s} label={d.label} selected={delay === d.s} onPress={() => setDelay(d.s)} />
                ))}
              </View>
            </View>
            <Button label={delay === 0 ? `Call me now` : `Schedule the call`} icon={<PhoneIncoming size={17} color={theme.primaryForeground} />} onPress={scheduleCall} />
          </>
        )}
      </Sheet>

      <Sheet
        open={sheet === 'checkin'}
        onClose={() => setSheet(null)}
        title="Check-in timer"
        subtitle="If you don't check in before it ends, SheShield asks if you're OK and offers SOS."
        icon={<Timer size={20} color={theme.primary} />}>
        {checking ? (
          <View style={{ gap: 14 }}>
            <T variant="display" style={{ fontSize: 48, lineHeight: 52, textAlign: 'center', fontVariant: ['tabular-nums'] }}>
              {mmss(left)}
            </T>
            <View style={[styles.bigBar, { backgroundColor: theme.surfaceAlt }]}>
              <View style={[styles.barFill, { backgroundColor: theme.primary, width: `${Math.round((1 - left / (checkIn.minutes * 60_000)) * 100)}%` }]} />
            </View>
            <View style={{ flexDirection: 'row', gap: 10 }}>
              <Button style={{ flex: 1 }} label="+10 min" variant="ghost" onPress={() => checkIn.extend(10)} />
              <Button
                style={{ flex: 1.4 }}
                label="I'm safe"
                onPress={() => {
                  checkIn.stop();
                  setSheet(null);
                  Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
                  toast('Checked in. Glad you made it.');
                }}
              />
            </View>
          </View>
        ) : (
          <>
            <View style={{ gap: 9 }}>
              <T variant="eyebrow">Check on me in</T>
              <View style={styles.chips}>
                {durations.map((m) => (
                  <Chip key={m} label={`${m} min`} selected={minutes === m} onPress={() => setMinutes(m)} />
                ))}
              </View>
            </View>
            <View style={[styles.hint, { borderColor: status.info + '40', backgroundColor: status.info + '14' }]}>
              <T variant="small" muted style={{ flex: 1 }}>
                Keep SheShield open in the background. The timer shows on Home until you check in.
              </T>
            </View>
            <Button
              label={`Start ${minutes} minute check-in`}
              icon={<Timer size={17} color={theme.primaryForeground} />}
              onPress={() => {
                checkIn.start(minutes);
                setSheet(null);
                Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
              }}
            />
          </>
        )}
      </Sheet>
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', gap: 12 },
  tile: { padding: 14, justifyContent: 'space-between' },
  tall: { height: 260 },
  small: { height: 124 },
  wide: { flexDirection: 'row', alignItems: 'center', gap: 12, padding: 14 },
  go: { width: 34, height: 34, borderRadius: 17, alignItems: 'center', justifyContent: 'center' },
  tileTop: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between' },
  tileIcon: { width: 40, height: 40, borderRadius: 13, alignItems: 'center', justifyContent: 'center' },
  dial: { alignSelf: 'center', width: RING, height: RING, alignItems: 'center', justifyContent: 'center' },
  dialCenter: { position: 'absolute', alignItems: 'center' },
  switch: { width: 34, height: 20, borderRadius: 10, padding: 2 },
  knob: { width: 16, height: 16, borderRadius: 8 },
  bigBar: { height: 6, borderRadius: 3, overflow: 'hidden' },
  barFill: { height: '100%', borderRadius: 3 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  hint: { flexDirection: 'row', padding: 12, borderRadius: 16, borderWidth: 1 },
});
