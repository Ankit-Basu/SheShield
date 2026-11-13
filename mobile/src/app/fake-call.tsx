import { LinearGradient } from 'expo-linear-gradient';
import { router, useLocalSearchParams } from 'expo-router';
import { Grid3x3, MicOff, Pause, Phone, PhoneOff, Plus, Video, Volume2 } from 'lucide-react-native';
import { useEffect, useState } from 'react';
import { Pressable, StyleSheet, Vibration, View } from 'react-native';
import Animated, { FadeIn, useAnimatedStyle, useSharedValue, withRepeat, withTiming } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { T } from '@/components/Text';

const GREEN = '#2fc062';
const RED = '#ef3b3b';

function Round({ color, onPress, label, children, size = 72 }: { color: string; onPress: () => void; label: string; children: React.ReactNode; size?: number }) {
  return (
    <View style={{ alignItems: 'center', gap: 8 }}>
      <Pressable onPress={onPress} accessibilityRole="button" accessibilityLabel={label} style={({ pressed }) => [{ width: size, height: size, borderRadius: size / 2, backgroundColor: color, alignItems: 'center', justifyContent: 'center', opacity: pressed ? 0.8 : 1 }]}>
        {children}
      </Pressable>
      <T variant="small" color="rgba(255,255,255,0.85)">
        {label}
      </T>
    </View>
  );
}

// A believable incoming call: deliberately looks like the phone's own call screen,
// with nothing that says SheShield. Rings (vibrates) until answered or declined.
export default function FakeCall() {
  const insets = useSafeAreaInsets();
  const { caller = 'Mum' } = useLocalSearchParams<{ caller?: string }>();
  const [answeredAt, setAnsweredAt] = useState<number | null>(null);
  const [now, setNow] = useState(() => Date.now());
  const [toggles, setToggles] = useState<Record<string, boolean>>({});

  const pulse = useSharedValue(0);
  useEffect(() => {
    pulse.set(withRepeat(withTiming(1, { duration: 1600 }), -1));
  }, [pulse]);
  const ring = (k: number) => {
    'worklet';
    return { opacity: 0.35 * (1 - ((pulse.value + k) % 1)), transform: [{ scale: 1 + 0.6 * ((pulse.value + k) % 1) }] };
  };
  const ringA = useAnimatedStyle(() => ring(0));
  const ringB = useAnimatedStyle(() => ring(0.5));

  useEffect(() => {
    if (answeredAt) return;
    Vibration.vibrate([0, 900, 700], true);
    return () => Vibration.cancel();
  }, [answeredAt]);

  useEffect(() => {
    if (!answeredAt) return;
    const t = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(t);
  }, [answeredAt]);

  const end = () => {
    Vibration.cancel();
    router.back();
  };
  const secs = answeredAt ? Math.floor((now - answeredAt) / 1000) : 0;
  const initials = caller.split(' ').map((w) => w[0]).join('').slice(0, 2).toUpperCase();

  const controls = [
    { k: 'mute', label: 'Mute', Icon: MicOff },
    { k: 'keypad', label: 'Keypad', Icon: Grid3x3 },
    { k: 'speaker', label: 'Speaker', Icon: Volume2 },
    { k: 'add', label: 'Add call', Icon: Plus },
    { k: 'video', label: 'Video', Icon: Video },
    { k: 'hold', label: 'Hold', Icon: Pause },
  ];

  return (
    <View style={styles.fill}>
      <LinearGradient colors={['#1b2330', '#10141c', '#07090d']} style={StyleSheet.absoluteFill} />
      <View style={[styles.top, { paddingTop: insets.top + 56 }]}>
        <T variant="small" color="rgba(255,255,255,0.7)">
          {answeredAt ? `${Math.floor(secs / 60)}:${String(secs % 60).padStart(2, '0')}` : 'Incoming call'}
        </T>
        <T variant="display" color="#fff" style={{ fontSize: 38, lineHeight: 44, textAlign: 'center' }}>
          {caller}
        </T>
        <T variant="small" color="rgba(255,255,255,0.6)">
          Mobile · India
        </T>
        <View style={styles.avatarWrap}>
          {!answeredAt ? (
            <>
              <Animated.View style={[styles.pulse, ringA]} />
              <Animated.View style={[styles.pulse, ringB]} />
            </>
          ) : null}
          <LinearGradient colors={['#6b7a90', '#3d4656']} style={styles.avatar}>
            <T variant="display" color="#fff" style={{ fontSize: 40, lineHeight: 46 }}>
              {initials}
            </T>
          </LinearGradient>
        </View>
      </View>

      {answeredAt ? (
        <Animated.View entering={FadeIn.duration(250)} style={[styles.bottom, { paddingBottom: insets.bottom + 40 }]}>
          <View style={styles.grid}>
            {controls.map(({ k, label, Icon }) => {
              const on = !!toggles[k];
              return (
                <Pressable key={k} onPress={() => setToggles((t) => ({ ...t, [k]: !t[k] }))} style={styles.control} accessibilityRole="button" accessibilityState={{ selected: on }} accessibilityLabel={label}>
                  <View style={[styles.controlIcon, { backgroundColor: on ? '#fff' : 'rgba(255,255,255,0.14)' }]}>
                    <Icon size={24} color={on ? '#111' : '#fff'} />
                  </View>
                  <T variant="small" color="rgba(255,255,255,0.8)">
                    {label}
                  </T>
                </Pressable>
              );
            })}
          </View>
          <Round color={RED} onPress={end} label="End">
            <PhoneOff size={28} color="#fff" />
          </Round>
        </Animated.View>
      ) : (
        <View style={[styles.answerRow, { paddingBottom: insets.bottom + 56 }]}>
          <Round color={RED} onPress={end} label="Decline">
            <PhoneOff size={28} color="#fff" />
          </Round>
          <Round
            color={GREEN}
            onPress={() => {
              Vibration.cancel();
              setNow(Date.now());
              setAnsweredAt(Date.now());
            }}
            label="Accept">
            <Phone size={28} color="#fff" />
          </Round>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  fill: { flex: 1, backgroundColor: '#07090d' },
  top: { alignItems: 'center', gap: 6 },
  avatarWrap: { marginTop: 48, width: 132, height: 132, alignItems: 'center', justifyContent: 'center' },
  pulse: { position: 'absolute', width: 132, height: 132, borderRadius: 66, backgroundColor: '#ffffff' },
  avatar: { width: 132, height: 132, borderRadius: 66, alignItems: 'center', justifyContent: 'center' },
  answerRow: { position: 'absolute', left: 0, right: 0, bottom: 0, flexDirection: 'row', justifyContent: 'space-around', paddingHorizontal: 30 },
  bottom: { position: 'absolute', left: 0, right: 0, bottom: 0, alignItems: 'center', gap: 34 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', rowGap: 24, width: 300 },
  control: { width: 84, alignItems: 'center', gap: 8 },
  controlIcon: { width: 68, height: 68, borderRadius: 34, alignItems: 'center', justifyContent: 'center' },
});
