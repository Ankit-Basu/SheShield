<?php
require_once __DIR__ . '/../app/models/mysqli_db.php';
require_once 'send_escort_email.php';

// Simple test page
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Test</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-xl font-bold mb-4">Test Email</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate escort_id
            if (empty($_POST['escort_id'])) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">';
                echo 'Error: Missing escort ID';
                echo '</div>';
            } else {
                // Required parameters for testing
                $requestDetails = [
                    'location' => 'Main Campus Gate',
                    'destination' => 'University Library'
                ];
                
                try {
                    $result = sendEscortEmail($_POST['escort_id'], $requestDetails);
                    $class = $result['success'] ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';
                    echo '<div class="' . $class . ' border-l-4 p-4 mb-4">';
                    echo htmlspecialchars($result['message']);
                    echo '</div>';
                } catch (Exception $e) {
                    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">';
                    echo 'Error: ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
            }
        }

        // Get active escorts
        $query = "SELECT escort_id, name, email FROM escorts WHERE status = 'active'";
        $result = $conn->query($query);
        ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block text-gray-700 mb-2">Select Escort:</label>
                <select name="escort_id" class="w-full border rounded p-2" required>
                    <option value="">Choose an escort...</option>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['escort_id']) . '">';
                            echo htmlspecialchars($row['name']) . ' (' . htmlspecialchars($row['email']) . ')';
                            echo '</option>';
                        }
                    }
                    ?>
                </select>
                <?php if (!$result || $result->num_rows === 0): ?>
                <p class="mt-2 text-sm text-red-600">No active escorts available in the system</p>
                <?php endif; ?>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white rounded py-2 px-4 hover:bg-blue-600"
                    <?php echo (!$result || $result->num_rows === 0) ? 'disabled' : ''; ?>>
                Send Test Email
            </button>
        </form>

        <div class="mt-4 text-sm text-gray-600">
            <p>Test will send email with:</p>
            <ul class="list-disc ml-4 mt-2">
                <li>Pickup: Main Campus Gate</li>
                <li>Destination: University Library</li>
            </ul>
        </div>
    </div>
</body>
</html>
