function markResolved(incidentId) {
    if (!confirm('Are you sure you want to mark this incident as resolved?')) return;
    
    fetch('/Women_safety_project/k23ndwebsite/resolve-incident.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({id: incidentId})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            const incidentElement = document.getElementById(`incident-${incidentId}`);
            if (incidentElement) {
                incidentElement.remove();
            }
        } else {
            console.error('Error from server:', data.error || 'Unknown error');
            alert('Failed to mark incident as resolved. See console for details.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to mark incident as resolved. Please try again later.');
    });

    // Refresh the incident list after successful resolution
    if (typeof refreshIncidents === 'function') {
        refreshIncidents();
    }
}