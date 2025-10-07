<?php
session_start();
require_once 'config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$programs = [];
$res = $conn->query("SELECT program_name FROM programs");
while ($row = $res->fetch_assoc()) {
    $programs[] = $row['program_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty and Staff Evaluation Portal - The College of Maasin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(120deg, #467C4F 0%, #5a8c68 60%, #2e4d34 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            position: relative;
            z-index: 2;
            background: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px rgba(70,124,79,0.10), 0 1.5px 8px rgba(70,124,79,0.08);
            padding: 2.5rem 2rem 2rem 2rem;
            width: 100%;
            max-width: 370px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: #467C4F;
            text-align: center;
            margin-bottom: 0.25rem;
        }
        .login-subtitle {
            color: #5a8c68;
            text-align: center;
            font-size: 1.05rem;
            font-weight: 400;
            margin-bottom: 1.5rem;
        }
        .login-form label {
            font-size: 0.98rem;
            color: #2e4d34;
            font-weight: 600;
            margin-bottom: 0.25rem;
            display: block;
        }
        .login-form input,
        .login-form select {
            width: 100%;
            box-sizing: border-box;
            padding: 0.85rem 1rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 0.75rem;
            font-size: 1rem;
            background: #f8fafb;
            color: #222;
            margin-bottom: 1.1rem;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .login-form input:focus,
        .login-form select:focus {
            border-color: #467C4F;
            outline: none;
            box-shadow: 0 0 0 2px #467c4f22;
        }
        .login-btn {
            width: 100%;
            padding: 0.9rem 0;
            background: linear-gradient(90deg, #467C4F 0%, #5a8c68 100%);
            color: #fff;
            font-size: 1.08rem;
            font-weight: 700;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(70,124,79,0.10);
            transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
        }
        .login-btn:hover,
        .login-btn:focus {
            background: linear-gradient(90deg, #3d6c45 0%, #467C4F 100%);
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 24px -6px rgba(70,124,79,0.18);
        }
        .error-message {
            color: #d32f2f;
            background: #ffeaea;
            border: 1px solid #f8bcbc;
            border-radius: 0.5rem;
            padding: 0.7rem 1rem;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 1rem;
        }
        .login-footer {
            text-align: center;
            font-size: 0.93rem;
            color: #6b9f7a;
            margin-top: 1.5rem;
        }
        .login-footer a {
            color: #467C4F;
            text-decoration: underline;
            transition: color 0.2s;
        }
        .login-footer a:hover {
            color: #5a8c68;
        }
        @media (max-width: 600px) {
            .login-container {
                padding: 1.2rem 0.5rem 1.2rem 0.5rem;
                max-width: 98vw;
            }
            .login-title {
                font-size: 1.3rem;
            }
            .login-subtitle {
                font-size: 0.98rem;
            }
            .login-btn {
                font-size: 1rem;
                padding: 0.8rem 0;
            }
        }

        /* Keyframes for bubble animation */
        @keyframes bubble-animation {
            0% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-20px) scale(1.1);
            }
            100% {
                transform: translateY(0) scale(1);
            }
        }

        /* Bubble styles */
        .bubble {
            position: fixed;
            border-radius: 50%;
            opacity: 0.18;
            z-index: 1;
            pointer-events: none;
            animation-timing-function: ease-in-out;
            animation-iteration-count: infinite;
        }
        .bubble1 {
            width: 70px; height: 70px;
            left: 12vw; bottom: 10vh;
            background: #b7e4c7;
            animation: float1 8s infinite alternate;
        }
        .bubble2 {
            width: 50px; height: 50px;
            left: 80vw; top: 12vh;
            background: #74c69d;
            animation: float2 10s infinite alternate;
        }
        .bubble3 {
            width: 90px; height: 90px;
            left: 60vw; bottom: 8vh;
            background: #52b788;
            animation: float3 12s infinite alternate;
        }
        .bubble4 {
            width: 40px; height: 40px;
            left: 30vw; top: 18vh;
            background: #d8f3dc;
            animation: float4 9s infinite alternate;
        }
        .bubble5 {
            width: 60px; height: 60px;
            left: 70vw; top: 70vh;
            background: #b7e4c7;
            animation: float5 11s infinite alternate;
        }

        @keyframes float1 {
            0% { transform: translateY(0) scale(1);}
            100% { transform: translateY(-40px) scale(1.08);}
        }
        @keyframes float2 {
            0% { transform: translateY(0) scale(1);}
            100% { transform: translateY(30px) scale(0.95);}
        }
        @keyframes float3 {
            0% { transform: translateY(0) scale(1);}
            100% { transform: translateY(-60px) scale(1.12);}
        }
        @keyframes float4 {
            0% { transform: translateY(0) scale(1);}
            100% { transform: translateY(25px) scale(1.05);}
        }
        @keyframes float5 {
            0% { transform: translateY(0) scale(1);}
            100% { transform: translateY(-35px) scale(1.1);}
        }
    </style>
</head>
<body>
    <!-- Decorative SVG bubbles background -->
    <div style="position:fixed; inset:0; z-index:0; pointer-events:none; overflow:hidden;">
        <svg width="100vw" height="100vh" viewBox="0 0 1600 900" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100vw;height:100vh;position:absolute;top:0;left:0;">
            <circle cx="300" cy="180" r="80" fill="#b7e4c7" fill-opacity="0.22"/>
            <circle cx="1400" cy="150" r="60" fill="#74c69d" fill-opacity="0.18"/>
            <circle cx="1200" cy="700" r="90" fill="#52b788" fill-opacity="0.13"/>
            <circle cx="400" cy="700" r="60" fill="#d8f3dc" fill-opacity="0.16"/>
            <circle cx="800" cy="450" r="40" fill="#b7e4c7" fill-opacity="0.11"/>
            <circle cx="1000" cy="200" r="30" fill="#74c69d" fill-opacity="0.13"/>
            <circle cx="200" cy="800" r="50" fill="#b7e4c7" fill-opacity="0.10"/>
        </svg>
    </div>
    <!-- Animated floating bubbles -->
    <div class="bubble bubble1"></div>
    <div class="bubble bubble2"></div>
    <div class="bubble bubble3"></div>
    <div class="bubble bubble4"></div>
    <div class="bubble bubble5"></div>
    <div class="login-container">
        <div style="display:flex;justify-content:center;align-items:center;margin-bottom:1rem;">
            <img src="SCHOOL LOGO.png" alt="School Logo" style="height:70px;width:auto;object-fit:contain;box-shadow:0 2px 12px #467c4f22;border-radius:1rem;background:#f8fafb;padding:0.5rem;">
        </div>
        <div>
            <div class="login-title">The College of Maasin</div>
            <div class="login-subtitle">Faculty & Staff Evaluation Portal</div>
        </div>
        <form id="loginForm" class="login-form" autocomplete="off" action="login_handler.php" method="POST">
            <label for="username">Username / User ID</label>
            <input type="text" id="username" name="username" placeholder="Enter your username or user id" required autocomplete="off" />

            <label for="password">Password</label>
            <div style="position:relative;">
                <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="off" style="padding-right:2.5rem;">
                <i id="togglePassword" class="fa-solid fa-eye" style="position:absolute;right:1rem;top:40%;transform:translateY(-50%);cursor:pointer;color:#467C4F;font-size:1.2rem;"></i>
            </div>
            <button type="submit" class="login-btn">Login</button>
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>
        </form>
        <div class="login-footer">
            © 2025 Faculty & Staff Evaluation System<br>
            <span>Need help? <a href="https://cm.edu.ph/" target="_blank">Contact IT Support</a></span>
        </div>
    </div>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>