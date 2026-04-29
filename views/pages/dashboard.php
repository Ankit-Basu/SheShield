<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trae AI Dashboard</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FontAwesome Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>

    <!-- Trae AI Theme -->
    <link href="src/trae-theme.css" rel="stylesheet">

    <style>
        body{
            background: linear-gradient(135deg, #1E1E2E 0%, #2E2E4E 100%);
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y:auto;
        }
        
        /* Glassmorphic Effects */
        .glass-effect {
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(74, 30, 115, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        /* .trae-sidebar {
            background: rgba(46, 46, 78, 0.3);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-right: 1px solid rgba(74, 30, 115, 0.3);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        } */
        
        .trae-card {
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(74, 30, 115, 0.25);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            transition: all 0.3s ease;
        }
        
         .trae-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(215, 109, 119, 0.3);
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
        
        /* Staggered animations for cards */
        .animate-fade-in > a:nth-child(1) { animation-delay: 0.1s; }
        .animate-fade-in > a:nth-child(2) { animation-delay: 0.2s; }
        .animate-fade-in > a:nth-child(3) { animation-delay: 0.3s; }
        
        /* Responsive sidebar behavior */
        
            /* #sidebar {
                transform: translateX(0);
                opacity: 1;
            } */
            /* .sidebar{
                transition: transform 0.3s ease-in-out;
                background: linear-gradient(180deg, #4A1E73 0%, #D76D77 100%);
            } */
            /* #mainContent {
                margin-left: 16rem;
            } */
            /* #sidebarToggle {
            transition: transform 0.3s ease-in-out;
            } */
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
        
        /* These classes are kept for backward compatibility */
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
        
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
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
                sidebarToggle.style.transform = 'translateX(16rem) translateY(-50%)';
                mainContent.style.marginLeft = '16rem';
            } else {
                sidebar.style.transform = 'translateX(-100%)';
                sidebar.style.opacity = '0';
                sidebarToggle.style.transform = 'translateX(0) translateY(-50%)';
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
        sidebarToggle.addEventListener('click', function() {
            updateSidebarState(!isSidebarVisible);
        });
    });
    
    function updateProgress(field) {
        fetch('update_incident_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'field=' + field
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh to update progress
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function rateExperience(rating) {
        // Update star display
        const stars = document.querySelectorAll('#starRating .fa-star');
        stars.forEach((star, index) => {
            star.classList.remove('fas', 'far', 'text-[#D76D77]', 'text-gray-400');
            if (index < rating) {
                star.classList.add('fas', 'text-yellow-500');
            } else {
                star.classList.add('far', 'text-gray-400');
            }
        });

        // Show feedback form for ratings less than 3
        const feedbackForm = document.getElementById('feedbackForm');
        if (rating < 3) {
            feedbackForm.classList.remove('hidden');
        } else {
            feedbackForm.classList.add('hidden');
            // For higher ratings, just save the rating
            saveFeedback(rating, '');
        }
    }

    function submitFeedback() {
        const stars = document.querySelectorAll('#starRating .fa-star.text-yellow-500');
        const rating = stars.length;
        const feedback = document.getElementById('feedbackText').value;
        saveFeedback(rating, feedback);
    }

    function saveFeedback(rating, feedback) {
        fetch('update_incident_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `rating=${rating}&feedback=${encodeURIComponent(feedback)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide feedback form after submission
                document.getElementById('feedbackForm').classList.add('hidden');
                // Show custom success message instead of alert
                const messageContainer = document.createElement('div');
                messageContainer.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 glass-effect p-6 rounded-xl text-center z-50';
                messageContainer.innerHTML = `
                    <div class="text-2xl text-gradient font-bold mb-3"><i class="fas fa-check-circle mr-2"></i>Thank You!</div>
                    <p class="text-white mb-4">Your suggestion is our utmost priority.<br>Your suggestion has been noted.</p>
                    <button class="bg-gradient-to-r from-[#4A1E73] to-[#D76D77] px-4 py-2 rounded-lg text-white hover:opacity-90 transition-opacity">Close</button>
                `;
                document.body.appendChild(messageContainer);
                
                // Add event listener to close button
                const closeButton = messageContainer.querySelector('button');
                closeButton.addEventListener('click', () => {
                    document.body.removeChild(messageContainer);
                });
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (document.body.contains(messageContainer)) {
                        document.body.removeChild(messageContainer);
                    }
                }, 5000);
            }
        })
        .catch(error => console.error('Error:', error));
    }
    </script>
