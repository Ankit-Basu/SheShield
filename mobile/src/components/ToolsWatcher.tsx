import * as Haptics from 'expo-haptics';
import { router } from 'expo-router';
import { useEffect, useRef } from 'react';
import { Alert, AppState } from 'react-native';

import { useCheckIn, useFakeCall } from '@/lib/tools';

// Runs the Safety tools timers from anywhere in the signed-in app: rings the fake
// call when it is due, and asks "Are you OK?" when a check-in runs out.
export function ToolsWatcher() {
  const callAt = useFakeCall((s) => s.at);
  const endsAt = useCheckIn((s) => s.endsAt);
  const asked = useRef<number | null>(null);

  useEffect(() => {
    if (!callAt) return;
    const t = setTimeout(() => {
      const { caller, cancel } = useFakeCall.getState();
      cancel();
      router.push({ pathname: '/fake-call', params: { caller } });
    }, Math.max(0, callAt - Date.now()));
    return () => clearTimeout(t);
  }, [callAt]);

  useEffect(() => {
    if (!endsAt) return;
    const ask = () => {
      if (asked.current === endsAt || Date.now() < endsAt) return;
      asked.current = endsAt;
      Haptics.notificationAsync(Haptics.NotificationFeedbackType.Warning);
      Alert.alert('Are you OK?', 'Your check-in timer has ended.', [
        { text: 'Open SOS', style: 'destructive', onPress: () => router.push('/sos') },
        { text: '10 more minutes', onPress: () => useCheckIn.getState().extend(10) },
        { text: "I'm safe", onPress: () => useCheckIn.getState().stop() },
      ]);
    };
    const t = setTimeout(ask, Math.max(0, endsAt - Date.now()));
    // Timers can be paused in the background; check again when the app comes back.
    const sub = AppState.addEventListener('change', (s) => s === 'active' && ask());
    return () => {
      clearTimeout(t);
      sub.remove();
    };
  }, [endsAt]);

  return null;
}
