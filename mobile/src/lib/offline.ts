import AsyncStorage from '@react-native-async-storage/async-storage';
import NetInfo from '@react-native-community/netinfo';
import { create } from 'zustand';

import { api, ApiError } from './api';

// Reports written without signal are kept on the phone and sent automatically
// when the connection comes back. Photos stay as local file URIs until then.
export type ReportDraft = {
  id: string;
  incident_type: string;
  description: string;
  location: string;
  date_time: string;
  severity: string;
  is_anonymous: boolean;
  photos: string[];
  savedAt: number;
};

const KEY = 'ss.report-queue';

export function toFormData(d: ReportDraft) {
  const form = new FormData();
  form.append('incident_type', d.incident_type);
  form.append('description', d.description);
  form.append('location', d.location);
  form.append('date_time', d.date_time);
  form.append('severity', d.severity);
  if (d.is_anonymous) form.append('is_anonymous', '1');
  d.photos.forEach((uri, i) => {
    const ext = (uri.split('.').pop() || 'jpg').toLowerCase().replace('jpeg', 'jpg');
    // React Native's FormData takes a { uri, name, type } file descriptor.
    form.append('evidence[]', { uri, name: `evidence-${i + 1}.${ext}`, type: ext === 'png' ? 'image/png' : 'image/jpeg' } as any);
  });
  return form;
}

export const sendReport = (d: ReportDraft) =>
  api<{ report_id: string; id: number }>('api/incidents/create.php', { form: toFormData(d), timeoutMs: 90000 });

type QueueState = {
  queue: ReportDraft[];
  flushing: boolean;
  load: () => Promise<void>;
  add: (d: ReportDraft) => Promise<void>;
  flush: () => Promise<number>;
};

export const useReportQueue = create<QueueState>((set, get) => ({
  queue: [],
  flushing: false,
  load: async () => {
    try {
      set({ queue: JSON.parse((await AsyncStorage.getItem(KEY)) || '[]') });
    } catch {
      set({ queue: [] });
    }
  },
  add: async (d) => {
    const queue = [...get().queue, d];
    set({ queue });
    await AsyncStorage.setItem(KEY, JSON.stringify(queue));
  },
  flush: async () => {
    if (get().flushing || get().queue.length === 0) return 0;
    set({ flushing: true });
    let sent = 0;
    const remaining: ReportDraft[] = [];
    for (const d of get().queue) {
      try {
        await sendReport(d);
        sent++;
      } catch (e) {
        // Keep it only if it failed for lack of signal; a rejected report would never succeed.
        if (e instanceof ApiError && e.offline) remaining.push(d);
      }
    }
    set({ queue: remaining, flushing: false });
    await AsyncStorage.setItem(KEY, JSON.stringify(remaining));
    return sent;
  },
}));

// Retries the queue whenever the phone comes back online.
export function watchConnection(onSent: (n: number) => void) {
  return NetInfo.addEventListener(async (s) => {
    if (s.isConnected && s.isInternetReachable !== false) {
      const n = await useReportQueue.getState().flush();
      if (n > 0) onSent(n);
    }
  });
}
