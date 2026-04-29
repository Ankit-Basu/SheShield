<?php
require_once 'utils/session.php';
Session::start();

if (!isset($_SESSION['user_id'])) {
    die('User not logged in');
}
$user_id = $_SESSION['user_id'];
?>
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

    <!-- Trae AI Theme -->
    <link href="src/trae-theme.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #1E1E2E 0%, #2E2E4E 100%);
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: hidden;
        }
        
        /* Glassmorphic Effects */
        .glass-effect {
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(215, 109, 119, 0.3);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        .sidebar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform, opacity;
            background: rgba(46, 46, 78, 0.3);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-right: 1px solid rgba(74, 30, 115, 0.3);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            height: 100vh;
            overflow-y: auto;
        }

        .trae-sidebar-item {
            transition: all 0.3s ease;
            border: 1px solid transparent;
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            color: #F0F0F0;
        }

        .trae-sidebar-item.active {
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.5), rgba(215, 109, 119, 0.5));
            border: 1px solid rgba(215, 109, 119, 0.3);
        }
        
        .trae-sidebar-item:hover {
            background: rgba(215, 109, 119, 0.15);
            border: 1px solid rgba(215, 109, 119, 0.2);
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.5), rgba(215, 109, 119, 0.5));
            color: #F0F0F0;
        }
        
        .trae-card {
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(74, 30, 115, 0.25);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            transition: all 0.3s ease;
        }

        #map { height: 100%; width: 100%; }

        /* Sidebar behavior */
        .sidebar-hidden { 
            transform: translateX(-100%);
            opacity: 0;
        }
        .sidebar-visible { 
            transform: translateX(0);
            opacity: 1;
        }
        .toggle-moved { 
            transform: translateX(16rem) translateY(-50%);
        }
        .toggle-default { 
            transform: translateX(0) translateY(-50%);
        }
        .content-shifted { 
            margin-left: 16rem;
            transition: all 0.3s ease-in-out;
        }
        .content-full { 
            margin-left: 0;
            transition: all 0.3s ease-in-out;
        }
        

        
        #sidebarToggle {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }
        
        @media (min-width: 768px) {
            #sidebar {
                transform: translateX(0);
                opacity: 1;
            }
            #sidebarToggle {
                transform: translateX(16rem) translateY(-50%);
            }
            #mainContent {
                margin-left: 16rem;
            }
        }
    </style>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <!-- Leaflet.markercluster CSS -->
    <link href="src/trae-theme.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    
    <!-- Load locations data -->
    <script src="locations.js"></script>
