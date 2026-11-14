import Constants from 'expo-constants';
import * as Haptics from 'expo-haptics';
import * as ImagePicker from 'expo-image-picker';
import { Camera, HeartHandshake, LogOut, Palette, Phone, Server, ShieldCheck, UserRound } from 'lucide-react-native';
import { useState } from 'react';
import { ActivityIndicator, Alert, Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';

import { Avatar, Badge, SectionHeader } from '@/components/bits';
import { Button } from '@/components/Button';
import { Field } from '@/components/Field';
import { Card } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { SourceBanner } from '@/components/SourceBanner';
import { T } from '@/components/Text';
import { ThemePicker } from '@/components/ThemePicker';
import { toast } from '@/components/Toast';
import { api } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { absoluteUrl, API_URL } from '@/lib/config';
import { parseServerDate } from '@/lib/format';
import { Me, useMe, useUpdateProfile, useUploadPhoto } from '@/lib/queries';
import { themes, useTheme, useThemeStore } from '@/lib/theme';

// Starts from the saved values; keyed by user so a different account gets a fresh form.
function ContactForm({ user }: { user: Me['user'] }) {
  const theme = useTheme();
  const update = useUpdateProfile();
  const [contactName, setContactName] = useState(user.emergency_contact_name);
  const [contactPhone, setContactPhone] = useState(user.emergency_contact_phone);
  const [phone, setPhone] = useState(user.phone);

  const save = () =>
    update.mutate(
      { first_name: user.first_name, last_name: user.last_name, phone: phone.trim(), emergency_contact_name: contactName.trim(), emergency_contact_phone: contactPhone.trim() },
      {
        onSuccess: () => {
          Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
          toast('Saved. SOS will offer to call this person.');
        },
        onError: (e) => toast(e.message, 'error'),
      },
    );

  return (
    <Card radius={26} style={{ padding: 16, gap: 14 }}>
      <View style={styles.note}>
        <HeartHandshake size={18} color={theme.primary} />
        <T variant="small" muted style={{ flex: 1 }}>
          SOS will offer to call this person right after the alert goes out.
        </T>
      </View>
      <Field label="Their name" value={contactName} onChangeText={setContactName} placeholder="Mum" icon={<UserRound size={17} color={theme.muted} />} />
      <Field label="Their phone" value={contactPhone} onChangeText={setContactPhone} placeholder="+91 98765 43210" keyboardType="phone-pad" icon={<Phone size={17} color={theme.muted} />} />
      <Field label="Your phone" value={phone} onChangeText={setPhone} placeholder="+91" keyboardType="phone-pad" icon={<Phone size={17} color={theme.muted} />} />
      <Button label="Save" loading={update.isPending} onPress={save} />
    </Card>
  );
}

export default function Profile() {
  const upload = useUploadPhoto();
  // Square-cropped, compressed photo from the gallery; the server accepts JPG or PNG up to 5 MB.
  const changePhoto = async () => {
    const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perm.granted) return toast('Photo access is off. Allow it in settings to change your photo.', 'error');
    const res = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], allowsEditing: true, aspect: [1, 1], quality: 0.7 });
    if (res.canceled) return;
    upload.mutate(res.assets[0].uri, {
      onSuccess: () => {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        toast('Photo updated');
      },
      onError: (e) => toast(e.message, 'error'),
    });
  };
  const theme = useTheme();
  const themeName = useThemeStore((s) => s.name);
  const { data, refetch, isRefetching } = useMe();
  const signOut = useAuth((s) => s.signOut);

  const u = data?.user;
  const fullName = u ? `${u.first_name} ${u.last_name}`.trim() : '';
  const since = parseServerDate(u?.member_since)?.toLocaleDateString('en-IN', { month: 'long', year: 'numeric' });

  const confirmSignOut = () =>
    Alert.alert('Sign out?', 'You will need your password to sign back in.', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Sign out',
        style: 'destructive',
        onPress: async () => {
          // Revoke the token on the server too, then forget it here even if the server is unreachable.
          api('api/auth/logout.php', { method: 'POST', timeoutMs: 8000 }).catch(() => {});
          await signOut();
        },
      },
    ]);

  return (
    <Screen refreshing={isRefetching} onRefresh={refetch}>
      <SourceBanner keys={["me"]} />
      <Animated.View entering={FadeInDown.springify().damping(18)}>
        <Card radius={30} strong style={styles.hero}>
          <Pressable onPress={changePhoto} disabled={upload.isPending} accessibilityRole="button" accessibilityLabel="Change profile photo" hitSlop={6}>
            <Avatar uri={absoluteUrl(u?.photo)} name={fullName} size={78} />
            <View style={[styles.camera, { backgroundColor: theme.primary, borderColor: theme.surface }]}>
              {upload.isPending ? <ActivityIndicator size="small" color={theme.primaryForeground} /> : <Camera size={14} color={theme.primaryForeground} />}
            </View>
          </Pressable>
          <View style={{ flex: 1, gap: 4 }}>
            <T variant="title" numberOfLines={1}>
              {fullName || '…'}
            </T>
            <T variant="small" muted numberOfLines={1}>
              {u?.email}
            </T>
            <View style={{ flexDirection: 'row', gap: 6, marginTop: 4, flexWrap: 'wrap' }}>
              {u?.is_admin ? <Badge value="admin" /> : null}
              {since ? (
                <T variant="small" muted>
                  Member since {since}
                </T>
              ) : null}
            </View>
          </View>
        </Card>
      </Animated.View>

      <Animated.View entering={FadeInDown.delay(60).springify().damping(18)}>
        <Card radius={22} flat style={styles.stats}>
          {[
            ['Reports', data?.stats.reports ?? 0],
            ['Resolved', data?.stats.resolved ?? 0],
            ['Pending', data?.stats.pending ?? 0],
          ].map(([label, value], i) => (
            <View key={label as string} style={[styles.statBox, i > 0 && styles.statDivider]}>
              <T variant="title" style={{ fontSize: 24, lineHeight: 28 }}>
                {value as number}
              </T>
              <T variant="small" muted>
                {label as string}
              </T>
            </View>
          ))}
        </Card>
      </Animated.View>

      <View style={styles.themeHead}>
        <T variant="label" muted style={{ fontSize: 13, letterSpacing: 0.2 }}>
          Theme
        </T>
        <T variant="small" muted>
          {themes[themeName].name} · {themes[themeName].mode === 'light' ? 'Light' : 'Dark'}
        </T>
      </View>
      <Animated.View entering={FadeInDown.delay(100).springify().damping(18)}>
        <ThemePicker />
      </Animated.View>

      <SectionHeader title="Emergency contact" />
      <Animated.View entering={FadeInDown.delay(140).springify().damping(18)}>
        {u ? <ContactForm key={u.id} user={u} /> : null}
      </Animated.View>

      <SectionHeader title="About" />
      <Card radius={24} style={{ padding: 16, gap: 12 }}>
        <View style={styles.aboutRow}>
          <ShieldCheck size={17} color={theme.muted} />
          <T variant="small" style={{ flex: 1 }}>
            SheShield {Constants.expoConfig?.version ?? ''}
          </T>
        </View>
        <View style={styles.aboutRow}>
          <Server size={17} color={theme.muted} />
          <T variant="small" muted style={{ flex: 1 }} numberOfLines={1}>
            {API_URL.replace(/^https?:\/\//, '')}
          </T>
        </View>
        <View style={styles.aboutRow}>
          <Palette size={17} color={theme.muted} />
          <T variant="small" muted style={{ flex: 1 }}>
            Built by students at Lovely Professional University
          </T>
        </View>
      </Card>

      <Button label="Sign out" variant="ghost" icon={<LogOut size={17} color={theme.foreground} />} onPress={confirmSignOut} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  camera: { position: 'absolute', right: -2, bottom: -2, width: 28, height: 28, borderRadius: 14, alignItems: 'center', justifyContent: 'center', borderWidth: 2 },
  hero: { flexDirection: 'row', alignItems: 'center', gap: 15, padding: 18 },
  stats: { flexDirection: 'row', paddingVertical: 14 },
  statBox: { flex: 1, alignItems: 'center', gap: 2 },
  statDivider: { borderLeftWidth: StyleSheet.hairlineWidth, borderLeftColor: 'rgba(127,127,127,0.22)' },
  themeHead: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 6, marginBottom: -6, paddingHorizontal: 4 },
  note: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  aboutRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
});
