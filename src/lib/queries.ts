import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { api } from './api';

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

export const useMe = () => useQuery({ queryKey: keys.me, queryFn: () => api<Me>('api/user/me.php') });

export const useReports = () =>
  useQuery({ queryKey: keys.reports, queryFn: async () => (await api<{ reports: Report[] }>('api/incidents/mine.php')).reports });

export const useWalks = () =>
  useQuery({ queryKey: keys.walks, queryFn: async () => (await api<{ walks: Walk[] }>('api/walks/mine.php')).walks });

export const useSafeSpaces = () =>
  useQuery({ queryKey: keys.safeSpaces, queryFn: () => api<SafeSpace[]>('api/safespaces/list.php'), staleTime: 60_000 });

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
      const previous = qc.getQueryData<Walk[]>(keys.walks);
      qc.setQueryData<Walk[]>(keys.walks, (w) => w?.map((x) => (x.walk_id === walkId ? { ...x, status: 'cancelled' } : x)));
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
