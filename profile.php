<?php
session_start();

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

/* ================= SESSION DATA ================= */
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';
$user_role = $_SESSION['user_role'] ?? '';

/* ================= DB CONNECTION ================= */
$conn = new mysqli("localhost", "root", "", "classorbit");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$success_msg = '';
$error_msg = '';

/* ================= DETERMINE TABLE ================= */
if ($user_role === 'Admin') $table = 'admin';
elseif ($user_role === 'Student') $table = 'student';
elseif ($user_role === 'Faculty') $table = 'faculty';
elseif ($user_role === 'Club') $table = 'club';
else {
    die("Invalid user role.");
}

/* ================= UPDATE PROFILE PIC ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (empty($_FILES['profile_pic']['name'])) {
        $error_msg = "Please select a profile picture.";
    } else {
        $allowed = ['jpg', 'jpeg', 'png'];
        $file_name = $_FILES['profile_pic']['name'];
        $tmp = $_FILES['profile_pic']['tmp_name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error_msg = "Only JPG, JPEG, and PNG images are allowed.";
        } elseif (!getimagesize($tmp)) {
            $error_msg = "Invalid image file.";
        } else {
            $upload_dir = "uploads/users/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Avoid overwrite - append timestamp if file exists
            $new_file_name = $file_name;
            $counter = 1;
            while (file_exists($upload_dir . $new_file_name)) {
                $new_file_name = pathinfo($file_name, PATHINFO_FILENAME) . "_$counter." . $ext;
                $counter++;
            }

            if (move_uploaded_file($tmp, $upload_dir . $new_file_name)) {
                $stmt = $conn->prepare("UPDATE $table SET pic = ? WHERE id = ?");
                $stmt->bind_param("si", $new_file_name, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['profile_pic'] = $new_file_name;
                    $success_msg = "Profile picture updated successfully!";
                } else {
                    $error_msg = "Failed to update database.";
                    unlink($upload_dir . $new_file_name); // rollback upload
                }
                $stmt->close();
            } else {
                $error_msg = "Failed to upload image.";
            }
        }
    }
}

/* ================= FETCH USER DATA ================= */
$stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}

// Extract data safely
$user_pic_name = $user['pic'] ?? '';
$user_pic = !empty($user_pic_name) ? "uploads/users/" . $user_pic_name : "uploads/users/default-avatar.png"; // fallback

// For display name and department/club (adjust column names as per your DB schema)
$display_name = $user['name'] ?? $user_name;
$display_email = $user['email'] ?? $user_email;

// Common column names - adjust if different in your tables
$dept_or_club = '';
if ($user_role === 'Student' || $user_role === 'Faculty') {
    $dept_or_club = $user['dept'] ?? 'Not specified';
} elseif ($user_role === 'Club') {
    $dept_or_club = $user['club_name'] ?? $user['name'] ?? 'Not specified';
}

