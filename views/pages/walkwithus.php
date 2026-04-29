<?php
session_start();
require_once 'mysqli_db.php';

function getEscortProfile($escortId) {
    global $conn;
    $query = "SELECT * FROM escorts WHERE escort_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $escortId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk With Us - Campus Safety Monitoring</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FontAwesome Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Trae AI Theme -->
    <link href="src/trae-theme.css" rel="stylesheet">

    <!-- Custom JS -->
    <script src="js/walkwithus.js"></script>
<!-- <script>
Handle sidebar toggle functionality

</script> -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleButton = document.getElementById('sidebarToggle');
    
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
        .glass-card, .trae-card {
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(74, 30, 115, 0.25);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            transition: all 0.3s ease;
            position: relative;
        }
        .glass-card:hover, .trae-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.45);
            border-color: rgba(215, 109, 119, 0.5);
        }
        .glass-card:hover::after, .trae-card:hover::after {
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
        .glass-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            color: white;
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(215, 109, 119, 0.3);
            box-shadow: 0 0 15px rgba(215, 109, 119, 0.2);
        }
        .glass-button {
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.5), rgba(215, 109, 119, 0.5));
            backdrop-filter: blur(5px);
            border: 1px solid rgba(215, 109, 119, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .glass-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transition: 0.5s;
        }
        .glass-button:hover::before {
            left: 100%;
        }
        .glass-button:hover {
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.7), rgba(215, 109, 119, 0.7));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(215, 109, 119, 0.4);
        }
        /* Loading animation */
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        /* Smooth transitions */
        .transition-all {
            transition-duration: 300ms;
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
        
        /* Location suggestions styles */
        .suggestion-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .suggestion-item:last-child {
            border-bottom: none;
        }
        .suggestion-item:hover {
            background: rgba(215, 109, 119, 0.2);
            border: 1px solid rgba(215, 109, 119, 0.3);
        }
        .location-suggestions {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-height: 200px;
            overflow-y: auto;
        }
        
        /* Loading spinner styles */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid rgba(215, 109, 119, 1);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Active walk card styles */
        .active-walk-card {
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.1), rgba(215, 109, 119, 0.1));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(215, 109, 119, 0.2);
        }
        
        /* Add styles for transitions */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid rgba(215, 109, 119, 1);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .card-container {
            margin-top: 2rem;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            will-change: opacity, transform;
            visibility: visible;
        }

        .card-container.hidden {
            display: none;
            visibility: hidden;
        }

        .card-container.show {
            opacity: 1;
            transform: translateY(0);
        }

        .active-walk-card {
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.1), rgba(215, 109, 119, 0.1));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(215, 109, 119, 0.2);
        }

        /* Ensure cards are visible when shown */
        #escortDetails:not(.hidden),
        #activeWalk:not(.hidden) {
            display: block;
            visibility: visible;
        }
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
</head>
<body class="bg-[#1E1E2E] text-[#F0F0F0] overflow-hidden">
    <div class="flex h-screen overflow-hidden relative z-0">
        <!-- Background gradient shapes -->
        <div class="absolute -top-[300px] -right-[300px] w-[600px] h-[600px] bg-gradient-to-r from-[rgba(74,30,115,0.3)] to-[rgba(215,109,119,0.3)] rounded-full blur-3xl -z-10 animate-pulse-slow"></div>
        <div class="absolute -bottom-[200px] -left-[200px] w-[500px] h-[500px] bg-gradient-to-r from-[rgba(215,109,119,0.2)] to-[rgba(74,30,115,0.2)] rounded-full blur-3xl -z-10 animate-pulse-slow opacity-70"></div>
    <div class="flex h-screen">
        <!-- Sidebar Toggle Button -->
        <button id="sidebarToggle" class="fixed left-0 top-1/2 text-white p-3 rounded-r z-50 transition-all duration-300 ease-in-out toggle-default toggle-moved hover:shadow-lg hover:translate-x-1 bg-gradient-to-r from-[rgba(74,30,115,0.8)] to-[rgba(215,109,119,0.8)] backdrop-blur-md border border-[rgba(215,109,119,0.3)]">
            <i class="fas fa-bars"></i>
        </button>
        <!-- Sidebar -->
        <aside id="sidebar" class="trae-sidebar w-64 text-white p-5 flex flex-col h-full z-40 sidebar-visible fixed">
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
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="location.href='safespace.php'">
                        <i class="fa-solid fa-shield-heart"></i> <span>Safe Space</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 active">
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
        
        <!-- Main Content -->
        <main id="mainContent" class="flex-1 p-8 md:ml-64 transition-all duration-300 ease-in-out overflow-y-auto content-shifted">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-4xl font-bold mb-8 text-gradient">
                    Walk With Us
                </h1>
                
                <!-- Info Card -->
                <div class="glass-effect rounded-xl p-6 mb-8 transform hover:scale-[1.02] transition-all duration-300">
                    <h2 class="text-2xl font-bold mb-4 text-white">Walk With Us - Your Safety Companion</h2>
                    <p class="text-[#A0A0B0] mb-4">Our escort service ensures your safety by connecting you with trusted companions for your journey. Whether you're heading to class, returning to your hostel, or moving around campus, we're here to help.</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="glass-card p-4 rounded-lg">
                            <i class="fa-solid fa-shield-heart text-[#D76D77] text-xl mb-2"></i>
                            <h3 class="font-semibold mb-2">Verified Escorts</h3>
                            <p class="text-[#A0A0B0]">All our escorts are verified students and staff members.</p>
                        </div>
                        <div class="glass-card p-4 rounded-lg">
                            <i class="fa-solid fa-location-dot text-[#D76D77] text-xl mb-2"></i>
                            <h3 class="font-semibold mb-2">Real-time Tracking</h3>
                            <p class="text-[#A0A0B0]">Track your journey in real-time for added security.</p>
                        </div>
                        <div class="glass-card p-4 rounded-lg">
                            <i class="fa-solid fa-clock text-[#D76D77] text-xl mb-2"></i>
                            <h3 class="font-semibold mb-2">24/7 Available</h3>
                            <p class="text-[#A0A0B0]">Request an escort any time you need one.</p>
                        </div>
                    </div>
                </div>

                <!-- Main content grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Request a Walk Section -->
                    <div class="glass-card rounded-xl p-6 transform transition-all duration-300">
                        <h2 class="text-2xl font-bold mb-6 text-white">Request a Walk</h2>
                        <form id="requestWalkForm" class="space-y-6" onsubmit="return false;">
                            <div class="space-y-2 relative">
                                <label class="block text-sm font-medium text-[#D76D77]">Pickup Location</label>
                                <input type="text" id="pickupLocation" class="glass-input w-full rounded-lg p-3 focus:outline-none" placeholder="Enter your current location" required>
                                <div id="locationSuggestions" class="absolute z-10 w-full mt-1 rounded-lg hidden location-suggestions">
                                    <!-- Location suggestions will be populated here -->
                                </div>
                            </div>
                            <div class="space-y-2 relative">
                                <label class="block text-sm font-medium text-[#D76D77]">Destination</label>
                                <input type="text" id="destination" class="glass-input w-full rounded-lg p-3 focus:outline-none" placeholder="Enter your destination" required>
                                <div id="destinationSuggestions" class="absolute z-10 w-full mt-1 rounded-lg hidden location-suggestions">
                                    <!-- Destination suggestions will be populated here -->
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-[#D76D77]">Preferred Time</label>
                                <input type="datetime-local" id="preferredTime" class="glass-input w-full rounded-lg p-3 focus:outline-none" required>
                            </div>
                            <button type="submit" class="glass-button w-full py-3 px-4 rounded-lg text-white font-medium hover:shadow-lg">
                                <span class="relative z-10 flex items-center justify-center">
                                    <i class="fa-solid fa-person-walking mr-2"></i>
                                    Request Walk
                                </span>
                            </button>
                        </form>
                    </div>

                    <!-- Volunteer as Walker Section -->
                    <div class="glass-card rounded-xl p-6 transform transition-all duration-300">
                        <h2 class="text-2xl font-bold mb-6 text-white">Volunteer as Walker</h2>
                        <form id="volunteerForm" class="space-y-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-[#D76D77]">Available Time From</label>
                                <input type="datetime-local" class="glass-input w-full rounded-lg p-3 focus:outline-none" required>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-[#D76D77]">Available Time To</label>
                                <input type="datetime-local" class="glass-input w-full rounded-lg p-3 focus:outline-none" required>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-[#D76D77]">Preferred Areas</label>
                                <select multiple class="glass-input w-full rounded-lg p-3 focus:outline-none" required>
                                    <option class="bg-[#1a1a2e]">Block 32 - Academic block with classrooms and labs</option>
                                    <option class="bg-[#1a1a2e]">Block 34 - Academic block with lecture halls</option>
                                    <option class="bg-[#1a1a2e]">Girls Hostel - Girls residential area</option>
                                    <option class="bg-[#1a1a2e]">Boys Hostel - Boys residential area</option>
                                    <option class="bg-[#1a1a2e]">Uni Mall - Shopping and dining complex</option>
                                    <option class="bg-[#1a1a2e]">Library - Central library and study area</option>
                                    <option class="bg-[#1a1a2e]">Sports Complex - Sports facilities and grounds</option>
                                    <option class="bg-[#1a1a2e]">Main Gate - Main entrance to the campus</option>
                                </select>
                                <p class="text-xs text-[#A0A0B0] mt-1">Hold Ctrl/Cmd to select multiple areas</p>
                            </div>
                            <button type="submit" class="glass-button w-full py-3 px-4 rounded-lg text-white font-medium hover:shadow-lg">
                                <span class="relative z-10">Register as Walker</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Loading Animation (Initially Hidden) -->
                <div id="loadingAnimation" class="hidden fixed top-0 left-0 w-full h-full flex items-center justify-center bg-gray-900 bg-opacity-50 z-50">
                    <div class="bg-gray-800 p-8 rounded-lg flex flex-col items-center">
                        <div class="loading-spinner mb-4"></div>
                        <p class="text-white text-lg">Finding an escort...</p>
                    </div>
                </div>

                <!-- Escort Details Section (Initially Hidden) -->
                <div id="escortDetails" class="hidden card-container w-full">
                    <div class="glass-card p-6 rounded-lg shadow-lg mx-auto">
                        <h2 class="text-2xl font-bold mb-6 text-white">Your Escort Details</h2>
                        <div class="flex flex-col md:flex-row items-start space-y-4 md:space-y-0 md:space-x-6">
                            <div class="flex-shrink-0 relative group">
                                <img id="escortImage" src="" alt="Escort" class="w-32 h-32 rounded-full object-cover">
                                <div class="absolute bottom-0 right-0 bg-green-500 w-6 h-6 rounded-full border-4 border-gray-900 flex items-center justify-center">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                            </div>
                            <div class="flex-grow">
                                <h3 id="escortName" class="text-xl font-semibold"></h3>
                                <p id="escortId" class="text-[#A0A0B0] text-sm mb-2"></p>
                                <div id="escortRating" class="text-yellow-400 mb-1"></div>
                                <p id="escortCompletedWalks" class="text-[#A0A0B0] mb-4"></p>
                                
                                <div class="space-y-2 text-[#A0A0B0]">
                                    <p><i class="fas fa-route mr-2"></i>Route: <span id="walkRoute"></span></p>
                                    <p><i class="fas fa-walking mr-2"></i>Distance: <span id="walkDistance"></span></p>
                                    <p><i class="far fa-clock mr-2"></i>ETA: <span id="walkEta"></span></p>
                                </div>

                                <div class="flex space-x-4 mt-6">
                                    <button id="startWalk" class="glass-button px-6 py-2 rounded-lg text-white font-medium hover:shadow-lg">
                                        <span class="relative z-10 flex items-center justify-center">
                                            <i class="fas fa-walking mr-2"></i>Start Walk
                                        </span>
                                    </button>
                                    <button id="cancelWalk" class="bg-gray-700 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-all duration-300">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Walk Section (Initially Hidden) -->
                <div id="activeWalk" class="hidden card-container w-full">
                    <div class="active-walk-card p-6 rounded-lg shadow-lg mx-auto">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#4A1E73] to-[#D76D77]">Active Walk</h2>
                            <span class="px-3 py-1 rounded-full bg-green-500 text-white text-sm">In Progress</span>
                        </div>
                        
                        <div class="flex flex-col md:flex-row items-start space-y-4 md:space-y-0 md:space-x-6">
                            <div class="flex-shrink-0 relative group">
                                <img id="activeEscortImage" src="" alt="Escort" class="w-32 h-32 rounded-full object-cover border-4 border-green-500">
                                <div class="absolute bottom-0 right-0 bg-green-500 w-6 h-6 rounded-full border-4 border-gray-900 flex items-center justify-center">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                            </div>
                            
                            <div class="flex-grow">
                                <div class="flex items-center space-x-3 mb-2">
                                    <h3 id="activeEscortName" class="text-xl font-semibold"></h3>
                                    <span id="activeEscortId" class="text-[#A0A0B0] text-sm"></span>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <p class="text-[#A0A0B0]">Current Location</p>
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-map-marker-alt text-[#D76D77]"></i>
                                                <span id="activeCurrentLocation"></span>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <p class="text-[#A0A0B0]">Destination</p>
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-flag-checkered text-[#4A1E73]"></i>
                                                <span id="activeDestination"></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-clock text-blue-400"></i>
                                            <span>Started: <span id="activeStartTime" class="text-[#A0A0B0]"></span></span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-walking text-green-400"></i>
                                            <span>Distance: <span id="activeDistance" class="text-[#A0A0B0]"></span></span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-hourglass-half text-yellow-400"></i>
                                            <span>ETA: <span id="activeEta" class="text-[#A0A0B0]"></span></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex space-x-4 mt-6">
                                    <button id="endWalk" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all duration-300">
                                        <i class="fas fa-stop-circle mr-2"></i>End Walk
                                    </button>
                                    <button id="emergencyButton" class="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition-all duration-300">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>Emergency
                                    </button>
                                </div>
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
            const mainContent = document.getElementById('mainContent');
            const toggleButton = document.getElementById('sidebarToggle');

            function toggleSidebar() {
                const isMobile = window.innerWidth < 768;
                if (isMobile) {
                    if (sidebar.classList.contains('sidebar-visible')) {
                        sidebar.classList.remove('sidebar-visible');
                        sidebar.classList.add('sidebar-hidden');
                        toggleButton.classList.remove('toggle-moved');
                        mainContent.classList.remove('content-shifted');
                        mainContent.classList.add('content-full');
                    } else {
                        sidebar.classList.remove('sidebar-hidden');
                        sidebar.classList.add('sidebar-visible');
                        toggleButton.classList.add('toggle-moved');
                        mainContent.classList.remove('content-full');
                        mainContent.classList.add('content-shifted');
                    }
                }
            }

            toggleButton.addEventListener('click', toggleSidebar);

            // Set initial state
            if (window.innerWidth >= 768) {
                mainContent.classList.add('content-shifted');
                mainContent.classList.remove('content-full');
            } else {
                mainContent.classList.add('content-full');
                mainContent.classList.remove('content-shifted');
            }
        });

    if (!pickupLocation || !destination) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Information',
            text: 'Please provide both pickup location and destination.'
        });
        return;
    }

    // Show loading animation
    document.getElementById('loadingAnimation').classList.remove('hidden');

    // Prepare request data
    const requestData = {
        escortId: escortId,
        userId: '<?php echo isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : ""; ?>',
        pickupLocation: pickupLocation,
        destination: destination,
        requestTime: new Date().toISOString()
    };

    // Send request to backend
    fetch('api/walks/request_walk.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading animation
        document.getElementById('loadingAnimation').classList.add('hidden');

        if (data.success) {
            // Show the active walk card immediately
            showActiveWalkCard({
                walkId: data.walkId,
                escortId: escortId,
                pickupLocation: pickupLocation,
                destination: destination,
                status: 'pending'
            });
            
            // Determine message based on email status
            let message = 'Your walk request has been submitted successfully.';
            let icon = 'success';
            
            // Check if email was sent
            if (data.hasOwnProperty('emailSent')) {
                if (data.emailSent === true) {
                    message += ' The escort has been notified via email.';
                } else {
                    message += ' However, the email notification to the escort failed. They will still see your request when they log in.';
                    icon = 'warning';
                }
            }
            
            Swal.fire({
                icon: icon,
                title: 'Walk Requested!',
                text: message,
                showConfirmButton: true
            });

            // Clear form
            document.getElementById('pickupLocation').value = '';
            document.getElementById('destination').value = '';
        } else {
            throw new Error(data.message || 'Failed to submit walk request');
        }
    })
    .catch(error => {
        // Hide loading animation
        document.getElementById('loadingAnimation').classList.add('hidden');

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Failed to submit walk request. Please try again.'
        });
    });


