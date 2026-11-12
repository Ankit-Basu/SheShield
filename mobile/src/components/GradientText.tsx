import RNMaskedView from '@react-native-masked-view/masked-view';
import { LinearGradient } from 'expo-linear-gradient';
import { ComponentProps, ComponentType, ReactElement, ReactNode } from 'react';
import { Platform, StyleSheet } from 'react-native';

import { useTheme } from '@/lib/theme';

import { T } from './Text';

// masked-view 0.3's class types don't match React 19.3's JSX types; the component works.
const MaskedView = RNMaskedView as unknown as ComponentType<{ maskElement: ReactElement; children: ReactNode }>;

// Text filled with the theme's accent → primary gradient, like the website's headlines.
export function GradientText({ children, colors, ...props }: ComponentProps<typeof T> & { colors?: readonly [string, string, ...string[]] }) {
  const theme = useTheme();
  const fill = colors ?? ([theme.accent, theme.primary] as const);
  // The web build has no masking; a solid colour reads the same there.
  if (Platform.OS === 'web') return <T {...props} color={theme.primary}>{children}</T>;
  return (
    <MaskedView maskElement={<T {...props}>{children}</T>}>
      <LinearGradient colors={fill} start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}>
        <T {...props} style={[props.style, styles.hidden]}>
          {children}
        </T>
      </LinearGradient>
    </MaskedView>
  );
}

const styles = StyleSheet.create({ hidden: { opacity: 0 } });
