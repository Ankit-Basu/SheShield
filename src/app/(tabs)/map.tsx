import * as Haptics from 'expo-haptics';
import * as Location from 'expo-location';
import { router } from 'expo-router';
import { Building2, DoorOpen, Dumbbell, Footprints, GraduationCap, House, Layers, LocateFixed, Maximize, ParkingCircle, ShieldCheck, ShoppingBag, X } from 'lucide-react-native';
import { ReactNode, useEffect, useRef, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import Animated, { FadeInDown, FadeOutDown } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Button } from '@/components/Button';
import { CampusMap, MapHandle } from '@/components/CampusMap';
import { Glass } from '@/components/Glass';
import { TAB_BAR_SPACE } from '@/components/Screen';
import { T } from '@/components/Text';
import { toast } from '@/components/Toast';
import { landmarks } from '@/lib/campus';
import { useSafeSpaces } from '@/lib/queries';
import { status, useTheme } from '@/lib/theme';

const typeIcon: Record<string, typeof House> = {
  entrance: DoorOpen,
  academic: GraduationCap,
  commercial: ShoppingBag,
  residential: Building2,
  recreational: Dumbbell,
};
const typeLabel: Record<string, string> = {
  entrance: 'Entrance',
  academic: 'Academic',
  commercial: 'Shops & services',
  residential: 'Hostel',
  recreational: 'Sports',
};
// Over bright satellite imagery, white glass washes out; map overlays use a darker tint.
const overMap = { backgroundColor: 'rgba(20,11,18,0.78)' };

const iconFor = (name: string, type: string) => (name.includes('Parking') ? ParkingCircle : typeIcon[type] ?? House);