// Function to display the active walk card
function showActiveWalkCard(walkData) {
    // Get the active walk card and request section elements
    const activeWalkCard = document.getElementById('activeWalk');
    const requestSection = document.getElementById('requestSection');
    
    if (!activeWalkCard) return;
    
    // Save walk data to localStorage for persistence
    localStorage.setItem('activeWalk', JSON.stringify(walkData));
    
    // Update the active walk card with walk details
    document.getElementById('walkId').textContent = walkData.walkId || 'N/A';
    document.getElementById('walkStatus').textContent = walkData.status || 'pending';
    document.getElementById('walkPickup').textContent = walkData.pickupLocation || 'N/A';
    document.getElementById('walkDestination').textContent = walkData.destination || 'N/A';
    
    // Set current time as start time
    const startTimeElement = document.getElementById('activeStartTime');
    if (startTimeElement) {
        const now = new Date();
        startTimeElement.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
    
    // Hide request section and show active walk card
    if (requestSection) requestSection.classList.add('hidden');
    activeWalkCard.classList.remove('hidden');
    
    // Scroll to the active walk card
    activeWalkCard.scrollIntoView({ behavior: 'smooth' });
}

// Check for active walk on page load
document.addEventListener('DOMContentLoaded', function() {
    const activeWalkData = localStorage.getItem('activeWalk');
    if (activeWalkData) {
        try {
            const walkData = JSON.parse(activeWalkData);
            showActiveWalkCard(walkData);
        } catch (e) {
            console.error('Error parsing active walk data:', e);
            localStorage.removeItem('activeWalk');
        }
    }
});

function showEscortProfile(escortId) {
    fetch('get_escort.php?escort_id=' + escortId)
        .then(response => response.json())
        .then(data => {
            const profileCard = document.createElement('div');
            profileCard.className = 'glass-card p-6 mb-4';
            profileCard.innerHTML = `
                <div class="flex items-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-gray-700 mr-4"></div>
                    <div>
                        <h3 class="text-xl font-bold">${data.escort.name}</h3>
                        <p class="text-gray-400">ID: ${data.escort.id}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-400">Rating:</p>
                        <p>${data.escort.rating}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Completed Walks:</p>
                        <p>${data.escort.completedWalks}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="glass-button px-4 py-2 w-full" onclick="requestWalk('${data.escort.id}')">
                        Request Walk
                    </button>
                </div>
            `;
            document.getElementById('profile-container').appendChild(profileCard);
        });
}
// document.addEventListener('DOMContentLoaded', function() {
//         const sidebar = document.getElementById('sidebar');
//         const toggleButton = document.getElementById('sidebarToggle');
//         const mainContent = document.getElementById('mainContent');

//         function setInitialState() {
//             if (window.innerWidth < 768) {
//                 sidebar.classList.add('sidebar-hidden');
//                 sidebar.classList.remove('sidebar-visible');
//                 toggleButton.classList.add('toggle-default');
//                 toggleButton.classList.remove('toggle-moved');
//                 mainContent.classList.add('content-full');
//                 mainContent.classList.remove('content-shifted');
//             } else {
//                 sidebar.classList.remove('sidebar-hidden');
//                 sidebar.classList.add('sidebar-visible');
//                 toggleButton.classList.remove('toggle-default');
//                 toggleButton.classList.add('toggle-moved');
//                 mainContent.classList.remove('content-full');
//                 mainContent.classList.add('content-shifted');
//             }
//         }
        
//         setInitialState();
//         window.addEventListener('resize', setInitialState);

//         toggleButton.addEventListener('click', function() {
//             if (sidebar.classList.contains('sidebar-hidden')) {
//                 sidebar.classList.remove('sidebar-hidden');
//                 sidebar.classList.add('sidebar-visible');
//                 toggleButton.classList.remove('toggle-default');
//                 toggleButton.classList.add('toggle-moved');
//                 mainContent.classList.remove('content-full');
//                 mainContent.classList.add('content-shifted');
//             } else {
//                 sidebar.classList.add('sidebar-hidden');
//                 sidebar.classList.remove('sidebar-visible');
//                 toggleButton.classList.add('toggle-default');
//                 toggleButton.classList.remove('toggle-moved');
//                 mainContent.classList.add('content-full');
//                 mainContent.classList.remove('content-shifted');
//             }
//         });
//     });
    </script>

    <script src="locations.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const currentLocationInput = document.getElementById('pickupLocation');
            const destinationInput = document.getElementById('destination');
            const locationSuggestions = document.getElementById('locationSuggestions');
            const destinationSuggestions = document.getElementById('destinationSuggestions');

            function createSuggestionsList(input, suggestionsDiv) {
                const value = input.value.toLowerCase();
                if (value.length < 1) {
                    suggestionsDiv.classList.add('hidden');
                    return;
                }

                const filteredLocations = lpuLocations.filter(location => 
                    location.name.toLowerCase().includes(value)
                );

                if (filteredLocations.length > 0) {
                    suggestionsDiv.innerHTML = filteredLocations.map(location => `
                        <div class="suggestion-item p-3 cursor-pointer hover:bg-pink-500/20 transition-all duration-200"
                             data-name="${location.name}" data-lat="${location.lat}" data-lng="${location.lng}">
                            <div class="font-medium">${location.name}</div>
                            <div class="text-sm text-[#A0A0B0]">${location.description}</div>
                        </div>
                    `).join('');
                    suggestionsDiv.classList.remove('hidden');
                } else {
                    suggestionsDiv.classList.add('hidden');
                }
            }

            function handleSuggestionClick(input, suggestionsDiv) {
                suggestionsDiv.addEventListener('click', (e) => {
                    const item = e.target.closest('.suggestion-item');
                    if (item) {
                        input.value = item.dataset.name;
                        input.dataset.lat = item.dataset.lat;
                        input.dataset.lng = item.dataset.lng;
                        suggestionsDiv.classList.add('hidden');
                    }
                });
            }

            // Set up event listeners for current location
            currentLocationInput.addEventListener('input', () => 
                createSuggestionsList(currentLocationInput, locationSuggestions)
            );
            currentLocationInput.addEventListener('focus', () => 
                createSuggestionsList(currentLocationInput, locationSuggestions)
            );
            handleSuggestionClick(currentLocationInput, locationSuggestions);

            // Set up event listeners for destination
            destinationInput.addEventListener('input', () => 
                createSuggestionsList(destinationInput, destinationSuggestions)
            );
            destinationInput.addEventListener('focus', () => 
                createSuggestionsList(destinationInput, destinationSuggestions)
            );
            handleSuggestionClick(destinationInput, destinationSuggestions);

            // Hide suggestions when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#pickupLocation') && !e.target.closest('#locationSuggestions')) {
                    locationSuggestions.classList.add('hidden');
                }
                if (!e.target.closest('#destination') && !e.target.closest('#destinationSuggestions')) {
                    destinationSuggestions.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
