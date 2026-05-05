<?php
require_once __DIR__ . '/../models/mysqli_db.php';
require_once __DIR__ . '/../middleware/session_bootstrap.php';
configure_session_storage();
session_start();

// Get current user ID from session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Fetch total incidents for current user
$total_incidents_query = "SELECT COUNT(*) as count FROM incidents WHERE user_id = $user_id";
$result = mysqli_query($conn, $total_incidents_query);
$total_incidents = mysqli_fetch_assoc($result)['count'];

// Fetch resolved incidents for resolution rate
$resolved_incidents_query = "SELECT COUNT(*) as count FROM incidents WHERE status = 'resolved' AND user_id = $user_id";
$result = mysqli_query($conn, $resolved_incidents_query);
$resolved_incidents = mysqli_fetch_assoc($result)['count'];
$resolution_rate = $total_incidents > 0 ? round(($resolved_incidents / $total_incidents) * 100) : 0;

// Fetch recent incidents (last 7 days)
$recent_incidents_query = "SELECT COUNT(*) as count FROM incidents WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND user_id = $user_id";
$result = mysqli_query($conn, $recent_incidents_query);
$recent_incidents = mysqli_fetch_assoc($result)['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Campus Safety Monitoring</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- FontAwesome Icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js" crossorigin="anonymous"></script>
    <link href="src/trae-theme.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            transition: transform 0.3s ease-in-out;
        }
        .toggle-default {
            transform: translateX(0) translateY(-50%);
            transition: transform 0.3s ease-in-out;
        }
        .content-shifted {
            margin-left: 16rem;
            transition: margin-left 0.3s ease-in-out;
        }
        .content-full {
            margin-left: 0;
            transition: margin-left 0.3s ease-in-out;
        }
        #sidebarToggle {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
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

        @keyframes pulse-slow {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 0.6; }
        }
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
        <button id="sidebarToggle" class="fixed left-0 top-1/2 glass-effect bg-gradient-to-r from-[rgba(74,30,115,0.5)] to-[rgba(215,109,119,0.5)] text-white p-3 rounded-r z-50 toggle-moved hover:shadow-lg">
            <i class="fa-solid fa-bars"></i>
        </button>

        <!-- Main Content -->
        <main id="mainContent" class="flex-1 p-10 content-shifted overflow-y-auto h-screen">
            <div id="content">
                <h1 class="text-gradient text-3xl font-bold">Analytics</h1>
                <p class="mt-2">View and analyze safety data and statistics.</p>
                
                <!-- Summary Section -->
                <div class="mt-8 glass-effect rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 text-[#F0F0F0]  bg-clip-text">Campus Safety Overview</h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="glass-effect p-4 rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:border-[rgba(215,109,119,0.3)] border border-[rgba(74,30,115,0.2)]">
                            <h3 class="text-lg font-semibold text-[#D76D77] bg-clip-text">Total Incidents</h3>
                            <p class="text-3xl font-bold text-[#F0F0F0]"><?php echo $total_incidents; ?></p>
                        </div>
                        <div class="glass-effect p-4 rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:border-[rgba(215,109,119,0.3)] border border-[rgba(74,30,115,0.2)]">
                            <h3 class="text-lg font-semibold text-[#D76D77] bg-clip-text">Resolution Rate</h3>
                            <p class="text-3xl font-bold text-[#F0F0F0]"><?php echo $resolution_rate; ?>%</p>
                        </div>
                        <div class="glass-effect p-4 rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:border-[rgba(215,109,119,0.3)] border border-[rgba(74,30,115,0.2)]">
                            <h3 class="text-lg font-semibold text-[#D76D77] bg-clip-text">Recent Incidents (7 days)</h3>
                            <p class="text-3xl font-bold text-[#F0F0F0]"><?php echo $recent_incidents; ?></p>
                        </div>
                        <div class="glass-effect p-4 rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:border-[rgba(215,109,119,0.3)] border border-[rgba(74,30,115,0.2)]">
                            <h3 class="text-lg font-semibold text-[#D76D77] bg-clip-text">Active Cases</h3>
                            <p class="text-3xl font-bold text-[#F0F0F0]"><?php echo $total_incidents - $resolved_incidents; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Analytics Content -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Resolution Status Chart -->
                    <div class="glass-effect p-6 rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:border-[rgba(215,109,119,0.3)] border border-[rgba(74,30,115,0.2)]">
                        <h2 class="text-xl font-semibold mb-4 text-[#F0F0F0] bg-clip-text">Resolution Status</h2>
                        <canvas id="resolutionChart"></canvas>
                    </div>

                    <!-- Average Response Time -->
                    <div class="glass-effect p-6 rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:border-[rgba(215,109,119,0.3)] border border-[rgba(74,30,115,0.2)] shadow-lg">
                        <h2 class="text-xl font-semibold mb-4 bg-gradient-to-r from-[#D76D77] to-[#AB1E5C] text-transparent bg-clip-text">Average Response Time</h2>
                        <div class="h-64">
                            <canvas id="responseTimeChart"></canvas>
                        </div>
                    </div>


                
                   <div class="glass-effect p-6 rounded-lg transition-all duration-300 hover:transform hover:scale-105 hover:border-[rgba(215,109,119,0.3)] border border-[rgba(74,30,115,0.2)] shadow-lg">
                        <h2 class="text-xl font-semibold mb-4 bg-gradient-to-r from-[#D76D77] to-[#AB1E5C] text-transparent bg-clip-text">Safety Score Trend</h2>
                        <div class="h-64">
                            <canvas id="safetyScoreChart"></canvas>
                        </div>
                    </div>

                </div>

                <!-- PHP Code to Fetch Analytics Data -->
                <?php
                require_once 'includes/database.php';

                // Get Resolution Status Distribution for the user
                $status_query = "SELECT status, COUNT(*) as count FROM incidents WHERE user_id = ? GROUP BY status";
                $stmt = $conn->prepare($status_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $status_result = $stmt->get_result();
                $status_data = array();
                while ($row = mysqli_fetch_assoc($status_result)) {
                    $status_data[$row['status']] = $row['count'];
                }

                // Get Average Response Time for the user
                $response_time_query = "SELECT 
                    DATE(date_time) as date,
                    AVG(TIMESTAMPDIFF(HOUR, date_time, NOW())) as avg_hours
                    FROM incidents 
                    WHERE user_id = ?
                    GROUP BY DATE(date_time)
                    ORDER BY date DESC LIMIT 7";
                $stmt = $conn->prepare($response_time_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $response_time_result = $stmt->get_result();
                $response_time_data = array();
                while ($row = mysqli_fetch_assoc($response_time_result)) {
                    $response_time_data[$row['date']] = round($row['avg_hours'], 2);
                }

                // Calculate Safety Score based on resolution status and time
                $safety_score_query = "SELECT 
                    DATE(date_time) as date,
                    AVG(
                        CASE 
                            WHEN status = 'resolved' THEN 90
                            WHEN status = 'pending' THEN 70
                            ELSE 60
                        END -
                        LEAST(TIMESTAMPDIFF(HOUR, date_time, NOW())/24 * 5, 20)
                    ) as safety_score
                    FROM incidents 
                    WHERE user_id = ?
                    GROUP BY DATE(date_time)
                    ORDER BY date DESC LIMIT 7";
                $stmt = $conn->prepare($safety_score_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $safety_score_result = $stmt->get_result();
                $safety_score_data = array();
                while ($row = mysqli_fetch_assoc($safety_score_result)) {
                    $safety_score_data[$row['date']] = round($row['safety_score'], 1);
                }


                ?>

                <!-- JavaScript for Charts -->
                <script>
                    // Resolution Status Chart
                    new Chart(document.getElementById('resolutionChart'), {
                        type: 'doughnut',
                        data: {
                            labels: <?php echo json_encode(array_keys($status_data)); ?>,
                            datasets: [{
                                data: <?php echo json_encode(array_values($status_data)); ?>,
                                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });

                    // Response Time Chart
                    new Chart(document.getElementById('responseTimeChart'), {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode(array_keys($response_time_data)); ?>,
                            datasets: [{
                                label: 'Average Response Time (Hours)',
                                data: <?php echo json_encode(array_values($response_time_data)); ?>,
                                borderColor: '#D76D77',
                                backgroundColor: 'rgba(215, 109, 119, 0.3)',
                                borderWidth: 3,
                                pointBackgroundColor: '#AB1E5C',
                                pointBorderColor: '#fff',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        },
                                        color: '#F0F0F0'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(74, 30, 115, 0.8)',
                                    titleFont: {
                                        size: 16,
                                        weight: 'bold'
                                    },
                                    bodyFont: {
                                        size: 14
                                    },
                                    callbacks: {
                                        label: function(context) {
                                            return `Response Time: ${context.raw.toFixed(1)} hours`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: '#F0F0F0',
                                        font: {
                                            weight: 'bold'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Hours',
                                        color: '#F0F0F0',
                                        font: { weight: 'bold', size: 14 }
                                    }
                                },
                                x: {
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: '#F0F0F0',
                                        font: {
                                            weight: 'bold'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Date',
                                        color: '#F0F0F0',
                                        font: { weight: 'bold', size: 14 }
                                    }
                                }
                            }
                        }
                    });

                    // Safety Score Chart
                    new Chart(document.getElementById('safetyScoreChart'), {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode(array_keys($safety_score_data)); ?>,
                            datasets: [{
                                label: 'Safety Score',
                                data: <?php echo json_encode(array_values($safety_score_data)); ?>,
                                borderColor: '#AB1E5C',
                                backgroundColor: 'rgba(171, 30, 92, 0.3)',
                                borderWidth: 3,
                                pointBackgroundColor: '#D76D77',
                                pointBorderColor: '#fff',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        },
                                        color: '#F0F0F0'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(74, 30, 115, 0.8)',
                                    titleFont: {
                                        size: 16,
                                        weight: 'bold'
                                    },
                                    bodyFont: {
                                        size: 14
                                    },
                                    callbacks: {
                                        label: function(context) {
                                            return `Safety Score: ${context.raw}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: '#F0F0F0',
                                        font: {
                                            weight: 'bold'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Score',
                                        color: '#F0F0F0',
                                        font: { weight: 'bold', size: 14 }
                                    }
                                },
                                x: {
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: '#F0F0F0',
                                        font: {
                                            weight: 'bold'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Date',
                                        font: { weight: 'bold' }
                                    }
                                }
                            }
                        }
                    });
                </script>

<!-- Chart.js Library -->`
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                

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
</body>
</html>
