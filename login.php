<?php
session_start();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Database connection (adjust credentials as needed)
        $servername = 'localhost';
        $username = 'root';
        $db_password = ''; // Empty for default MySQL setup
        $dbname = 'classorbit'; // Assuming database name based on schema
        $conn = new mysqli($servername, $username, $db_password, $dbname);
        if ($conn->connect_error) {
            $error = 'Database connection failed: ' . $conn->connect_error;
        } else {
            // Check across user tables for the email
            $user = null;
            $role = '';
            // Check Student
            $stmt = $conn->prepare("SELECT id, name, password, dept, priority_id as priority FROM Student WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $user = $row;
                    $role = 'Student';
                }
            }
            // If not found, check Faculty
            if (!$user) {
                $stmt = $conn->prepare("SELECT id, name, password, dept, priority_id as priority FROM Faculty WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        $user = $row;
                        $role = 'Faculty';
                    }
                }
            }
            // If not found, check Club
            if (!$user) {
                $stmt = $conn->prepare("SELECT id, name, password, clubname as dept, priority_id as priority FROM Club WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        $user = $row;
                        $role = 'Club';
                    }
                }
            }
            // If not found, check Admin (assuming Admin has no dept/priority for simplicity)
            if (!$user) {
                $stmt = $conn->prepare("SELECT id, name, password FROM Admin WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        $user = $row;
                        $role = 'Admin';
                    }
                }
            }
            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
                if (isset($user['dept'])) {
                    $_SESSION['user_dept'] = $user['dept'];
                }
                if (isset($user['priority'])) {
                    $_SESSION['user_priority'] = $user['priority'];
                }
                // Handle remember me (basic cookie for 30 days)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                    // In production, store token in DB linked to user for security
                }
                // Redirect based on role
                if ($role === 'Admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ClassOrbit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #f1f5f9;
            line-height: 1.6;
            position: relative;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Baloo Da 2', cursive;
        }
        /* Full width SVG background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="%23334d5a" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>') repeat;
            opacity: 0.1;
            z-index: -1;
            width: 100vw;
            height: 100vh;
        }
        nav {
            background-color: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            padding: 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 3rem;
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .logoName {
            color: #f59e0b;
            font-size: 1.75rem;
            font-weight: bold;
            font-family: 'Baloo Da 2', cursive;
        }
        .orbitclass {
            color: #ffffff;
        }
        .others-section {
            display: flex;
            gap: 1rem;
            list-style: none;
        }
        .others-section a {
            color: #e2e8f0;
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 500;
        }
        .others-section a:hover,
        .others-section .active {
            color: #f59e0b;
           
        }
        .auth-buttons {
            display: flex;
            gap: 0.75rem;
        }
        .button {
            padding: 0.875rem 1.75rem;
            font-size: 1rem;
            border-radius: 6px;
            border: 1px solid #f59e0b;
            background-color: transparent;
            color: #f59e0b;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            text-decoration: none;
        }
        .button.primary {
            background-color: #f59e0b;
            color: #ffffff;
        }
        .button:hover {
            background-color: #f59e0b;
            color: #ffffff;
        }
        .button.primary:hover {
            background-color: #d97706;
        }
        .main-content {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 40px 60px;
            background: transparent;
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }
        .hero-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background-color: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            height: 500px;
            position: relative;
            z-index: 1;
        }
        .hero-left {
            flex: 1;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 3rem;
            text-align: left;
            color: #ffffff;
        }
        .hero-left i {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }
        .hero-left h2 {
            font-size: 2.25rem;
            margin-bottom: 1rem;
            font-weight: 600;
            line-height: 1.3;
            font-family: 'Baloo Da 2', cursive;
        }
        .hero-left p {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.95;
            max-width: 80%;
        }
        .hero-right {
            flex: 1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .hero-right h3 {
            font-size: 1.75rem;
            text-align: center;
            margin-bottom: 2rem;
            color: #f8fafc;
            font-weight: 600;
            font-family: 'Baloo Da 2', cursive;
        }
        .hero-right h3 span {
            background: linear-gradient(135deg, #3b82f6 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #f8fafc;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .form-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 1rem;
            border: 1px solid #475569;
            border-radius: 6px;
            background-color: #0f172a;
            color: #f1f5f9;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s ease;
        }
        .form-group input:focus {
            border-color: #3b82f6;
        }
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(40%);
            cursor: pointer;
            color: #64748b;
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }
        .password-toggle:hover {
            color: #f59e0b;
        }
        .form-group.checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            text-align: center;
        }
       
        .form-group.checkbox input[type="checkbox"] {
            width: auto;
            transform: scale(1.4);
        }
.form-group.checkbox label {
    transform: translateY(4px); /* Moves text slightly down */
}
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: #f59e0b;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px 0 rgba(221, 188, 2, 0.4);
        }
        .auth-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        .auth-links a {
            color: #f59e0b;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .auth-links a:hover {
            color: #d48b0dff;
        }
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: flex-end;
            align-items: flex-start;
            z-index: 2000;
            padding: 20px;
        }
        .modal-content {
            background-color: #1e293b;
            color: #f1f5f9;
            padding: 1.5rem;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
            border: 1px solid #334155;
            position: relative;
        }
        .modal-content h4 {
            margin-bottom: 1rem;
            color: #dc2626;
            font-family: 'Baloo Da 2', cursive;
        }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: #f1f5f9;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        .modal-close:hover {
            opacity: 1;
        }
        .modal-overlay.show {
            display: flex;
        }
        footer {
            background-color: #0f172a;
            padding: 3rem 40px 1rem;
            border-top: 1px solid #334155;
        }
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        .footer-section h3 {
            color: #f59e0b;
            margin-bottom: 1rem;
            font-family: 'Baloo Da 2', cursive;
        }
        .footer-section ul {
            list-style: none;
        }
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        .footer-section ul li a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer-section ul li a:hover {
            color: #f59e0b;
        }
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid #334155;
            color: #64748b;
        }
        @media (max-width: 768px) {
            .nav-container {
                padding: 1rem;
                flex-wrap: wrap;
            }
            .search-section {
                order: 3;
                width: 100%;
                margin: 1rem 0;
                max-width: none;
            }
            .auth-buttons {
                order: 4;
            }
            .main-content {
                padding: 140px 20px 40px;
                flex-direction: column;
            }
            .hero-container {
                flex-direction: column;
                height: auto;
                max-width: 400px;
            }
            .hero-left,
            .hero-right {
                padding: 2rem;
            }
            .hero-left {
                align-items: center;
                text-align: center;
            }
            .hero-left h2 {
                font-size: 1.875rem;
            }
            .hero-left p {
                max-width: 100%;
            }
            .hero-right h3 {
                font-size: 1.5rem;
            }
            .modal-overlay {
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .modal-content {
                border-radius: 20px;
                max-width: 90vw;
                margin: auto;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav-container">
            <div class="logo-section">
                <i class="fas fa-graduation-cap" style="color: #f59e0b; font-size: 1.75rem;"></i>
                <span class="logoName">Class<span class="orbitclass">Orbit</span></span>
            </div>
            <ul class="others-section">
                <li><a href="home.php">Home</a></li>
                <li><a href="home.php#services">Services</a></li>
                <li><a href="home.php#contact">Contact</a></li>
            </ul>
            <div class="auth-buttons">
                <button class="button primary">Login</button>
                <a href="signup.php" class="button">Sign Up</a>
            </div>
        </div>
    </nav>
    <main class="main-content">
        <div class="hero-container">
            <div class="hero-left">
                <i class="fas fa-graduation-cap"></i>
                <h2>Secure Access to Your Campus Hub</h2>
                <p>ClassOrbit provides an integrated platform for efficient class scheduling, resource management, and seamless collaboration across your institution. Access your personalized dashboard to manage your academic journey with precision and ease.</p>
            </div>
            <div class="hero-right">
                <h3>Sign In to<span> ClassOrbit</span></h3>
               
                <form action="login.php" method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-eye-slash password-toggle" onclick="togglePassword()"></i>
                    </div>
                    <div class="form-group checkbox">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <button type="submit" class="submit-btn">Sign In</button>
                </form>
                <div class="auth-links">
                    <p><a href="#">Forgot Password?</a></p>
                    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                </div>
            </div>
        </div>
    </main>
    <!-- Modal for Error Messages -->
    <div class="modal-overlay" id="errorModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h4 id="modalTitle">Error</h4>
            <p id="modalMessage"></p>
        </div>
    </div>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>ClassOrbit</h3>
                <p>Empowering campuses with smart scheduling solutions.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="home.php#services">Services</a></li>
                    <li><a href="home.php#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Support</h3>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Info</h3>
                <ul>
                    <li><a href="mailto:info@classorbit.com">info@classorbit.com</a></li>
                    <li><a href="tel:+1234567890">+1 (234) 567-890</a></li>
                    <li><i class="fab fa-twitter"></i> @ClassOrbit</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 ClassOrbit. All rights reserved.</p>
        </div>
    </footer>
    <script>
        // Password Toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
        // Modal Functions
        function showModal(title, message) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('errorModal').classList.add('show');
            setTimeout(closeModal, 2000);
        }
        function closeModal() {
            document.getElementById('errorModal').classList.remove('show');
        }
        // Show error modal if error exists
        <?php if (!empty($error)): ?>
            showModal('Login Error', '<?php echo htmlspecialchars($error); ?>');
        <?php endif; ?>
        // Close modal on overlay click
        document.getElementById('errorModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>