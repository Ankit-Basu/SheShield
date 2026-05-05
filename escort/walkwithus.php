<?php
require_once __DIR__ . '/../app/models/mysqli_db.php';

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

    <!-- Custom JS -->
    <script src="js/walkwithus.js"></script>
<script>
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
</script>

    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .glass-nav-item {
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        .glass-nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        .glass-nav-item.active {
            background: rgba(209, 46, 121, 0.2);
            border: 1px solid rgba(209, 46, 121, 0.3);
        }
        .nav-icon {
            transition: all 0.3s ease;
        }
        .glass-nav-item:hover .nav-icon {
            transform: scale(1.1);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
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
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }
        .glass-button {
            background: rgba(209, 46, 121, 0.3);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(209, 46, 121, 0.3);
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
            background: rgba(209, 46, 121, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(209, 46, 121, 0.4);
        }
        .sidebar-hidden { 
            transform: translateX(-100%);
            opacity: 0;
        }
        .sidebar-visible { 
            transform: translateX(0);
            opacity: 1;
        }
        .toggle-moved { 
            transform: translateX(16rem);
            opacity: 1;
        }
        .toggle-default { 
            transform: translateX(0);
            opacity: 1;
        }
        .content-shifted { 
            margin-left: 16rem;
            transition: all 0.3s ease-in-out;
        }
        .content-full { 
            margin-left: 0;
            transition: all 0.3s ease-in-out;
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
            background: rgba(255, 255, 255, 0.1);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(209, 46, 121, 0.5);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(209, 46, 121, 0.7);
        }
        
        /* Toggle button styles */
        .toggle-button {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(209, 46, 121, 0.2);
            border: 1px solid rgba(209, 46, 121, 0.3);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .toggle-button:hover {
            background: rgba(209, 46, 121, 0.3);
            transform: scale(1.05);
        }
        .toggle-button i {
            transition: transform 0.3s ease;
        }
        
        /* Location suggestions styles */
        .suggestion-item {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .suggestion-item:last-child {
            border-bottom: none;
        }
        .suggestion-item:hover {
            background: rgba(209, 46, 121, 0.2);
            border: 1px solid rgba(209, 46, 121, 0.3);
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
            border-top: 5px solid #D12E79;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Active walk card styles */
        .active-walk-card {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.1), rgba(147, 51, 234, 0.1));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(236, 72, 153, 0.2);
        }
        
        /* Add styles for transitions */
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid #D12E79;
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
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.1), rgba(147, 51, 234, 0.1));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(236, 72, 153, 0.2);
        }

        /* Ensure cards are visible when shown */
        #escortDetails:not(.hidden),
        #activeWalk:not(.hidden) {
            display: block;
            visibility: visible;
        }
    </style>
