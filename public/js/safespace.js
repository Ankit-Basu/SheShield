document.addEventListener('DOMContentLoaded', function() {
    // Initialize map centered on LPU Campus
    let map = L.map('map', {
        center: [31.246521, 75.701843], // LPU Main Gate coordinates
        zoom: 17,
        minZoom: 15,
        maxZoom: 18,
        maxBounds: [
            [31.2400, 75.6900], // Southwest
            [31.2520, 75.7130]  // Northeast
        ]
    });

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Create a FeatureGroup to store drawn items
    const drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    // Custom icons
    const safeIcon = L.divIcon({
        html: '<i class="fas fa-shield-heart text-green-500 text-2xl"></i>',
        className: 'custom-div-icon',
        iconSize: [30, 30],
        iconAnchor: [15, 30]
    });

    const incidentIcon = L.divIcon({
        html: '<i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>',
        className: 'custom-div-icon',
        iconSize: [30, 30],
        iconAnchor: [15, 30]
    });

    // Add Leaflet Draw CSS
    const drawCssLink = document.createElement('link');
    drawCssLink.rel = 'stylesheet';
    drawCssLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css';
    document.head.appendChild(drawCssLink);

    // Add Leaflet Draw JS
    const drawScript = document.createElement('script');
    drawScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js';
    document.head.appendChild(drawScript);

    // Add LPU location markers
    const plusCodeCoordinates = {
        '7P44+JRR': { lat: 31.246521, lng: 75.701843 },
        '7P54+5WF': { lat: 31.247234, lng: 75.702156 },
        '7P55+C45': { lat: 31.248012, lng: 75.703245 }
    };

    

    // Handle map clicks
    map.on('click', function(e) {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        
        if (latInput && lngInput) {
            latInput.value = e.latlng.lat.toFixed(6);
            lngInput.value = e.latlng.lng.toFixed(6);
        }
    });

    


    // Initialize Leaflet Draw when the script is loaded
    drawScript.onload = function() {
        // Initialize draw control
        const drawControl = new L.Control.Draw({
            draw: {
                marker: false,
                circle: false,
                circlemarker: false,
                polyline: false,
                polygon: {
                    allowIntersection: false,
                    drawError: {
                        color: '#e1e100',
                        message: '<strong>Error:</strong> Polygon edges cannot intersect!'
                    },
                    shapeOptions: {
                        color: '#28a745',
                        fillOpacity: 0.3
                    }
                },
                rectangle: {
                    shapeOptions: {
                        color: '#28a745',
                        fillOpacity: 0.3
                    }
                }
            },
            edit: {
                featureGroup: drawnItems,
                remove: true
            }
        });
        map.addControl(drawControl);

        // Handle draw created event
        map.on('draw:created', function(e) {
            const layer = e.layer;
            drawnItems.addLayer(layer);

            // Get coordinates of the drawn shape
            let coordinates;
if (layer instanceof L.Polygon) {
    coordinates = layer.getLatLngs()[0]; // Get first ring for polygon
} else if (layer instanceof L.Rectangle) {
    const bounds = layer.getBounds();
    coordinates = [
        bounds.getSouthWest(),
        bounds.getNorthEast()
    ];
} else {
    alert('Invalid shape type');
    return;
}

// Validate coordinates
if (!coordinates || coordinates.length < 3) {
    alert('Invalid coordinates');
    return;
}
            
            // Show modal or form for additional details
            const description = prompt('Enter description for this safe zone:');
            if (description) {
                // Save safe zone to database using XMLHttpRequest
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'save_safe_zone.php');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        alert('Safe zone saved successfully!');
                    } else {
                        alert('Error saving safe zone: ' + xhr.statusText);
                        drawnItems.removeLayer(layer);
                    }
                };
                xhr.onerror = function() {
                    alert('Network error occurred while saving safe zone');
                    drawnItems.removeLayer(layer);
                };
                const formData = new FormData();
                formData.append('polygon_data', coordinates);
                formData.append('description', description);
                xhr.send(formData);
            } else {
                drawnItems.removeLayer(layer);
            }
        });


    };

    // Handle safe space form submission
    const safeSpaceForm = document.getElementById('safeSpaceForm');
    if (safeSpaceForm) {
        safeSpaceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'save_safe_space.php');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        // Add marker to map
                        const marker = L.marker(
                            [parseFloat(formData.get('latitude')), parseFloat(formData.get('longitude'))], 
                            {icon: safeIcon}
                        )
                        .bindPopup(`<div class="p-2">
                            <h3 class="font-bold mb-2">${formData.get('description')}</h3>
                            <p class="text-sm text-gray-600">Active for: ${formData.get('timeActive')} hours</p>
                        </div>`)
                        .addTo(safeSpaces);
                        
                        // Clear form
                        e.target.reset();
                        alert('Safe space added successfully!');
                    } else {
                        alert('Error saving safe space: ' + (data.message || 'Unknown error'));
                    }
                } else {
                    alert('Error saving safe space: ' + xhr.statusText);
                }
            };
            xhr.send(formData);
        });
    }


});