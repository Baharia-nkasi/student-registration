<?php
// ============================================================
//   STUDENT ENROLLMENT SYSTEM - UDOM THEME
//   Background Slideshow + Improved Dashboard + CRUD
//   Bilingual (English & Kiswahili)
// ============================================================

session_start();

$conn = mysqli_connect("localhost", "root", "", "student_db");
if (!$conn) die("Database connection failed / Muunganisho wa database umeshindikana.");

// ------------------- DELETE (FUTA) LOGIC -------------------
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $is_self = ($delete_id == $_SESSION['user_id']);
    
    $delete_sql = "DELETE FROM students WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $delete_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    
    if ($is_self) {
        session_destroy();
        header("Location: index.php?deleted=self");
    } else {
        header("Location: index.php?deleted=success");
    }
    exit;
}

// ------------------- REGISTER (CREATE) LOGIC -------------------
$reg_msg = '';
if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];

    // VALIDATION: Check empty fields
    if (empty($name) || empty($username) || empty($password) || empty($cpassword)) {
        $reg_msg = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Tafadhali jaza sehemu zote. / Please fill all fields.</div>';
    } 
    // VALIDATION: Check password length (at least 8 characters)
    elseif (strlen($password) < 8) {
        $reg_msg = '<div class="alert alert-error"><i class="fas fa-key"></i> Nenosiri lazima liwe na angalau herufi 8. / Password must be at least 8 characters.</div>';
    }
    // VALIDATION: Check if passwords match
    elseif ($password !== $cpassword) {
        $reg_msg = '<div class="alert alert-error"><i class="fas fa-times-circle"></i> Maneno ya siri hayafanani. / Passwords do not match.</div>';
    } else {
        // Check if username already exists
        $check_sql = "SELECT id FROM students WHERE username = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "s", $username);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $reg_msg = '<div class="alert alert-error"><i class="fas fa-user-slash"></i> Jina la mtumiaji tayari limechukuliwa. / Username already taken.</div>';
        } else {
            // Password hashing for security
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
            // Password verification
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
    
    if (empty($new_name) || empty($new_password)) {
        $update_msg = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Tafadhali jaza sehemu zote. / Please fill all fields.</div>';
    } elseif (strlen($new_password) < 8) {
        $update_msg = '<div class="alert alert-error"><i class="fas fa-key"></i> Nenosiri lazima liwe na angalau herufi 8. / Password must be at least 8 characters.</div>';
    } else {
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
    }
}

// ------------------- FETCH ALL STUDENTS (READ) -------------------
$all_students = [];
if (isset($_SESSION['user_id'])) {
    $fetch_sql = "SELECT id, name, username FROM students ORDER BY id DESC";
    $fetch_result = mysqli_query($conn, $fetch_sql);
    if ($fetch_result) {
        $all_students = mysqli_fetch_all($fetch_result, MYSQLI_ASSOC);
    }
}

