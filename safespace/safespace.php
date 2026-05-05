<?php
require_once __DIR__ . '/../app/models/mysqli_db.php';

// Get total number of safe spaces
$query = "SELECT COUNT(*) as total FROM safe_spaces";
$result = $conn->query($query);
$totalSafeSpaces = 0;
if ($result && $row = $result->fetch_assoc()) {
    $totalSafeSpaces = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Space - Campus Safety Monitoring</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FontAwesome Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>

    <!-- Leaflet CSS and Draw Plugin -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />

    <style>
        .sidebar-hidden { transform: translateX(-100%); }
        .sidebar-visible { transform: translateX(0); }
        .toggle-moved { transform: translateX(16rem) translateY(-50%); }
        .toggle-default { transform: translateX(0) translateY(-50%); }
        .content-shifted { margin-left: 16rem; }
        .content-full { margin-left: 0; }
        #map { height: 60vh; width: 100%; border-radius: 12px; }
        .custom-popup .leaflet-popup-content-wrapper {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 8px;
            box-shadow: 0 3px 14px rgba(0,0,0,0.2);
        }
    </style>
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
                    <li class="p-3 rounded flex items-center space-x-2 bg-[#AB1E5C]">
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
            <!-- Dashboard Tiles -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-700">Total Safe Spaces</h3>
                    <p class="text-2xl font-bold text-[#D12E79]"><?php echo $totalSafeSpaces; ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-700">Total Incidents</h3>
                    <p class="text-2xl font-bold text-[#D12E79]">0</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-700">Active Users Today</h3>
                    <p class="text-2xl font-bold text-[#D12E79]">0</p>
                </div>
            </div>

            <!-- Map Controls -->
            <div class="flex gap-2 mb-4">
                <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600" onclick="toggleMarkers('safe')">Show Safe Spaces</button>
                <button class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600" onclick="toggleMarkers('incidents')">Show Incidents</button>
                <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" onclick="toggleMarkers('all')">Show All</button>
            </div>

            <!-- Main Content Area -->
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Map Section (70%) -->
                <div class="w-full md:w-[70%] bg-white rounded-lg shadow-md p-4">
                    <div id="map"></div>
                </div>

                <!-- Form Section (30%) -->
                <div class="w-full md:w-[30%] bg-white rounded-lg shadow-md p-4">
                    <h2 class="text-xl font-bold mb-4">Mark Safe Space</h2>
                    <form id="safeSpaceForm" class="space-y-4">
                        <input type="hidden" id="coordinates" name="coordinates">
                        <p class="text-sm text-gray-600 mb-4">Draw a polygon or circle on the map to mark a safe zone</p>
                        <p class="text-sm text-gray-600 mb-4" id="defaultMessage">No safe zone marked yet. Please draw on the map to mark a safe zone.</p>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#D12E79] focus:ring focus:ring-[#D12E79] focus:ring-opacity-50" required></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Time Active</label>
                            <select id="timeActive" name="timeActive" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#D12E79] focus:ring focus:ring-[#D12E79] focus:ring-opacity-50">
                                <option value="24">24 Hours</option>
                                <option value="48">48 Hours</option>
                                <option value="72">72 Hours</option>
                                <option value="168">1 Week</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-[#D12E79] text-white py-2 px-4 rounded-md hover:bg-[#AB1E5C] focus:outline-none focus:ring-2 focus:ring-[#D12E79] focus:ring-opacity-50">Submit</button>
                    </form>
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
                sidebar.classList.toggle('sidebar-hidden');
                sidebar.classList.toggle('sidebar-visible');
                toggleButton.classList.toggle('toggle-default');
                toggleButton.classList.toggle('toggle-moved');
                mainContent.classList.toggle('content-full');
                mainContent.classList.toggle('content-shifted');
            });
        });
    </script>

    <!-- Leaflet JS, Draw Plugin and Map Initialization -->
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="js/safespace.js"></script>
</body>
</html>
