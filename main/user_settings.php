<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once "../config/database.php";

// Create database connection and get user data
$database = new Database();
$db = $database->getConnection();

// Get user data
$stmt = $db->prepare("SELECT username, gender FROM tbl_users WHERE id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings - GrowEase</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Load Ably script first -->
    <script src="https://cdn.jsdelivr.net/npm/ably@1.2.25/browser/static/ably.min.js"></script>

    <!-- <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize Ably and channel in the global scope
        window.ably = new Ably.Realtime("Pv6ihg.dTVV5g:WvuHeDy0GRFUyeczjP7yn5iVbNygMVBhq0p20dr1jho");
        window.channel = window.ably.channels.get("esp32");
    });

    // Define sendResetCommandToAbly in the global scope
    window.sendResetCommandToAbly = function() {
        const message = {
            name: "device_control",
            data: {
                command: "reset_device",
            },
        };

        // Publish message to Ably channel
        window.channel.publish("message", message, (err) => {
            if (err) {
                console.error("Failed to send reset command to ESP32:", err);
            }
            console.log("📢 Reset command sent successfully to ESP32:", message);
            Swal.fire({
                title: 'Success!',
                text: 'Reset command sent successfully to device.',
                icon: 'success'
            });
        });
    };

    // Define sendRestartCommandToAbly in the global scope
    window.sendRestartCommandToAbly = function() {
        const message = {
            name: "device_control",
            data: {
                command: "restart_device",
            },
        };

        // Publish message to Ably channel
        window.channel.publish("message", message, (err) => {
            if (err) {
                console.error("Failed to send restart command to ESP32:", err);
            }
            console.log("📢 Restart command sent successfully to ESP32:", message);
            Swal.fire({
                title: 'Success!',
                text: 'Restart command sent successfully to device.',
                icon: 'success'
            });
        });
    };
    </script> -->
</head>

<body class="bg-green-50 min-h-screen">
    <!-- Header -->
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
                                echo isset($user['username']) && !empty($user['username']) 
                                     ? htmlspecialchars($user['username']) 
                                     : htmlspecialchars($_SESSION['email']); 
                            ?>
                        </li>
                        <li>
                            <a href="user_dashboard.php" class="text-white hover:text-green-100">
                                <i class="bi bi-arrow-left-circle-fill text-xl"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 border border-green-100">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">User Settings</h2>

                <form id="userSettingsForm" class="space-y-6">
                    <!-- Profile Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 pb-2 border-b border-gray-200">
                            Profile Information
                        </h3>

                        <!-- Email (read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" id="username" name="username"
                                value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                placeholder="Enter username">
                        </div>

                        <!-- Gender -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select id="gender" name="gender"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                                <option value="">Select gender</option>
                                <option value="male" <?php echo ($user['gender'] === 'male') ? 'selected' : ''; ?>>Male
                                </option>
                                <option value="female" <?php echo ($user['gender'] === 'female') ? 'selected' : ''; ?>>
                                    Female</option>
                                <option value="other" <?php echo ($user['gender'] === 'other') ? 'selected' : ''; ?>>
                                    Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 pb-2 border-b border-gray-200">
                            Change Password
                        </h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                            <div class="relative">
                                <input type="password" id="oldPassword" name="oldPassword"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                    placeholder="Enter current password">
                                <i class="bi bi-eye-slash absolute right-3 top-3 cursor-pointer"
                                    id="toggleOldPassword"></i>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <div class="relative">
                                <input type="password" id="newPassword" name="newPassword"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                    placeholder="Enter new password">
                                <i class="bi bi-eye-slash absolute right-3 top-3 cursor-pointer"
                                    id="toggleNewPassword"></i>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <div class="relative">
                                <input type="password" id="confirmPassword" name="confirmPassword"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500"
                                    placeholder="Confirm new password">
                                <i class="bi bi-eye-slash absolute right-3 top-3 cursor-pointer"
                                    id="toggleConfirmPassword"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end pt-4">
                        <button type="submit"
                            class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2">
                            <i class="bi bi-check2"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
    document.getElementById('userSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = {
            username: document.getElementById('username').value,
            gender: document.getElementById('gender').value,
            oldPassword: document.getElementById('oldPassword').value,
            newPassword: document.getElementById('newPassword').value,
            confirmPassword: document.getElementById('confirmPassword').value
        };

        fetch('../api/update_user_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Settings updated successfully',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Clear password fields after successful update
                        document.getElementById('oldPassword').value = '';
                        document.getElementById('newPassword').value = '';
                        document.getElementById('confirmPassword').value = '';
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to update settings',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred. Please try again.',
                    icon: 'error'
                });
            });
    });

    // Toggle password visibility
    function togglePasswordVisibility(inputId, toggleIconId) {
        const input = document.getElementById(inputId);
        const toggleIcon = document.getElementById(toggleIconId);
        if (input.type === 'password') {
            input.type = 'text';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        } else {
            input.type = 'password';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        }
    }

    document.getElementById('toggleOldPassword').addEventListener('click', function() {
        togglePasswordVisibility('oldPassword', 'toggleOldPassword');
    });

    document.getElementById('toggleNewPassword').addEventListener('click', function() {
        togglePasswordVisibility('newPassword', 'toggleNewPassword');
    });

    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
        togglePasswordVisibility('confirmPassword', 'toggleConfirmPassword');
    });
    </script>
</body>

</html>