import React, { forwardRef, useImperativeHandle, useMemo, useRef } from 'react';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import { WebView as RNWebView } from 'react-native-webview';

import { useTheme } from '@/lib/theme';

import { buildMapHtml, MapHandle } from './map-html';

export type { MapHandle };

// react-native-webview 14's prop types collapse to `never` against React Native 0.88's types.
// The component works; this keeps the type checker out of the way for this one import.
const WebView = RNWebView as unknown as React.ComponentType<any>;

// Leaflet inside a WebView: satellite tiles from Esri and the OSM campus outline,
// with no map API key needed in the installed app. React Native talks to the page
// through injected calls; the page reports landmark taps back with postMessage.
export const CampusMap = forwardRef<MapHandle, { onSelect?: (name: string) => void }>(function CampusMap({ onSelect }, ref) {
  const theme = useTheme();
  const web = useRef<{ injectJavaScript: (js: string) => void }>(null);
  const call = (js: string) => web.current?.injectJavaScript(`${js};true;`);

  useImperativeHandle(ref, () => ({
    setUser: (lat, lng, acc = 30) => call(`window.ss && ss.user(${lat},${lng},${acc})`),
    setSafeSpaces: (items) => call(`window.ss && ss.safe(${JSON.stringify(items)})`),
    setLayer: (layer) => call(`window.ss && ss.layer(${JSON.stringify(layer)})`),
    recenter: () => call('window.ss && ss.home()'),
  }));

  const html = useMemo(() => buildMapHtml(theme), [theme]);

  return (
    <View style={styles.fill}>
      <WebView
        ref={web}
        originWhitelist={['*']}
        source={{ html, baseUrl: 'https://sheshield-ab.vercel.app/' }}
        style={[styles.fill, { backgroundColor: theme.base }]}
        javaScriptEnabled
        domStorageEnabled
        startInLoadingState
        renderLoading={() => (
          <View style={[StyleSheet.absoluteFill, styles.loading, { backgroundColor: theme.base }]}>
            <ActivityIndicator color={theme.primary} />
          </View>
        )}
        onMessage={(e: { nativeEvent: { data: string } }) => {
          try {
            const msg = JSON.parse(e.nativeEvent.data);
            if (msg.type === 'select') onSelect?.(msg.name);
          } catch {}
        }}
      />
    </View>
  );
});

const styles = StyleSheet.create({ fill: { flex: 1 }, loading: { alignItems: 'center', justifyContent: 'center' } });
