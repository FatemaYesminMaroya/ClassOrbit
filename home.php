<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClassOrbit - Smart Classroom Scheduling</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400;500;600;700&display=swap" rel="stylesheet">
   
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Baloo Da 2', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            line-height: 1.6;
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
            display: inline-block;
            text-align: center;
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

        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 100px 40px 60px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="%23334d5a" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>') repeat;
            opacity: 0.1;
        }

        .hero-content {
            width: 100%;
            max-width: 1400px;
            margin: auto;
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 3rem;
            position: relative;
            z-index: 1;
        }

        .hero-text {
            flex: 1;
            min-width: 500px;
        }

        .hero-text h1 {
            font-size: 4.5rem;
            line-height: 1.1;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 1.5rem;
        }

        .hero-text h1 span {
            background: linear-gradient(135deg, #3b82f6 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-text p {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            color: #cbd5e1;
            max-width: 600px;
        }

        .hero-buttons {
            display: flex;
            gap: 1.25rem;
            flex-wrap: nowrap;
        }

        .btn-primary,
        .btn-outline {
            text-decoration: none;
            padding: 1.125rem 2.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.125rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(59, 130, 246, 0.5);
        }

        .btn-outline {
            border: 2px solid #3b82f6;
            color: #3b82f6;
            background: transparent;
        }

        .btn-outline:hover {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .hero-image {
            flex: 1;
            display: flex;
            justify-content: center;
            min-width: 500px;
            animation: fadeInUp 1s ease-out;
        }

        .hero-image img {
            width: 100%;
            max-width: 500px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* New Sections Styles */
        .section {
            padding: 5rem 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section h2 {
            font-size: 3rem;
            text-align: center;
            margin-bottom: 3rem;
            color: #f8fafc;
        }

        .section h2 span {
            background: linear-gradient(135deg, #3b82f6 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .feature-card {
            background-color: #1e293b;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #334155;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
        }

        .feature-card i {
            font-size: 3rem;
            color: #f59e0b;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #f8fafc;
        }

        .feature-card p {
            color: #cbd5e1;
        }

        .testimonials {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .testimonial-card {
            background-color: #1e293b;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #334155;
        }

        .testimonial-card i {
            color: #f59e0b;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .testimonial-card p {
            font-style: italic;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        .testimonial-card h4 {
            color: #f8fafc;
            font-size: 1.1rem;
        }

        .contact-form {
            max-width: 600px;
            margin: 0 auto;
            background-color: #1e293b;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #334155;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #f8fafc;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #475569;
            border-radius: 6px;
            background-color: #0f172a;
            color: #f1f5f9;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #f59e0b;
            outline: none;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(59, 130, 246, 0.5);
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

        @media (max-width: 1200px) {
            .hero-content {
                flex-wrap: wrap;
                gap: 4rem;
            }

            .hero-text {
                min-width: auto;
            }

            .hero-image {
                min-width: auto;
            }
        }

        @media (max-width: 768px) {
            .nav-container {
                padding: 1rem;
                flex-wrap: wrap;
            }

            .auth-buttons {
                order: 4;
            }

            .hero {
                padding-top: 120px;
                text-align: center;
            }

            .hero-content {
                gap: 2rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-text h1 {
                font-size: 3rem;
            }

            .section {
                padding: 3rem 20px;
            }

            .section h2 {
                font-size: 2.5rem;
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
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="login.php" class="button">Login</a>
                <a href="signup.php" class="button primary">Sign Up</a>
            </div>
        </div>
    </nav>
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Smart Classroom Scheduling for <span>Smarter Campuses</span></h1>
                <p>
                    Streamline classroom management with intuitive scheduling, real-time availability tracking, and
                    automated booking. Empower your campus with efficiency and ease.
                </p>
                <div class="hero-buttons">
                    <a href="#get-started" class="btn-primary">
                        <i class="fas fa-rocket"></i> Get Started
                    </a>
                    <a href="#learn-more" class="btn-outline">
                        <i class="fas fa-play-circle"></i> Learn More
                    </a>
                </div>
            </div>
            <div class="hero-image"> <!-- 3D Model Viewer -->
                <model-viewer src="https://modelviewer.dev/shared-assets/models/RobotExpressive.glb" camera-controls
                    auto-rotate style="width: 500px; height: 500px;">
                </model-viewer>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="section" id="services">
        <h2>Our <span>Services</span></h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Real-Time Scheduling</h3>
                <p>Book classrooms instantly with live availability updates and conflict-free planning.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-chart-line"></i>
                <h3>Analytics & Reports</h3>
                <p>Track usage patterns and generate insights to optimize campus resources.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Secure Booking</h3>
                <p>Enterprise-grade security ensures your data and schedules remain protected.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-users"></i>
                <h3>Collaborative Tools</h3>
                <p>Share schedules with teams and integrate with calendars like Google and Outlook.</p>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section testimonials">
        <h2>What Our <span>Users Say</span></h2>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <i class="fas fa-quote-left"></i>
                <p>"ClassOrbit transformed our chaotic scheduling into a seamless experience. Highly recommended!"</p>
                <h4>Dr. Emily Carter, University Dean</h4>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-quote-left"></i>
                <p>"The real-time features saved us hours every week. It's a game-changer for admins."</p>
                <h4>Mike Johnson, IT Coordinator</h4>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-quote-left"></i>
                <p>"Easy to use and integrates perfectly with our existing systems."</p>
                <h4>Sarah Lee, Faculty Member</h4>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section" id="contact">
        <h2>Get In <span>Touch</span></h2>
        <form class="contact-form">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="submit-btn">Send Message</button>
        </form>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>ClassOrbit</h3>
                <p>Empowering campuses with smart scheduling solutions.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#contact">Contact</a></li>
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

    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
</body>

</html>