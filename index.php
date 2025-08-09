<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/antibot/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinwar Reborn - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Audiowide&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --danger: #e74c3c;
            --danger-dark: #c0392b;
            --success: #2ecc71;
            --success-dark: #27ae60;
            --warning: #f39c12;
            --warning-dark: #e67e22;

            --panel-bg: #1e1e1e;
            --card-bg: #2b2b2b;
            --input-bg: #363636;
            --sidebar-bg-top: #2c3e50;
            --sidebar-bg-bottom: #1f2a38;

            --light-text: #f0f0f0;
            --gray-text: #b0b0b0;
            --border-color-dark: rgba(255,255,255,0.08);
            --shadow-dark: 0 8px 30px rgba(0,0,0,0.6);
            --border-radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background-color: var(--panel-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--light-text);
            font-size: 16px;
        }

        .login-container {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-dark);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 1px solid var(--border-color-dark);
            min-height: 500px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .logo-container {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color-dark);
        }

        .logo-text {
            font-family: 'Audiowide', cursive;
            font-size: 40px;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(52, 152, 219, 0.7);
            display: block;
            margin: 0 auto;
            max-width: 180px;
            line-height: 1.2;
        }

        .login-container h2 {
            font-size: 32px;
            font-weight: 700;
            color: var(--light-text);
            margin-bottom: 10px;
        }

        .login-container p {
            font-size: 15px;
            color: var(--gray-text);
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            color: var(--light-text);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color-dark);
            background-color: var(--input-bg);
            border-radius: 6px;
            font-size: 16px;
            color: var(--light-text);
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .input-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .input-group input::placeholder {
            color: var(--gray-text);
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 20px;
        }

        .login-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .message-box {
            display: none;
            padding: 15px;
            margin-top: 20px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            text-align: left;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .message-box.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .message-box.error {
            background-color: var(--danger-dark);
            color: var(--light-text);
            border: 1px solid var(--danger);
        }

        .message-box.success {
            background-color: var(--success-dark);
            color: var(--light-text);
            border: 1px solid var(--success);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div>
            <div class="logo-container">
                <span class="logo-text">Sinwar Reborn</span>
            </div>
            <h2>Welcome Back!</h2>
            <p>Sinwar Reborn.</p>
        </div>

        <form id="loginForm">
            <div class="input-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="input-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>

        <div id="messageBox" class="message-box"></div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const messageBox = document.getElementById('messageBox');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = usernameInput.value.trim();
            const password = passwordInput.value.trim();

            if (!username || !password) {
                showMessage('Please enter both username and password.', 'error');
                return;
            }

            try {
                const response = await fetch('auth1.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ username, password }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    showMessage('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'donaldtrump.php';
                    }, 1500);
                } else {
                    showMessage(data.message || 'Invalid username or password.', 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                showMessage('An error occurred during login. Please try again later.', 'error');
            }
        });

        function showMessage(message, type) {
            messageBox.textContent = message;
            messageBox.className = `message-box active ${type}`;
            setTimeout(() => {
                messageBox.classList.remove('active');
            }, 5000);
        }
    </script>
</body>
</html>