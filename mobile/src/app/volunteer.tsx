import * as Haptics from 'expo-haptics';
import { router } from 'expo-router';
import { BadgeCheck, IdCard, Phone, X } from 'lucide-react-native';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Chip } from '@/components/bits';
import { Button } from '@/components/Button';
import { Field } from '@/components/Field';
import { Glass } from '@/components/Glass';
import { Scene } from '@/components/Scene';
import { T } from '@/components/Text';
import { toast } from '@/components/Toast';
import { landmarks } from '@/lib/campus';
import { useMe, useRegisterWalker, useWalkerProfile, WalkerForm } from '@/lib/queries';
import { useTheme } from '@/lib/theme';

const roles = [
  { value: 'student', label: 'Student' },
  { value: 'staff', label: 'Staff' },
  { value: 'security', label: 'Security' },
] as const;
const genders = [
  { value: 'female', label: 'Female' },
  { value: 'male', label: 'Male' },
  { value: 'other', label: 'Other' },
] as const;
const idTypes = ['University ID', 'Staff ID', 'Government ID'] as const;
const fromTimes = ['06:00', '08:00', '12:00', '16:00', '18:00', '20:00'];
const untilTimes = ['10:00', '14:00', '18:00', '20:00', '22:00', '23:59'];

function Group({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View style={{ gap: 9 }}>
      <T variant="eyebrow">{title}</T>
      <View style={styles.chips}>{children}</View>
    </View>
  );
}

// Volunteer as a walker. New walkers stay unverified until an admin checks their ID,
// the same rule as on the website.
export default function Volunteer() {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const { data: me } = useMe();
  const { data: profile } = useWalkerProfile();
  const register = useRegisterWalker();

  const [type, setType] = useState<WalkerForm['type'] | null>(null);
  const [gender, setGender] = useState<WalkerForm['gender'] | null>(null);
  const [phone, setPhone] = useState(me?.user.phone ?? '');
  const [idType, setIdType] = useState<WalkerForm['idProofType']>('University ID');
  const [idNumber, setIdNumber] = useState('');
  const [from, setFrom] = useState(profile?.available_from?.slice(0, 5) ?? '18:00');
  const [until, setUntil] = useState(profile?.available_to?.slice(0, 5) ?? '22:00');
  const [areas, setAreas] = useState<string[]>(profile?.preferred_areas ?? []);

  const toggleArea = (name: string) => setAreas((a) => (a.includes(name) ? a.filter((x) => x !== name) : [...a, name]));

  const submit = () => {
    if (!type || !gender) return toast('Choose your role and gender.', 'error');
    if (phone.trim().length < 8) return toast('Add a phone number walkers can be reached on.', 'error');
    if (idNumber.trim().length < 3) return toast('Add your ID number. Only admins see it.', 'error');
    if (areas.length === 0) return toast('Choose at least one area you can cover.', 'error');
    if (from >= until) return toast('Your start time needs to be before your end time.', 'error');
    register.mutate(
      { phone: phone.trim(), type, gender, idProofType: idType, idProofNumber: idNumber.trim(), availableFrom: from, availableUntil: until, preferredAreas: areas },
      {
        onSuccess: () => {
          Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
          toast(profile ? 'Walker profile updated' : 'Thanks for volunteering. An admin will verify your ID.');
          router.back();
        },
        onError: (e) => toast(e.message, 'error'),
      },
    );
  };

  return (
    <Scene intensity={0.85}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={[styles.body, { paddingTop: insets.top + 12, paddingBottom: insets.bottom + 28 }]} keyboardShouldPersistTaps="handled">
          <View style={styles.head}>
            <View style={{ flex: 1 }}>
              <T variant="title">{profile ? 'Your walker profile' : 'Volunteer as a walker'}</T>
              <T muted style={{ marginTop: 4 }}>
                Walk with students who don&apos;t want to go alone. Verified by an admin before your first walk.
              </T>
            </View>
            <Pressable onPress={() => router.back()} hitSlop={12} style={styles.close} accessibilityLabel="Close">
              <X size={20} color={theme.foreground} />
            </Pressable>
          </View>

          <Animated.View entering={FadeInDown.springify().damping(20)}>
            <Glass radius={24} style={styles.card}>
              <Group title="You are">
                {roles.map((r) => (
                  <Chip key={r.value} label={r.label} selected={type === r.value} onPress={() => setType(r.value)} />
                ))}
              </Group>
              <Group title="Gender">
                {genders.map((g) => (
                  <Chip key={g.value} label={g.label} selected={gender === g.value} onPress={() => setGender(g.value)} />
                ))}
              </Group>
              <Field label="Phone" value={phone} onChangeText={setPhone} placeholder="+91 98765 43210" keyboardType="phone-pad" icon={<Phone size={17} color={theme.muted} />} />
            </Glass>
          </Animated.View>

          <Animated.View entering={FadeInDown.delay(60).springify().damping(20)}>
            <Glass radius={24} style={styles.card}>
              <Group title="ID for verification">
                {idTypes.map((t) => (
                  <Chip key={t} label={t} selected={idType === t} onPress={() => setIdType(t)} />
                ))}
              </Group>
              <Field label="ID number" value={idNumber} onChangeText={setIdNumber} placeholder="Only admins can see this" autoCapitalize="characters" icon={<IdCard size={17} color={theme.muted} />} />
            </Glass>
          </Animated.View>

          <Animated.View entering={FadeInDown.delay(120).springify().damping(20)}>
            <Glass radius={24} style={styles.card}>
              <Group title="Available from">
                {fromTimes.map((t) => (
                  <Chip key={t} label={t} selected={from === t} onPress={() => setFrom(t)} />
                ))}
              </Group>
              <Group title="Until">
                {untilTimes.map((t) => (
                  <Chip key={t} label={t} selected={until === t} onPress={() => setUntil(t)} />
                ))}
              </Group>
              <Group title={`Areas you can cover${areas.length ? ` · ${areas.length}` : ''}`}>
                {landmarks.map((l) => (
                  <Chip key={l.name} label={l.name} selected={areas.includes(l.name)} onPress={() => toggleArea(l.name)} />
                ))}
              </Group>
            </Glass>
          </Animated.View>

          <Button label={profile ? 'Save changes' : 'Volunteer'} size="lg" icon={<BadgeCheck size={18} color={theme.primaryForeground} />} loading={register.isPending} onPress={submit} />
        </ScrollView>
      </KeyboardAvoidingView>
    </Scene>
  );
}

const styles = StyleSheet.create({
  body: { paddingHorizontal: 18, gap: 14 },
  head: { flexDirection: 'row', alignItems: 'flex-start', gap: 12, marginBottom: 2 },
  close: { width: 42, height: 42, borderRadius: 21, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(255,255,255,0.08)' },
  card: { padding: 16, gap: 16 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
});
