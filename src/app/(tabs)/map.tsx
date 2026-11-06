import * as Location from 'expo-location';
import { router } from 'expo-router';
import { Footprints, Layers, LocateFixed, Maximize, ShieldCheck, X } from 'lucide-react-native';
import { useEffect, useRef, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown, FadeOutDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { CampusMap, MapHandle } from '@/components/CampusMap';
import { Button } from '@/components/Button';
import { Glass } from '@/components/Glass';
import { TAB_BAR_SPACE } from '@/components/Screen';
import { T } from '@/components/Text';
import { toast } from '@/components/Toast';
import { useSafeSpaces } from '@/lib/queries';
import { status, useTheme } from '@/lib/theme';

function RoundButton({ onPress, children, label }: { onPress: () => void; children: React.ReactNode; label: string }) {
  return (
    <Glass onPress={onPress} radius={18} strong style={styles.round} accessibilityLabel={label}>
      {children}
    </Glass>
  );
}

export default function MapScreen() {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const map = useRef<MapHandle>(null);
  const [layer, setLayer] = useState<'satellite' | 'streets'>('satellite');
  const [selected, setSelected] = useState<string | null>(null);
  const [locating, setLocating] = useState(false);
  const { data: safe } = useSafeSpaces();

  useEffect(() => {
    if (safe) setTimeout(() => map.current?.setSafeSpaces(safe), 600);
  }, [safe]);

  const locate = async () => {
    setLocating(true);
    try {
      const { granted } = await Location.requestForegroundPermissionsAsync();
      if (!granted) {
        toast('Location permission is off. Enable it in settings to see yourself on the map.', 'error');
        return;
      }
      const p = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
      map.current?.setUser(p.coords.latitude, p.coords.longitude, p.coords.accuracy ?? 30);
    } catch {
      toast('Could not get your location.', 'error');
    } finally {
      setLocating(false);
    }
  };

  return (
    <View style={{ flex: 1, backgroundColor: theme.base }}>
      <CampusMap ref={map} onSelect={setSelected} />

      <View style={[styles.top, { top: insets.top + 10 }]} pointerEvents="box-none">
        <Glass radius={20} strong style={styles.title}>
          <T variant="eyebrow">LPU · Phagwara</T>
          <T variant="heading">Campus safety map</T>
          <View style={styles.legend}>
            <View style={[styles.dot, { backgroundColor: theme.primary }]} />
            <T variant="small" muted>
              Landmarks
            </T>
            <View style={[styles.dot, { backgroundColor: status.good, marginLeft: 8 }]} />
            <T variant="small" muted>
              Safe spaces {safe ? `(${safe.length})` : ''}
            </T>
          </View>
        </Glass>
        <View style={{ gap: 10 }}>
          <RoundButton
            label={layer === 'satellite' ? 'Show street map' : 'Show satellite'}
            onPress={() => {
              const next = layer === 'satellite' ? 'streets' : 'satellite';
              setLayer(next);
              map.current?.setLayer(next);
            }}>
            <Layers size={20} color={theme.foreground} />
          </RoundButton>
          <RoundButton label="Show whole campus" onPress={() => map.current?.recenter()}>
            <Maximize size={19} color={theme.foreground} />
          </RoundButton>
          <RoundButton label="Show my location" onPress={locate}>
            <LocateFixed size={20} color={locating ? theme.primary : theme.foreground} />
          </RoundButton>
        </View>
      </View>

      {selected && (
        <Animated.View entering={FadeInDown.springify().damping(18)} exiting={FadeOutDown} style={[styles.sheet, { bottom: TAB_BAR_SPACE + insets.bottom - 18 }]}>
          <Glass radius={26} strong style={{ padding: 16, gap: 12 }}>
            <View style={styles.sheetHead}>
              <View style={{ flex: 1 }}>
                <T variant="eyebrow">Landmark</T>
                <T variant="title" style={{ fontSize: 24 }}>
                  {selected}
                </T>
              </View>
              <Pressable onPress={() => setSelected(null)} hitSlop={10} accessibilityLabel="Close">
                <X size={20} color={theme.muted} />
              </Pressable>
            </View>
            <View style={{ flexDirection: 'row', gap: 10 }}>
              <Button
                label="Walk from here"
                icon={<Footprints size={16} color={theme.primaryForeground} />}
                style={{ flex: 1 }}
                onPress={() => router.navigate({ pathname: '/walks', params: { from: selected } })}
              />
              <Button
                label="Report here"
                variant="ghost"
                icon={<ShieldCheck size={16} color={theme.foreground} />}
                style={{ flex: 1 }}
                onPress={() => router.push({ pathname: '/report', params: { location: selected } })}
              />
            </View>
          </Glass>
        </Animated.View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  top: { position: 'absolute', left: 14, right: 14, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: 10 },
  title: { padding: 14, gap: 2, flexShrink: 1 },
  legend: { flexDirection: 'row', alignItems: 'center', gap: 5, marginTop: 6 },
  dot: { width: 8, height: 8, borderRadius: 4 },
  round: { width: 48, height: 48, alignItems: 'center', justifyContent: 'center' },
  sheet: { position: 'absolute', left: 14, right: 14 },
  sheetHead: { flexDirection: 'row', alignItems: 'flex-start', gap: 10 },
});
