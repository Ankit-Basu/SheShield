import { useQueryClient } from '@tanstack/react-query';
import * as Haptics from 'expo-haptics';
import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import * as Location from 'expo-location';
import { router, useLocalSearchParams } from 'expo-router';
import * as Icons from 'lucide-react-native';
import { Camera, EyeOff, ImagePlus, LocateFixed, X } from 'lucide-react-native';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Switch, View } from 'react-native';
import Animated, { FadeInDown, ZoomIn } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Scene } from '@/components/Scene';
import { Chip } from '@/components/bits';
import { Button } from '@/components/Button';
import { Field } from '@/components/Field';
import { Glass } from '@/components/Glass';
import { T } from '@/components/Text';
import { toast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { incidentTypes, landmarks } from '@/lib/campus';
import { istLocalString } from '@/lib/format';
import { ReportDraft, sendReport, useReportQueue } from '@/lib/offline';
import { keys } from '@/lib/queries';
import { useTheme } from '@/lib/theme';

const times = [
  { label: 'Just now', hours: 0 },
  { label: '1 hour ago', hours: 1 },
  { label: 'Earlier today', hours: 4 },
  { label: 'Yesterday', hours: 24 },
];
const severities = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
];

export default function Report() {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const qc = useQueryClient();
  const params = useLocalSearchParams<{ location?: string }>();
  const addToQueue = useReportQueue((s) => s.add);

  const [type, setType] = useState<string | null>(null);
  const [severity, setSeverity] = useState('medium');
  const [hoursAgo, setHoursAgo] = useState(0);
  const [location, setLocation] = useState(params.location ?? '');
  const [description, setDescription] = useState('');
  const [anonymous, setAnonymous] = useState(false);
  const [photos, setPhotos] = useState<string[]>([]);
  const [busy, setBusy] = useState(false);
  const [locating, setLocating] = useState(false);
  const [doneId, setDoneId] = useState<string | null>(null);

  const pick = async (camera: boolean) => {
    const perm = camera ? await ImagePicker.requestCameraPermissionsAsync() : await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perm.granted) return toast(camera ? 'Camera permission is off.' : 'Photo access is off.', 'error');
    const opts: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], quality: 0.6, allowsMultipleSelection: !camera, selectionLimit: 3 - photos.length };
    const res = camera ? await ImagePicker.launchCameraAsync(opts) : await ImagePicker.launchImageLibraryAsync(opts);
    if (!res.canceled) setPhotos((p) => [...p, ...res.assets.map((a) => a.uri)].slice(0, 3));
  };

  const useMyLocation = async () => {
    setLocating(true);
    try {
      const { granted } = await Location.requestForegroundPermissionsAsync();
      if (!granted) return toast('Location permission is off.', 'error');
      const p = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
      // Name the nearest landmark when within ~250 m, otherwise use coordinates.
      const near = [...landmarks]
        .map((l) => ({ l, d: Math.hypot((l.lat - p.coords.latitude) * 111000, (l.lng - p.coords.longitude) * 95000) }))
        .sort((a, b) => a.d - b.d)[0];
      setLocation(near && near.d < 250 ? `Near ${near.l.name} (${p.coords.latitude.toFixed(5)}, ${p.coords.longitude.toFixed(5)})` : `${p.coords.latitude.toFixed(5)}, ${p.coords.longitude.toFixed(5)}`);
    } catch {
      toast('Could not get your location.', 'error');
    } finally {
      setLocating(false);
    }
  };

  const submit = async () => {
    if (!type) return toast('Choose what happened.', 'error');
    if (!location.trim()) return toast('Add where it happened.', 'error');
    if (description.trim().length < 10) return toast('Add a few words about what happened.', 'error');
    const draft: ReportDraft = {
      id: `${Date.now()}`,
      incident_type: type,
      description: description.trim(),
      location: location.trim(),
      date_time: istLocalString(new Date(Date.now() - hoursAgo * 3600000)),
      severity,
      is_anonymous: anonymous,
      photos,
      savedAt: Date.now(),
    };
    setBusy(true);
    try {
      const res = await sendReport(draft);
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
      qc.invalidateQueries({ queryKey: keys.me });
      qc.invalidateQueries({ queryKey: keys.reports });
      setDoneId(res.report_id ?? 'sent');
    } catch (e) {
      if (e instanceof ApiError && e.offline) {
        await addToQueue(draft);
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Warning);
        toast('No signal. Saved on your phone; it will send when you are back online.');
        router.back();
      } else {
        Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
        toast((e as Error).message, 'error');
      }
    } finally {
      setBusy(false);
    }
  };

  if (doneId) {
    return (
      <Scene>
        <View style={[styles.done, { paddingBottom: insets.bottom + 24 }]}>
          <Animated.View entering={ZoomIn.springify()} style={[styles.doneIcon, { backgroundColor: theme.primary }]}>
            <Icons.Check size={44} color={theme.primaryForeground} strokeWidth={3} />
          </Animated.View>
          <Animated.View entering={FadeInDown.delay(120)} style={{ alignItems: 'center', gap: 8 }}>
            <T variant="title">Report received.</T>
            <T variant="mono" color={theme.primary}>
              {doneId}
            </T>
            <T muted style={{ textAlign: 'center' }}>
              {anonymous ? 'It is not linked to your account, so it will not appear in your list.' : 'You can follow its status under My reports.'}
            </T>
          </Animated.View>
          <Button label="Done" size="lg" style={{ alignSelf: 'stretch' }} onPress={() => router.back()} />
        </View>
      </Scene>
    );
  }

  return (
    <Scene intensity={0.8}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={[styles.body, { paddingTop: insets.top + 12, paddingBottom: insets.bottom + 28 }]} keyboardShouldPersistTaps="handled">
          <View style={styles.head}>
            <View style={{ flex: 1 }}>
              <T variant="eyebrow">Confidential</T>
              <T variant="title">Report an incident</T>
            </View>
            <Pressable onPress={() => router.back()} hitSlop={12} style={styles.close} accessibilityLabel="Close">
              <X size={20} color={theme.foreground} />
            </Pressable>
          </View>

          <Glass radius={26} style={styles.card}>
            <T variant="eyebrow">What happened</T>
            <View style={styles.typeGrid}>
              {incidentTypes.map((t) => {
                const Icon = (Icons as any)[t.icon] ?? Icons.CircleHelp;
                const on = type === t.value;
                return (
                  <Pressable
                    key={t.value}
                    onPress={() => {
                      Haptics.selectionAsync();
                      setType(t.value);
                    }}
                    style={[styles.type, on ? { borderColor: theme.primary, backgroundColor: theme.primary + '24' } : null]}
                    accessibilityRole="radio"
                    accessibilityState={{ checked: on }}>
                    <Icon size={20} color={on ? theme.primary : theme.muted} />
                    <T variant="small" color={on ? theme.foreground : theme.muted} style={{ textAlign: 'center' }}>
                      {t.label}
                    </T>
                  </Pressable>
                );
              })}
            </View>

            <T variant="eyebrow" style={{ marginTop: 6 }}>
              How serious
            </T>
            <View style={styles.row}>
              {severities.map((s) => (
                <Chip key={s.value} label={s.label} selected={severity === s.value} onPress={() => setSeverity(s.value)} />
              ))}
            </View>

            <T variant="eyebrow" style={{ marginTop: 6 }}>
              When
            </T>
            <View style={styles.row}>
              {times.map((t) => (
                <Chip key={t.label} label={t.label} selected={hoursAgo === t.hours} onPress={() => setHoursAgo(t.hours)} />
              ))}
            </View>
          </Glass>

          <Glass radius={26} style={styles.card}>
            <Field label="Where" value={location} onChangeText={setLocation} placeholder="e.g. Block 34, ground floor" />
            <View style={styles.row}>
              <Chip label={locating ? 'Locating…' : 'Use my location'} icon={<LocateFixed size={14} color={theme.primary} />} onPress={useMyLocation} />
              {landmarks.slice(0, 6).map((l) => (
                <Chip key={l.name} label={l.name} selected={location === l.name} onPress={() => setLocation(l.name)} />
              ))}
            </View>
            <Field label="What happened" value={description} onChangeText={setDescription} placeholder="Describe it in your own words. Include anything that could help, like a description or a vehicle number." multiline />
          </Glass>

          <Glass radius={26} style={styles.card}>
            <T variant="eyebrow">Evidence (optional)</T>
            <View style={styles.row}>
              {photos.map((uri) => (
                <View key={uri} style={styles.thumb}>
                  <Image source={{ uri }} style={StyleSheet.absoluteFill} contentFit="cover" />
                  <Pressable onPress={() => setPhotos((p) => p.filter((x) => x !== uri))} style={styles.remove} hitSlop={8} accessibilityLabel="Remove photo">
                    <X size={13} color="#fff" />
                  </Pressable>
                </View>
              ))}
              {photos.length < 3 && (
                <>
                  <Pressable onPress={() => pick(true)} style={styles.add} accessibilityLabel="Take a photo">
                    <Camera size={20} color={theme.muted} />
                  </Pressable>
                  <Pressable onPress={() => pick(false)} style={styles.add} accessibilityLabel="Choose photos">
                    <ImagePlus size={20} color={theme.muted} />
                  </Pressable>
                </>
              )}
            </View>

            <View style={styles.anon}>
              <EyeOff size={18} color={theme.muted} />
              <View style={{ flex: 1 }}>
                <T variant="label">Report anonymously</T>
                <T variant="small" muted>
                  Not linked to your account at all.
                </T>
              </View>
              <Switch value={anonymous} onValueChange={setAnonymous} trackColor={{ true: theme.primary, false: 'rgba(255,255,255,0.2)' }} thumbColor="#fff" />
            </View>
          </Glass>

          <Button label="Send report" size="lg" loading={busy} onPress={submit} />
        </ScrollView>
      </KeyboardAvoidingView>
    </Scene>
  );
}

