<?php
// ============================================================
//   STUDENT ENROLLMENT SYSTEM - UDOM THEME (IMPROVED READABILITY)
//   Bilingual (English & Kiswahili) + Smooth Animations
// ============================================================

session_start();

$conn = mysqli_connect("localhost", "root", "", "student_db");
if (!$conn) die("Database connection failed / Muunganisho wa database umeshindikana.");

// ------------------- REGISTER LOGIC -------------------
$reg_msg = '';
if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    if (empty($name) || empty($username) || empty($password) || empty($cpassword)) {
        $reg_msg = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Tafadhali jaza sehemu zote. / Please fill all fields.</div>';
    } elseif ($password !== $cpassword) {
        $reg_msg = '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Maneno ya siri hayafanani. / Passwords do not match.</div>';
    } else {
        $check_sql = "SELECT id FROM students WHERE username = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $reg_msg = '<div class="alert alert-error"><i class="fas fa-user-slash"></i> Jina la mtumiaji tayari limechukuliwa. / Username already taken.</div>';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO students (name, username, password) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "sss", $name, $username, $hashed);
            if (mysqli_stmt_execute($insert_stmt)) {
                $reg_msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Usajili umefanikiwa! Tafadhali ingia. / Registration successful! Please login.</div>';
            } else {
                $reg_msg = '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Usajili umeshindikana. Jaribu tena. / Registration failed. Try again.</div>';
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}

// ------------------- LOGIN LOGIC -------------------
$login_msg = '';
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    if (empty($username) || empty($password)) {
        $login_msg = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Tafadhali jaza jina na nenosiri. / Please fill username and password.</div>';
    } else {
        $sql = "SELECT id, name, password FROM students WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['name'] = $row['name'];
                header("Location: index.php");
                exit;
            } else {
                $login_msg = '<div class="alert alert-error"><i class="fas fa-key"></i> Nenosiri si sahihi. / Incorrect password.</div>';
            }
        } else {
            $login_msg = '<div class="alert alert-error"><i class="fas fa-user-slash"></i> Jina la mtumiaji halijapatikana. / Username not found.</div>';
        }
        mysqli_stmt_close($stmt);
    }
}

// ------------------- UPDATE PROFILE LOGIC -------------------
$update_msg = '';
if (isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    $new_password = $_POST['password'];
    $user_id = $_SESSION['user_id'];
    if (!empty($new_name) && !empty($new_password)) {
        $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE students SET name = ?, password = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ssi", $new_name, $hashed_new, $user_id);
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['name'] = $new_name;
            $update_msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Profaili imesasishwa! / Profile updated!</div>';
        } else {
            $update_msg = '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Sasisho limeshindikana. / Update failed.</div>';
        }
        mysqli_stmt_close($update_stmt);
    } else {
        $update_msg = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Tafadhali jaza sehemu zote. / Please fill all fields.</div>';
    }
}

