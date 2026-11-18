// Campus landmarks, shared with the mobile app (mobile/src/lib/campus.ts), which says
// where each position comes from. Main Gate, Sports Complex and Parking Zone are from
// OpenStreetMap; the rest are read from satellite imagery and are approximate.
const lpuLocations = [
    {
        name: "Block 32",
        lat: 31.2526,
        lng: 75.7027,
        type: "academic",
        description: "Academic block with classrooms and labs"
    },
    {
        name: "Block 34",
        lat: 31.2524,
        lng: 75.7046,
        type: "academic",
        description: "Academic block with lecture halls"
    },
    {
        name: "Girls Hostel",
        lat: 31.2570,
        lng: 75.7050,
        type: "residential",
        description: "Girls residential area"
    },
    {
        name: "Boys Hostel",
        lat: 31.2492,
        lng: 75.7000,
        type: "residential",
        description: "Boys residential area"
    },
    {
        name: "Uni Mall",
        lat: 31.2549,
        lng: 75.7046,
        type: "commercial",
        description: "Shopping and dining complex"
    },
    {
        name: "Main Gate",
        lat: 31.2606,
        lng: 75.7068,
        type: "entrance",
        description: "Main entrance to the campus"
    },
    {
        name: "Library",
        lat: 31.2527,
        lng: 75.7037,
        type: "academic",
        description: "Central library and study area"
    },
    {
        name: "Sports Complex",
        lat: 31.2482,
        lng: 75.7018,
        type: "recreational",
        description: "Sports facilities and grounds"
    },
    {
        name: "Parking Zone",
        lat: 31.2530,
        lng: 75.7057,
        type: "commercial",
        description: "Campus parking and transit area"
    }
];

// Export the locations array
if (typeof module !== 'undefined' && module.exports) {
    module.exports = lpuLocations;
}
