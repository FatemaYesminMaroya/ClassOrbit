<?php
session_start();
$error = '';

// Form processing variables
$name = $email = $role = $dept_or_club = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm-password'] ?? '';
    $role = $_POST['role'] ?? '';
    $dept_or_club = trim($_POST['dept_or_club'] ?? '');

   /* ---------- IMAGE UPLOAD LOGIC (FIXED, REQUIRED IMAGE, ORIGINAL NAME) ---------- */

if (empty($_FILES['profile_pic']['name'])) {
    $error = "Profile picture is required";
} else {
    $allowed = ['jpg', 'jpeg', 'png'];

    $originalName = basename($_FILES['profile_pic']['name']); // keep original name
    $tmpPath = $_FILES['profile_pic']['tmp_name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        $error = "Only JPG, JPEG, PNG images allowed";
    } 
    elseif (!getimagesize($tmpPath)) {
        $error = "Invalid image file";
    } 
    else {
        $uploadDir = "uploads/users/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Prevent overwrite (optional but safe)
        if (file_exists($uploadDir . $originalName)) {
            $error = "Image with same name already exists. Please rename your file.";
        } 
        else {
            if (move_uploaded_file($tmpPath, $uploadDir . $originalName)) {
                $db_filename = $originalName; // ORIGINAL filename saved
            } else {
                $error = "Failed to upload image.";
            }
        }
    }
}


    /* ---------- VALIDATION ---------- */
    if (!$error) {
        if (!$name || !$email || !$password || !$confirm || !$role || !$dept_or_club) {
            $error = "All fields are required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        }
    }

    /* ---------- DATABASE EXECUTION ---------- */
    if (!$error) {
        $conn = new mysqli("localhost", "root", "", "classorbit");
        if ($conn->connect_error) die("DB Error");

        $check = $conn->prepare("
            SELECT id FROM Student WHERE email=?
            UNION SELECT id FROM Faculty WHERE email=?
            UNION SELECT id FROM Club WHERE email=?
        ");
        $check->bind_param("sss", $email, $email, $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Email already registered";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            if ($role === "Student") {
                $q = $conn->prepare("INSERT INTO Student(name,email,password,pic,dept,priority_id) VALUES(?,?,?,?,?,3)");
                $q->bind_param("sssss", $name, $email, $hash, $db_filename, $dept_or_club);
            } elseif ($role === "Faculty") {
                $q = $conn->prepare("INSERT INTO Faculty(name,email,password,pic,dept,priority_id) VALUES(?,?,?,?,?,1)");
                $q->bind_param("sssss", $name, $email, $hash, $db_filename, $dept_or_club);
            } else {
                $q = $conn->prepare("INSERT INTO Club(name,email,password,pic,clubname,priority_id) VALUES(?,?,?,?,?,2)");
                $q->bind_param("sssss", $name, $email, $hash, $db_filename, $dept_or_club);
            }

            if ($q->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Signup failed";
            }
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | ClassOrbit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #020617;
            --bg-darker: #0f172a;
            --accent: #f59e0b;
            --accent-dark: #d97706;
            --border: #334155;
            --text: #fff;
            --error-bg: #fecaca;
            --error-text: #7f1d1d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Baloo Da 2', sans-serif;
            background: linear-gradient(135deg, var(--bg-dark), #1e293b);
            color: var(--text);
            min-height: 100vh;
        }

        .container { display: flex; min-height: 100vh; align-items: stretch; }

        .left {
            flex: 1;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #ffffff;
        }

        .left h1 { font-size: 2.4rem; font-weight: 700; margin-bottom: 1.5rem; }
        .left p { font-size: 1rem; line-height: 1.7; opacity: 0.95; }

        .right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
        }

        .right::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="%23334d5a" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>') repeat;
            opacity: 0.1;
            z-index: -1;
        }

        .form-box {
            width: 100%;
            max-width: 600px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid rgba(51, 65, 85, 0.5);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .form-box h2 { text-align: center; font-size: 1.5rem; margin-bottom: 1.8rem; font-weight: 600; }

        .pic-upload { text-align: center; margin-bottom: 1.2rem; cursor: pointer; }
        .pic-upload .upload-area {
            position: relative;
            display: inline-block;
            width: 80px; height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px dashed var(--border);
            background: var(--bg-darker);
            transition: all 0.3s ease;
        }

        .pic-upload .upload-area:hover { border-color: var(--accent); }
        .pic-upload img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

        .pic-upload .overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.3s ease;
        }

        .pic-upload .upload-area:hover .overlay { opacity: 1; }
        .pic-upload input[type="file"] { display: none; }
        .pic-upload .hint { margin-top: 0.8rem; font-size: 0.8rem; color: #94a3b8; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; }

        input, select {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: var(--bg-darker);
            color: var(--text);
            font-size: 0.95rem;
        }

        input:focus { outline: none; border-color: var(--accent); }

        button {
            width: 100%;
            padding: 1rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1.5rem;
            grid-column: 1 / -1;
        }

        .footer-text { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; grid-column: 1 / -1; }
        .footer-text a { color: var(--accent); text-decoration: none; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; position: absolute; top: 20px; right: 20px; padding: 20px; border-radius: 10px; width: 300px; text-align: center; color: var(--error-text); }
        .close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; }

        .input-wrapper { position: relative; }
        .toggle-password { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; }

        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .container { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left">
            <h1>Join ClassOrbit</h1>
            <p data-text="Smart classroom scheduling, seamless booking, and enhanced campus efficiency for students, faculty, and clubs.">Smart classroom scheduling, seamless booking, and enhanced campus efficiency for students, faculty, and clubs.</p>
        </div>
        <div class="right">
            <div class="form-box">
                <h2>Create Your Account</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="pic-upload">
                        <div class="upload-area">
                            <img id="preview" src="" alt="Profile preview">
                            <div class="overlay">
                                <i class="fas fa-camera"></i>
                                <span>Upload</span>
                            </div>
                        </div>
                        <input type="file" name="profile_pic" id="profile_pic" accept="image/*">
                        <div class="hint">Click to upload a profile picture</div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role" required>
                                <option value="">Select Role</option>
                                <option value="Student" <?= $role == 'Student' ? 'selected' : '' ?>>Student</option>
                                <option value="Faculty" <?= $role == 'Faculty' ? 'selected' : '' ?>>Faculty</option>
                                <option value="Club" <?= $role == 'Club' ? 'selected' : '' ?>>Club Representative</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Dept / Club Name</label>
                            <input type="text" name="dept_or_club" value="<?= htmlspecialchars($dept_or_club) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="input-wrapper">
                                <input type="password" name="password" id="p1" required>
                                <i class="fas fa-eye toggle-password" data-target="p1"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div class="input-wrapper">
                                <input type="password" name="confirm-password" id="p2" required>
                                <i class="fas fa-eye-slash toggle-password" data-target="p2"></i>
                            </div>
                        </div>
                        <button type="submit">Sign Up</button>
                    </div>
                </form>
                <p class="footer-text">Already have an account? <a href="login.php">Log in</a></p>
            </div>
        </div>
    </div>

    <div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p id="errorText"></p>
        </div>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const target = document.getElementById(toggle.dataset.target);
                const isPass = target.type === 'password';
                target.type = isPass ? 'text' : 'password';
                toggle.classList.toggle('fa-eye');
                toggle.classList.toggle('fa-eye-slash');
            });
        });

        document.querySelector('.upload-area').onclick = () => document.getElementById('profile_pic').click();
        document.getElementById('profile_pic').onchange = function() {
            if (this.files && this.files[0]) {
                document.getElementById('preview').src = URL.createObjectURL(this.files[0]);
            }
        };

        function typeWriter(el, text, speed = 50, callback) {
            let i = 0; el.innerHTML = '';
            function type() {
                if (i < text.length) {
                    el.innerHTML = text.substring(0, i + 1) + '|';
                    i++; setTimeout(type, speed);
                } else { el.innerHTML = text; if (callback) callback(); }
            }
            type();
        }
        
        document.addEventListener('DOMContentLoaded', () => {
            const p = document.querySelector('.left p');
            typeWriter(p, p.getAttribute('data-text'));
        });

        <?php if ($error): ?>
        const modal = document.getElementById('errorModal');
        document.getElementById('errorText').textContent = <?= json_encode($error) ?>;
        modal.style.display = 'block';
        document.querySelector('.close').onclick = () => modal.style.display = 'none';
        setTimeout(() => modal.style.display = 'none', 3000);
        <?php endif; ?>
    </script>
</body>
</html>