import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';

// Check-in timer: "ask me if I'm OK in 20 minutes". Saved on the phone so it
// survives the app being closed; when it runs out, SheShield asks and offers SOS.
type CheckIn = {
  endsAt: number | null;
  minutes: number;
  start: (minutes: number) => void;
  extend: (minutes: number) => void;
  stop: () => void;
};

export const useCheckIn = create<CheckIn>()(
  persist(
    (set, get) => ({
      endsAt: null,
      minutes: 20,
      start: (minutes) => set({ minutes, endsAt: Date.now() + minutes * 60_000 }),
      extend: (minutes) => {
        const base = Math.max(get().endsAt ?? Date.now(), Date.now());
        set({ endsAt: base + minutes * 60_000, minutes: get().minutes + minutes });
      },
      stop: () => set({ endsAt: null }),
    }),
    { name: 'ss-checkin', storage: createJSONStorage(() => AsyncStorage), partialize: (s) => ({ endsAt: s.endsAt, minutes: s.minutes }) },
  ),
);

// Fake call: a believable incoming call to step out of an uncomfortable moment.
// Only the pending call lives here; the timer itself runs in ToolsWatcher.
type FakeCall = {
  at: number | null;
  caller: string;
  schedule: (caller: string, delaySec: number) => void;
  cancel: () => void;
};

export const useFakeCall = create<FakeCall>()((set) => ({
  at: null,
  caller: 'Mum',
  schedule: (caller, delaySec) => set({ caller, at: Date.now() + delaySec * 1000 }),
  cancel: () => set({ at: null }),
}));

export const mmss = (ms: number) => {
  const s = Math.max(0, Math.ceil(ms / 1000));
  return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
};
