import { router } from 'expo-router';
import { ArrowRight, MapPin } from 'lucide-react-native';
import { StyleSheet, View } from 'react-native';
import Animated, { FadeInDown, FadeInUp } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Scene } from '@/components/Scene';
import { Logo } from '@/components/bits';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { T } from '@/components/Text';
import { useTheme } from '@/lib/theme';

const features = [
  { title: 'Hold for SOS', text: 'Your location goes to campus security, with 112 one tap away.' },
  { title: 'Walk with someone', text: 'A verified student walker meets you at any landmark.' },
  { title: 'Report safely', text: 'Anonymous if you want, and saved even without signal.' },
];

export default function Welcome() {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  return (
    <Scene>
      <View style={[styles.body, { paddingTop: insets.top + 18, paddingBottom: insets.bottom + 22 }]}>
        <Animated.View entering={FadeInDown.duration(600)} style={styles.brand}>
          <Logo size={34} />
          <T variant="title" style={{ fontSize: 24 }}>
            SheShield
          </T>
        </Animated.View>

        <View style={{ gap: 14 }}>
          <Animated.View entering={FadeInDown.delay(120).duration(700)}>
            <View style={styles.where}>
              <MapPin size={13} color={theme.primary} />
              <T variant="eyebrow" color={theme.primary}>
                LPU · Phagwara
              </T>
            </View>
          </Animated.View>
          <Animated.View entering={FadeInDown.delay(200).duration(800)}>
            <T variant="display" style={{ fontSize: 50, lineHeight: 54 }}>
              No one walks
            </T>
            <T variant="display" color={theme.primary} style={{ fontSize: 50, lineHeight: 54 }}>
              home alone.
            </T>
          </Animated.View>
          <Animated.View entering={FadeInDown.delay(320).duration(800)}>
            <T muted style={{ fontSize: 16, lineHeight: 24 }}>
              Help one tap away, a walking companion when you want one, and a calm way to report what happened.
            </T>
          </Animated.View>
        </View>

        <Animated.View entering={FadeInUp.delay(420).springify().damping(20)}>
          <Card radius={24} style={{ paddingHorizontal: 18 }}>
            {features.map((f, i) => (
              <View key={f.title} style={[styles.feature, i < features.length - 1 && styles.featureDivider]}>
                <T variant="mono" color={theme.primary} style={{ fontSize: 12, marginTop: 2 }}>
                  0{i + 1}
                </T>
                <View style={{ flex: 1, gap: 2 }}>
                  <T variant="label">{f.title}</T>
                  <T variant="small" muted style={{ lineHeight: 18 }}>
                    {f.text}
                  </T>
                </View>
              </View>
            ))}
          </Card>
        </Animated.View>

        <Animated.View entering={FadeInUp.delay(720).duration(600)} style={{ gap: 10 }}>
          <Button label="Create an account" size="lg" icon={<ArrowRight size={18} color={theme.primaryForeground} />} onPress={() => router.push('/signup')} />
          <Button label="I already have an account" variant="ghost" size="lg" onPress={() => router.push('/login')} />
        </Animated.View>
      </View>
    </Scene>
  );
}

const styles = StyleSheet.create({
  body: { flex: 1, paddingHorizontal: 22, justifyContent: 'space-between' },
  brand: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  where: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  feature: { flexDirection: 'row', gap: 14, paddingVertical: 15 },
  featureDivider: { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: 'rgba(127,127,127,0.22)' },
});
