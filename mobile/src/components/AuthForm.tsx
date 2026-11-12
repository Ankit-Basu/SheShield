import * as Device from 'expo-device';
import * as Haptics from 'expo-haptics';
import { router } from 'expo-router';
import { ArrowLeft, Eye, EyeOff, Lock, Mail, UserRound } from 'lucide-react-native';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { api } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { saveProfile } from '@/lib/fallback';
import { useTheme } from '@/lib/theme';

import { Scene } from './Scene';
import { Logo } from './bits';
import { Button } from './Button';
import { Field } from './Field';
import { Glass } from './Glass';
import { T } from './Text';

export function AuthForm({ mode }: { mode: 'login' | 'signup' }) {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const signIn = useAuth((s) => s.signIn);
  const [first, setFirst] = useState('');
  const [last, setLast] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [show, setShow] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const signup = mode === 'signup';

  const submit = async () => {
    setError('');
    if (signup && (!first.trim() || !last.trim())) return setError('Please enter your first and last name.');
    if (!/^\S+@\S+\.\S+$/.test(email.trim())) return setError('Please enter a valid email address.');
    if (password.length < (signup ? 8 : 1)) return setError(signup ? 'Use at least 8 characters for your password.' : 'Please enter your password.');
    setBusy(true);
    try {
      const device = Device.modelName ?? Platform.OS;
      const body = signup
        ? { first_name: first.trim(), last_name: last.trim(), email: email.trim(), password, client: 'mobile', device }
        : { email: email.trim(), password, client: 'mobile', device };
      const res = await api<{ token?: string; user?: { first_name?: string; last_name?: string; email?: string } }>(signup ? 'api/auth/signup.php' : 'api/auth/login.php', { json: body });
      if (!res.token) throw new Error('The server did not return a sign-in token. It may need updating.');
      // Remembered so the app can still greet you by name if the server is asleep later.
      await saveProfile({ first_name: res.user?.first_name ?? first.trim(), last_name: res.user?.last_name ?? last.trim(), email: res.user?.email ?? email.trim() });
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
      await signIn(res.token);
    } catch (e: any) {
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Error);
      setError(e.message || 'Something went wrong.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <Scene>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={[styles.body, { paddingTop: insets.top + 10, paddingBottom: insets.bottom + 24 }]} keyboardShouldPersistTaps="handled">
          <Pressable onPress={() => router.back()} hitSlop={12} style={styles.back} accessibilityRole="button" accessibilityLabel="Back">
            <ArrowLeft size={22} color={theme.foreground} />
          </Pressable>

          <Animated.View entering={FadeInDown.duration(600)} style={{ gap: 12, marginTop: 18 }}>
            <Logo size={40} />
            <T variant="display" style={{ fontSize: 38, lineHeight: 40 }}>
              {signup ? 'Create your\naccount.' : 'Welcome\nback.'}
            </T>
            <T muted>{signup ? 'A minute now means one less thing to think about later.' : 'Sign in to reach SOS, walks and your reports.'}</T>
          </Animated.View>

          <Animated.View entering={FadeInDown.delay(150).duration(650)}>
            <Glass style={styles.card} radius={28} strong>
              {signup && (
                <View style={styles.row}>
                  <View style={{ flex: 1 }}>
                    <Field label="First name" value={first} onChangeText={setFirst} placeholder="Aanya" autoComplete="given-name" textContentType="givenName" icon={<UserRound size={17} color={theme.muted} />} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Field label="Last name" value={last} onChangeText={setLast} placeholder="Sharma" autoComplete="family-name" textContentType="familyName" />
                  </View>
                </View>
              )}
              <Field
                label="Email"
                value={email}
                onChangeText={setEmail}
                placeholder="you@lpu.in"
                keyboardType="email-address"
                autoCapitalize="none"
                autoComplete="email"
                textContentType="emailAddress"
                icon={<Mail size={17} color={theme.muted} />}
              />
              <Field
                label="Password"
                value={password}
                onChangeText={setPassword}
                placeholder={signup ? 'At least 8 characters' : 'Your password'}
                secureTextEntry={!show}
                autoCapitalize="none"
                autoComplete={signup ? 'new-password' : 'current-password'}
                textContentType={signup ? 'newPassword' : 'password'}
                onSubmitEditing={submit}
                icon={<Lock size={17} color={theme.muted} />}
                right={
                  <Pressable onPress={() => setShow((s) => !s)} hitSlop={10} accessibilityLabel={show ? 'Hide password' : 'Show password'}>
                    {show ? <EyeOff size={18} color={theme.muted} /> : <Eye size={18} color={theme.muted} />}
                  </Pressable>
                }
              />
              {error ? (
                <View style={[styles.error, { borderColor: theme.destructive + '66', backgroundColor: theme.destructive + '1f' }]}>
                  <T variant="small" color="#ffb3b8">
                    {error}
                  </T>
                </View>
              ) : null}
              <Button label={signup ? 'Create account' : 'Sign in'} size="lg" loading={busy} onPress={submit} />
              {busy ? (
                <T variant="small" muted style={{ textAlign: 'center' }}>
                  The server may take up to a minute to wake on the free plan.
                </T>
              ) : null}
            </Glass>
          </Animated.View>

          <Pressable onPress={() => router.replace(signup ? '/login' : '/signup')} style={{ alignSelf: 'center', padding: 8 }}>
            <T variant="small" muted>
              {signup ? 'Already have an account? ' : 'New to SheShield? '}
              <T variant="small" color={theme.primary}>
                {signup ? 'Sign in' : 'Create an account'}
              </T>
            </T>
          </Pressable>
        </ScrollView>
      </KeyboardAvoidingView>
    </Scene>
  );
}

const styles = StyleSheet.create({
  body: { paddingHorizontal: 20, gap: 22 },
  back: { width: 44, height: 44, borderRadius: 22, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(255,255,255,0.07)' },
  card: { padding: 18, gap: 15 },
  row: { flexDirection: 'row', gap: 10 },
  error: { padding: 12, borderRadius: 14, borderWidth: 1 },
});
