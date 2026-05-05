<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FontAwesome Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>

    <style>
        .sidebar-hidden { transform: translateX(-100%); }
        .sidebar-visible { transform: translateX(0); }
        .toggle-moved { transform: translateX(16rem) translateY(-50%); }
        .toggle-default { transform: translateX(0) translateY(-50%); }
        .content-shifted { margin-left: 16rem; }
        .content-full { margin-left: 0; }
        #map { height: 100%; width: 100%; }
    </style>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    
    <!-- Load locations data -->
    <script src="locations.js"></script>
</head>
<body class="bg-[#F9E9F0] text-[#333333]">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed w-64 bg-[#D12E79] text-white p-5 flex flex-col h-full z-40 transition-transform duration-300 ease-in-out sidebar-hidden md:sidebar-visible">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-shield-halved text-3xl"></i>
                    <span class="text-lg font-bold">User Name</span>
                </div>
            </div>
            <nav>
                <ul>
                    <li class="p-3 rounded flex items-center space-x-2 hover:bg-[#AB1E5C] cursor-pointer" onclick="location.href='dashboard.php'">
                        <i class="fa-solid fa-house"></i> <span>Home</span>
                    </li>
                    <li class="p-3 rounded flex items-center space-x-2 hover:bg-[#AB1E5C] cursor-pointer" onclick="location.href='report.php'">
                        <i class="fa-solid fa-file"></i> <span>Reports</span>
                    </li>
                    <li class="p-3 rounded flex items-center space-x-2 hover:bg-[#AB1E5C] cursor-pointer">
                        <i class="fa-solid fa-chart-bar"></i> <span>Analytics</span>
                    </li>
                    <li class="p-3 rounded flex items-center space-x-2 hover:bg-[#AB1E5C] cursor-pointer" onclick="location.href='map.php'">
                        <i class="fa-solid fa-map"></i> <span>Map</span>
                    </li>
                    <li class="p-3 rounded flex items-center space-x-2 hover:bg-[#AB1E5C] cursor-pointer" onclick="location.href='safespace.php'">
                        <i class="fa-solid fa-shield-heart"></i> <span>Safe Space</span>
                    </li>
                    <li class="p-3 rounded flex items-center space-x-2 hover:bg-[#AB1E5C] cursor-pointer" onclick="location.href='walkwithus.php'">
                        <i class="fa-solid fa-person-walking"></i> <span>Walk With Us</span>
                    </li>
                    <li class="p-3 rounded flex items-center space-x-2 hover:bg-[#AB1E5C] cursor-pointer" onclick="location.href='templates.php'">
                        <i class="fa-solid fa-file-lines"></i> <span>Templates</span>
                    </li>
                    <li class="p-3 rounded flex items-center space-x-2 hover:bg-[#AB1E5C] cursor-pointer" onclick="window.location.href='settings.php'">
                        <i class="fa-solid fa-gear"></i> <span>Settings</span>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Sidebar Toggle Button -->
        <button id="sidebarToggle" class="fixed left-0 top-1/2 bg-[#D12E79] text-white p-2 rounded-r z-50 transition-transform duration-300 ease-in-out toggle-default md:toggle-moved">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main id="mainContent" class="flex-1 p-10 transition-all duration-300 ease-in-out content-full md:content-shifted">
            <div id="content" class="flex flex-col md:flex-row gap-6 w-full max-w-screen-xl mx-auto px-4">
                <!-- Map Section -->
                <div class="w-full md:w-[70%] h-[calc(100vh-10rem)] rounded-lg shadow-lg bg-white">
                    <div id="map" class="w-full h-full rounded-lg"></div>
                </div>

                <!-- Dashboard Tiles -->
                <div class="w-full md:w-[40%] space-y-6">
                    <!-- Active Incidents Tile -->
                    <div class="bg-white p-4 rounded-lg shadow-lg mb-6" style="height: 300px; overflow-y: auto;">
                        <h2 class="text-xl font-bold mb-4 flex items-center space-x-2">
                            <i class="fa-solid fa-bell text-red-500"></i>
                            <span>Active Incidents</span>
                        </h2>
                        <div class="space-y-3">
                            <?php
require_once __DIR__ . '/../app/models/mysqli_db.php';

$sql = "SELECT * FROM incidents WHERE status='pending' ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<div class="p-3 rounded-lg border">';
        echo '<div class="flex justify-between items-center">';
        echo '<span class="font-medium">' . htmlspecialchars($row['incident_type'] ?? 'Unknown') . '</span>';
        echo '<span class="text-sm text-gray-500">' . date('g:i A', strtotime($row['created_at'])) . '</span>';
        echo '</div>';
        echo '<p class="text-sm text-gray-600 mt-1">' . htmlspecialchars($row['location']) . '</p>';
        echo '<div class="mt-2 flex justify-between items-center">';
        echo '<span class="px-2 py-1 text-xs rounded-full ' . getSeverityClass($row['severity'] ?? 'low') . '">' . ucfirst($row['severity'] ?? 'low') . '</span>';
        echo '<button class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-600 hover:bg-green-200" onclick="markResolved(' . $row['id'] . ')">Mark Resolved</button>';
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '<div class="text-center text-gray-500">No active incidents</div>';
}



