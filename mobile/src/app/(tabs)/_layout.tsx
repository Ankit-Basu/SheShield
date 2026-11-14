import { Tabs } from 'expo-router';

import { TabBar } from '@/components/TabBar';
import { ToolsWatcher } from '@/components/ToolsWatcher';
import { useTheme } from '@/lib/theme';

export default function TabsLayout() {
  const theme = useTheme();
  return (
    <>
    <ToolsWatcher />
    <Tabs
      tabBar={(props) => <TabBar {...props} />}
      screenOptions={{ headerShown: false, sceneStyle: { backgroundColor: theme.base }, animation: 'shift' }}>
      <Tabs.Screen name="index" />
      <Tabs.Screen name="map" />
      <Tabs.Screen name="walks" />
      <Tabs.Screen name="profile" />
    </Tabs>
    </>
  );
}