</head>
<body class="text-white overflow-x-hidden bg-[#1E1E2E]">
    <div class="flex h-screen relative z-0">
        <!-- Background gradient shapes -->
        <div class="absolute -top-[200px] -right-[200px] w-[500px] h-[500px] bg-gradient-to-r from-[rgba(74,30,115,0.2)] to-[rgba(215,109,119,0.2)] rounded-full blur-3xl -z-10 animate-pulse-slow opacity-70"></div>
        <div class="absolute -bottom-[200px] -left-[200px] w-[500px] h-[500px] bg-gradient-to-r from-[rgba(215,109,119,0.2)] to-[rgba(74,30,115,0.2)] rounded-full blur-3xl -z-10 animate-pulse-slow opacity-70"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed w-64 text-white p-5 flex flex-col h-full z-40 sidebar-visible">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center space-x-4 w-full">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-[#4A1E73] to-[#D76D77] flex items-center justify-center overflow-hidden flex-shrink-0">
                        <?php
                        require_once 'includes/profile_image_handler.php';
                        $profileImage = null;
                        if (isset($_SESSION['user_id'])) {
                            $profileImage = getProfileImage($_SESSION['user_id']);
                        }
                        if ($profileImage) {
                            echo '<img src="' . htmlspecialchars($profileImage) . '" class="w-full h-full object-cover profile-image" alt="Profile Picture">';
                        } else {
                            echo '<i class="fa-solid fa-user text-xl text-white"></i>';
                        }
                        ?>
                    </div>
                    <div class="flex-grow">
                        <span class="text-lg font-bold text-gradient"><?php 
                        if (!isset($_SESSION)) { session_start(); }
                        $firstName = isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : '';
                        $lastName = isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : '';
                        echo !empty($firstName) || !empty($lastName) ? trim("$firstName $lastName") : 'User Name';
                        ?></span>
                    </div>
                </div>
            </div>
            <nav>
                <ul>
                <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer active" onclick="window.location.href='dashboard.php'">
                        <i class="fa-solid fa-house"></i> <span>Home</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='report.php'">
                        <i class="fa-solid fa-file"></i> <span>Reports</span>
                    </li>
                    <li>
                        <a href="analytics.php" class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer">
                        <i class="fa-solid fa-chart-bar"></i> <span>Analytics</span>
                        </a>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='map.php'">
                        <i class="fa-solid fa-map"></i> <span>Map</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='safespace.php'">
                        <i class="fa-solid fa-shield-heart"></i> <span>Safe Space</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='walkwithus.php'">
                        <i class="fa-solid fa-person-walking"></i> <span>Walk With Us</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='templates.php'">
                        <i class="fa-solid fa-file-lines"></i> <span>Templates</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='settings.php'">
                        <i class="fa-solid fa-gear"></i> <span>Settings</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='auth/logout.php'">
                        <i class="fa-solid fa-sign-out-alt"></i> <span>Logout</span>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Sidebar Toggle Button -->
        <button id="sidebarToggle" class="fixed left-0 top-1/2 text-white p-3 rounded-r z-50 transition-all duration-300 ease-in-out toggle-default md:toggle-moved hover:shadow-lg hover:translate-x-1 bg-gradient-to-r from-[rgba(74,30,115,0.8)] to-[rgba(215,109,119,0.8)] backdrop-blur-md border border-[rgba(215,109,119,0.3)]">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main id="mainContent" class="flex-1 p-10 transition-all duration-300 ease-in-out">
            <div id="content" class="flex flex-col gap-6 w-full max-w-screen-xl mx-auto px-4">
                <!-- Heat Map Description -->
                <div class="glass-effect p-6 rounded-lg shadow-lg mb-2">
                    <h2 class="text-2xl font-bold mb-3 text-gradient flex items-center gap-2">
                        <i class="fa-solid fa-map-location-dot"></i>
                        Campus Safety Heat Map
                    </h2>
                    <p class="text-white leading-relaxed">
                        This interactive heat map visualizes safety incident density across campus areas. Red zones indicate higher incident concentration, while green areas show lower activity. Use this map to identify safe routes and stay informed about campus safety patterns.
                    </p>
                </div>

                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Map Section -->
                    <div class="w-full md:w-[70%] h-[calc(100vh-15rem)] rounded-lg shadow-lg glass-effect">
                    <div id="map" class="w-full h-full rounded-lg"></div>
                </div>

                <!-- Dashboard Tiles -->
                <div class="w-full md:w-[40%] space-y-6 overflow-y-hidden max-h-[calc(100vh-10rem)]">
                    <!-- Active Incidents Tile -->
                    <div class="glass-effect p-4 rounded-lg shadow-lg mb-6" style="height: 450px; overflow-y: auto;">
                        <h2 class="text-xl font-bold mb-4 flex items-center space-x-2">
                            <i class="fa-solid fa-bell text-red-500"></i>
                            <span>Active Incidents</span>
                        </h2>
                        <div class="space-y-3">
                            <?php
require_once 'mysqli_db.php';

$sql = "SELECT * FROM incidents WHERE status='pending' AND user_id = " . Session::getUserId() . " ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<div class="p-3 rounded-lg glass-effect mb-3">';
        echo '<div class="flex justify-between items-center">';
        echo '<span class="font-medium text-white">' . htmlspecialchars($row['incident_type'] ?? 'Unknown') . '</span>';
        echo '<span class="text-sm text-gray-300">' . date('g:i A', strtotime($row['created_at'])) . '</span>';
        echo '</div>';
        echo '<p class="text-sm text-gray-300 mt-1">' . htmlspecialchars($row['location']) . '</p>';
        echo '<div class="mt-2 flex justify-between items-center">';
        echo '<span class="px-2 py-1 text-xs rounded-full ' . getSeverityClass($row['severity'] ?? 'low') . '">' . ucfirst($row['severity'] ?? 'low') . '</span>';
        echo '<button class="px-3 py-1 text-sm rounded-full bg-gradient-to-r from-[rgba(74,30,115,0.7)] to-[rgba(215,109,119,0.7)] text-white hover:from-[rgba(74,30,115,0.9)] hover:to-[rgba(215,109,119,0.9)]" onclick="markResolved(' . $row['id'] . ')">Mark Resolved</button>';
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '<div class="text-center text-gray-300">No active incidents</div>';
}



