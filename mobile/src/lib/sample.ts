// Realistic sample data shown only when the server can't be reached and nothing is
// saved on the phone yet. Screens show a banner whenever this is on screen.
import type { Me, Report, SafeSpace, Walk } from './queries';
import { readProfile } from './fallback';

const ist = (hoursAgo: number) => {
  const d = new Date(Date.now() - hoursAgo * 3600000 + 5.5 * 3600000);
  return d.toISOString().slice(0, 19).replace('T', ' ');
};

export const sampleReports = (): Report[] => [
  {
    id: 9003,
    type: 'stalking',
    description: 'Someone followed me from the library to the girls hostel gate after the evening lecture.',
    location: 'Library',
    date_time: ist(5),
    severity: 'high',
    status: 'in_progress',
    has_evidence: true,
    created_at: ist(5),
  },
  {
    id: 9002,
    type: 'harassment',
    description: 'Comments shouted from a bike near Uni Mall. Number plate partly visible in the photo.',
    location: 'Uni Mall',
    date_time: ist(30),
    severity: 'medium',
    status: 'pending',
    has_evidence: true,
    created_at: ist(30),
  },
  {
    id: 9001,
    type: 'theft',
    description: 'Phone taken from the reading hall desk while I was away for five minutes.',
    location: 'Block 34',
    date_time: ist(120),
    severity: 'low',
    status: 'resolved',
    has_evidence: false,
    created_at: ist(120),
  },
];

export const sampleWalks = (): Walk[] => [
  { walk_id: 'WALK-SAMPLE-01', pickup_location: 'Library', destination: 'Girls Hostel', request_time: ist(-0.5), status: 'accepted', walker_name: 'Priya S.', walker_phone: null },
  { walk_id: 'WALK-SAMPLE-00', pickup_location: 'Block 32', destination: 'Main Gate', request_time: ist(26), status: 'completed', walker_name: 'Neha K.', walker_phone: null },
];

export const sampleSafeSpaces = (): SafeSpace[] => [
  { id: 1, latitude: 31.2531, longitude: 75.7041, description: 'Campus security desk, open 24 hours', user_name: 'SheShield' },
  { id: 2, latitude: 31.2543, longitude: 75.7036, description: 'Girls hostel warden office', user_name: 'SheShield' },
];

export async function sampleMe(): Promise<Me> {
  const profile = await readProfile();
  const reports = sampleReports();
  return {
    user: {
      id: 0,
      first_name: profile?.first_name ?? 'there',
      last_name: profile?.last_name ?? '',
      email: profile?.email ?? '',
      phone: '',
      emergency_contact_name: '',
      emergency_contact_phone: '',
      photo: null,
      is_admin: false,
      member_since: ist(24 * 30),
    },
    stats: { reports: reports.length, resolved: 1, pending: 1, active_walkers: 6, safe_spaces: 2 },
    latest_reports: reports,
    reports_by_type: reports.map((r) => ({ type: r.type, count: 1 })),
    active_walk: sampleWalks()[0],
  };
}
