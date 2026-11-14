import * as Clipboard from 'expo-clipboard';
import * as Haptics from 'expo-haptics';
import { router } from 'expo-router';
import { Check, CircleAlert, Copy, LoaderCircle, MapPin, MessageSquare, Phone, Share2, X } from 'lucide-react-native';
import { useEffect, useState } from 'react';
import { Linking, Pressable, Share, StyleSheet, View } from 'react-native';
import Animated, { Easing, FadeIn, FadeInDown, useAnimatedStyle, useSharedValue, withRepeat, withTiming, ZoomIn } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';

import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { HoldToSend } from '@/components/HoldToSend';
import { T } from '@/components/Text';
import { api } from '@/lib/api';
import { Fix, getFix } from '@/lib/location';
import { useMe } from '@/lib/queries';
import { status } from '@/lib/theme';

type Phase = 'idle' | 'locating' | 'sending' | 'sent' | 'failed';
function Spinner({ color }: { color: string }) {
  const r = useSharedValue(0);
  useEffect(() => {
    r.set(withRepeat(withTiming(360, { duration: 900, easing: Easing.linear }), -1));
  }, [r]);
  const s = useAnimatedStyle(() => ({ transform: [{ rotate: `${r.value}deg` }] }));
  return (
    <Animated.View style={s}>
      <LoaderCircle size={18} color={color} />
    </Animated.View>
  );
}

function Step({ done, active, label }: { done: boolean; active: boolean; label: string }) {
  return (
    <View style={styles.step}>
      <View style={[styles.stepDot, done && { backgroundColor: status.good }]}>
        {done ? <Check size={13} color="#0b2a17" strokeWidth={3} /> : active ? <Spinner color="#fff" /> : null}
      </View>
      <T variant="label" color={done || active ? '#fff' : 'rgba(255,255,255,0.5)'}>
        {label}
      </T>
    </View>
  );
}

