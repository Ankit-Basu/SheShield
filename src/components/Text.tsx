import { StyleSheet, Text, TextProps } from 'react-native';

import { fonts, useTheme } from '@/lib/theme';

type Variant = 'display' | 'title' | 'heading' | 'body' | 'label' | 'small' | 'eyebrow' | 'mono';

const styles = StyleSheet.create({
  display: { fontFamily: fonts.display, fontSize: 40, lineHeight: 42, letterSpacing: -0.6 },
  title: { fontFamily: fonts.display, fontSize: 28, lineHeight: 32, letterSpacing: -0.3 },
  heading: { fontFamily: fonts.bold, fontSize: 17, lineHeight: 22 },
  body: { fontFamily: fonts.body, fontSize: 15, lineHeight: 22 },
  label: { fontFamily: fonts.semibold, fontSize: 14, lineHeight: 19 },
  small: { fontFamily: fonts.medium, fontSize: 12.5, lineHeight: 17 },
  eyebrow: { fontFamily: fonts.mono, fontSize: 10.5, letterSpacing: 1.6, textTransform: 'uppercase' },
  mono: { fontFamily: fonts.mono, fontSize: 12.5 },
});

export function T({ variant = 'body', muted, color, style, ...rest }: TextProps & { variant?: Variant; muted?: boolean; color?: string }) {
  const theme = useTheme();
  return (
    <Text
      {...rest}
      maxFontSizeMultiplier={1.35}
      style={[styles[variant], { color: color ?? (muted || variant === 'eyebrow' ? theme.muted : theme.foreground) }, style]}
    />
  );
}