function RoundButton({ onPress, children, label, active }: { onPress: () => void; children: ReactNode; label: string; active?: boolean }) {
  const theme = useTheme();
  return (
    <Glass onPress={onPress} radius={18} strong style={[styles.round, overMap, active && { borderColor: theme.primary }]} accessibilityLabel={label}>
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
  const place = landmarks.find((l) => l.name === selected);

  useEffect(() => {
    if (safe) setTimeout(() => map.current?.setSafeSpaces(safe), 600);
  }, [safe]);

  const choose = (name: string) => {
    Haptics.selectionAsync();
    setSelected(name);
    map.current?.focus(name);
  };

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

  const bottom = TAB_BAR_SPACE + insets.bottom - 14;

  return (
    <View style={{ flex: 1, backgroundColor: theme.base }}>
      <CampusMap ref={map} onSelect={choose} />

      <View style={[styles.top, { top: insets.top + 10 }]} pointerEvents="box-none">
        <Glass radius={20} strong style={[styles.title, overMap]}>
          <T variant="eyebrow">LPU · Phagwara</T>
          <T variant="heading" style={{ fontSize: 18 }}>
            Campus safety map
          </T>
          <View style={styles.legend}>
            <View style={styles.legendItem}>
              <View style={[styles.dot, { backgroundColor: theme.primary }]} />
              <T variant="small" muted>
                {landmarks.length} landmarks
              </T>
            </View>
            <View style={styles.legendItem}>
              <View style={[styles.dot, { backgroundColor: status.good }]} />
              <T variant="small" muted>
                {safe ? `${safe.length} safe spaces` : 'Safe spaces'}
              </T>
            </View>
          </View>
        </Glass>
        <View style={{ gap: 10 }}>
          <RoundButton
            label={layer === 'satellite' ? 'Show street map' : 'Show satellite'}
            active={layer === 'streets'}
            onPress={() => {
              const next = layer === 'satellite' ? 'streets' : 'satellite';
              setLayer(next);
              map.current?.setLayer(next);
            }}>
            <Layers size={20} color={layer === 'streets' ? theme.primary : theme.foreground} />
          </RoundButton>
          <RoundButton
            label="Show whole campus"
            onPress={() => {
              setSelected(null);
              map.current?.recenter();
            }}>
            <Maximize size={19} color={theme.foreground} />
          </RoundButton>
          <RoundButton label="Show my location" onPress={locate} active={locating}>
            <LocateFixed size={20} color={locating ? theme.primary : theme.foreground} />
          </RoundButton>
        </View>
      </View>

      {place ? (
        <Animated.View key={place.name} entering={FadeInDown.springify().damping(18)} exiting={FadeOutDown.duration(150)} style={[styles.sheet, { bottom }]}>
          <Glass radius={26} strong style={[{ padding: 16, gap: 14 }, overMap]}>
            <View style={styles.sheetHead}>
              <View style={[styles.sheetIcon, { backgroundColor: theme.primary + '26', borderColor: theme.primary + '55' }]}>
                {(() => {
                  const Icon = iconFor(place.name, place.type);
                  return <Icon size={22} color={theme.primary} />;
                })()}
              </View>
              <View style={{ flex: 1 }}>
                <T variant="eyebrow">{typeLabel[place.type] ?? 'Landmark'}</T>
                <T variant="title" style={{ fontSize: 24, lineHeight: 28 }}>
                  {place.name}
                </T>
              </View>
              <Pressable onPress={() => setSelected(null)} hitSlop={12} style={styles.close} accessibilityLabel="Close">
                <X size={18} color={theme.foreground} />
              </Pressable>
            </View>
            <View style={{ flexDirection: 'row', gap: 10 }}>
              <Button
                label="Walk from here"
                icon={<Footprints size={16} color={theme.primaryForeground} />}
                style={{ flex: 1 }}
                onPress={() => router.navigate({ pathname: '/walks', params: { from: place.name } })}
              />
              <Button
                label="Report here"
                variant="ghost"
                icon={<ShieldCheck size={16} color={theme.foreground} />}
                style={{ flex: 1 }}
                onPress={() => router.push({ pathname: '/report', params: { location: place.name } })}
              />
            </View>
          </Glass>
        </Animated.View>
      ) : (
        <Animated.View entering={FadeInDown.springify().damping(18)} exiting={FadeOutDown.duration(150)} style={[styles.rail, { bottom }]}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 10, paddingHorizontal: 14 }}>
            {landmarks.map((l) => {
              const Icon = iconFor(l.name, l.type);
              return (
                <Glass key={l.name} onPress={() => choose(l.name)} radius={18} strong style={[styles.card, overMap]} accessibilityLabel={`Show ${l.name}`}>
                  <View style={[styles.cardIcon, { backgroundColor: theme.primary + '26' }]}>
                    <Icon size={17} color={theme.primary} />
                  </View>
                  <View>
                    <T variant="label" numberOfLines={1}>
                      {l.name}
                    </T>
                    <T variant="small" muted style={{ fontSize: 11.5 }}>
                      {typeLabel[l.type]}
                    </T>
                  </View>
                </Glass>
              );
            })}
          </ScrollView>
        </Animated.View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  top: { position: 'absolute', left: 14, right: 14, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: 10 },
  title: { paddingVertical: 12, paddingHorizontal: 14, gap: 1, flex: 1 },
  legend: { flexDirection: 'row', flexWrap: 'wrap', columnGap: 12, rowGap: 4, marginTop: 6 },
  legendItem: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  dot: { width: 8, height: 8, borderRadius: 4 },
  round: { width: 48, height: 48, alignItems: 'center', justifyContent: 'center' },
  sheet: { position: 'absolute', left: 14, right: 14 },
  sheetHead: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  sheetIcon: { width: 48, height: 48, borderRadius: 16, borderWidth: 1, alignItems: 'center', justifyContent: 'center' },
  close: { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(255,255,255,0.1)' },
  rail: { position: 'absolute', left: 0, right: 0 },
  card: { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 10, paddingLeft: 10, paddingRight: 16 },
  cardIcon: { width: 34, height: 34, borderRadius: 11, alignItems: 'center', justifyContent: 'center' },
});
