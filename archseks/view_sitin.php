<?php
session_start();
include('config.php');

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. Optimized Query: JOIN with users table to get ID Number and Sessions
$query = "SELECT r.id AS sit_id, u.id_number, CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
          r.purpose, r.lab, u.sessions, r.status, r.date, r.time_in, r.time_out
          FROM reservations r
          JOIN users u ON r.student_id = u.id_number
          ORDER BY r.id DESC"; 

$result = mysqli_query($conn, $query);

// Summary Stats for the Top Bar
$total_count = mysqli_num_rows($result);
$active_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as active FROM reservations WHERE status = 'Active'"))['active'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in History | CCS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <style>
        :root { 
            --ccs-purple: #4b2c82; 
            --ccs-purple-dark: #3a235c; 
            --ccs-gold: #ffcc00; 
            --bg-gray: #f4f7f6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-gray); color: #334155; line-height: 1.6; }
        
        /* Navbar */
        .professional-header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; height: 80px; }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { text-decoration: none; color: #64748b; font-weight: 500; font-size: 0.85rem; transition: 0.3s; }
        .nav-links a.active { color: var(--ccs-purple); font-weight: 600; }
        .btn-logout { background: var(--ccs-gold); padding: 8px 20px; border-radius: 40px; color: var(--ccs-purple-dark) !important; font-weight: 700 !important; }

        .container { max-width: 1400px; margin: 30px auto; padding: 0 40px; }

        /* Stats Bar */
        .records-stats { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat-mini-card { background: white; padding: 15px 25px; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; gap: 15px; border: 1px solid #e2e8f0; }
        .stat-mini-card i { font-size: 1.2rem; color: var(--ccs-purple); opacity: 0.7; }
        .stat-mini-card span { font-weight: 700; font-size: 1.1rem; }

        /* Table Card */
        .table-wrapper { 
            background: white; 
            border-radius: 20px; 
            padding: 25px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            border: 1px solid #e2e8f0; 
        }

        .table-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .table-header-flex h2 { font-size: 1.25rem; color: var(--ccs-purple); font-weight: 700; }

        /* Datatables Styling */
        #recordsTable { width: 100% !important; border-collapse: collapse; }
        #recordsTable thead th { background: #f8fafc; color: #64748b; font-size: 0.75rem; text-transform: uppercase; padding: 15px; text-align: left; border-bottom: 2px solid #e2e8f0; }
        #recordsTable tbody td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }

        /* Badges & Indicators */
        .status-badge { padding: 5px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
        .status-active { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
        .status-done { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
        
        .session-indicator { font-weight: 700; color: var(--ccs-purple); background: #f3f0ff; padding: 5px 12px; border-radius: 8px; border: 1px solid #dcd3ff; }

        .time-box { background: #fdfcf6; border: 1px solid #fef3c7; padding: 8px 12px; border-radius: 12px; font-size: 0.8rem; line-height: 1.5; }
        .time-box i { color: var(--ccs-purple); margin-right: 5px; }
        .id-text { font-family: 'Courier New', Courier, monospace; font-weight: 700; color: #475569; }
    </style>
</head>
<body>

<header class="professional-header">
    <div class="header-container">
        <div class="logo-area">
            <img src="uc.logo.png" alt="Logo" style="width: 45px;">
            <h1 style="font-size: 1.1rem; color: var(--ccs-purple);">College of Computer Studies <span style="font-weight: 400; color: #64748b;">| Admin</span></h1>
        </div>
        <div class="nav-links">
            <a href="admin_dashboard.php">Home</a>
            <a href="manage_students.php">Students</a>
            <a href="sit_in.php">Sit-in</a>
            <a href="view_sitin.php" class="active">View Records</a>
            <a href="feedback_report.php">Feedback Reports</a>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Logout Admin?')">Logout</a>
        </div>
    </div>
</header>

<div class="container">
    <div class="records-stats">
        <div class="stat-mini-card">
            <i class="fas fa-database"></i>
            <div>
                <p style="font-size: 0.75rem; color: #64748b;">Total History Logs</p>
                <span><?php echo $total_count; ?></span>
            </div>
        </div>
        <div class="stat-mini-card">
            <i class="fas fa-clock"></i>
            <div>
                <p style="font-size: 0.75rem; color: #64748b;">Active Now</p>
                <span style="color: #059669;"><?php echo $active_count; ?></span>
            </div>
        </div>
    </div>

    <div class="table-wrapper">
        <div class="table-header-flex">
            <h2><i class="fas fa-history" style="margin-right: 10px;"></i>Detailed Sit-in Records</h2>
        </div>

        <table id="recordsTable">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>ID Number</th>
                    <th>Full Name</th>
                    <th>Lab</th>
                    <th>Date</th>
                    <th>Log Times (In/Out)</th>
                    <th>Remaining Session</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $is_active = ($row['status'] == 'Active');
                ?>
                <tr>
                    <td style="font-weight: 700; color: #94a3b8;">#<?php echo $row['sit_id']; ?></td>
                    <td class="id-text"><?php echo htmlspecialchars($row['id_number']); ?></td>
                    <td style="font-weight: 600;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><b><?php echo htmlspecialchars($row['lab']); ?></b></td>
                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                    <td>
                        <div class="time-box">
                            <div><i class="far fa-clock"></i> In: <?php echo date('h:i A', strtotime($row['time_in'])); ?></div>
                            <?php if($row['time_out']): ?>
                                <div style="color: #64748b;"><i class="fas fa-sign-out-alt"></i> Out: <?php echo date('h:i A', strtotime($row['time_out'])); ?></div>
                            <?php else: ?>
                                <div style="color: #10b981;"><i class="fas fa-spinner fa-spin"></i> In Session</div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><span class="session-indicator"><?php echo $row['sessions']; ?></span></td>
                    <td>
                        <span class="status-badge <?php echo $is_active ? 'status-active' : 'status-done'; ?>">
                            <?php echo $is_active ? '● Active' : 'Logged Out'; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#recordsTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 10,
            "language": {
                "search": "",
                "searchPlaceholder": "Filter history...",
                "lengthMenu": "_MENU_ per page"
            }
        });

        $('.dataTables_filter input').css({
            'padding': '10px 15px',
            'border-radius': '10px',
            'border': '1px solid #e2e8f0',
            'outline': 'none',
            'margin-bottom': '15px'
        });
    });
</script>
</body>
</html>