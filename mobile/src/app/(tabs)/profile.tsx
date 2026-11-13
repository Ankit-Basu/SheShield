import Constants from 'expo-constants';
import * as Haptics from 'expo-haptics';
import { LinearGradient } from 'expo-linear-gradient';
import * as ImagePicker from 'expo-image-picker';
import { Camera, Check, HeartHandshake, LogOut, Palette, Phone, Server, ShieldCheck, UserRound } from 'lucide-react-native';
import { useState } from 'react';
import { ActivityIndicator, Alert, Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';

import { Avatar, Badge, SectionHeader } from '@/components/bits';
import { Button } from '@/components/Button';
import { Field } from '@/components/Field';
import { Glass } from '@/components/Glass';
import { Screen } from '@/components/Screen';
import { SourceBanner } from '@/components/SourceBanner';
import { T } from '@/components/Text';
import { toast } from '@/components/Toast';
import { api } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { absoluteUrl, API_URL } from '@/lib/config';
import { parseServerDate } from '@/lib/format';
import { Me, useMe, useUpdateProfile, useUploadPhoto } from '@/lib/queries';
import { ThemeName, themes, useTheme, useThemeStore } from '@/lib/theme';

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
    <Glass radius={26} style={{ padding: 16, gap: 14 }}>
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
    </Glass>
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
  const { name: themeName, setTheme } = useThemeStore();
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
        <Glass radius={30} strong style={styles.hero} tint={[theme.primary + '22', 'rgba(255,255,255,0.02)']}>
          <Pressable onPress={changePhoto} disabled={upload.isPending} accessibilityRole="button" accessibilityLabel="Change profile photo" hitSlop={6}>
            <Avatar uri={absoluteUrl(u?.photo)} name={fullName} size={78} />
            <View style={[styles.camera, { backgroundColor: theme.primary }]}>
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
        </Glass>
      </Animated.View>

      <Animated.View entering={FadeInDown.delay(60).springify().damping(18)}>
        <Glass radius={22} flat style={styles.stats}>
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
        </Glass>
      </Animated.View>

      <SectionHeader title="Theme" />
      <Animated.View entering={FadeInDown.delay(100).springify().damping(18)} style={styles.themes}>
        {(Object.keys(themes) as ThemeName[]).map((k) => {
          const t = themes[k];
          const on = themeName === k;
          return (
            <Pressable
              key={k}
              onPress={() => {
                Haptics.selectionAsync();
                setTheme(k);
              }}
              style={{ flex: 1 }}
              accessibilityRole="radio"
              accessibilityState={{ checked: on }}
              accessibilityLabel={`${t.name} theme`}>
              <View style={[styles.swatch, { borderColor: on ? t.primary : 'rgba(255,255,255,0.12)' }]}>
                <LinearGradient colors={[t.aurora[1], t.aurora[0], t.base]} style={StyleSheet.absoluteFill} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }} />
                <View style={[styles.swatchDot, { backgroundColor: t.primary }]}>{on ? <Check size={14} color={t.primaryForeground} strokeWidth={3} /> : null}</View>
                <T variant="small" color="#fff" style={{ fontSize: 11.5 }}>
                  {t.name}
                </T>
              </View>
            </Pressable>
          );
        })}
      </Animated.View>

      <SectionHeader title="Emergency contact" />
      <Animated.View entering={FadeInDown.delay(140).springify().damping(18)}>
        {u ? <ContactForm key={u.id} user={u} /> : null}
      </Animated.View>

      <SectionHeader title="About" />
      <Glass radius={24} style={{ padding: 16, gap: 12 }}>
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
      </Glass>

      <Button label="Sign out" variant="ghost" icon={<LogOut size={17} color={theme.foreground} />} onPress={confirmSignOut} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  camera: { position: 'absolute', right: -2, bottom: -2, width: 28, height: 28, borderRadius: 14, alignItems: 'center', justifyContent: 'center', borderWidth: 2, borderColor: 'rgba(20,12,18,0.9)' },
  hero: { flexDirection: 'row', alignItems: 'center', gap: 15, padding: 18 },
  stats: { flexDirection: 'row', paddingVertical: 14 },
  statBox: { flex: 1, alignItems: 'center', gap: 2 },
  statDivider: { borderLeftWidth: StyleSheet.hairlineWidth, borderLeftColor: 'rgba(255,255,255,0.16)' },
  themes: { flexDirection: 'row', gap: 10 },
  swatch: { height: 104, borderRadius: 20, borderWidth: 2, overflow: 'hidden', padding: 11, justifyContent: 'space-between' },
  swatchDot: { width: 26, height: 26, borderRadius: 13, alignItems: 'center', justifyContent: 'center' },
  note: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  aboutRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
});