function getSeverityClass($severity) {
    switch ($severity) {
        case 'high': return 'bg-gradient-to-r from-[rgba(220,38,38,0.7)] to-[rgba(248,113,113,0.7)] text-white';
        case 'medium': return 'bg-gradient-to-r from-[rgba(217,119,6,0.7)] to-[rgba(251,191,36,0.7)] text-white';
        default: return 'bg-gradient-to-r from-[rgba(5,150,105,0.7)] to-[rgba(16,185,129,0.7)] text-white';
    }
}
?>
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
            
            // Get initial state from localStorage or screen size
            let isSidebarVisible = localStorage.getItem('sidebarVisible') !== null ?
                localStorage.getItem('sidebarVisible') === 'true' :
                window.innerWidth >= 768;

            function updateSidebarState(visible, saveState = true) {
                isSidebarVisible = visible;
                
                // Save state to localStorage
                if (saveState) {
                    localStorage.setItem('sidebarVisible', visible);
                }

                // Update DOM with CSS classes
                if (visible) {
                    sidebar.style.transform = 'translateX(0)';
                    sidebar.style.opacity = '1';
                    toggleButton.style.transform = 'translateX(16rem) translateY(-50%)';
                    mainContent.style.marginLeft = '16rem';
                } else {
                    sidebar.style.transform = 'translateX(-100%)';
                    sidebar.style.opacity = '0';
                    toggleButton.style.transform = 'translateX(0) translateY(-50%)';
                    mainContent.style.marginLeft = '0';
                }
            }

            // Set initial state without saving
            updateSidebarState(isSidebarVisible, false);

            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    const shouldBeVisible = window.innerWidth >= 768;
                    if (shouldBeVisible !== isSidebarVisible) {
                        updateSidebarState(shouldBeVisible);
                    }
                }, 250);
            });

            // Handle toggle button click
            toggleButton.addEventListener('click', function() {
                updateSidebarState(!isSidebarVisible);
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
    <!-- Leaflet.markercluster JS -->
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

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

        // Define incident type icons with different colors
        const incidentTypeIcons = {
            assault: L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="incident-marker" style="background-color: #FF0000; padding: 10px; border-radius: 50%;"><i class="fa-solid fa-exclamation text-white" style="font-size: 16px;"></i></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            }),
            harassment: L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="incident-marker" style="background-color: #FF6B00; padding: 10px; border-radius: 50%;"><i class="fa-solid fa-exclamation text-white" style="font-size: 16px;"></i></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            }),
            suspicious_activity: L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="incident-marker" style="background-color: #FFB700; padding: 10px; border-radius: 50%;"><i class="fa-solid fa-exclamation text-white" style="font-size: 16px;"></i></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            }),
            other: L.divIcon({
                className: 'custom-div-icon',
                html: '<div class="incident-marker" style="background-color: #9C27B0; padding: 10px; border-radius: 50%;"><i class="fa-solid fa-exclamation text-white" style="font-size: 16px;"></i></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            })
        };


        // Create a marker cluster group
        const markerClusterGroup = L.markerClusterGroup({
            showCoverageOnHover: false,
            spiderfyOnMaxZoom: true,
            removeOutsideVisibleBounds: true,
            maxClusterRadius: 35
        });

        // Create a map to store user-specific colors
        const userColors = {};
        let colorIndex = 0;
        const colors = ['#FF0000', '#00FF00', '#0000FF', '#FF00FF', '#00FFFF', '#FFFF00'];

        // Fetch and add incident markers
        fetch('fetch_incidents.php')
            .then(response => response.json())
            .then(incidents => {
                incidents.forEach(incident => {
                    if (incident.latitude && incident.longitude) {
                        // Assign a unique color for each user
                        if (!userColors[incident.user_id]) {
                            userColors[incident.user_id] = colors[colorIndex % colors.length];
                            colorIndex++;
                        }

                        // Create custom icon with user-specific color
                        const customIcon = L.divIcon({
                            className: 'custom-div-icon',
                            html: `<div class="incident-marker" style="background-color: ${userColors[incident.user_id]}; padding: 10px; border-radius: 50%;"><i class="fa-solid fa-exclamation text-white" style="font-size: 16px;"></i></div>`,
                            iconSize: [40, 40],
                            iconAnchor: [20, 20]
                        });

                        const marker = L.marker([incident.latitude, incident.longitude], {
                            icon: customIcon
                        });

                        marker.bindPopup(`
                            <div class="p-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <div style="background-color: ${userColors[incident.user_id]}" class="w-3 h-3 rounded-full"></div>
                                    <span class="font-medium">${incident.first_name} ${incident.last_name}</span>
                                </div>
                                <h3 class="font-bold text-lg mb-1" style="color: ${userColors[incident.user_id]}">${incident.incident_type.replace('_', ' ').toUpperCase()}</h3>
                                <p class="text-gray-600">${incident.description || 'No description available'}</p>
                                <p class="text-sm text-gray-500 mt-1">Location: ${incident.location}</p>
                                <p class="text-sm text-gray-500">Time: ${new Date(incident.created_at).toLocaleString()}</p>
                            </div>
                        `, {
                            maxWidth: 300,
                            className: 'custom-popup'
                        });
                        
                        // Add marker to cluster group instead of directly to map
                        markerClusterGroup.addLayer(marker);
                    }
                });
                
                // Add the cluster group to the map
                map.addLayer(markerClusterGroup);
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