function getSeverityClass($severity) {
    switch ($severity) {
        case 'high': return 'bg-red-100 text-red-600';
        case 'medium': return 'bg-yellow-100 text-yellow-600';
        default: return 'bg-green-100 text-green-600';
    }
}
?>
                        </div>
                    </div>

                    <!-- Heat Map Insights Tile -->
                    <div class="bg-white p-4 rounded-lg shadow-lg mb-6">
                        <h2 class="text-xl font-bold mb-4 flex items-center space-x-2">
                            <i class="fa-solid fa-fire text-orange-500"></i>
                            <span>Heat Map Insights</span>
                        </h2>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 bg-red-500 rounded-full"></div>
                                <span>High Risk Area</span>
                                <span class="ml-auto text-gray-500">3</span>
                            </div>
                            <button class="w-full mt-3 px-4 py-2 text-sm rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200">View Full Heat Map</button>
                        </div>
                    </div>

                    <!-- Recent Reports Tile -->
                    <div class="bg-white p-4 rounded-lg shadow-lg mb-6">
                        <h2 class="text-xl font-bold mb-4 flex items-center space-x-2">
                            <i class="fa-solid fa-chart-line text-purple-500"></i>
                            <span>Recent Reports</span>
                        </h2>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Today</span>
                                <span class="font-medium">3</span>
                            </div>
                            <div class="flex justify-between">
                                <span>This Week</span>
                                <span class="font-medium">10</span>
                            </div>
                        </div>
                    </div>

                    <!-- Frequent Incidents Tile -->
                    <div class="bg-white p-4 rounded-lg shadow-lg mb-6">
                        <h2 class="text-xl font-bold mb-4 flex items-center space-x-2">
                            <i class="fa-solid fa-exclamation-triangle text-yellow-500"></i>
                            <span>Frequent Incidents</span>
                        </h2>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2">
                                <span class="w-20">Harassment</span>
                                <div class="flex-1 h-2 bg-gray-200 rounded-full">
                                    <div class="h-2 bg-red-500 rounded-full" style="width: 50%"></div>
                                </div>
                                <span class="text-sm text-gray-500">50%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Location Risk Tile -->
                    <div class="bg-white p-4 rounded-lg shadow-lg mb-6">
                        <h2 class="text-xl font-bold mb-4 flex items-center space-x-2">
                            <i class="fa-solid fa-location-dot text-blue-500"></i>
                            <span>Location Risk</span>
                        </h2>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span>Hostel Area</span>
                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-600">High</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.getElementById('sidebarToggle');
            const mainContent = document.getElementById('mainContent');

            function setInitialState() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('sidebar-hidden');
                    sidebar.classList.remove('sidebar-visible');
                    toggleButton.classList.add('toggle-default');
                    toggleButton.classList.remove('toggle-moved');
                    mainContent.classList.add('content-full');
                    mainContent.classList.remove('content-shifted');
                } else {
                    sidebar.classList.remove('sidebar-hidden');
                    sidebar.classList.add('sidebar-visible');
                    toggleButton.classList.remove('toggle-default');
                    toggleButton.classList.add('toggle-moved');
                    mainContent.classList.remove('content-full');
                    mainContent.classList.add('content-shifted');
                }
            }
            
            setInitialState();
            window.addEventListener('resize', setInitialState);

            toggleButton.addEventListener('click', function() {
                if (sidebar.classList.contains('sidebar-hidden')) {
                    sidebar.classList.remove('sidebar-hidden');
                    sidebar.classList.add('sidebar-visible');
                    toggleButton.classList.remove('toggle-default');
                    toggleButton.classList.add('toggle-moved');
                    mainContent.classList.remove('content-full');
                    mainContent.classList.add('content-shifted');
                } else {
                    sidebar.classList.add('sidebar-hidden');
                    sidebar.classList.remove('sidebar-visible');
                    toggleButton.classList.add('toggle-default');
                    toggleButton.classList.remove('toggle-moved');
                    mainContent.classList.add('content-full');
                    mainContent.classList.remove('content-shifted');
                }
            });
        });
    </script>

    <script>
        function markResolved(id) {
            fetch('resolve-incident.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to mark incident as resolved');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking the incident as resolved');
            });
        }
    </script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>

    <script>
        // Initialize the map
        const map = L.map('map').setView([31.2533, 75.7050], 15);

        // Add a tile layer from OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Define marker icons for different location types
        const markerIcons = {
            academic: L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background-color: #4CAF50; padding: 8px; border-radius: 50%; border: 2px solid white;"><i class="fa-solid fa-graduation-cap text-white"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            }),
            residential: L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background-color: #2196F3; padding: 8px; border-radius: 50%; border: 2px solid white;"><i class="fa-solid fa-home text-white"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            }),
            commercial: L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background-color: #FF9800; padding: 8px; border-radius: 50%; border: 2px solid white;"><i class="fa-solid fa-shopping-cart text-white"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            }),
            entrance: L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background-color: #9C27B0; padding: 8px; border-radius: 50%; border: 2px solid white;"><i class="fa-solid fa-door-open text-white"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            }),
            recreational: L.divIcon({
                className: 'custom-div-icon',
                html: '<div style="background-color: #F44336; padding: 8px; border-radius: 50%; border: 2px solid white;"><i class="fa-solid fa-futbol text-white"></i></div>',
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            })
        };

        // Add CSS for pulsing effect
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7); }
                70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(255, 0, 0, 0); }
                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 0, 0, 0); }
            }
            .incident-marker {
                animation: pulse 2s infinite;
                background-color: #FF0000;
                border: 3px solid #FF3333;
                box-shadow: 0 0 15px rgba(255, 0, 0, 0.5);
            }
        `;
        document.head.appendChild(style);

        // Create a red marker icon for incidents
        const incidentIcon = L.divIcon({
            className: 'custom-div-icon',
            html: '<div class="incident-marker" style="padding: 10px; border-radius: 50%;"><i class="fa-solid fa-exclamation text-white" style="font-size: 16px;"></i></div>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });


        // Fetch and add incident markers
        fetch('fetch_incidents.php')
            .then(response => response.json())
            .then(incidents => {
                incidents.forEach(incident => {
                    if (incident.latitude && incident.longitude) {
                        const marker = L.marker([incident.latitude, incident.longitude], {
                            icon: incidentIcon
                        }).addTo(map);

                        marker.bindPopup(`
                            <div class="p-2">
                                <h3 class="font-bold text-lg mb-1 text-red-600">${incident.incident_type}</h3>
                                <p class="text-gray-600">${incident.description || 'No description available'}</p>
                                <p class="text-sm text-gray-500 mt-1">Location: ${incident.location}</p>
                                <p class="text-sm text-gray-500">Reported: ${new Date(incident.created_at).toLocaleString()}</p>
                            </div>
                        `, {
                            maxWidth: 300,
                            className: 'custom-popup'
                        });
                    }
                });
            })
            .catch(error => console.error('Error fetching incidents:', error));

        // Add markers for each location
        lpuLocations.forEach(location => {
            const marker = L.marker([location.lat, location.lng], {
                icon: markerIcons[location.type]
            }).addTo(map);

            // Add popup with location information
            marker.bindPopup(`
                <div class="p-2">
                    <h3 class="font-bold text-lg mb-1">${location.name}</h3>
                    <p class="text-gray-600">${location.description}</p>
                    <p class="text-sm text-gray-500 mt-1">Type: ${location.type.charAt(0).toUpperCase() + location.type.slice(1)}</p>
                </div>
            `, {
                maxWidth: 300,
                className: 'custom-popup'
            });
        });
    </script>

    <script>
        function markResolved(id) {
            fetch('resolve-incident.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to mark incident as resolved');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking the incident as resolved');
            });
        }
    </script>
</body>
</html>
