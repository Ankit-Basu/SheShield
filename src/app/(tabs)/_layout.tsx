import { Tabs } from 'expo-router';

import { GlassTabBar } from '@/components/GlassTabBar';
import { useTheme } from '@/lib/theme';

export default function TabsLayout() {
  const theme = useTheme();
  return (
    <Tabs
      tabBar={(props) => <GlassTabBar {...props} />}
      screenOptions={{ headerShown: false, sceneStyle: { backgroundColor: theme.base }, animation: 'shift' }}>
      <Tabs.Screen name="index" />
      <Tabs.Screen name="map" />
      <Tabs.Screen name="walks" />
      <Tabs.Screen name="profile" />
    </Tabs>
  );
}