const styles = StyleSheet.create({
  body: { paddingHorizontal: 18, gap: 14 },
  head: { flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 2 },
  close: { width: 42, height: 42, borderRadius: 21, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(255,255,255,0.08)' },
  card: { padding: 16, gap: 12 },
  typeGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, justifyContent: 'space-between' },
  type: { width: '31.5%', minHeight: 78, borderRadius: 16, borderWidth: 1, borderColor: 'rgba(255,255,255,0.12)', backgroundColor: 'rgba(255,255,255,0.04)', alignItems: 'center', justifyContent: 'center', gap: 6, padding: 8 },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  thumb: { width: 74, height: 74, borderRadius: 14, overflow: 'hidden' },
  remove: { position: 'absolute', top: 4, right: 4, width: 22, height: 22, borderRadius: 11, backgroundColor: 'rgba(0,0,0,0.6)', alignItems: 'center', justifyContent: 'center' },
  add: { width: 74, height: 74, borderRadius: 14, borderWidth: 1.5, borderStyle: 'dashed', borderColor: 'rgba(255,255,255,0.25)', alignItems: 'center', justifyContent: 'center' },
  anon: { flexDirection: 'row', alignItems: 'center', gap: 12, marginTop: 4 },
  done: { flex: 1, paddingHorizontal: 24, alignItems: 'center', justifyContent: 'center', gap: 22 },
  doneIcon: { width: 96, height: 96, borderRadius: 48, alignItems: 'center', justifyContent: 'center' },
});
