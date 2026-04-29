const locationInput = document.getElementById('locationInput');
const searchResults = document.getElementById('searchResults');

// LPU Campus locations with coordinates
const lpuLocations = [
    {
        name: "LPU Main Gate Area",
        code: "7P44+JRR",
        area: "Khajurla, Punjab 144411"
    },
    {
        name: "LPU Drinking Water Area",
        code: "7P54+5WF",
        area: "Punjab 144411"
    },
    {
        name: "LPU Sunken Ground",
        code: "7P55+C45",
        area: "Nanak Nagri, Punjab"
    }
];

locationInput.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    
    if (query.length < 2) {
        searchResults.classList.add('hidden');
        return;
    }

    const filteredLocations = lpuLocations.filter(location => 
        location.name.toLowerCase().includes(query) ||
        location.code.toLowerCase().includes(query) ||
        location.area.toLowerCase().includes(query)
    );

    searchResults.innerHTML = '';
    searchResults.classList.remove('hidden');

    if (filteredLocations.length === 0) {
        const div = document.createElement('div');
        div.className = 'p-2 text-gray-500 text-sm';
        div.textContent = 'No campus locations found';
        searchResults.appendChild(div);
        return;
    }

    filteredLocations.forEach(location => {
        const div = document.createElement('div');
        div.className = 'p-2 text-sm hover:bg-pink-50 cursor-pointer';
        div.textContent = `${location.name} (${location.code})`;
        div.addEventListener('click', () => {
            locationInput.value = `${location.name} - ${location.code}`;
            searchResults.classList.add('hidden');
        });
        searchResults.appendChild(div);
    });
});

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!locationInput.contains(e.target)) {
        searchResults.classList.add('hidden');
    }
});
