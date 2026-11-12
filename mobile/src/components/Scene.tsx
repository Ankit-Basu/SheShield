import { BlurTargetView } from 'expo-blur';
import { ComponentRef, ReactNode, useRef } from 'react';
import { StyleSheet, View } from 'react-native';

import { BackdropContext } from '@/lib/blur';

import { Aurora } from './Aurora';

// A screen's two layers: the background (aurora and orbs) in its own blur source, so
// every glass card above it can frost it, and the content on top.
export function Scene({ children, intensity, orbs }: { children: ReactNode; intensity?: number; orbs?: boolean }) {
  const backdrop = useRef<ComponentRef<typeof View>>(null);
  return (
    <View style={styles.fill}>
      <BlurTargetView ref={backdrop} style={StyleSheet.absoluteFill}>
        <Aurora intensity={intensity} orbs={orbs} />
      </BlurTargetView>
      <BackdropContext.Provider value={backdrop}>{children}</BackdropContext.Provider>
    </View>
  );
}

const styles = StyleSheet.create({ fill: { flex: 1 } });
