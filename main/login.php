<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: user_dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GrowEase</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-green-50 min-h-screen">
    <header>
        <div class="w-full bg-green-600 p-8">
            <div class="max-w-7xl mx-auto flex justify-between items-center px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <img src="../assets/images/grass-logo.svg" alt="GrowEase" class="w-8 h-8">
                    <h1 class="text-white text-xl sm:text-2xl font-bold">GrowEase</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 border border-green-100">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login</h2>

                <form id="loginForm" class="space-y-4">
                    <!-- Error message container -->
                    <div id="errorMessage" class="hidden p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50"></div>

                    <!-- Identifier input -->
                    <div class="relative">
                        <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1">
                            Email or Username
                        </label>
                        <div class="flex items-center">
                            <i class="bi bi-envelope-fill text-gray-400 mr-2"></i>
                            <input type="text" id="identifier" name="identifier" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                placeholder="Enter your email or username">
                        </div>
                    </div>

                    <!-- Password input -->
                    <div class="relative">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <div class="flex items-center">
                            <i class="bi bi-lock-fill text-gray-400 mr-2"></i>
                            <input type="password" id="password" name="password" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 pr-10"
                                placeholder="Enter your password">
                            <i class="bi bi-eye-slash cursor-pointer text-gray-400 absolute right-3 top-1/8 transform -translate-y-1/8"
                                id="togglePassword"></i>
                        </div>
                    </div>

                    <!-- Submit button -->
                    <button type="submit"
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors flex items-center justify-center gap-2">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Login
                    </button>
                </form>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-green-100 fixed bottom-0 w-full">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-center items-center">
                <p class="text-sm text-green-600">&copy; <span id="current-year"></span> GrowEase. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
    document.getElementById('current-year').textContent = new Date().getFullYear();

    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });

    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = {
            identifier: document.getElementById('identifier').value,
            password: document.getElementById('password').value
        };

        fetch('../api/login.php', {
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
                        icon: 'success',
                        title: 'Login Successful',
                        text: 'Redirecting to your dashboard...',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = 'user_dashboard.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: data.message || 'Invalid credentials, please try again.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'An error occurred',
                    text: 'Please try again later.'
                });
            });
    });
    </script>
</body>

</html>