export default function Sos() {
  const insets = useSafeAreaInsets();
  const { data: me } = useMe();
  const [phase, setPhase] = useState<Phase>('idle');
  const [fix, setFix] = useState<Fix | null>(null);
  const [error, setError] = useState('');

  const mapsLink = fix ? `https://maps.google.com/?q=${fix.lat.toFixed(6)},${fix.lng.toFixed(6)}` : '';
  const message = fix
    ? `SOS from ${me?.user.first_name ?? 'SheShield'}: I need help. My location: ${mapsLink}`
    : `SOS from ${me?.user.first_name ?? 'SheShield'}: I need help. I'm on the LPU campus.`;
  const contact = me?.user.emergency_contact_phone;

  const send = async () => {
    setError('');
    setPhase('locating');
    const f = await getFix();
    setFix(f);
    setPhase('sending');
    try {
      await api('api/emergency/alert.php', { json: { latitude: f?.lat ?? null, longitude: f?.lng ?? null }, timeoutMs: 20000 });
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
      setPhase('sent');
    } catch (e: any) {
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      setError(e.message);
      setPhase('failed');
    }
  };

  const busy = phase === 'locating' || phase === 'sending';
  const done = phase === 'sent' || phase === 'failed';

  return (
    <View style={{ flex: 1, backgroundColor: '#2a070c' }}>
      <StatusBar style="light" />
      <View style={[styles.body, { paddingTop: insets.top + 10, paddingBottom: insets.bottom + 20 }]}>
        <View style={styles.head}>
          <View>
            <T variant="eyebrow" color="rgba(255,255,255,0.7)">
              Emergency
            </T>
            <T variant="title" color="#fff">
              {phase === 'sent' ? 'Help is on the way.' : phase === 'failed' ? 'Use these now.' : 'Hold to alert.'}
            </T>
          </View>
          <Pressable onPress={() => router.back()} hitSlop={12} style={styles.close} accessibilityLabel="Close SOS">
            <X size={22} color="#fff" />
          </Pressable>
        </View>

        {!done ? (
          <View style={styles.center}>
            <HoldToSend onComplete={send} disabled={busy} label={busy ? 'Sending…' : 'Hold 2 seconds'} />
            <Animated.View entering={FadeIn.delay(200)} style={{ gap: 10, alignSelf: 'stretch' }}>
              <Step done={phase === 'sending'} active={phase === 'locating'} label="Find your location" />
              <Step done={false} active={phase === 'sending'} label="Alert campus security" />
              <T variant="small" color="rgba(255,255,255,0.65)" style={{ textAlign: 'center', marginTop: 6 }}>
                Holding avoids sending by accident. You can still call 112 directly below.
              </T>
            </Animated.View>
          </View>
        ) : (
          <Animated.View entering={FadeInDown.springify().damping(18)} style={styles.center}>
            <Animated.View entering={ZoomIn.springify()} style={[styles.result, { backgroundColor: phase === 'sent' ? status.good : '#ffb020' }]}>
              {phase === 'sent' ? <Check size={44} color="#0b2a17" strokeWidth={3} /> : <CircleAlert size={44} color="#3a2600" strokeWidth={2.5} />}
            </Animated.View>
            <T color="#fff" style={{ textAlign: 'center', fontSize: 16 }}>
              {phase === 'sent'
                ? 'Your alert and location reached SheShield. Call 112 too if you are in danger.'
                : `The alert could not be sent (${error}). Call or text instead; these work without SheShield.`}
            </T>
            {fix ? (
              <Card radius={18} style={styles.coords} fill="rgba(255,255,255,0.12)">
                <MapPin size={16} color="#fff" />
                <T variant="mono" color="#fff" style={{ flex: 1 }}>
                  {fix.lat.toFixed(5)}, {fix.lng.toFixed(5)}
                  {fix.accuracy ? `  ±${Math.round(fix.accuracy)} m` : ''}
                </T>
                <Pressable
                  onPress={async () => {
                    await Clipboard.setStringAsync(mapsLink);
                    Haptics.selectionAsync();
                  }}
                  hitSlop={10}
                  accessibilityLabel="Copy location link">
                  <Copy size={16} color="#fff" />
                </Pressable>
              </Card>
            ) : (
              <T variant="small" color="rgba(255,255,255,0.7)">
                Location was not available. Turn on location for future alerts.
              </T>
            )}
          </Animated.View>
        )}

        <View style={{ gap: 10 }}>
          <Button label="Call 112" variant="danger" size="lg" icon={<Phone size={18} color="#fff" />} onPress={() => Linking.openURL('tel:112')} />
          <View style={{ flexDirection: 'row', gap: 10 }}>
            {contact ? (
              <Button
                label={`Call ${me?.user.emergency_contact_name || 'contact'}`}
                variant="onDark"
                style={{ flex: 1 }}
                icon={<Phone size={16} color="#fff" />}
                onPress={() => Linking.openURL(`tel:${contact}`)}
              />
            ) : null}
            <Button
              label={contact ? 'Text them' : 'Share location'}
              variant="onDark"
              style={{ flex: 1 }}
              icon={contact ? <MessageSquare size={16} color="#fff" /> : <Share2 size={16} color="#fff" />}
              onPress={() =>
                contact ? Linking.openURL(`sms:${contact}?body=${encodeURIComponent(message)}`) : Share.share({ message })
              }
            />
          </View>
          {contact ? <Button label="Share location with anyone" variant="onDark" icon={<Share2 size={16} color="#fff" />} onPress={() => Share.share({ message })} /> : null}
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  body: { flex: 1, paddingHorizontal: 20, justifyContent: 'space-between' },
  head: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  close: { width: 44, height: 44, borderRadius: 22, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(255,255,255,0.12)' },
  center: { alignItems: 'center', gap: 22 },
  step: { flexDirection: 'row', alignItems: 'center', gap: 10, alignSelf: 'center', width: 230 },
  stepDot: { width: 24, height: 24, borderRadius: 12, alignItems: 'center', justifyContent: 'center', borderWidth: 1.5, borderColor: 'rgba(255,255,255,0.4)' },
  result: { width: 96, height: 96, borderRadius: 48, alignItems: 'center', justifyContent: 'center' },
  coords: { flexDirection: 'row', alignItems: 'center', gap: 10, padding: 14, alignSelf: 'stretch' },
});
