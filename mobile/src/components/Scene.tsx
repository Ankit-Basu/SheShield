import { ReactNode } from 'react';
import { View } from 'react-native';

import { useTheme } from '@/lib/theme';

// A screen's background: the theme's solid base colour.
export function Scene({ children }: { children: ReactNode }) {
  const theme = useTheme();
  return <View style={{ flex: 1, backgroundColor: theme.base }}>{children}</View>;
}
