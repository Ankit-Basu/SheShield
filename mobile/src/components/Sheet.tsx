import { ReactNode } from 'react';
import { Modal, Pressable, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useTheme } from '@/lib/theme';

import { T } from './Text';

type Props = { open: boolean; onClose: () => void; title: string; subtitle?: string; icon?: ReactNode; children: ReactNode };

// A bottom sheet: dimmed backdrop, a solid panel with a grab handle, closes on a tap outside.
export function Sheet({ open, onClose, title, subtitle, icon, children }: Props) {
  const insets = useSafeAreaInsets();
  const theme = useTheme();
  return (
    <Modal visible={open} transparent animationType="slide" onRequestClose={onClose} statusBarTranslucent navigationBarTranslucent>
      <Pressable style={styles.backdrop} onPress={onClose} accessibilityLabel="Close" />
      <View style={[styles.panel, { paddingBottom: insets.bottom + 18, backgroundColor: theme.surface, borderColor: theme.line }]}>
        <View style={[styles.handle, { backgroundColor: theme.line }]} />
        <View style={styles.head}>
          {icon ? <View style={[styles.icon, { backgroundColor: theme.soft }]}>{icon}</View> : null}
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
  backdrop: { flex: 1, backgroundColor: 'rgba(10,10,12,0.45)' },
  panel: {
    borderTopLeftRadius: 30,
    borderTopRightRadius: 30,
    overflow: 'hidden',
    paddingHorizontal: 20,
    paddingTop: 10,
    gap: 18,
    borderWidth: 1,
    borderBottomWidth: 0,
  },
  handle: { alignSelf: 'center', width: 40, height: 4, borderRadius: 2 },
  head: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  icon: { width: 44, height: 44, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
});
