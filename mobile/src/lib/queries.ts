import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from './api';
import { isFallback, READ_TIMEOUT_MS, RETRY_WHILE_FALLBACK_MS, withFallback } from './fallback';
import { sampleMe, sampleReports, sampleSafeSpaces, sampleWalks } from './sample';

export type Report = {
  id: number;
  type: string;
  description: string;
  location: string;
  date_time: string;
  severity: 'low' | 'medium' | 'high';
  status: 'pending' | 'in_progress' | 'resolved' | 'closed' | string;
  has_evidence: boolean;
  created_at: string;
};

export type Walk = {
  walk_id: string;
  pickup_location: string;
  destination: string;
  request_time: string;
  status: 'pending' | 'accepted' | 'rejected' | 'completed' | 'cancelled';
  walker_name?: string | null;
  walker_phone?: string | null;
};

export type Me = {
  user: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    emergency_contact_name: string;
    emergency_contact_phone: string;
    photo: string | null;
    is_admin: boolean;
    member_since: string;
  };
  stats: { reports: number; resolved: number; pending: number; active_walkers: number; safe_spaces: number };
  latest_reports: Report[];
  reports_by_type: { type: string; count: number }[];
  active_walk: Walk | null;
};

export type SafeSpace = { id: number; latitude: number; longitude: number; description: string; user_name: string };

export const keys = {
  me: ['me'] as const,
  reports: ['reports'] as const,
  walks: ['walks'] as const,
  safeSpaces: ['safe-spaces'] as const,
};

// Every read falls back to saved or sample data when the server is asleep, and
// retries every 15 s until live data arrives (see lib/fallback.ts).
const retryWhileFallback = (key: string) => () => (isFallback(key) ? RETRY_WHILE_FALLBACK_MS : false);
const read = { timeoutMs: READ_TIMEOUT_MS };

export const useMe = () =>
  useQuery({
    queryKey: keys.me,
    queryFn: () => withFallback('me', () => api<Me>('api/user/me.php', read), sampleMe),
    refetchInterval: retryWhileFallback('me'),
  });

export const useReports = () =>
  useQuery({
    queryKey: keys.reports,
    queryFn: () => withFallback('reports', async () => (await api<{ reports: Report[] }>('api/incidents/mine.php', read)).reports, sampleReports),
    refetchInterval: retryWhileFallback('reports'),
  });

export type WalkerProfile = { verification_status: 'pending' | 'verified' | 'rejected'; available_from: string | null; available_to: string | null; preferred_areas: string[] };
type WalksData = { walks: Walk[]; walker_profile: WalkerProfile | null };

// One request feeds both your walks and your walker profile.
const walksQuery = {
  queryKey: keys.walks,
  queryFn: async (): Promise<WalksData> => {
    const data = await withFallback<WalksData | Walk[]>(
      'walks',
      () => api<WalksData>('api/walks/mine.php', read),
      () => ({ walks: sampleWalks(), walker_profile: null }),
    );
    // Data saved by an older version of the app was just the list.
    return Array.isArray(data) ? { walks: data, walker_profile: null } : data;
  },
  refetchInterval: retryWhileFallback('walks'),
};

export const useWalks = () => useQuery({ ...walksQuery, select: (d: WalksData) => d.walks });
export const useWalkerProfile = () => useQuery({ ...walksQuery, select: (d: WalksData) => d.walker_profile });

export const useSafeSpaces = () =>
  useQuery({
    queryKey: keys.safeSpaces,
    queryFn: () => withFallback('safe-spaces', () => api<SafeSpace[]>('api/safespaces/list.php', read), sampleSafeSpaces),
    staleTime: 60_000,
    refetchInterval: retryWhileFallback('safe-spaces'),
  });

export function useRequestWalk() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (v: { pickupLocation: string; destination: string; requestTime?: string }) =>
      api<{ walkId: string; walkStatus: string; message: string }>('api/walks/create_request.php', { json: v }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: keys.walks });
      qc.invalidateQueries({ queryKey: keys.me });
    },
  });
}

export function useCancelWalk() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (walkId: string) => api('api/walks/cancel_request.php', { json: { walkId } }),
    // Optimistic: show it cancelled at once, roll back if the server says no.
    onMutate: async (walkId) => {
      await qc.cancelQueries({ queryKey: keys.walks });
      // The cache holds { walks, walker_profile }; only the walk's status changes.
      const previous = qc.getQueryData<WalksData>(keys.walks);
      qc.setQueryData<WalksData>(keys.walks, (d) =>
        d ? { ...d, walks: d.walks.map((x) => (x.walk_id === walkId ? { ...x, status: 'cancelled' } : x)) } : d,
      );
      return { previous };
    },
    onError: (_e, _v, ctx) => ctx?.previous && qc.setQueryData(keys.walks, ctx.previous),
    onSettled: () => {
      qc.invalidateQueries({ queryKey: keys.walks });
      qc.invalidateQueries({ queryKey: keys.me });
    },
  });
}

export function useUpdateProfile() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (v: Partial<Me['user']>) => api('api/user/update_profile.php', { json: v }),
    onSuccess: () => qc.invalidateQueries({ queryKey: keys.me }),
  });
}

export function useResolveReport() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => api('api/incidents/resolve.php', { json: { id } }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: keys.reports });
      qc.invalidateQueries({ queryKey: keys.me });
    },
  });
}

export function useUploadPhoto() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (uri: string) => {
      const ext = (uri.split('.').pop() || 'jpg').toLowerCase();
      const form = new FormData();
      form.append('profile_image', { uri, name: `photo.${ext === 'png' ? 'png' : 'jpg'}`, type: ext === 'png' ? 'image/png' : 'image/jpeg' } as any);
      return api('api/user/upload_photo.php', { form, timeoutMs: 60000 });
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: keys.me }),
  });
}

export type WalkerForm = {
  phone: string;
  type: 'student' | 'staff' | 'security';
  gender: 'female' | 'male' | 'other';
  idProofType: 'University ID' | 'Staff ID' | 'Government ID';
  idProofNumber: string;
  availableFrom: string;
  availableUntil: string;
  preferredAreas: string[];
};

export function useRegisterWalker() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (v: WalkerForm) => api<{ message: string }>('api/walks/register_walker.php', { json: v }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: keys.walks });
      qc.invalidateQueries({ queryKey: keys.me });
    },
  });
}
