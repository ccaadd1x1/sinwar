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

            /* Dark background colors */
            --dark-bg-start: #0a0a0f;
            --dark-bg-end: #1a1a2a;
            
            /* Glassmorphism card colors */
            --card-bg-blur: rgba(25, 25, 35, 0.4); /* Slightly darker, more opaque for better text contrast */
            --card-border: rgba(255,255,255,0.1); /* Subtle border */
            --input-bg: rgba(30, 30, 45, 0.6); /* Input background */

            --light-text: #f0f0f0;
            --gray-text: #b0b0b0;
            --shadow-dark: 0 8px 30px rgba(0,0,0,0.8);
            --border-radius: 8px;

            /* Blockchain parallax colors */
            --blockchain-line: rgba(70, 200, 255, 0.08); /* Light blue for lines */
            --blockchain-glow: rgba(70, 200, 255, 0.3); /* For the "struck" effect */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--dark-bg-start), var(--dark-bg-end));
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--light-text);
            font-size: 16px;
            overflow: hidden;
            position: relative;
        }

        /* Parallax Background - Blockchain Style */
        .parallax-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            transform-origin: center center;
            transition: transform 0.05s ease-out; /* More sensitive parallax */
            pointer-events: none; /* Allow interaction with elements behind it */
            z-index: 1; /* Below login card */
            filter: brightness(0.8); /* Slightly dim the grid */
        }

        /* Struck effect overlay */
        .parallax-strike-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at var(--mouse-x) var(--mouse-y), var(--blockchain-glow) 0%, transparent 15%);
            opacity: 0;
            transition: opacity 0.3s ease-out;
            pointer-events: none;
            z-index: 2;
        }

        .parallax-strike-overlay.active {
            opacity: 1;
        }

        .login-container {
            background: var(--card-bg-blur);
            backdrop-filter: blur(15px) saturate(180%); /* Increased blur for stronger glass effect */
            -webkit-backdrop-filter: blur(15px) saturate(180%);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-dark);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            border: 1px solid var(--card-border);
            min-height: 500px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            z-index: 10; /* Above parallax */
        }

        .logo-container {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--card-border);
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

        /* Dynamic Welcome Message Styling - Live Color Change */
        #dynamicWelcome {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            min-height: 38px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            /* Text color managed by JavaScript for live changes */
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
            border: 1px solid var(--card-border);
            background-color: var(--input-bg);
            border-radius: 6px;
            font-size: 16px;
            color: var(--light-text); /* Ensure input text is visible */
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

        /* Keyframe animations for blinking */
        @keyframes blink-green {
            0%, 49% { background-color: var(--success); }
            50%, 100% { background-color: var(--primary); }
        }

        @keyframes blink-red {
            0%, 49% { background-color: var(--danger); }
            50%, 100% { background-color: var(--primary); }
        }

        .login-btn.blink-green {
            animation: blink-green 0.3s step-end infinite;
        }

        .login-btn.blink-red {
            animation: blink-red 0.3s step-end infinite;
        }

        /* Message box hidden */
        .message-box {
            display: none;
        }
    </style>
</head>
<body>
    <div class="parallax-bg" id="parallaxBg"></div>
    <div class="parallax-strike-overlay" id="strikeOverlay"></div>
    <div class="login-container">
        <div>
            <div class="logo-container">
                <span class="logo-text">Sinwar Reborn</span>
            </div>
            <h2 id="dynamicWelcome"></h2>
            <p>Sign In</p>
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
        const welcomeMessages = [
            "Oh, it’s you!", "Hey there, stranger!", "Look who’s back!", "Well, well, well…", "You again?",
            "Fancy seeing you here!", "Guess who!", "Back for more?", "Welcome aboard!", "Ah, a familiar face!",
            "Long time no see!", "Back in action!", "Hey, superstar!", "You made it!", "Ready to roll?",
            "Oh, it’s you again!", "Guess who’s back!", "Look who just showed up!", "Hey, rockstar!", "Back so soon?",
            "You’re here!", "Well, hello there!", "Ready for round two?", "Back in the game!", "Ah, my favorite human!",
            "And we meet again!", "Couldn’t stay away, huh?", "Long time, no type!", "You’ve returned!",
            "Your seat’s still warm!", "Welcome, legend!", "Hello, champion!", "It’s go time!", "There you are!",
            "Good to see you!", "Hey, you made it!", "Back for greatness!", "Glad you’re here!", "Welcome, hero!",
            "Let’s do this!"
        ];

        const loginForm = document.getElementById('loginForm');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const dynamicWelcome = document.getElementById('dynamicWelcome');
        const loginButton = document.querySelector('.login-btn');
        const parallaxBg = document.getElementById('parallaxBg');
        const strikeOverlay = document.getElementById('strikeOverlay');

        // Set a random welcome message on page load
        document.addEventListener('DOMContentLoaded', () => {
            const randomIndex = Math.floor(Math.random() * welcomeMessages.length);
            dynamicWelcome.textContent = welcomeMessages[randomIndex];
            startColorCycle(); // Start color cycling for the welcome message
        });

        // Parallax effect with "strike" and "come back"
        let strikeTimeout;
        document.addEventListener('mousemove', (e) => {
            const centerX = window.innerWidth / 2;
            const centerY = window.innerHeight / 2;
            // Adjusted divisors for more sensitive movement and "striking"
            const offsetX = (e.clientX - centerX) / 20; 
            const offsetY = (e.clientY - centerY) / 20;
            
            parallaxBg.style.transform = `translate(${offsetX}px, ${offsetY}px)`;

            // "Strike" effect
            strikeOverlay.style.setProperty('--mouse-x', `${e.clientX}px`);
            strikeOverlay.style.setProperty('--mouse-y', `${e.clientY}px`);
            strikeOverlay.classList.add('active');

            clearTimeout(strikeTimeout);
            strikeTimeout = setTimeout(() => {
                strikeOverlay.classList.remove('active');
            }, 100); // Overlay fades out after 100ms of no movement
        });

        // Live color changing for dynamic welcome text
        function startColorCycle() {
            let hue = 0;
            setInterval(() => {
                hue = (hue + 1) % 360;
                dynamicWelcome.style.color = `hsl(${hue}, 100%, 70%)`;
            }, 50); // Change color every 50ms
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = usernameInput.value.trim();
            const password = passwordInput.value.trim();

            if (!username || !password) {
                await blinkButton('red', 3);
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
                    await blinkButton('green', 3);
                    setTimeout(() => {
                        window.location.href = 'donaldtrump.php';
                    }, 500);
                } else {
                    await blinkButton('red', 3);
                }
            } catch (error) {
                console.error('Login error:', error);
                await blinkButton('red', 3);
            }
        });

        async function blinkButton(color, times) {
            return new Promise(resolve => {
                let count = 0;
                const originalBg = window.getComputedStyle(loginButton).backgroundColor;
                const blinkClass = `blink-${color}`;

                const interval = setInterval(() => {
                    if (count < times * 2) {
                        loginButton.classList.toggle(blinkClass);
                        count++;
                    } else {
                        clearInterval(interval);
                        loginButton.classList.remove(blinkClass);
                        loginButton.style.backgroundColor = '';
                        resolve();
                    }
                }, 300);
            });
        }
    </script>
</body>
</html>