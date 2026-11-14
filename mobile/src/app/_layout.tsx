import { JetBrainsMono_500Medium } from '@expo-google-fonts/jetbrains-mono';
import {
  PlusJakartaSans_400Regular,
  PlusJakartaSans_500Medium,
  PlusJakartaSans_600SemiBold,
  PlusJakartaSans_700Bold,
} from '@expo-google-fonts/plus-jakarta-sans';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useFonts } from 'expo-font';
import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useRef } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';

import { toast, ToastHost } from '@/components/Toast';
import { useAuth } from '@/lib/auth';
import { clearCache } from '@/lib/fallback';
import { useReportQueue, watchConnection } from '@/lib/offline';
import { useTheme } from '@/lib/theme';

SplashScreen.preventAutoHideAsync();

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { staleTime: 30_000, retry: 1, refetchOnWindowFocus: false },
  },
});

export default function RootLayout() {
  const theme = useTheme();
  const { token, ready, restore } = useAuth();
  const [fontsLoaded] = useFonts({
    ClashDisplay: require('../../assets/fonts/ClashDisplay-Semibold.ttf'),
    PlusJakartaSans_400Regular,
    PlusJakartaSans_500Medium,
    PlusJakartaSans_600SemiBold,
    PlusJakartaSans_700Bold,
    JetBrainsMono_500Medium,
  });

  useEffect(() => {
    restore();
    useReportQueue.getState().load();
  }, [restore]);

  // Queued offline reports go out as soon as the phone is back online.
  useEffect(() => {
    if (!token) return;
    return watchConnection((n) => {
      toast(`${n} saved report${n === 1 ? '' : 's'} sent`);
      queryClient.invalidateQueries();
    });
  }, [token]);

  // A different account must never see the previous one's data, so signing out clears
  // it. Only a real sign-out counts: at launch the token is briefly empty while it
  // loads from secure storage, and that must not wipe the saved data.
  const hadToken = useRef(false);
  useEffect(() => {
    if (token) {
      hadToken.current = true;
    } else if (hadToken.current) {
      hadToken.current = false;
      queryClient.clear();
      clearCache();
    }
  }, [token]);

  useEffect(() => {
    if (fontsLoaded && ready) SplashScreen.hideAsync();
  }, [fontsLoaded, ready]);

  if (!fontsLoaded || !ready) return null;

  return (
    <GestureHandlerRootView style={{ flex: 1, backgroundColor: theme.base }}>
      <SafeAreaProvider>
        <QueryClientProvider client={queryClient}>
          <StatusBar style={theme.mode === "light" ? "dark" : "light"} />
          <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: theme.base }, animation: 'fade_from_bottom' }}>
            <Stack.Protected guard={!token} redirectTo="/">
              <Stack.Screen name="welcome" />
              <Stack.Screen name="login" options={{ animation: 'slide_from_right' }} />
              <Stack.Screen name="signup" options={{ animation: 'slide_from_right' }} />
            </Stack.Protected>
            <Stack.Protected guard={!!token} redirectTo="/welcome">
              <Stack.Screen name="(tabs)" />
              <Stack.Screen name="sos" options={{ presentation: 'fullScreenModal', animation: 'fade' }} />
              <Stack.Screen name="report" options={{ presentation: 'modal', animation: 'slide_from_bottom' }} />
              <Stack.Screen name="reports" options={{ animation: 'slide_from_right' }} />
              <Stack.Screen name="volunteer" options={{ presentation: 'modal', animation: 'slide_from_bottom' }} />
              <Stack.Screen name="fake-call" options={{ presentation: 'fullScreenModal', animation: 'fade', gestureEnabled: false }} />
            </Stack.Protected>
          </Stack>
          <ToastHost />
        </QueryClientProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