if (isset($_GET['updated']) || !empty($success_msg)) {
    $success_msg = "Profile picture updated successfully!";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | ClassOrbit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Da+2:wght@500;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #f59e0b;
            --primary-dark: #d97706;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-sidebar: #020617;
            --border: #334155;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
            color: var(--text-main);
            min-height: 100vh;
        }
        nav {
            position: fixed;
            top: 0; width: 100%; height: 75px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 5%; z-index: 1000;
        }
        .logo-section { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logoName { font-family: 'Baloo Da 2', cursive; font-size: 1.85rem; font-weight: 700; color: var(--primary); letter-spacing: -0.5px; }
        .logoName span { color: #fff; }
        .nav-actions { display: flex; align-items: center; gap: 1.5rem; }
        .menu-btn { font-size: 1.4rem; color: var(--primary); cursor: pointer; }

        .sidebar {
            position: fixed; right: -320px; top: 0; height: 100%; width: 300px;
            background: rgba(2, 6, 23, 0.95); backdrop-filter: blur(15px);
            border-left: 1px solid rgba(51, 65, 85, 0.5);
            padding: 100px 1.2rem 2rem; transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 999; box-shadow: -10px 0 30px rgba(0, 0, 0, 0.5);
        }
        .sidebar.active { right: 0; }
        .sidebar-link {
            display: flex; align-items: center; gap: 14px; padding: 12px 20px;
            color: var(--text-muted); text-decoration: none; border-radius: 10px;
            margin-bottom: 8px; transition: all 0.3s ease; font-weight: 500; font-size: 0.95rem;
            position: relative; overflow: hidden;
        }
        .sidebar-link:hover { color: #fff; background: rgba(245, 158, 11, 0.08); padding-left: 28px; }
        .sidebar-link.active { background: linear-gradient(90deg, rgba(245, 158, 11, 0.15) 0%, transparent 100%); color: white; font-weight: 600; padding-left: 28px; }
        .sidebar-link::before {
            content: ''; position: absolute; left: 0; top: 20%; bottom: 20%; width: 3px;
            background: var(--primary); border-radius: 0 4px 4px 0; transform: scaleY(0);
            transition: transform 0.3s cubic-bezier(0.65, 0, 0.35, 1);
        }
        .sidebar-link:hover::before, .sidebar-link.active::before { transform: scaleY(1); }

        main { padding: 120px 5% 60px; max-width: 1200px; margin: auto; }
        .profile-wrapper { display: grid; grid-template-columns: 320px 1fr; gap: 2rem; align-items: start; }
        .profile-card-static { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; position: sticky; top: 100px; }
        .profile-cover-color { height: 100px; background: linear-gradient(135deg, var(--primary-dark), #f59e0b, #fbbf24); }
        .profile-avatar-wrapper { margin-top: -50px; display: flex; flex-direction: column; align-items: center; padding: 0 1.5rem 2rem; text-align: center; }
        .profile-avatar-container { position: relative; width: 110px; height: 110px; margin-bottom: 1rem; }
        .profile-avatar-container img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg-card); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); }
        .edit-pic-btn { position: absolute; bottom: 5px; right: 5px; background: var(--primary); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; cursor: pointer; border: 2px solid var(--bg-card); transition: 0.3s; }
        .profile-name-title h2 { font-size: 1.4rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .profile-name-title p { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.2px; font-weight: 600; }

        .info-section { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border); padding: 2.5rem; }
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
        .detail-row { display: flex; padding: 1.25rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { width: 180px; color: var(--text-muted); font-size: 0.9rem; }
        .detail-value { flex: 1; color: var(--text-main); font-weight: 600; }
        .status-indicator { display: inline-flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .update-btn { background: var(--primary); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; width: 100%; margin-top: 1rem; }
        .update-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }

        .toast {
            position: fixed; top: 100px; right: 20px; background: #10b981; color: white;
            padding: 1rem 1.5rem; border-radius: 8px; transform: translateX(120%); opacity: 0;
            transition: 0.4s ease; z-index: 10000;
        }
        .toast.show { transform: translateX(0); opacity: 1; }

        @media (max-width: 992px) {
            .profile-wrapper { grid-template-columns: 1fr; }
            .profile-card-static { position: static; }
            .detail-row { flex-direction: column; gap: 0.5rem; }
            .detail-label { width: auto; }
        }
    </style>
</head>
<body>
    <nav>
        <a href="dashboard.php" class="logo-section">
            <i class="fas fa-graduation-cap" style="color: var(--primary); font-size: 1.7rem;"></i>
            <h1 class="logoName">Class<span>Orbit</span></h1>
        </a>
        <div class="nav-actions">
            <i class="fas fa-bars-staggered menu-btn" id="menuToggle"></i>
        </div>
    </nav>

    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-link"><i class="fas fa-house"></i> Dashboard</a>
        <a href="my_schedule.php" class="sidebar-link"><i class="fas fa-clipboard-list"></i> My Schedule</a>
        <a href="profile.php" class="sidebar-link active"><i class="fas fa-user-gear"></i> Account Settings</a>
        <div style="margin-top: 3rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
            <a href="?logout=1" class="sidebar-link" style="color: #ef4444;"><i class="fas fa-power-off"></i> Sign Out</a>
        </div>
    </aside>

    <?php if (!empty($success_msg)): ?>
        <div id="toast" class="toast"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div id="toast" class="toast" style="background: #ef4444;"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <main>
        <div style="margin-bottom: 2.5rem;">
            <h2 style="font-size: 2rem; font-family: 'Baloo Da 2'; font-weight: 700;">Account Settings</h2>
            <p style="color: var(--text-muted);">View your credentials and manage your public identity.</p>
        </div>

        <div class="profile-wrapper">
            <aside class="profile-card-static">
                <div class="profile-cover-color"></div>
                <div class="profile-avatar-wrapper">
                    <form id="avatarForm" method="POST" enctype="multipart/form-data">
                        <div class="profile-avatar-container">
                            <img src="<?= htmlspecialchars($user_pic) ?>" alt="Profile Picture" id="profileImg">
                            <label for="profilePicInput" class="edit-pic-btn">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profilePicInput" name="profile_pic" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                        </div>
                        <div class="profile-name-title">
                            <h2><?= htmlspecialchars($display_name) ?></h2>
                            <p><?= htmlspecialchars($user_role) ?></p>
                        </div>
                        <button type="submit" name="update_profile" class="update-btn">Save Photo</button>
                    </form>
                </div>
            </aside>

            <section class="info-section">
                <div class="section-header">
                    <h3><i class="fas fa-user-shield" style="color: var(--primary); margin-right: 12px;"></i> Personal Information</h3>
                    <div class="status-indicator"><i class="fas fa-check-circle"></i> Verified Profile</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Display Name</div>
                    <div class="detail-value"><?= htmlspecialchars($display_name) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value"><?= htmlspecialchars($display_email) ?></div>
                </div>

                <?php if ($user_role !== 'Admin'): ?>
                <div class="detail-row">
                    <div class="detail-label"><?= ($user_role == 'Club') ? 'Club Name' : 'Department' ?></div>
                    <div class="detail-value"><?= htmlspecialchars($dept_or_club) ?></div>
                </div>
                <?php endif; ?>

                <div style="margin-top: 3rem; padding: 1.5rem; background: rgba(245, 158, 11, 0.05); border-radius: 12px; border: 1px solid rgba(245, 158, 11, 0.2);">
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
                        <strong style="color: var(--primary); display: block; margin-bottom: 5px;">Note on Data Privacy</strong>
                        Your email and department data are managed by the institutional database. To request a change to your primary details, please submit a ticket to the IT support desk.
                    </p>
                </div>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const menuToggle = document.getElementById("menuToggle");
            const sidebar = document.getElementById("sidebar");
            menuToggle.onclick = () => {
                sidebar.classList.toggle("active");
                menuToggle.classList.toggle("fa-bars-staggered");
                menuToggle.classList.toggle("fa-xmark");
            };

            const profilePicInput = document.getElementById('profilePicInput');
            const profileImg = document.getElementById('profileImg');

            profilePicInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        profileImg.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Auto-show toast
            const toast = document.getElementById('toast');
            if (toast) {
                setTimeout(() => {
                    toast.classList.add('show');
                    setTimeout(() => {
                        toast.classList.remove('show');
                    }, 4000);
                }, 500);
            }
        });
    </script>
</body>
</html>