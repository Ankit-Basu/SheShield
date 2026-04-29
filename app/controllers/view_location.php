<?php
require_once 'mysqli_db.php';

if (!isset($_GET['id'])) {
    die('No location ID provided');
}

$location_id = $_GET['id'];

// Get location data
$stmt = $conn->prepare("SELECT * FROM shared_locations WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $location_id);
$stmt->execute();
$result = $stmt->get_result();
$location = $result->fetch_assoc();

if (!$location) {
    die('Location not found or has expired');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Location - SheShield</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        #map {
            height: 400px;
            width: 100%;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-[#D12E79] mb-4">Shared Location</h1>
            <div id="map" class="mb-4"></div>
            <div class="text-sm text-gray-600">
                <p>Shared at: <?php echo date('F j, Y g:i A', strtotime($location['timestamp'])); ?></p>
                <p class="mt-2">Coordinates: <?php echo round($location['latitude'], 6); ?>, <?php echo round($location['longitude'], 6); ?></p>
            </div>
            <div class="mt-4 flex space-x-2">
                <button onclick="openInMaps()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Open in Maps
                </button>
                <button onclick="copyCoordinates()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Copy Coordinates
                </button>
            </div>
        </div>
    </div>

    <script>
        const lat = <?php echo $location['latitude']; ?>;
        const lng = <?php echo $location['longitude']; ?>;

        // Initialize map
        const map = L.map('map').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add marker
        L.marker([lat, lng]).addTo(map)
            .bindPopup('Shared Location')
            .openPopup();

        function openInMaps() {
            window.open(`https://www.google.com/maps?q=${lat},${lng}`);
        }

        function copyCoordinates() {
            navigator.clipboard.writeText(`${lat}, ${lng}`).then(() => {
                alert('Coordinates copied to clipboard!');
            });
        }
    </script>
</body>
</html>
