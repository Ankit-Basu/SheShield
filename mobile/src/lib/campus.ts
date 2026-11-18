// LPU landmarks, shared with the website (public/js/locations.js).
// Main Gate, Sports Complex and Parking Zone are from OpenStreetMap: the covered gate on
// the driveway in from NH44, the cluster of courts and the cricket ground, and the large
// surface car park. The rest are placed on the matching buildings in Esri satellite
// imagery (the academic row south of the central plaza, the large building north of it,
// the residential towers) and are approximate: worth checking on campus.
export const landmarks = [
  { name: 'Main Gate', lat: 31.2606, lng: 75.7068, type: 'entrance' },
  { name: 'Block 32', lat: 31.2526, lng: 75.7027, type: 'academic' },
  { name: 'Block 34', lat: 31.2524, lng: 75.7046, type: 'academic' },
  { name: 'Library', lat: 31.2527, lng: 75.7037, type: 'academic' },
  { name: 'Uni Mall', lat: 31.2549, lng: 75.7046, type: 'commercial' },
  { name: 'Girls Hostel', lat: 31.257, lng: 75.705, type: 'residential' },
  { name: 'Boys Hostel', lat: 31.2492, lng: 75.7, type: 'residential' },
  { name: 'Sports Complex', lat: 31.2482, lng: 75.7018, type: 'recreational' },
  { name: 'Parking Zone', lat: 31.253, lng: 75.7057, type: 'commercial' },
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
