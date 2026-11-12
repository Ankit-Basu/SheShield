// LPU landmarks (same list as the website) and helplines.
export const landmarks = [
  { name: 'Main Gate', lat: 31.255, lng: 75.703, type: 'entrance' },
  { name: 'Block 32', lat: 31.2533, lng: 75.705, type: 'academic' },
  { name: 'Block 34', lat: 31.2528, lng: 75.7045, type: 'academic' },
  { name: 'Library', lat: 31.2535, lng: 75.7048, type: 'academic' },
  { name: 'Uni Mall', lat: 31.2538, lng: 75.7042, type: 'commercial' },
  { name: 'Girls Hostel', lat: 31.2545, lng: 75.7035, type: 'residential' },
  { name: 'Boys Hostel', lat: 31.252, lng: 75.706, type: 'residential' },
  { name: 'Sports Complex', lat: 31.2515, lng: 75.7055, type: 'recreational' },
  { name: 'Parking Zone', lat: 31.2525, lng: 75.7058, type: 'commercial' },
] as const;

export const LPU_CENTER = { lat: 31.2536, lng: 75.7037 };

export const helplines = [
  { number: '112', label: 'National emergency' },
  { number: '1091', label: "Women's helpline" },
  { number: '100', label: 'Police' },
  { number: '181', label: 'Women in distress' },
] as const;

export const incidentTypes = [
  { value: 'harassment', label: 'Harassment', icon: 'TriangleAlert' },
  { value: 'stalking', label: 'Stalking', icon: 'Eye' },
  { value: 'theft', label: 'Theft', icon: 'Wallet' },
  { value: 'assault', label: 'Assault', icon: 'ShieldAlert' },
  { value: 'cybercrime', label: 'Cybercrime', icon: 'Smartphone' },
  { value: 'domestic', label: 'Domestic violence', icon: 'House' },
  { value: 'other', label: 'Other', icon: 'CircleHelp' },
] as const;

export const typeLabel = (v: string) =>
  incidentTypes.find((t) => t.value === v)?.label ?? v.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
