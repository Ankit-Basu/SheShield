<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draw Safe Zone</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <!-- Leaflet.draw CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <style>
        #map { height: 500px; width: 100%; }
    </style>
</head>
<body>
    <h2>Draw Safe Zone</h2>
    <div id="map"></div>
    <form id="safeZoneForm" method="POST" action="save_safe_zone.php">
        <input type="hidden" name="polygon_data" id="polygonData">
        <input type="hidden" name="description" id="zoneDescription">
    </form>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <!-- Leaflet.draw JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

    <script>
        // Initialize the map
        var map = L.map('map').setView([51.505, -0.09], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Initialize drawing controls
        var drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        var drawControl = new L.Control.Draw({
            draw: {
                polygon: true,
                rectangle: true,
                circle: false,
                circlemarker: false,
                marker: false,
                polyline: false
            },
            edit: {
                featureGroup: drawnItems,
                remove: true
            }
        });
        map.addControl(drawControl);

        // Handle draw events
        map.on('draw:created', function(e) {
            drawnItems.clearLayers();
            var layer = e.layer;
            drawnItems.addLayer(layer);

            // Get coordinates
            var coordinates = [];
            if (layer instanceof L.Polygon || layer instanceof L.Rectangle) {
                coordinates = layer.getLatLngs()[0].map(function(latlng) {
                    return [latlng.lat, latlng.lng];
                });
            }

            // Prompt for description
            var description = prompt('Please enter a description for this safe zone:');
            if (description) {
                document.getElementById('polygonData').value = JSON.stringify([coordinates]);
                document.getElementById('zoneDescription').value = description;
                document.getElementById('safeZoneForm').submit();
            }
        });
    </script>
</body>
</html>