<?php
session_start();
require_once 'mysqli_db.php';

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
    <link href="src/trae-theme.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>

    <!-- Leaflet CSS and Draw Plugin -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />

    <style>
        body {
            background: linear-gradient(135deg, #1E1E2E 0%, #2E2E4E 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Glassmorphic Effects */
        .glass-effect {
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(74, 30, 115, 0.2);
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
        
        .trae-card {
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(74, 30, 115, 0.25);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .trae-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.45);
            border-color: rgba(215, 109, 119, 0.5);
        }
        
        .trae-card:hover::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border: 2px solid rgba(215, 109, 119, 0.5);
            border-radius: inherit;
            animation: glowingOutline 2s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes glowingOutline {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        
        .trae-sidebar-item {
            transition: all 0.3s ease;
            border: 1px solid transparent;
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
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
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(46, 46, 78, 0.3);
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.7), rgba(215, 109, 119, 0.7));
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.9), rgba(215, 109, 119, 0.9));
        }

        /* Animations */
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse-slow {
            0% { opacity: 0.7; }
            50% { opacity: 0.9; }
            100% { opacity: 0.7; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out forwards;
        }
        
        .animate-pulse-slow {
            animation: pulse-slow 8s ease-in-out infinite;
        }

        /* Original sidebar behavior */
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

        #map { 
            height: 60vh; 
            width: 100%; 
            border-radius: 12px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .custom-popup .leaflet-popup-content-wrapper {
            background: rgba(46, 46, 78, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(74, 30, 115, 0.2);
            border-radius: 8px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            color: #F0F0F0;
        }
    </style>
</head>
<body class="bg-[#1E1E2E] text-[#F0F0F0] opacity-0 transition-opacity duration-300">
    <div class="flex h-screen overflow-hidden relative z-0">
        <!-- Background gradient shapes -->
        <div class="absolute -top-[300px] -right-[300px] w-[600px] h-[600px] bg-gradient-to-r from-[rgba(74,30,115,0.3)] to-[rgba(215,109,119,0.3)] rounded-full blur-3xl -z-10 animate-pulse-slow"></div>
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
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="location.href='dashboard.php'">
                        <i class="fa-solid fa-house"></i> <span>Home</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="location.href='report.php'">
                        <i class="fa-solid fa-file"></i> <span>Reports</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="location.href='analytics.php'">
                        <i class="fa-solid fa-chart-bar"></i> <span>Analytics</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="location.href='map.php'">
                        <i class="fa-solid fa-map"></i> <span>Map</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 active">
                        <i class="fa-solid fa-shield-heart"></i> <span>Safe Space</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="location.href='walkwithus.php'">
                        <i class="fa-solid fa-person-walking"></i> <span>Walk With Us</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="location.href='templates.php'">
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
        <button id="sidebarToggle" class="fixed left-0 top-1/2 glass-effect bg-gradient-to-r from-[rgba(74,30,115,0.5)] to-[rgba(215,109,119,0.5)] text-white p-3 rounded-r z-50 transition-transform duration-300 ease-in-out toggle-moved hover:shadow-lg">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main id="mainContent" class="flex-1 p-10 content-shifted">
            <!-- Dashboard Tiles -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 ">
                <div class="trae-card p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-[#D76D77]">Total Safe Spaces</h3>
                    <p class="text-2xl font-bold text-[#F0F0F0] bg-clip-text"><?php echo $totalSafeSpaces; ?></p>
                </div>
                <div class="trae-card p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-[#D76D77]">Total Incidents</h3>
                    <p class="text-2xl font-bold text-[#F0F0F0] bg-clip-text">0</p>
                </div>
                <div class="trae-card p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-[#D76D77]">Active Users Today</h3>
                    <p class="text-2xl font-bold text-[#F0F0F0] bg-clip-text">0</p>
                </div>
            </div>

            <!-- Map Controls -->
            <div class="flex gap-2 mb-4 ">
                <button class="glass-effect text-white px-4 py-2 rounded bg-gradient-to-r from-[rgba(74,30,115,0.5)] to-[rgba(215,109,119,0.5)] hover:shadow-lg transition-all duration-300" onclick="toggleMarkers('safe')">Show Safe Spaces</button>
                <button class="glass-effect text-white px-4 py-2 rounded bg-gradient-to-r from-[rgba(74,30,115,0.5)] to-[rgba(215,109,119,0.5)] hover:shadow-lg transition-all duration-300" onclick="toggleMarkers('incidents')">Show Incidents</button>
                <button class="glass-effect text-white px-4 py-2 rounded bg-gradient-to-r from-[rgba(74,30,115,0.5)] to-[rgba(215,109,119,0.5)] hover:shadow-lg transition-all duration-300" onclick="toggleMarkers('all')">Show All</button>
            </div>

            <!-- Main Content Area -->
            <div class="flex flex-col md:flex-row gap-6 ">
                <!-- Map Section (70%) -->
                <div class="w-full md:w-[70%] trae-card rounded-lg p-4">
                    <div id="map"></div>
                </div>

                <!-- Form Section (30%) -->
                <div class="w-full md:w-[30%] trae-card rounded-lg p-4">
                    <h2 class="text-xl font-bold mb-4 text-[#D76D77] bg-clip-text">Mark Safe Space</h2>
                    <form id="safeSpaceForm" class="space-y-4">
                        <input type="hidden" id="coordinates" name="coordinates">
                        <p class="text-sm text-[#F0F0F0] opacity-80 mb-4">Draw a polygon or circle on the map to mark a safe zone</p>
                        <p class="text-sm text-[#F0F0F0] opacity-80 mb-4" id="defaultMessage">No safe zone marked yet. Please draw on the map to mark a safe zone.</p>
                        <div>
                            <label class="block text-sm font-medium text-[#F0F0F0]">Description</label>
                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md bg-[rgba(46,46,78,0.3)] border-[rgba(74,30,115,0.3)] text-[#F0F0F0] shadow-sm focus:border-[rgba(215,109,119,0.7)] focus:ring focus:ring-[rgba(74,30,115,0.3)] focus:ring-opacity-50" required></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#F0F0F0]">Time Active</label>
                            <select id="timeActive" name="timeActive" class="mt-1 block w-full rounded-md bg-[rgba(46,46,78,0.3)] border-[rgba(74,30,115,0.3)] text-[#F0F0F0] shadow-sm focus:border-[rgba(215,109,119,0.7)] focus:ring focus:ring-[rgba(74,30,115,0.3)] focus:ring-opacity-50">
                                <option value="24">24 Hours</option>
                                <option value="48">48 Hours</option>
                                <option value="72">72 Hours</option>
                                <option value="168">1 Week</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full glass-effect text-white py-2 px-4 rounded-md bg-gradient-to-r from-[rgba(74,30,115,0.5)] to-[rgba(215,109,119,0.5)] hover:shadow-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[rgba(215,109,119,0.7)] focus:ring-opacity-50">Submit</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const body = document.body;
            const sidebar = document.getElementById('sidebar');
            const toggleButton = document.getElementById('sidebarToggle');
            const mainContent = document.getElementById('mainContent');
            
            // Set initial state before showing content
            setInitialState();
            // Show content after initial state is set
            setTimeout(() => {
                body.style.opacity = '1';
            }, 0);

            function setInitialState() {
                if (window.innerWidth < 768) {
                    sidebar.classList.add('sidebar-hidden');
                    sidebar.classList.remove('sidebar-visible');
                    toggleButton.classList.add('toggle-default');
                    toggleButton.classList.remove('toggle-moved');
                    mainContent.classList.add('content-full');
                    mainContent.classList.remove('content-shifted');
                } else {
                    sidebar.classList.add('sidebar-visible');
                    sidebar.classList.remove('sidebar-hidden');
                    toggleButton.classList.add('toggle-moved');
                    toggleButton.classList.remove('toggle-default');
                    mainContent.classList.add('content-shifted');
                    mainContent.classList.remove('content-full');
                }
            }

            setInitialState();
            window.addEventListener('resize', setInitialState);

            toggleButton.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-hidden');
                sidebar.classList.toggle('sidebar-visible');
                toggleButton.classList.toggle('toggle-moved');
                toggleButton.classList.toggle('toggle-default');
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