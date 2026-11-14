import { ReactNode, useState } from 'react';
import { StyleSheet, TextInput, TextInputProps, View } from 'react-native';

import { fonts, useTheme } from '@/lib/theme';

import { T } from './Text';

type Props = TextInputProps & { label?: string; icon?: ReactNode; right?: ReactNode; error?: string };

export function Field({ label, icon, right, error, style, multiline, ...rest }: Props) {
  const theme = useTheme();
  const [focused, setFocused] = useState(false);
  return (
    <View style={{ gap: 7 }}>
      {label ? <T variant="eyebrow">{label}</T> : null}
      <View
        style={[
          styles.box,
          multiline && { alignItems: 'flex-start', paddingTop: 14, minHeight: 120 },
          { backgroundColor: theme.surfaceAlt, borderColor: error ? theme.destructive : focused ? theme.primary : theme.line },
        ]}>
        {icon}
        <TextInput
          {...rest}
          multiline={multiline}
          placeholderTextColor={theme.muted + "99"}
          selectionColor={theme.primary}
          onFocus={(e) => {
            setFocused(true);
            rest.onFocus?.(e);
          }}
          onBlur={(e) => {
            setFocused(false);
            rest.onBlur?.(e);
          }}
          style={[styles.input, { color: theme.foreground }, multiline && { textAlignVertical: 'top', minHeight: 100 }, style]}
        />
        {right}
      </View>
      {error ? (
        <T variant="small" color={theme.destructive}>
          {error}
        </T>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  box: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingHorizontal: 16,
    minHeight: 54,
    borderRadius: 18,
    borderWidth: 1,
  },
  input: { flex: 1, fontFamily: fonts.medium, fontSize: 15.5, paddingVertical: 12 },
});
