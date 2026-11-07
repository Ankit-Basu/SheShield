import { useFocusEffect } from 'expo-router';
import { ComponentRef, RefObject, useCallback, useRef } from 'react';
import { View } from 'react-native';
import { create } from 'zustand';

// On Android, a BlurView can only blur what is inside a BlurTargetView it is given.
// Each tab screen wraps its content in one and registers it while focused, so the
// floating tab bar can blur whatever is scrolling beneath it.
type Target = RefObject<ComponentRef<typeof View> | null>;

export const useBlurTarget = create<{ target: Target | null; set: (t: Target | null) => void }>((set) => ({
  target: null,
  set: (target) => set({ target }),
}));

export function useRegisterBlurTarget() {
  const ref = useRef<ComponentRef<typeof View>>(null);
  useFocusEffect(
    useCallback(() => {
      useBlurTarget.getState().set(ref);
      return () => {
        if (useBlurTarget.getState().target === ref) useBlurTarget.getState().set(null);
      };
    }, []),
  );
  return ref;
}