$total_students = count($all_students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UDOM Enrollment | Usajili UDOM</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        /* ============================================================
           RESET & BODY WITH BACKGROUND SLIDESHOW
           ============================================================ */
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
            padding: 25px;
            overflow-x: hidden;
            position: relative;
            background: #1a1a2e;
        }

        /* Background Slideshow Container */
        .slideshow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .slideshow .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.8s ease-in-out;
            animation: slideFade 24s infinite;
        }

        /* Each slide has different animation delay */
        .slideshow .slide:nth-child(1) { animation-delay: 0s; }
        .slideshow .slide:nth-child(2) { animation-delay: 8s; }
        .slideshow .slide:nth-child(3) { animation-delay: 16s; }

        @keyframes slideFade {
            0% { opacity: 0; }
            8% { opacity: 1; }
            33% { opacity: 1; }
            41% { opacity: 0; }
            100% { opacity: 0; }
        }

        /* Overlay ya giza iliyo na rangi inayobadilika */
        .slideshow::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(4px);
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1100px;
        }

        /* ============================================================
           GLASS CARD - MAIN CONTAINER
           ============================================================ */
        .glass-card {
            background: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 40px;
            padding: 40px 35px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255,255,255,0.15);
            border: 1px solid rgba(255, 255, 255, 0.12);
            animation: fadeSlideUp 0.9s ease forwards;
        }
        @keyframes fadeSlideUp {
            0% { opacity: 0; transform: translateY(50px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================
           HEADER (Login/Register page)
           ============================================================ */
        .system-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .system-title h1 {
            font-size: 38px;
            font-weight: 800;
            color: #fff;
            text-shadow: 0 4px 25px rgba(0,0,0,0.4);
            letter-spacing: -0.5px;
        }
        .system-title h1 i {
            color: #fcd34d;
            margin-right: 12px;
        }
        .system-title .sub {
            font-size: 16px;
            color: rgba(255,255,255,0.85);
            font-weight: 300;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        /* ============================================================
           TABS
           ============================================================ */
        .tabs {
            display: flex;
            gap: 10px;
            background: rgba(255,255,255,0.10);
            border-radius: 60px;
            padding: 6px;
            margin-bottom: 30px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .tab-btn {
            flex: 1;
            padding: 14px 10px;
            border: none;
            background: transparent;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 17px;
            color: rgba(255,255,255,0.6);
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .tab-btn.active {
            background: rgba(255,255,255,0.20);
            color: #fff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            backdrop-filter: blur(5px);
        }
        .tab-btn:hover:not(.active) {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        /* ============================================================
           FORMS
           ============================================================ */
        .form-wrapper {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        .form-wrapper.active {
            display: block;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: scale(0.96) translateY(12px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        .form-title {
            font-size: 26px;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 2px 15px rgba(0,0,0,0.3);
        }
        .form-subtitle {
            font-size: 15px;
            color: rgba(255,255,255,0.75);
            margin-bottom: 25px;
        }

        /* ============================================================
           INPUT GROUPS
           ============================================================ */
        .input-group {
            position: relative;
            margin-bottom: 22px;
        }
        .input-group input {
            width: 100%;
            padding: 18px 22px;
            padding-right: 55px;
            border: 2px solid rgba(255,255,255,0.15);
            border-radius: 30px;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(5px);
            color: #fff;
            transition: all 0.3s ease;
            outline: none;
        }
        .input-group input::placeholder {
            color: rgba(255,255,255,0.5);
            font-weight: 300;
        }
        .input-group input:focus {
            border-color: #fcd34d;
            background: rgba(255,255,255,0.15);
            box-shadow: 0 0 0 5px rgba(252, 211, 77, 0.15);
        }
        .input-group .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.4);
            font-size: 20px;
            transition: color 0.3s;
        }
        .input-group input:focus ~ .input-icon {
            color: #fcd34d;
        }
        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.4);
            cursor: pointer;
            font-size: 20px;
            background: none;
            border: none;
            transition: color 0.3s;
        }
        .toggle-password:hover {
            color: #fff;
        }

        /* Password strength indicator */
        .password-hint {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            margin-top: 6px;
            padding-left: 10px;
        }
        .password-hint i {
            margin-right: 6px;
        }
        .password-hint.valid {
            color: #34d399;
        }
        .password-hint.invalid {
            color: #f87171;
        }

        /* ============================================================
           BUTTONS
           ============================================================ */
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #fcd34d, #f59e0b);
            border: none;
            border-radius: 60px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 18px;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px -5px rgba(252, 211, 77, 0.3);
            margin-top: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }
        .btn-submit:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 20px 45px -5px rgba(252, 211, 77, 0.5);
        }
        .btn-submit:active { transform: scale(0.97); }

        .btn-logout {
            background: linear-gradient(135deg, #f87171, #ef4444);
            box-shadow: 0 10px 30px -5px rgba(239, 68, 68, 0.3);
        }
        .btn-logout:hover {
            box-shadow: 0 20px 45px -5px rgba(239, 68, 68, 0.5);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.25);
            padding: 6px 18px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.3);
            color: #fff;
            transform: scale(1.05);
        }

        /* ============================================================
           ALERTS
           ============================================================ */
        .alert {
            padding: 14px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(10px);
            border-left: 6px solid;
            color: #fff;
            text-shadow: 0 1px 4px rgba(0,0,0,0.2);
        }
        .alert-success { border-color: #34d399; }
        .alert-error { border-color: #f87171; }

        /* ============================================================
           DASHBOARD STYLES (Logged in)
           ============================================================ */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(255,255,255,0.08);
        }
        .dashboard-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 2px 15px rgba(0,0,0,0.3);
        }
        .dashboard-header h2 i {
            color: #fcd34d;
            margin-right: 10px;
        }
        .user-badge {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            padding: 10px 25px;
            border-radius: 60px;
            color: #fff;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .user-badge i {
            color: #fcd34d;
            margin-right: 8px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(10px);
            padding: 22px 20px;
            border-radius: 25px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.06);
            transition: all 0.3s ease;
            color: #fff;
        }
        .stat-card:hover {
            transform: translateY(-8px);
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.15);
        }
        .stat-card .number {
            font-size: 38px;
            font-weight: 800;
            color: #fcd34d;
            text-shadow: 0 2px 20px rgba(252, 211, 77, 0.2);
        }
        .stat-card .label {
            font-size: 14px;
            opacity: 0.7;
            margin-top: 4px;
        }
        .stat-card i {
            font-size: 32px;
            display: block;
            margin-bottom: 10px;
            color: #fcd34d;
            opacity: 0.8;
        }

        /* Dashboard Grid (Profile + Update) */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 35px;
        }
        @media (max-width: 700px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        .dash-card {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(10px);
            padding: 28px 25px;
            border-radius: 25px;
            border: 1px solid rgba(255,255,255,0.06);
            color: #fff;
            transition: 0.3s;
        }
        .dash-card:hover {
            background: rgba(255,255,255,0.09);
        }
        .dash-card h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 18px;
            color: #fcd34d;
        }
        .dash-card h3 i {
            margin-right: 12px;
        }
        .profile-detail {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .profile-detail .label { opacity: 0.6; font-weight: 300; }
        .profile-detail .value { font-weight: 500; }

        /* Table (CRUD Read) */
        .table-section {
            margin-top: 10px;
        }
        .table-section h3 {
            color: #fcd34d;
            margin-bottom: 15px;
            font-size: 22px;
            font-weight: 600;
        }
        .table-section h3 i {
            margin-right: 12px;
        }

        .table-wrapper {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 5px 0;
            border: 1px solid rgba(255,255,255,0.05);
            overflow-x: auto;
        }
        .table-wrapper table {
            width: 100%;
            border-collapse: collapse;
            color: #fff;
            font-size: 15px;
        }
        .table-wrapper th {
            padding: 16px 18px;
            text-align: left;
            border-bottom: 2px solid rgba(255,255,255,0.08);
            font-weight: 600;
            color: #fcd34d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-wrapper td {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .table-wrapper tr:hover td {
            background: rgba(255,255,255,0.04);
        }
        .table-wrapper .text-center {
            text-align: center;
        }

        .table-footer {
            color: rgba(255,255,255,0.4);
            font-size: 13px;
            margin-top: 12px;
            text-align: center;
        }
        .table-footer i {
            margin-right: 6px;
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 480px) {
            .glass-card { padding: 25px 16px; }
            .tab-btn { font-size: 14px; padding: 10px; }
            .system-title h1 { font-size: 24px; }
            .table-wrapper td, .table-wrapper th { font-size: 13px; padding: 10px 12px; }
            .stat-card .number { font-size: 28px; }
            .dashboard-header h2 { font-size: 22px; }
            .user-badge { font-size: 13px; padding: 6px 16px; }
        }

        /* ============================================================
           SCROLLBAR STYLING (Optional)
           ============================================================ */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
    </style>
</head>
<body>

    <!-- ============================================================
    BACKGROUND SLIDESHOW - Beautiful images that match text colors
    ============================================================ -->
    <div class="slideshow">
        <!-- Slide 1: University / Education theme -->
        <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1523050854058-8df90110c7f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');"></div>
        <!-- Slide 2: Nature / Serene -->
        <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');"></div>
        <!-- Slide 3: Abstract / Modern -->
        <div class="slide" style="background-image: url('https://images.unsplash.com/photo-1541701494587-cb58502866ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');"></div>
    </div>

    <div class="container">

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- ============================================================
            DASHBOARD (LOGGED IN)
            ============================================================ -->
            <div class="glass-card">
                
                <!-- Delete success messages -->
                <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 'success'): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Mtumiaji amefutwa kikamilifu! / User deleted successfully!</div>
                <?php endif; ?>
                <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 'self'): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Akaunti yako imefutwa. / Your account has been deleted.</div>
                <?php endif; ?>

                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <h2><i class="fas fa-university"></i> UDOM Dashboard</h2>
                    <div class="user-badge">
                        <i class="fas fa-user-circle"></i> 
                        <?php echo htmlspecialchars($_SESSION['name']); ?> 
                        <span style="opacity:0.5; margin:0 6px;">|</span>
                        @<?php echo htmlspecialchars($_SESSION['username']); ?>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <div class="number"><?php echo $total_students; ?></div>
                        <div class="label">Jumla ya Wanafunzi / Total Students</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-user-check"></i>
                        <div class="number"><?php echo $total_students; ?></div>
                        <div class="label">Waliojiandikisha / Registered</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="number">2026</div>
                        <div class="label">Mwaka wa Masomo / Academic Year</div>
                    </div>
                </div>

                <!-- Profile + Update Grid -->
                <div class="dashboard-grid">
                    <!-- Left: Profile Card -->
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
                            <span class="value" style="color: #34d399;">
                                <i class="fas fa-circle" style="font-size: 10px;"></i> Active / Hai
                            </span>
                        </div>
                        <div style="margin-top: 25px;">
                            <a href="logout.php" class="btn-submit btn-logout" style="text-decoration:none; text-align:center;">
                                <i class="fas fa-sign-out-alt"></i> Toka / Logout
                            </a>
                        </div>
                    </div>

                    <!-- Right: Update Profile -->
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
                                <input type="password" id="dash-password" name="password" placeholder="Nenosiri lipya (angalau herufi 8) / New Password (min 8 chars)" required>
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

                <!-- CRUD: Read All Students (Table) -->
                <div class="table-section">
                    <h3><i class="fas fa-list"></i> Wanafunzi Wote / All Students</h3>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Jina / Name</th>
                                    <th>Jina la Mtumiaji / Username</th>
                                    <th class="text-center">Kitendo / Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($all_students) > 0): ?>
                                    <?php foreach ($all_students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['id']; ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                                        <td class="text-center">
                                            <a href="?delete_id=<?php echo $student['id']; ?>" 
                                               class="btn-delete" 
                                               onclick="return confirm('Je, una uhakika unataka kufuta mtumiaji huyu? / Are you sure you want to delete this user?');">
                                                <i class="fas fa-trash-alt"></i> Futa / Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 30px; opacity: 0.5;">
                                            <i class="fas fa-user-slash"></i> Hakuna wanafunzi waliosajiliwa bado. / No students registered yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">
                        <i class="fas fa-info-circle"></i> Bonyeza "Futa" ili kufuta akaunti. / Click "Delete" to remove an account.
                    </div>
                </div>

            </div>

        <?php else: ?>
            <!-- ============================================================
            LOGIN / REGISTER (NOT LOGGED IN)
            ============================================================ -->
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
                    <form method="POST" id="registerForm">
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
                            <input type="password" id="reg-pass" name="password" placeholder="Nenosiri (angalau herufi 8) / Password (min 8 chars)" required>
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
                        <!-- Password hint -->
                        <div id="passwordHint" class="password-hint">
                            <i class="fas fa-info-circle"></i> Nenosiri lazima liwe na angalau herufi 8. / Password must be at least 8 characters.
                        </div>
                        <button type="submit" name="register" class="btn-submit">
                            <i class="fas fa-user-plus"></i> Jisajili / Register
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- ============================================================
    JQUERY SCRIPT
    ============================================================ -->
    <script>
    $(document).ready(function() {

        // ---------- Toggle Password Visibility ----------
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

        // ---------- Tab Switching (only when not logged in) ----------
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

        // ---------- Auto-hide alerts after 6 seconds ----------
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 6000);

        // ---------- Input icon color change on focus ----------
        $('.input-group input').focus(function() {
            $(this).closest('.input-group').find('.input-icon').css('color', '#fcd34d');
        }).blur(function() {
            $(this).closest('.input-group').find('.input-icon').css('color', 'rgba(255,255,255,0.4)');
        });

        // ---------- Password Validation (Live) ----------
        $('#reg-pass, #reg-cpass').on('keyup', function() {
            var password = $('#reg-pass').val();
            var confirm = $('#reg-cpass').val();
            var hint = $('#passwordHint');
            
            if (password.length > 0 && password.length < 8) {
                hint.html('<i class="fas fa-times-circle"></i> Nenosiri ni fupi sana! Angalau herufi 8. / Password too short! At least 8 characters.');
                hint.removeClass('valid').addClass('invalid');
            } else if (password.length >= 8 && confirm.length > 0 && password !== confirm) {
                hint.html('<i class="fas fa-times-circle"></i> Maneno ya siri hayafanani. / Passwords do not match.');
                hint.removeClass('valid').addClass('invalid');
            } else if (password.length >= 8 && confirm.length > 0 && password === confirm) {
                hint.html('<i class="fas fa-check-circle"></i> Nenosiri linafaa! / Password is valid!');
                hint.removeClass('invalid').addClass('valid');
            } else if (password.length >= 8) {
                hint.html('<i class="fas fa-check-circle"></i> Urefu wa nenosiri ni sawa. / Password length is good.');
                hint.removeClass('invalid').addClass('valid');
            } else {
                hint.html('<i class="fas fa-info-circle"></i> Nenosiri lazima liwe na angalau herufi 8. / Password must be at least 8 characters.');
                hint.removeClass('valid invalid');
            }
        });

    });
    </script>

</body>
</html>