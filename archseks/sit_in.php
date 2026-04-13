<?php
session_start();
include('config.php');

// 1. Admin Security
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sit-in Monitoring | CCS Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ccs-purple: #4b2c82; --ccs-purple-dark: #3a235c; --ccs-gold: #ffcc00; --bg-gray: #f4f7f6; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-gray); }

        /* Header UI */
        .professional-header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; height: 80px; }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { text-decoration: none; color: #64748b; font-weight: 500; font-size: 0.85rem; cursor: pointer; }
        .nav-links a.active { color: var(--ccs-purple); font-weight: 600; }
        .btn-logout { background: var(--ccs-gold); color: var(--ccs-purple-dark) !important; padding: 8px 20px !important; border-radius: 40px; font-weight: 700 !important; }

        /* Content UI */
        .container { max-width: 1300px; margin: 40px auto; padding: 0 40px; }
        .page-header { margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
        
        .table-card { background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: linear-gradient(135deg, var(--ccs-purple) 0%, var(--ccs-purple-dark) 100%); color: white; }
        th { padding: 18px 20px; text-align: left; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #1e293b; }

        /* Status & Badges */
        .session-count { font-weight: 800; color: var(--ccs-purple); background: #f0ebf8; padding: 4px 10px; border-radius: 8px; }
        .status-active { color: #166534; font-weight: 700; }
        .status-completed { color: #64748b; font-weight: 600; font-style: italic; }
    </style>
</head>
<body>

<header class="professional-header">
    <div class="header-container">
        <div class="logo-area">
            <img src="uc.logo.png" style="width:45px;">
            <h1 style="font-size: 1.1rem; color: var(--ccs-purple);">College of Computer Studies <span style="font-weight: 400; color: #64748b;">| Admin</span></h1>
        </div>
        <div class="nav-links">
            <a href="admin_dashboard.php">Home</a>
            <a href="admin_dashboard.php?openSearch=true"><i class="fas fa-search"></i> Search</a>
            <a href="manage_students.php">Students</a>
            <a href="sit_in.php" class="active">Sit-in</a>
            <a href="view_sitin.php">View Records</a>
            <a href="feedback_report.php">Feedback Reports</a> 
            <a href="logout.php" class="btn-logout" onclick="return confirm('Log out from Admin?')">Log out</a>
        </div>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <h2 style="color:var(--ccs-purple);">Today's Sit-in Monitoring</h2>
        <p style="color: #64748b; font-size: 0.9rem;">Real-time view of students currently in the laboratory.</p>
    </div>
    
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Sessions</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetches all student records for today (Active at the top)
                $query = "SELECT r.*, u.first_name, u.last_name, u.sessions 
                          FROM reservations r 
                          JOIN users u ON r.student_id = u.id_number 
                          WHERE r.date = CURRENT_DATE() 
                          ORDER BY FIELD(r.status, 'Active') DESC, r.id DESC";
                $result = mysqli_query($conn, $query);
                
                if(mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $is_active = ($row['status'] == 'Active');
                        echo "<tr>
                                <td style='font-weight:600;'>{$row['student_id']}</td>
                                <td>{$row['first_name']} {$row['last_name']}</td>
                                <td>{$row['purpose']}</td>
                                <td>Lab {$row['lab']}</td>
                                <td><span class='session-count'>{$row['sessions']}</span></td>
                                <td>";
                        
                        if($is_active) {
                            echo "<span class='status-active'><i class='fas fa-circle' style='font-size:8px;'></i> Active</span>";
                        } else {
                            echo "<span class='status-completed'><i class='fas fa-check-circle'></i> Logged Out</span>";
                        }

                        echo "</td><td>";

                        if($is_active) {
                            echo "<a href='logout_sitin.php?id={$row['id']}' 
                                     onclick=\"return confirm('Deduct session and log out student?')\" 
                                     style='color:#ef4444; font-weight:700; text-decoration:none;'>
                                     <i class='fas fa-sign-out-alt'></i> Log out
                                  </a>";
                        } else {
                            echo "<span style='color:#94a3b8; font-size:0.8rem;'>Done</span>";
                        }

                        echo "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center; padding:30px; color:#64748b;'>No sit-in activities recorded today.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>