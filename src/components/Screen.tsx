import { ReactNode } from 'react';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useTheme } from '@/lib/theme';

import { Aurora } from './Aurora';

export const TAB_BAR_SPACE = 112;

type Props = {
  children: ReactNode;
  scroll?: boolean;
  refreshing?: boolean;
  onRefresh?: () => void;
  tabs?: boolean;
  padded?: boolean;
};

// Every screen: the aurora behind, safe-area padding, and room for the floating tab bar.
export function Screen({ children, scroll = true, refreshing, onRefresh, tabs = true, padded = true }: Props) {
  const insets = useSafeAreaInsets();
  const theme = useTheme();
  const pad = { paddingTop: insets.top + 12, paddingBottom: (tabs ? TAB_BAR_SPACE : 24) + insets.bottom, paddingHorizontal: padded ? 18 : 0 };
  return (
    <View style={styles.fill}>
      <Aurora />
      {scroll ? (
        <ScrollView
          contentContainerStyle={[pad, { gap: 16 }]}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
          refreshControl={
            onRefresh ? (
              <RefreshControl refreshing={!!refreshing} onRefresh={onRefresh} tintColor={theme.primary} colors={[theme.primary]} progressViewOffset={insets.top} />
            ) : undefined
          }>
          {children}
        </ScrollView>
      ) : (
        <View style={[styles.fill, pad]}>{children}</View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({ fill: { flex: 1 } });
