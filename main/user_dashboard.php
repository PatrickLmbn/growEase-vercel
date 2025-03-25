<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once "../config/database.php";

// Create database instance
$database = new Database();
$db = $database->getConnection();

// Use the connection
try {
    // Example query
    $query = "SELECT COUNT(DISTINCT moisture_pin) as total_sensors, COUNT(name) as total_plants FROM tbl_plants";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Get results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <script src="https://cdn.jsdelivr.net/npm/ably/browser/static/ably.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>GrowEase</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <header>
        <div class="w-full bg-green-600 p-8">
            <div class="max-w-7xl mx-auto flex justify-between items-center px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <img src="../assets/images/grass-logo.svg" alt="GrowEase" class="w-8 h-8">
                    <h1 class="text-white text-xl sm:text-2xl font-bold">GrowEase</h1>
                </div>
                <nav>
                    <ul class="flex items-center gap-4">
                        <li class="text-white text-sm sm:text-base">
                            <?php 
                                // Get username from database
                                $stmt = $db->prepare("SELECT username FROM tbl_users WHERE id = :user_id");
                                $stmt->execute([':user_id' => $_SESSION['user_id']]);
                                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                echo isset($user['username']) && !empty($user['username']) 
                                     ? htmlspecialchars($user['username']) 
                                     : htmlspecialchars($_SESSION['email']); 
                            ?>
                        </li>
                        <li class="relative">
                            <i class="bi bi-gear-fill text-white text-xl sm:text-2xl hover:text-green-100 cursor-pointer"
                                id="settingsButton"></i>
                            <!-- Settings Dropdown -->
                            <div id="settingsDropdown"
                                class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-50">
                                <div class="py-1">
                                    <a href="user_settings.php"
                                        class="block px-4 py-2 text-left text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                        <i class="bi bi-person-gear"></i>
                                        User Settings
                                    </a>
                                    <a href="../api/logout.php" onclick="confirmLogout(event)"
                                        class="block px-4 py-2 text-left text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                        <i class="bi bi-box-arrow-right"></i>
                                        Logout
                                    </a>
                                </div>
                            </div>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="bg-green-50 min-h-screen">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 gap-6">

                <!-- Status message section -->
                <div id="status" class="text-center text-green-600 mt-6 text-xl"></div>

                <div class="bg-white rounded-lg shadow-md p-6 border border-green-100">
                    <h3 class="text-xl font-semibold text-green-800 mb-6">Dashboard Overview</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex items-center gap-4 p-4 border rounded-lg border-green-100 hover:bg-green-50">
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="bi bi-flower1 text-2xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-green-600">Total Plants</p>
                                <p class="text-2xl font-bold text-green-800" id="total-plants">
                                    <?php echo $results[0]['total_plants']; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 border rounded-lg border-green-100 hover:bg-green-50">
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="bi bi-moisture text-2xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-green-600">Active Sensors</p>
                                <p class="text-2xl font-bold text-green-800" id="active-sensors">
                                    <?php echo $results[0]['total_sensors']; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 border rounded-lg border-green-100 hover:bg-green-50">
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="bi bi-bell-fill text-2xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-green-600">Notifications</p>
                                <p class="text-2xl font-bold text-green-800" id="notifications">8</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6 border border-green-100">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div class="flex items-center gap-4">
                            <button id="addPlantBtn"
                                class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors flex items-center gap-2">
                                <i class="bi bi-plus-lg"></i>
                                Add Plant
                            </button>
                            <button id="waterSelectedButton"
                                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="bi bi-droplet-fill"></i>
                                Water Selected
                            </button>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4">
                            <select id="wateringMode""
                                class=" rounded-md border-gray-300 text-gray-600 focus:ring-green-500
                                focus:border-green-500">
                                <option value="manual">Manual Watering</option>
                                <option value="auto">Automatic Watering</option>
                            </select>

                            <select id="wateringSchedule"
                                class="rounded-md border-gray-300 text-gray-600 focus:ring-green-500 focus:border-green-500 hidden">
                                <option value="none">No Schedule</option>
                                <option value="morning">Morning (6:00 AM)</option>
                                <option value="evening">Evening (6:00 PM)</option>
                                <option value="twice">Twice Daily (6:00 AM & 6:00 PM)</option>
                            </select>
                            <button id="saveSchedule" onclick="saveWateringSchedule()"
                                class="bg-blue-500 hover:bg-blue-600 text-white rounded-md p-2 pl-2 pr-2 hidden">
                                <i class="bi bi-save"></i> Save
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="mb-4">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" id="selectAll" class="rounded text-green-600">
                                <span class="text-sm text-green-600">Select All Plants</span>
                            </label>
                        </div>
                        <div id="plantCardsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Plant cards will be dynamically added here -->
                        </div>
                    </div>
                </div>

                <!-- Add Plant Modal -->
                <div id="addPlantModal"
                    class="fixed inset-0 backdrop-blur-[2px] bg-black/20 hidden items-center justify-center z-50">
                    <div class="bg-white/90 backdrop-blur-sm rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Add New Plant</h3>
                            <button type="button" id="modalCloseX" class="text-gray-400 hover:text-gray-500">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <form id="addPlantForm" class="space-y-4">
                            <!-- Add error message div -->
                            <div id="formError" class="hidden p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
                            </div>

                            <!-- Plant Name (Required) -->
                            <div>
                                <label for="plantName" class="block text-sm font-medium text-gray-700">
                                    Plant Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="plantName" name="plantName" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 focus:border-green-500 focus:ring-green-500"
                                    placeholder="Enter plant name">
                            </div>

                            <!-- Plant Type (Optional) -->
                            <div>
                                <label for="plantType" class="block text-sm font-medium text-gray-700">
                                    Plant Type <span class="text-gray-400">(optional)</span>
                                </label>
                                <input type="text" id="plantType" name="plantType"
                                    class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                    placeholder="e.g., Herbs, Vegetables, etc.">
                            </div>

                            <!-- Moisture Sensor Pin Selection -->
                            <div>
                                <label for="sensorPin" class="block text-sm font-medium text-gray-700">
                                    Moisture Sensor Pin <span class="text-red-500">*</span>
                                    <span class="text-xs text-gray-500 ml-1">(Hover over options for details)</span>
                                </label>
                                <select id="sensorPin" name="sensorPin" required
                                    class="mt-1 p-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 cursor-help">
                                    <option value="">Loading available pins...</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Only analog-capable GPIO pins are shown</p>
                            </div>

                            <!-- Buttons -->
                            <div class="flex justify-end gap-3 mt-6">
                                <button type="button" id="modalCancelBtn"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md">
                                    Save Plant
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-green-100">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-center items-center">
                <p class="text-sm text-green-600">&copy; <span id="current-year"></span> GrowEase. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
    document.getElementById("current-year").textContent = new Date().getFullYear();
    </script>
    <script src="../assets/js/main.js"></script>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal"
        class="fixed inset-0 backdrop-blur-[2px] bg-black/20 hidden items-center justify-center z-50">
        <div class="bg-white/90 backdrop-blur-sm rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
            <div class="text-center">
                <div class="mb-4">
                    <i class="bi bi-exclamation-triangle text-red-500 text-4xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Plant</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to delete this plant? This action cannot be undone.
                </p>
                <input type="hidden" id="deletePlantPin" value="">
                <div class="flex justify-center gap-3">
                    <button id="cancelDelete"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Cancel
                    </button>
                    <button id="confirmDelete"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">
                        Delete Plant
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- User Settings Modal -->
    <div id="userSettingsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-900">User Settings</h3>
                <button onclick="closeUserSettings()" class="text-gray-400 hover:text-gray-500">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form id="userSettingsForm" class="space-y-4">
                <!-- Profile Section -->
                <div class="space-y-4">
                    <h4 class="text-lg font-medium text-gray-900">Profile Information</h4>

                    <!-- Email (read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly
                            class="mt-1 block w-full rounded-md bg-gray-50 border-gray-300 shadow-sm">
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" name="username" id="username" placeholder="Enter username"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gender</label>
                        <select name="gender" id="gender"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <!-- Password Section -->
                <div class="border-t border-gray-200 pt-4 space-y-4">
                    <h4 class="text-lg font-medium text-gray-900">Change Password</h4>

                    <!-- Old Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input type="password" name="oldPassword" id="oldPassword" placeholder="Enter current password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" name="newPassword" id="newPassword" placeholder="Enter new password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeUserSettings()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md flex items-center gap-2">
                        <i class="bi bi-check2"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>