import { CircleAlert, CircleCheck } from 'lucide-react-native';
import { useEffect } from 'react';
import { StyleSheet, View } from 'react-native';
import Animated, { FadeInUp, FadeOutUp } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { create } from 'zustand';

import { glass, status } from '@/lib/theme';

import { T } from './Text';

type ToastState = { message: string | null; kind: 'success' | 'error'; id: number; show: (m: string, k?: 'success' | 'error') => void; hide: () => void };

export const useToast = create<ToastState>((set) => ({
  message: null,
  kind: 'success',
  id: 0,
  show: (message, kind = 'success') => set((s) => ({ message, kind, id: s.id + 1 })),
  hide: () => set({ message: null }),
}));

export const toast = (m: string, k?: 'success' | 'error') => useToast.getState().show(m, k);

export function ToastHost() {
  const { message, kind, id, hide } = useToast();
  const insets = useSafeAreaInsets();
  useEffect(() => {
    if (!message) return;
    const t = setTimeout(hide, 3400);
    return () => clearTimeout(t);
  }, [id, message, hide]);
  if (!message) return null;
  const c = kind === 'error' ? '#ff6b6b' : status.good;
  return (
    <Animated.View key={id} entering={FadeInUp.springify().damping(16)} exiting={FadeOutUp} style={[styles.wrap, { top: insets.top + 8 }]} pointerEvents="none" accessibilityLiveRegion="polite">
      <View style={styles.toast}>
        {kind === 'error' ? <CircleAlert size={18} color={c} /> : <CircleCheck size={18} color={c} />}
        <T variant="label" style={{ flex: 1 }}>
          {message}
        </T>
      </View>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  wrap: { position: 'absolute', left: 16, right: 16, zIndex: 100 },
  toast: { flexDirection: 'row', alignItems: 'center', gap: 10, padding: 14, borderRadius: 18, backgroundColor: glass.solid, borderWidth: 1, borderColor: glass.line, borderTopColor: glass.lineTop },
});