</head>
<body class="bg-[#1E1E2E] text-[#F0F0F0]">
    <div class="flex min-h-screen relative z-0">
        <!-- Background gradient shapes -->
        <div class="absolute -top-[300px] -right-[300px] w-[600px] h-[600px] bg-gradient-to-r from-[rgba(74,30,115,0.3)] to-[rgba(215,109,119,0.3)] rounded-full blur-3xl -z-10 animate-pulse-slow"></div>
        <div class="absolute -bottom-[200px] -left-[200px] w-[500px] h-[500px] bg-gradient-to-r from-[rgba(215,109,119,0.2)] to-[rgba(74,30,115,0.2)] rounded-full blur-3xl -z-10 animate-pulse-slow opacity-70"></div>
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed w-64 text-white p-5 flex flex-col h-full z-40  sidebar-visible">

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
                        $firstName = isset($_SESSION['first_name']) ? trim(htmlspecialchars($_SESSION['first_name'])) : '';
                        $lastName = isset($_SESSION['last_name']) ? trim(htmlspecialchars($_SESSION['last_name'])) : '';
                        echo !empty($firstName) || !empty($lastName) ? "$firstName $lastName" : 'User Name';
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
        <button id="sidebarToggle" class="fixed left-0 top-1/2 text-white p-3 rounded-r z-50 transition-all duration-300 ease-in-out toggle-moved hover:shadow-lg hover:translate-x-1 bg-gradient-to-r from-[rgba(74,30,115,0.8)] to-[rgba(215,109,119,0.8)] backdrop-blur-md border border-[rgba(215,109,119,0.3)]">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main id="mainContent" class="flex-1 p-10  content-shifted">
            <div id="content">
                <h1 class="text-3xl font-bold text-gradient">Dashboard Overview</h1>
                
                <!-- Dashboard Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                    <!-- Analytics Card -->
                    <a href="analytics.php" class="block">
                        <div class="trae-card rounded-xl overflow-hidden group">
                            <div class="bg-gradient-to-r from-[#4A1E73] to-[#D76D77] h-2 group-hover:h-3 "></div>
                            <div class="p-6 relative overflow-hidden z-10">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-xl font-semibold text-white">Analytics Dashboard</h2>
                                    <div class="glass-effect p-3 rounded-full group-hover:scale-110">
                                        <i class="fa-solid fa-chart-bar text-[#D76D77] text-xl"></i>
                                    </div>
                                </div>
                                <p class="text-[#A0A0B0] mt-4">View comprehensive analytics, case statistics, and data visualizations.</p>
                                <div class="mt-6 flex items-center text-[#D76D77] relative z-10  group-hover:translate-x-2">
                                    <span>Explore Analytics</span>
                                    <i class="fa-solid fa-arrow-right ml-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Reports Card -->
                    <a href="report.php" class="block">
                        <div class="trae-card rounded-xl overflow-hidden group">
                            <div class="bg-gradient-to-r from-[#4A1E73] to-[#D76D77] h-2 group-hover:h-3 "></div>
                            <div class="p-6 relative overflow-hidden z-10">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-xl font-semibold text-white">Reports</h2>
                                    <div class="glass-effect p-3 rounded-full  group-hover:scale-110">
                                        <i class="fa-solid fa-file text-[#D76D77] text-xl"></i>
                                    </div>
                                </div>
                                <p class="text-[#A0A0B0] mt-4">Access and manage incident reports and case documentation.</p>
                                <div class="mt-6 flex items-center text-[#D76D77] relative z-10  group-hover:translate-x-2">
                                    <span>View Reports</span>
                                    <i class="fa-solid fa-arrow-right ml-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Map Card -->
                    <a href="map.php" class="block">
                        <div class="trae-card rounded-xl overflow-hidden group">
                            <div class="bg-gradient-to-r from-[#4A1E73] to-[#D76D77] h-2 group-hover:h-3 "></div>
                            <div class="p-6 relative overflow-hidden z-10">
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-xl font-semibold text-white">Safety Map</h2>
                                    <div class="glass-effect p-3 rounded-full group-hover:scale-110">
                                        <i class="fa-solid fa-map text-[#D76D77] text-xl"></i>
                                    </div>
                                </div>
                                <p class="text-[#A0A0B0] mt-4">View incident locations and safety information on an interactive map.</p>
                                <div class="mt-6 flex items-center text-[#D76D77] relative z-10 group-hover:translate-x-2">
                                    <span>Open Map</span>
                                    <i class="fa-solid fa-arrow-right ml-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                    <!-- Latest Incident Report Tile -->
                    <?php
                    require_once 'mysqli_db.php';
                    $sql = "SELECT * FROM incidents WHERE user_id = ? ORDER BY date_time DESC LIMIT 1";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $latest_incident = $result->fetch_assoc();
                    ?>
                    <div class="trae-card h-[500px] w-[100%] -mr-[10%] p-6 rounded-xl overflow-hidden group">
                    <div class="bg-gradient-to-r from-[#4A1E73] to-[#D76D77] h-2 group-hover:h-3 transition-all duration-300"></div>
                    <div class="p-6 relative overflow-hidden z-10">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-xl font-semibold text-white">Latest Incident Report</h2>
                                <div class="glass-effect p-3 rounded-full group-hover:scale-110">
                                    <i class="fa-solid fa-triangle-exclamation text-[#D76D77] text-xl"></i>
                                </div>
                            </div>
                            <?php if ($latest_incident): ?>
                            <div class="mt-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-[#A0A0B0]">Type:</span>
                                    <span class="text-white"><?php echo $latest_incident ? htmlspecialchars($latest_incident['incident_type']) : 'N/A'; ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#A0A0B0]">Status:</span>
                                    <span class="text-white"><?php echo $latest_incident ? htmlspecialchars($latest_incident['status']) : 'N/A'; ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#A0A0B0]">Description:</span>
                                    <span class="text-white"><?php echo $latest_incident ? htmlspecialchars($latest_incident['description']) : 'N/A'; ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-[#A0A0B0]">Reported:</span>
                                    <span class="text-white"><?php echo $latest_incident ? date('M d, Y h:i A', strtotime($latest_incident['date_time'])) : 'N/A'; ?></span>
                                </div>
                                <!-- Star Rating System
                                <div class="mt-6 border-t border-[rgba(74,30,115,0.3)] pt-4">
                                    <h3 class="text-white text-lg mb-3">Rate Your Experience</h3>
                                    <div class="flex items-center space-x-2" id="starRating">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <button onclick="rateExperience(<?php echo $i; ?>)" class="text-2xl transition-colors duration-200 hover:text-[#D76D77]">
                                                <i class="fa-star <?php echo isset($_SESSION['rating']) && $_SESSION['rating'] >= $i ? 'fas text-[#D76D77]' : 'far text-gray-400'; ?>"></i>
                                            </button>
                                        <?php endfor; ?>
                                    </div>
                                    Feedback Form (Initially Hidden) 
                                    <div id="feedbackForm" class="mt-4 hidden">
                                        <textarea id="feedbackText" class="w-full p-3 rounded-lg bg-[rgba(46,46,78,0.3)] border border-[rgba(74,30,115,0.3)] text-white placeholder-gray-400 focus:outline-none focus:border-[#D76D77] transition-colors duration-200" placeholder="Please share your feedback for improvement..."></textarea>
                                        <button onclick="submitFeedback()" class="mt-2 px-4 py-2 bg-gradient-to-r from-[#4A1E73] to-[#D76D77] text-white rounded-lg hover:opacity-90 transition-opacity duration-200">Submit Feedback</button>
                                    </div>
                                 </div> -->
                            </div>
                            <?php else: ?>
                            <div class="mt-4 text-center text-[#A0A0B0]">
                                <p>No incidents reported yet</p>
                            </div>
                            <?php endif; ?>
                            <a href="report.php" class="mt-6 flex items-center text-[#D76D77] relative z-10 group-hover:translate-x-2">
                                <span>View All Reports</span>
                                <i class="fa-solid fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                    <!-- Incident Progress Card -->
                    <div class="trae-card h-[500px] w-[206%] -mr-[20%] rounded-xl overflow-hidden group">
                        <div class="bg-gradient-to-r from-[#4A1E73] to-[#D76D77] h-2 group-hover:h-3 transition-all duration-300"></div>
                        <div class="p-6 relative overflow-hidden z-10">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-xl font-semibold text-white">Incident Progress</h2>
                                <div class="glass-effect p-3 rounded-full group-hover:scale-110 transition-transform duration-300">
                                    <i class="fa-solid fa-list-check text-[#D76D77] text-xl"></i>
                                </div>
                            </div>
                            <?php if ($latest_incident): 
                                $progress = 0;
                                $security_confirmed = isset($_SESSION['security_confirmed']) ? $_SESSION['security_confirmed'] : false;
                                $victim_safe = isset($_SESSION['victim_safe']) ? $_SESSION['victim_safe'] : false;
                                $resolution_confirmed = isset($_SESSION['resolution_confirmed']) ? $_SESSION['resolution_confirmed'] : false;
                                
                                if ($security_confirmed) $progress += 33;
                                if ($victim_safe) $progress += 33;
                                if ($resolution_confirmed) $progress += 34;
                            ?>
                            <div class="mt-4 space-y-6">
                                <!-- Progress Bar -->
                                <div class="w-full bg-gray-700 rounded-full h-2.5 mb-4">
                                    <div class="bg-green-700 h-2.5 rounded-full transition-all duration-500" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                
                                <!-- Security Response -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[#A0A0B0]">Security Response</span>
                                        <!-- <button onclick="updateProgress('security_response')" class="bg-gradient-to-r from-[#4A1E73] to-[#D76D77] text-white px-4 py-2 rounded hover:opacity-90 transition-opacity">Confirm Arrival</button> -->
                                        <button onclick="updateProgress('security_confirmed')" class="px-3 py-1 rounded text-sm <?php echo $security_confirmed ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300'; ?>">
                                            <?php echo $security_confirmed ? 'Confirmed' : 'Confirm Arrival'; ?>
                                        </button>
                                    </div>
                                    <!-- <div class="flex items-center justify-between">
                                        <span class="text-[#A0A0B0]">Victim Safety Status</span>
                                        <button onclick="updateProgress('victim_safety')" class="bg-gradient-to-r from-[#4A1E73] to-[#D76D77] text-white px-4 py-2 rounded hover:opacity-90 transition-opacity">Confirm Safety</button>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-[#A0A0B0]">Resolution Status</span>
                                        <button onclick="updateProgress('resolution_status')" class="bg-gradient-to-r from-[#4A1E73] to-[#D76D77] text-white px-4 py-2 rounded hover:opacity-90 transition-opacity">Confirm Resolution</button>
                                    </div> -->
                                    
                             
                                </div>

                                <!-- Victim Safety Status -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[#A0A0B0]">Victim Safety Status</span>
                                        <button onclick="updateProgress('victim_safe')" class="px-3 py-1 rounded text-sm <?php echo $victim_safe ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300'; ?>">
                                            <?php echo $victim_safe ? 'Safe' : 'Confirm Safety'; ?>
                                        </button>
                                    </div>
                                </div>

                                <!-- Resolution Status -->
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[#A0A0B0]">Resolution Status</span>
                                        <button onclick="updateProgress('resolution_confirmed')" class="px-3 py-1 rounded text-sm <?php echo $resolution_confirmed ? 'bg-green-500/20 text-green-300' : 'bg-yellow-500/20 text-yellow-300'; ?>">
                                            <?php echo $resolution_confirmed ? 'Resolved' : 'Confirm Resolution'; ?>
                                        </button>
                                    </div>
                                </div>
                                    
                                </div>
                                <!-- Star Rating System -->
                                <div class="mt-6 border-t border-[rgba(74,30,115,0.3)] pt-4">
                                    <h3 class="text-white text-lg mb-3">Rate Your Experience</h3>
                                    <div class="flex items-center space-x-2" id="starRating">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <button onclick="rateExperience(<?php echo $i; ?>)" class="text-2xl transition-colors duration-200 hover:text-yellow-600">
                                                <i class="fa-star <?php echo isset($_SESSION['rating']) && $_SESSION['rating'] >= $i ? 'fas text-yellow-500' : 'far text-gray-400'; ?>"></i>
                                            </button>
                                        <?php endfor; ?>
                                    </div>
                                    <!-- Feedback Form (Initially Hidden) -->
                                    <div id="feedbackForm" class="mt-4 hidden">
                                        <textarea id="feedbackText" class="w-full p-1 cols-1 rounded-lg bg-[rgba(46,46,78,0.3)] border border-[rgba(74,30,115,0.3)] text-white placeholder-gray-400 focus:outline-none focus:border-[#D76D77] transition-colors duration-200" placeholder="Please share your feedback for improvement..."></textarea>
                                        <button onclick="submitFeedback()" class="mt-0.5 px-2 py-1 bg-gradient-to-r from-[#4A1E73] to-[#D76D77] text-white rounded-lg hover:opacity-90 transition-opacity duration-200">Submit Feedback</button>
                                    </div>
                                      
                                <!-- Resolution Status -->
                                <!-- <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-[#A0A0B0]">Resolution Status</span>
                                        <span class="text-[#D76D77]"><?php echo $latest_incident['status'] === 'resolved' ? '100%' : '50%'; ?></span>
                                    </div>
                                    <div class="h-2 bg-[rgba(74,30,115,0.2)] rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-[#4A1E73] to-[#D76D77] rounded-full" style="width: <?php echo $latest_incident['status'] === 'resolved' ? '100%' : '50%'; ?>"></div>
                                    </div>
                                </div> -->


                                <!-- Status Indicators -->
                                <!-- <div class="grid grid-cols-2 gap-4 mt-6">
                                    <div class="glass-effect p-4 rounded-lg text-center">
                                        <i class="fa-solid fa-shield-halved text-[#D76D77] text-xl mb-2"></i>
                                        <p class="text-[#A0A0B0] text-sm">Security Reached</p>
                                        <p class="text-white font-semibold mt-1"><?php echo $latest_incident['status'] === 'resolved' ? 'Yes' : 'En Route'; ?></p>
                                    </div>
                                    <div class="glass-effect p-4 rounded-lg text-center">
                                        <i class="fa-solid fa-clipboard-check text-[#D76D77] text-xl mb-2"></i>
                                        <p class="text-[#A0A0B0] text-sm">Case Resolution</p>
                                        <p class="text-white font-semibold mt-1"><?php echo $latest_incident['status'] === 'resolved' ? 'Complete' : 'In Progress'; ?></p>
                                    </div>
                                </div> -->
                            </div>
                            <?php else: ?>
                            <div class="mt-4 text-center text-[#A0A0B0]">
                                <p>No active incidents to track</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        class Sidebar {
            constructor() {
                this.sidebar = document.getElementById('sidebar');
                this.sidebarToggle = document.getElementById('sidebarToggle');
                this.mainContent = document.getElementById('mainContent');
                this.initializeSidebar();
            }

            initializeSidebar() {
                this.sidebarToggle.addEventListener('click', () => {
                    this.toggleSidebar();
                });
            }

            toggleSidebar() {
                this.sidebar.classList.toggle('sidebar-hidden');
                this.sidebar.classList.toggle('sidebar-visible');
                this.mainContent.classList.toggle('content-shifted');
                this.mainContent.classList.toggle('content-full');
                this.sidebarToggle.classList.toggle('toggle-moved');
                this.sidebarToggle.classList.toggle('toggle-default');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            new Sidebar();
        });
    </script>
</body>
</html>
 