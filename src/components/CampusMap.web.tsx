import { createElement, forwardRef, useEffect, useImperativeHandle, useMemo, useRef } from 'react';
import { View } from 'react-native';

import { useTheme } from '@/lib/theme';

import { buildMapHtml, MapHandle } from './map-html';

export type { MapHandle };

// Web build: the same Leaflet page in a same-origin iframe instead of a native WebView.
export const CampusMap = forwardRef<MapHandle, { onSelect?: (name: string) => void }>(function CampusMap({ onSelect }, ref) {
  const theme = useTheme();
  const frame = useRef<HTMLIFrameElement | null>(null);
  const html = useMemo(() => buildMapHtml(theme), [theme]);
  const ss = () => (frame.current?.contentWindow as any)?.ss;

  useImperativeHandle(ref, () => ({
    setUser: (lat, lng, acc = 30) => ss()?.user(lat, lng, acc),
    setSafeSpaces: (items) => ss()?.safe(items),
    setLayer: (layer) => ss()?.layer(layer),
    recenter: () => ss()?.home(),
    focus: (name) => ss()?.focus(name),
  }));

  useEffect(() => {
    const onMessage = (e: MessageEvent) => {
      if (e.source !== frame.current?.contentWindow || typeof e.data?.ssMap !== 'string') return;
      const msg = JSON.parse(e.data.ssMap);
      if (msg.type === 'select') onSelect?.(msg.name);
    };
    window.addEventListener('message', onMessage);
    return () => window.removeEventListener('message', onMessage);
  }, [onSelect]);

  return (
    <View style={{ flex: 1, backgroundColor: theme.base }}>
      {createElement('iframe', { ref: frame, srcDoc: html, title: 'Campus map', style: { border: 0, width: '100%', height: '100%' } })}
    </View>
  );
});