</head>
<body class="text-white overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed w-64 glass-effect p-5 flex flex-col h-full z-40 transition-all duration-300 ease-in-out transform -translate-x-full">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-shield-halved text-3xl text-pink-400 nav-icon"></i>
                    <span class="text-lg font-bold bg-clip-text text-transparent bg-gradient-to-r from-pink-400 to-purple-600">User Name</span>
                </div>
            </div>
            <nav class="flex-1">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="glass-nav-item p-3 rounded-lg flex items-center space-x-3">
                            <i class="nav-icon fa-solid fa-house text-pink-400"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li>
                        <a href="report.php" class="glass-nav-item p-3 rounded-lg flex items-center space-x-3">
                            <i class="nav-icon fa-solid fa-file text-pink-400"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="glass-nav-item p-3 rounded-lg flex items-center space-x-3">
                            <i class="nav-icon fa-solid fa-chart-bar text-pink-400"></i>
                            <span>Analytics</span>
                        </a>
                    </li>
                    <li>
                        <a href="map.php" class="glass-nav-item p-3 rounded-lg flex items-center space-x-3">
                            <i class="nav-icon fa-solid fa-map text-pink-400"></i>
                            <span>Map</span>
                        </a>
                    </li>
                    <li>
                        <a href="safespace.php" class="glass-nav-item p-3 rounded-lg flex items-center space-x-3">
                            <i class="nav-icon fa-solid fa-shield-heart text-pink-400"></i>
                            <span>Safe Space</span>
                        </a>
                    </li>
                    <li>
                        <div class="glass-nav-item active p-3 rounded-lg flex items-center space-x-3">
                            <i class="nav-icon fa-solid fa-person-walking text-pink-400"></i>
                            <span>Walk With Us</span>
                        </div>
                    </li>
                    <li>
                        <a href="templates.php" class="glass-nav-item p-3 rounded-lg flex items-center space-x-3">
                            <i class="nav-icon fa-solid fa-file-lines text-pink-400"></i>
                            <span>Templates</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="glass-nav-item p-3 rounded-lg flex items-center space-x-3">
                            <i class="nav-icon fa-solid fa-gear text-pink-400"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- Sidebar Toggle Button -->
        <button id="sidebarToggle" class="fixed left-0 top-1/2 -translate-y-1/2 toggle-button rounded-r z-50">
            <i class="fa-solid fa-chevron-right text-pink-400"></i>
        </button>

        <!-- Main Content -->
        <main id="mainContent" class="flex-1 p-8 ml-0 transition-all duration-300 overflow-y-auto content-full">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-4xl font-bold mb-8 text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-purple-600">
                    Walk With Us
                </h1>
                
                <!-- Info Card -->
                <div class="glass-effect rounded-xl p-6 mb-8 transform hover:scale-[1.02] transition-all duration-300">
                    <h2 class="text-2xl font-bold mb-4 text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-purple-600">Walk With Us - Your Safety Companion</h2>
                    <p class="text-gray-300 mb-4">Our escort service ensures your safety by connecting you with trusted companions for your journey. Whether you're heading to class, returning to your hostel, or moving around campus, we're here to help.</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="glass-card p-4 rounded-lg">
                            <i class="fa-solid fa-shield-heart text-pink-400 text-xl mb-2"></i>
                            <h3 class="font-semibold mb-2">Verified Escorts</h3>
                            <p class="text-gray-400">All our escorts are verified students and staff members.</p>
                        </div>
                        <div class="glass-card p-4 rounded-lg">
                            <i class="fa-solid fa-location-dot text-pink-400 text-xl mb-2"></i>
                            <h3 class="font-semibold mb-2">Real-time Tracking</h3>
                            <p class="text-gray-400">Track your journey in real-time for added security.</p>
                        </div>
                        <div class="glass-card p-4 rounded-lg">
                            <i class="fa-solid fa-clock text-pink-400 text-xl mb-2"></i>
                            <h3 class="font-semibold mb-2">24/7 Available</h3>
                            <p class="text-gray-400">Request an escort any time you need one.</p>
                        </div>
                    </div>
                </div>

                <!-- Main content grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Request a Walk Section -->
                    <div class="glass-card rounded-xl p-6 transform transition-all duration-300">
                        <h2 class="text-2xl font-bold mb-6 text-pink-400">Request a Walk</h2>
                        <form id="requestWalkForm" class="space-y-6" onsubmit="return false;">
                            <div class="space-y-2 relative">
                                <label class="block text-sm font-medium text-pink-300">Pickup Location</label>
                                <input type="text" id="pickupLocation" class="glass-input w-full rounded-lg p-3 focus:outline-none" placeholder="Enter your current location" required>
                                <div id="locationSuggestions" class="absolute z-10 w-full mt-1 rounded-lg hidden location-suggestions">
                                    <!-- Location suggestions will be populated here -->
                                </div>
                            </div>
                            <div class="space-y-2 relative">
                                <label class="block text-sm font-medium text-pink-300">Destination</label>
                                <input type="text" id="destination" class="glass-input w-full rounded-lg p-3 focus:outline-none" placeholder="Enter your destination" required>
                                <div id="destinationSuggestions" class="absolute z-10 w-full mt-1 rounded-lg hidden location-suggestions">
                                    <!-- Destination suggestions will be populated here -->
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-pink-300">Preferred Time</label>
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
                        <h2 class="text-2xl font-bold mb-6 text-pink-400">Volunteer as Walker</h2>
                        <form id="volunteerForm" class="space-y-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-pink-300">Available Time From</label>
                                <input type="datetime-local" id="availableTimeFrom" class="glass-input w-full rounded-lg p-3 focus:outline-none" required>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-pink-300">Available Time To</label>
                                <input type="datetime-local" id="availableTimeTo" class="glass-input w-full rounded-lg p-3 focus:outline-none" required>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-pink-300">Preferred Areas</label>
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
                                <p class="text-xs text-gray-400 mt-1">Hold Ctrl/Cmd to select multiple areas</p>
                            </div>
                            <button type="submit" class="glass-button w-full py-3 px-4 rounded-lg text-white font-medium hover:shadow-lg">
                                <span class="relative z-10">Register as Walker</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Loading Animation (Initially Hidden) -->
                <div id="loadingAnimation" class="fixed top-0 left-0 w-full h-full items-center justify-center bg-gray-900 bg-opacity-50 z-50" style="display: none;">
                    <div class="bg-gray-800 p-8 rounded-lg flex flex-col items-center">
                        <div class="loading-spinner mb-4"></div>
                        <p class="text-white text-lg">Finding an escort...</p>
                    </div>
                </div>

                <!-- Escort Details Section (Initially Hidden) -->
                <div id="escortDetails" class="hidden card-container w-full">
                    <div class="glass-card p-6 rounded-lg shadow-lg mx-auto">
                        <h2 class="text-2xl font-bold mb-6 text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-purple-600">Your Escort Details</h2>
                        <div class="flex flex-col md:flex-row items-start space-y-4 md:space-y-0 md:space-x-6">
                            <div class="flex-shrink-0 relative group">
                                <img id="escortImage" src="" alt="Escort" class="w-32 h-32 rounded-full object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-50 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <span class="text-white text-sm">Verified</span>
                                </div>
                            </div>
                            <div class="flex-grow">
                                <h3 id="escortName" class="text-xl font-semibold"></h3>
                                <p id="escortId" class="text-gray-400 text-sm mb-2"></p>
                                <div id="escortRating" class="text-yellow-400 mb-1"></div>
                                <p id="escortCompletedWalks" class="text-gray-300 mb-4"></p>
                                
                                <div class="space-y-2 text-gray-300">
                                    <p><i class="fas fa-route mr-2"></i>Route: <span id="walkRoute"></span></p>
                                    <p><i class="fas fa-walking mr-2"></i>Distance: <span id="walkDistance"></span></p>
                                    <p><i class="far fa-clock mr-2"></i>ETA: <span id="walkEta"></span></p>
                                </div>

                                <div class="flex space-x-4 mt-6">
                                    <button id="startWalk" class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-2 rounded-lg hover:from-pink-600 hover:to-purple-700 transition-all duration-300">
                                        <i class="fas fa-walking mr-2"></i>Start Walk
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
                            <h2 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-purple-600">Active Walk</h2>
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
                                    <span id="activeEscortId" class="text-gray-400 text-sm"></span>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <p class="text-gray-400">Current Location</p>
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-map-marker-alt text-pink-500"></i>
                                                <span id="activeCurrentLocation"></span>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <p class="text-gray-400">Destination</p>
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-flag-checkered text-purple-500"></i>
                                                <span id="activeDestination"></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-clock text-blue-400"></i>
                                            <span>Started: <span id="activeStartTime" class="text-gray-300"></span></span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-walking text-green-400"></i>
                                            <span>Distance: <span id="activeDistance" class="text-gray-300"></span></span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-hourglass-half text-yellow-400"></i>
                                            <span>ETA: <span id="activeEta" class="text-gray-300"></span></span>
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
            const toggleButton = document.getElementById('sidebarToggle');
            const toggleIcon = toggleButton.querySelector('i');
            const mainContent = document.getElementById('mainContent');

            function setInitialState() {
                // Always start with sidebar closed
                sidebar.style.transform = 'translateX(-100%)';
                toggleButton.style.transform = 'translate(0, -50%)';
                toggleIcon.style.transform = 'rotate(0deg)';
                mainContent.classList.add('content-full');
                mainContent.classList.remove('content-shifted');
            }

            // Set initial state
            setInitialState();

            // Handle sidebar toggle
            toggleButton.addEventListener('click', function() {
                const isHidden = sidebar.style.transform === 'translateX(-100%)';
                
                // Animate sidebar
                sidebar.style.transform = isHidden ? 'translateX(0)' : 'translateX(-100%)';
                
                // Animate toggle button
                toggleButton.style.transform = isHidden 
                    ? 'translate(16rem, -50%)' 
                    : 'translate(0, -50%)';
                
                // Rotate toggle icon
                toggleIcon.style.transform = isHidden 
                    ? 'rotate(180deg)' 
                    : 'rotate(0deg)';
                
                // Adjust main content
                mainContent.classList.toggle('content-shifted');
                mainContent.classList.toggle('content-full');
            });
        });
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
                            <div class="text-sm text-gray-400">${location.description}</div>
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
