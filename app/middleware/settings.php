<?php
session_start();

// Ensure profile image is loaded
if (!isset($_SESSION['profile_image']) || empty($_SESSION['profile_image'])) {
    // Try to load profile image if user is logged in but image is missing
    if (isset($_SESSION['user_id'])) {
        require_once 'includes/profile_image_handler.php';
        $profileImage = getProfileImage($_SESSION['user_id']);
        if ($profileImage) {
            $_SESSION['profile_image'] = $profileImage;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="src/trae-theme.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>
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

        @media (min-width: 768px) {
            .mainContent {
                margin-left: 16rem;
            }
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

        /* Sidebar behavior */
        .sidebar-hidden { transform: translateX(-100%); }
        .sidebar-visible { transform: translateX(0); }
        .toggle-moved { transform: translateX(16rem) translateY(-50%); }
        .toggle-default { transform: translateX(0) translateY(-50%); }
        .content-shifted { margin-left: 16rem; }
        .content-full { margin-left: 0; }
    </style>
</head>
<body class="bg-[#1E1E2E] text-[#F0F0F0]">
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
                        if (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])) {
                            echo '<img src="' . htmlspecialchars($_SESSION['profile_image']) . '" class="w-full h-full object-cover" alt="Profile Picture">';
                        } else {
                            echo '<i class="fa-solid fa-user text-2xl text-white"></i>';
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
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='dashboard.php'">
                        <i class="fa-solid fa-house"></i> <span>Home</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='report.php'">
                        <i class="fa-solid fa-file"></i> <span>Reports</span>
                    </li>
                    <li class="trae-sidebar-item p-3 rounded flex items-center space-x-2 cursor-pointer" onclick="window.location.href='analytics.php'">
                        <i class="fa-solid fa-chart-bar"></i> <span>Analytics</span>
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
                    <li class="trae-sidebar-item active p-3 rounded flex items-center space-x-2">
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
        <main id="mainContent" class="flex-1 p-8 transition-all duration-300 ease-in-out content-shifted overflow-y-auto h-screen">
            <div id="content" class="min-h-full">
                <h1 class="text-4xl font-bold text-gradient mb-8">Settings</h1>
                
                <!-- Settings Sections -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Profile Settings -->
                    <div class="glass-effect rounded-xl p-6">
                        <h2 class="text-2xl font-semibold mb-6 text-white">Profile Settings</h2>
                        <form class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Profile Picture</label>
                                <div class="flex items-center space-x-4">
                                    <div class="w-20 h-20 rounded-full bg-gradient-to-r from-[#4A1E73] to-[#D76D77] flex items-center justify-center overflow-hidden">
                                        <?php
                                        if (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])) {
                                            echo '<img src="' . htmlspecialchars($_SESSION['profile_image']) . '" class="w-full h-full object-cover" alt="Profile Picture">';
                                        } else {
                                            echo '<i class="fa-solid fa-user text-3xl text-white"></i>';
                                        }
                                        ?>
                                    </div>
                                    <div class="flex flex-col space-y-2">
                                        <label class="px-4 py-2 bg-gradient-to-r from-[#4A1E73] to-[#D76D77] text-white rounded-lg hover:from-[#3A1863] hover:to-[#C65D67] transition-all duration-300 cursor-pointer">
                                            <span>Change Picture</span>
                                            <input type="file" name="profile_image" id="profile_image" class="hidden" accept="image/jpeg,image/png,image/jpg" onchange="handleImageUpload(this)">
                                        </label>
                                        <span id="upload-status" class="text-sm"></span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Full Name</label>
                                <input type="text" class="w-full px-4 py-2 rounded-lg glass-effect bg-[rgba(46,46,78,0.3)] text-white border border-[rgba(215,109,119,0.3)] focus:outline-none focus:ring-2 focus:ring-[#D12E79]" value="<?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : ''; ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                                <input type="email" class="w-full px-4 py-2 rounded-lg glass-effect bg-[rgba(46,46,78,0.3)] text-white border border-[rgba(215,109,119,0.3)] focus:outline-none focus:ring-2 focus:ring-[#D12E79]" value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>">
                            </div>
                            <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-[#4A1E73] to-[#D76D77] text-white rounded-lg hover:from-[#3A1863] hover:to-[#C65D67] transition-all duration-300">Save Changes</button>
                        </form>
                    </div>

                    <!-- Security Settings -->
                    <div class="glass-effect rounded-xl p-6">
                        <h2 class="text-2xl font-semibold mb-6 text-white">Security Settings</h2>
                        <form class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Current Password</label>
                                <input type="password" class="w-full px-4 py-2 rounded-lg glass-effect bg-[rgba(46,46,78,0.3)] text-white border border-[rgba(215,109,119,0.3)] focus:outline-none focus:ring-2 focus:ring-[#D12E79]">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">New Password</label>
                                <input type="password" class="w-full px-4 py-2 rounded-lg glass-effect bg-[rgba(46,46,78,0.3)] text-white border border-[rgba(215,109,119,0.3)] focus:outline-none focus:ring-2 focus:ring-[#D12E79]">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Confirm New Password</label>
                                <input type="password" class="w-full px-4 py-2 rounded-lg glass-effect bg-[rgba(46,46,78,0.3)] text-white border border-[rgba(215,109,119,0.3)] focus:outline-none focus:ring-2 focus:ring-[#D12E79]">
                            </div>
                            <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-[#4A1E73] to-[#D76D77] text-white rounded-lg hover:from-[#3A1863] hover:to-[#C65D67] transition-all duration-300">Update Password</button>
                        </form>
                    </div>

                    <!-- Notification Settings -->
                    <div class="glass-effect rounded-xl p-6">
                        <h2 class="text-2xl font-semibold mb-6 text-white">Notification Settings</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-300">Email Notifications</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#4A1E73] peer-checked:to-[#D76D77]"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-300">SMS Alerts</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#4A1E73] peer-checked:to-[#D76D77]"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-300">Emergency Alerts</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#4A1E73] peer-checked:to-[#D76D77]"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Settings -->
                    <div class="glass-effect rounded-xl p-6">
                        <h2 class="text-2xl font-semibold mb-6 text-white">Privacy Settings</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-300">Share Location Data</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#4A1E73] peer-checked:to-[#D76D77]"></div>
                                </label>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-300">Public Profile</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#4A1E73] peer-checked:to-[#D76D77]"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize sidebar
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

            // Clean up event listeners on page unload
            window.addEventListener('unload', function() {
                clearTimeout(clickTimeout);
                clearTimeout(resizeTimeout);
            });

            // Update sidebar state on window resize with debounce
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    updateSidebarState(window.innerWidth >= 768);
                }, 100);
            });

        
        // Handle profile image upload
        function handleImageUpload(input) {
            if (input.files && input.files[0]) {
                // Show preview immediately after selection
                const reader = new FileReader();
                const statusElement = document.getElementById('upload-status');
                
                reader.onload = function(e) {
                    // Update all profile image containers in the document with preview
                    const allProfileContainers = document.querySelectorAll('.w-12.h-12.rounded-full, .w-20.h-20.rounded-full');
                    allProfileContainers.forEach(container => {
                        // Clear any existing content
                        container.innerHTML = '';
                        // Create and append the image element
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-full h-full object-cover';
                        img.alt = 'Profile Picture';
                        container.appendChild(img);
                    });
                    
                    statusElement.textContent = 'Image selected. Uploading...';
                    statusElement.className = 'text-sm text-blue-400';
                }
                reader.readAsDataURL(input.files[0]);
                
                // Prepare form data for upload
                const formData = new FormData();
                formData.append('profile_image', input.files[0]);

                // Send the image to server
                fetch('includes/handle_profile_update.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusElement.textContent = 'Profile picture updated successfully!';
                        statusElement.className = 'text-sm text-green-400';
                        
                        // We don't need to update the images again as we've already shown the preview
                        // Just keep a short timeout to show the success message
                        setTimeout(() => {
                            statusElement.textContent = '';
                        }, 3000);
                    } else {
                        statusElement.textContent = data.message || 'Error updating profile picture';
                        statusElement.className = 'text-sm text-red-400';
                    }
                })
                .catch(error => {
                    statusElement.textContent = 'Error uploading image';
                    statusElement.className = 'text-sm text-red-400';
                    console.error('Error:', error);
                });
            }
        }
    </script>
</body>
</html>
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
</body>
</html>
