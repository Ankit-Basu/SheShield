import { router } from 'expo-router';
import { ArrowRight, Footprints, MapPin, ShieldCheck, Siren } from 'lucide-react-native';
import { StyleSheet, View } from 'react-native';
import Animated, { FadeInDown, FadeInUp } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { Scene } from '@/components/Scene';
import { Logo } from '@/components/bits';
import { Button } from '@/components/Button';
import { Glass } from '@/components/Glass';
import { GradientText } from '@/components/GradientText';
import { T } from '@/components/Text';
import { useTheme } from '@/lib/theme';

const features = [
  { icon: Siren, title: 'Hold for SOS', text: 'Sends your location and dials 112' },
  { icon: Footprints, title: 'Walk With Us', text: 'A verified walker for your route' },
  { icon: ShieldCheck, title: 'Report safely', text: 'Anonymous, even offline' },
];

export default function Welcome() {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  return (
    <Scene intensity={1.15} orbs>
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
              <MapPin size={13} color={theme.accent} />
              <T variant="eyebrow" color={theme.accent}>
                LPU · Phagwara
              </T>
            </View>
          </Animated.View>
          <Animated.View entering={FadeInDown.delay(200).duration(800)}>
            <T variant="display" style={{ fontSize: 50, lineHeight: 54 }}>
              No one walks
            </T>
            <GradientText variant="display" style={{ fontSize: 50, lineHeight: 54 }}>
              home alone.
            </GradientText>
          </Animated.View>
          <Animated.View entering={FadeInDown.delay(320).duration(800)}>
            <T muted style={{ fontSize: 16, lineHeight: 24 }}>
              Help one tap away, a walking companion when you want one, and a calm way to report what happened.
            </T>
          </Animated.View>
        </View>

        <View style={{ gap: 10 }}>
          {features.map((f, i) => (
            <Animated.View key={f.title} entering={FadeInUp.delay(420 + i * 90).springify().damping(18)}>
              <Glass style={styles.feature} radius={20}>
                <View style={[styles.featureIcon, { backgroundColor: theme.primary + '26' }]}>
                  <f.icon size={19} color={theme.primary} />
                </View>
                <View style={{ flex: 1 }}>
                  <T variant="label">{f.title}</T>
                  <T variant="small" muted>
                    {f.text}
                  </T>
                </View>
              </Glass>
            </Animated.View>
          ))}
        </View>

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
  feature: { flexDirection: 'row', alignItems: 'center', gap: 13, padding: 13 },
  featureIcon: { width: 40, height: 40, borderRadius: 13, alignItems: 'center', justifyContent: 'center' },
});