// ------------------- TOTAL STUDENTS -------------------
$total_students = 0;
if (isset($_SESSION['user_id'])) {
    $count_sql = "SELECT COUNT(*) AS total FROM students";
    $count_result = mysqli_query($conn, $count_sql);
    if ($count_row = mysqli_fetch_assoc($count_result)) {
        $total_students = $count_row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UDOM Enrollment | Usajili UDOM</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        /* ===== RESET & BODY WITH GENTLE ANIMATION ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow-x: hidden;
            /* Background image with very slow, smooth zoom */
            background-image: url('https://images.unsplash.com/photo-1562774053-701939374585?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            animation: gentleZoom 40s ease-in-out infinite alternate;
        }

        /* Gentle, very slow zoom (less distracting) */
        @keyframes gentleZoom {
            0% {
                transform: scale(1);
            }
            100% {
                transform: scale(1.05);
            }
        }

        /* Overlay: Dark but not too strong, with a subtle gradient shift */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.65));
            z-index: 0;
            animation: overlayShift 20s ease-in-out infinite alternate;
        }
        @keyframes overlayShift {
            0% { opacity: 0.8; }
            100% { opacity: 1; }
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 950px;
        }

        /* ===== MAIN CARD - CLEAR GLASS WITH READABILITY ===== */
        .glass-card {
            background: rgba(255, 255, 255, 0.88); /* More opaque for better text contrast */
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 50px;
            padding: 40px 35px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s ease;
            animation: fadeSlideUp 0.8s ease forwards;
            color: #1e293b; /* Dark text for high contrast */
        }
        @keyframes fadeSlideUp {
            0% { opacity: 0; transform: translateY(40px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* ===== HEADER ===== */
        .system-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .system-title h1 {
            font-size: 36px;
            font-weight: 800;
            color: #1e293b;
            text-shadow: 0 2px 10px rgba(255,255,255,0.3);
            letter-spacing: -0.5px;
        }
        .system-title h1 i {
            color: #764ba2;
            margin-right: 12px;
        }
        .system-title .sub {
            font-size: 16px;
            color: #475569;
            font-weight: 400;
        }

        /* ===== TABS ===== */
        .tabs {
            display: flex;
            gap: 10px;
            background: #f1f5f9;
            border-radius: 60px;
            padding: 6px;
            margin-bottom: 30px;
        }
        .tab-btn {
            flex: 1;
            padding: 14px 10px;
            border: none;
            background: transparent;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 17px;
            color: #64748b;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .tab-btn:hover:not(.active) {
            background: rgba(0,0,0,0.05);
        }

        /* ===== FORMS ===== */
        .form-wrapper {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        .form-wrapper.active {
            display: block;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: scale(0.97); }
            100% { opacity: 1; transform: scale(1); }
        }

        .form-title {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
        }
        .form-subtitle {
            font-size: 15px;
            color: #64748b;
            margin-bottom: 25px;
        }

        /* ===== INPUT GROUPS - Clear and readable ===== */
        .input-group {
            position: relative;
            margin-bottom: 22px;
        }
        .input-group input {
            width: 100%;
            padding: 18px 22px;
            padding-right: 55px;
            border: 2px solid #e2e8f0;
            border-radius: 30px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            background: #f8fafc;
            color: #0f172a;
            transition: all 0.3s ease;
            outline: none;
        }
        .input-group input::placeholder {
            color: #94a3b8;
            font-weight: 300;
        }
        .input-group input:focus {
            border-color: #667eea;
            background: #ffffff;
            box-shadow: 0 0 0 5px rgba(102, 126, 234, 0.15);
        }
        .input-group .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 20px;
        }
        .input-group input:focus ~ .input-icon {
            color: #667eea;
        }
        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            font-size: 20px;
            background: none;
            border: none;
        }
        .toggle-password:hover {
            color: #475569;
        }

        /* ===== BUTTONS ===== */
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 60px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px -5px rgba(102, 126, 234, 0.4);
            margin-top: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -5px rgba(102, 126, 234, 0.6);
        }
        .btn-submit:active { transform: scale(0.97); }

        .btn-logout {
            background: linear-gradient(135deg, #f87171, #ef4444);
            box-shadow: 0 10px 30px -5px rgba(239, 68, 68, 0.4);
        }
        .btn-logout:hover {
            box-shadow: 0 20px 40px -5px rgba(239, 68, 68, 0.6);
        }

        /* ===== ALERTS - Highly readable ===== */
        .alert {
            padding: 14px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            border-left: 6px solid;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .alert-success { border-color: #10b981; color: #065f46; }
        .alert-error { border-color: #ef4444; color: #991b1b; }

        /* ============================================================
                   DASHBOARD STYLES (Logged in) - Clear & readable
                   ============================================================ */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        .dashboard-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }
        .dashboard-header h2 i {
            color: #764ba2;
        }
        .user-badge {
            background: #dbeafe;
            padding: 10px 25px;
            border-radius: 60px;
            color: #1e40af;
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: #f8fafc;
            padding: 20px;
            border-radius: 30px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: 0.3s;
            color: #1e293b;
        }
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .stat-card .number {
            font-size: 36px;
            font-weight: 800;
            color: #764ba2;
        }
        .stat-card .label {
            font-size: 14px;
            color: #64748b;
        }
        .stat-card i {
            font-size: 32px;
            display: block;
            margin-bottom: 8px;
            color: #667eea;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        @media (max-width: 700px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        .dash-card {
            background: #f8fafc;
            padding: 25px;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .dash-card h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 18px;
            color: #1e293b;
        }
        .dash-card h3 i {
            color: #764ba2;
            margin-right: 12px;
        }
        .profile-detail {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .profile-detail .label { color: #64748b; }
        .profile-detail .value { font-weight: 500; }

        /* Responsive */
        @media (max-width: 480px) {
            .glass-card { padding: 25px 18px; }
            .tab-btn { font-size: 14px; padding: 10px; }
            .system-title h1 { font-size: 26px; }
        }
    </style>
</head>
<body>

<div class="container">

    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- ===== DASHBOARD (LOGGED IN) ===== -->
        <div class="glass-card">
            <div class="dashboard-header">
                <h2><i class="fas fa-university"></i> UDOM Dashboard</h2>
                <div class="user-badge">
                    <i class="fas fa-user-circle"></i> 
                    <?php echo htmlspecialchars($_SESSION['name']); ?> 
                    (<?php echo htmlspecialchars($_SESSION['username']); ?>)
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="number"><?php echo $total_students; ?></div>
                    <div class="label">Jumla ya Wanafunzi / Total Students</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-check"></i>
                    <div class="number">1</div>
                    <div class="label">Waliowasilisha / Enrolled Today</div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="number">2026</div>
                    <div class="label">Mwaka wa Masomo / Academic Year</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dash-card">
                    <h3><i class="fas fa-id-card"></i> Profaili Yangu / My Profile</h3>
                    <div class="profile-detail">
                        <span class="label">Jina / Name</span>
                        <span class="value"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    </div>
                    <div class="profile-detail">
                        <span class="label">Jina la Mtumiaji / Username</span>
                        <span class="value"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                    <div class="profile-detail">
                        <span class="label">Hali / Status</span>
                        <span class="value" style="color: #10b981;"><i class="fas fa-circle" style="font-size: 10px;"></i> Active / Hai</span>
                    </div>
                    <div style="margin-top: 25px;">
                        <a href="logout.php" class="btn-submit btn-logout" style="text-decoration:none; text-align:center;">
                            <i class="fas fa-sign-out-alt"></i> Toka / Logout
                        </a>
                    </div>
                </div>

                <div class="dash-card">
                    <h3><i class="fas fa-edit"></i> Sasisha Profaili / Update Profile</h3>
                    <?php echo $update_msg; ?>
                    <form method="POST">
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="name" placeholder="Jina lipya / New Name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" required>
                        </div>
                        <div class="input-group">
                            <i class="fas fa-key input-icon"></i>
                            <input type="password" id="dash-password" name="password" placeholder="Nenosiri lipya / New Password" required>
                            <button type="button" class="toggle-password" data-target="dash-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <button type="submit" name="update_profile" class="btn-submit">
                            <i class="fas fa-save"></i> Sasisha / Update
                        </button>
                    </form>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- ===== LOGIN / REGISTER (NOT LOGGED IN) ===== -->
        <div class="glass-card">
            <div class="system-title">
                <h1><i class="fas fa-graduation-cap"></i> UDOM Enrollment</h1>
                <div class="sub">University of Dodoma | Student Registration System <br> Mfumo wa Usajili wa Wanafunzi</div>
            </div>

            <div class="tabs">
                <button class="tab-btn active" data-tab="login"><i class="fas fa-sign-in-alt"></i> Ingia / Login</button>
                <button class="tab-btn" data-tab="register"><i class="fas fa-user-plus"></i> Jisajili / Register</button>
            </div>

            <!-- Login Form -->
            <div id="login-form" class="form-wrapper active">
                <h2 class="form-title">Karibu Tena! / Welcome Back!</h2>
                <p class="form-subtitle">Ingiza taarifa zako. / Enter your credentials.</p>
                <?php echo $login_msg; ?>
                <form method="POST">
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" placeholder="Jina la Mtumiaji / Username" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="login-pass" name="password" placeholder="Nenosiri / Password" required>
                        <button type="button" class="toggle-password" data-target="login-pass">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <button type="submit" name="login" class="btn-submit">
                        <i class="fas fa-arrow-right-to-bracket"></i> Ingia / Login
                    </button>
                </form>
            </div>

            <!-- Register Form -->
            <div id="register-form" class="form-wrapper">
                <h2 class="form-title">Jiandikisha / Sign Up</h2>
                <p class="form-subtitle">Unda akaunti yako. / Create your account.</p>
                <?php echo $reg_msg; ?>
                <form method="POST">
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="name" placeholder="Jina Kamili / Full Name" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-user-tag input-icon"></i>
                        <input type="text" name="username" placeholder="Jina la Mtumiaji / Username" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-key input-icon"></i>
                        <input type="password" id="reg-pass" name="password" placeholder="Nenosiri / Password" required>
                        <button type="button" class="toggle-password" data-target="reg-pass">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-check-circle input-icon"></i>
                        <input type="password" id="reg-cpass" name="cpassword" placeholder="Thibitisha Nenosiri / Confirm Password" required>
                        <button type="button" class="toggle-password" data-target="reg-cpass">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <button type="submit" name="register" class="btn-submit">
                        <i class="fas fa-user-plus"></i> Jisajili / Register
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ===== JQUERY ===== -->
<script>
$(document).ready(function() {
    // Toggle password visibility
    $('.toggle-password').click(function() {
        var target = $('#' + $(this).data('target'));
        var icon = $(this).find('i');
        if (target.attr('type') === 'password') {
            target.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            target.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Tab switching (only when not logged in)
    $('.tab-btn').click(function() {
        $('.tab-btn').removeClass('active');
        $(this).addClass('active');
        var target = $(this).data('tab');
        $('.form-wrapper').removeClass('active');
        if (target === 'login') {
            $('#login-form').addClass('active');
        } else {
            $('#register-form').addClass('active');
        }
    });

    // Auto-hide alerts after 6s (gentle fade)
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 6000);

    // Input icon color change on focus
    $('.input-group input').focus(function() {
        $(this).closest('.input-group').find('.input-icon').css('color', '#667eea');
    }).blur(function() {
        $(this).closest('.input-group').find('.input-icon').css('color', '#94a3b8');
    });
});
</script>

</body>
</html>