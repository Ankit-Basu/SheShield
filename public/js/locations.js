const lpuLocations = [
    {
        name: "Block 32",
        lat: 31.2533,
        lng: 75.7050,
        type: "academic",
        description: "Academic block with classrooms and labs"
    },
    {
        name: "Block 34",
        lat: 31.2528,
        lng: 75.7045,
        type: "academic",
        description: "Academic block with lecture halls"
    },
    {
        name: "Girls Hostel",
        lat: 31.2545,
        lng: 75.7035,
        type: "residential",
        description: "Girls residential area"
    },
    {
        name: "Boys Hostel",
        lat: 31.2520,
        lng: 75.7060,
        type: "residential",
        description: "Boys residential area"
    },
    {
        name: "Uni Mall",
        lat: 31.2538,
        lng: 75.7042,
        type: "commercial",
        description: "Shopping and dining complex"
    },
    {
        name: "Main Gate",
        lat: 31.2550,
        lng: 75.7030,
        type: "entrance",
        description: "Main entrance to the campus"
    },
    {
        name: "Library",
        lat: 31.2535,
        lng: 75.7048,
        type: "academic",
        description: "Central library and study area"
    },
    {
        name: "Sports Complex",
        lat: 31.2515,
        lng: 75.7055,
        type: "recreational",
        description: "Sports facilities and grounds"
    }
];

// Export the locations array
if (typeof module !== 'undefined' && module.exports) {
    module.exports = lpuLocations;
}