import { BlurView } from 'expo-blur';
import { LinearGradient } from 'expo-linear-gradient';
import { ReactNode } from 'react';
import { Modal, Platform, Pressable, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { glass, useTheme } from '@/lib/theme';

import { T } from './Text';

type Props = { open: boolean; onClose: () => void; title: string; subtitle?: string; icon?: ReactNode; children: ReactNode };

// A bottom sheet: dimmed backdrop, a glass panel with a grab handle, closes on a tap outside.
export function Sheet({ open, onClose, title, subtitle, icon, children }: Props) {
  const insets = useSafeAreaInsets();
  const theme = useTheme();
  return (
    <Modal visible={open} transparent animationType="slide" onRequestClose={onClose} statusBarTranslucent navigationBarTranslucent>
      <Pressable style={styles.backdrop} onPress={onClose} accessibilityLabel="Close" />
      <View style={[styles.panel, { paddingBottom: insets.bottom + 18, backgroundColor: theme.base + 'f2' }]}>
        {Platform.OS === 'ios' ? <BlurView intensity={50} tint="dark" style={StyleSheet.absoluteFill} /> : null}
        <LinearGradient colors={[theme.primary + '1f', 'rgba(255,255,255,0.02)']} style={StyleSheet.absoluteFill} pointerEvents="none" />
        <View style={styles.handle} />
        <View style={styles.head}>
          {icon ? <View style={[styles.icon, { backgroundColor: theme.primary + '24' }]}>{icon}</View> : null}
          <View style={{ flex: 1 }}>
            <T variant="heading" style={{ fontSize: 19 }}>
              {title}
            </T>
            {subtitle ? (
              <T variant="small" muted style={{ marginTop: 2 }}>
                {subtitle}
              </T>
            ) : null}
          </View>
        </View>
        {children}
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.55)' },
  panel: {
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    overflow: 'hidden',
    paddingHorizontal: 20,
    paddingTop: 10,
    gap: 18,
    borderWidth: 1,
    borderBottomWidth: 0,
    borderColor: glass.line,
    borderTopColor: glass.lineTop,
  },
  handle: { alignSelf: 'center', width: 40, height: 4, borderRadius: 2, backgroundColor: 'rgba(255,255,255,0.25)' },
  head: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  icon: { width: 44, height: 44, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
});
