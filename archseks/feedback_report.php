<?php
session_start();
include('config.php');

// 1. Security: Only allow Admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 2. Data Fetching Logic for Statistics
$stats_query = mysqli_query($conn, "SELECT 
    AVG(rating) as avg_rating, 
    COUNT(*) as total,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as s5,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as s4,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as s3,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as s2,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as s1
    FROM reservations WHERE rating > 0");
$s = mysqli_fetch_assoc($stats_query);

$total = $s['total'] > 0 ? $s['total'] : 1; // Prevent division by zero

// 3. Fetch Detailed Feedback with Student Info
$feed_query = mysqli_query($conn, "SELECT r.*, u.first_name, u.last_name, u.profile_pic 
    FROM reservations r 
    LEFT JOIN users u ON r.student_id = u.id_number 
    WHERE r.rating > 0 
    ORDER BY r.date DESC, r.time_out DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Reports | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --ccs-purple: #4b2c82; 
            --ccs-purple-dark: #3a235c; 
            --ccs-gold: #ffcc00; 
            --text-dark: #1e293b; 
            --text-muted: #64748b; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; color: var(--text-dark); }

        /* --- DASHBOARD HEADER --- */
        .professional-header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; height: 80px; }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 0.85rem; cursor: pointer; }
        .nav-links a.active { color: var(--ccs-purple); font-weight: 600; }
        .btn-logout { background: var(--ccs-gold); color: var(--ccs-purple-dark) !important; padding: 8px 20px !important; border-radius: 40px; font-weight: 700 !important; }

        /* --- DASHBOARD LAYOUT --- */
        .dashboard-container { max-width: 1400px; margin: 40px auto; padding: 0 40px; }
        .stats-row { display: flex; gap: 24px; margin-bottom: 32px; align-items: stretch; }
        .stat-card { flex: 1; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .stat-header { background: linear-gradient(135deg, var(--ccs-purple) 0%, var(--ccs-purple-dark) 100%); color: white; padding: 15px; text-align: center; font-weight: 600; font-size: 0.9rem; }
        .stat-body { padding: 25px; text-align: center; display: flex; flex-direction: column; justify-content: center; height: 100%; }
        .stat-number { font-size: 3.5rem; font-weight: 800; color: var(--ccs-purple); line-height: 1; }

        /* --- RATING BREAKDOWN BARS --- */
        .breakdown-container { width: 100%; padding: 10px 20px; }
        .bar-item { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
        .bar-label { font-size: 0.75rem; font-weight: 700; color: var(--ccs-purple); width: 45px; }
        .bar-track { flex-grow: 1; height: 8px; background: #eee; border-radius: 10px; overflow: hidden; }
        .bar-fill { height: 100%; background: var(--ccs-gold); border-radius: 10px; }
        .bar-count { font-size: 0.7rem; font-weight: 600; color: var(--text-muted); width: 25px; }

        /* --- FEEDBACK LIST --- */
        .card { background: white; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, var(--ccs-purple) 0%, var(--ccs-purple-dark) 100%); color: white; padding: 18px 24px; font-weight: 700; }
        .feedback-item { display: flex; gap: 20px; padding: 25px; border-bottom: 1px solid #f1f5f9; }
        .student-photo { width: 55px; height: 55px; border-radius: 12px; object-fit: cover; border: 2px solid var(--ccs-gold); background: #eee; }
        .stars { color: var(--ccs-gold); font-size: 0.9rem; }
    </style>
</head>
<body>

<header class="professional-header">
    <div class="header-container">
        <div class="logo-area">
            <img src="uc.logo.png" alt="Logo" style="width: 45px;">
            <h1 style="font-size: 1.1rem; color: var(--ccs-purple);">College of Computer Studies <span style="font-weight: 400; color: var(--text-muted);">| Admin</span></h1>
        </div>
        <div class="nav-links">
            <a href="admin_dashboard.php">Home</a>
            <a href="manage_students.php">Students</a>
            <a href="sit_in.php">Sit-in</a>
            <a href="view_sitin.php">View Records</a>
            <a href="feedback_report.php" class="active">Feedback Reports</a> 
            <a href="logout.php" class="btn-logout">Log out</a>
        </div>
    </div>
</header>

<div class="dashboard-container">
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-header"><i class="fas fa-star"></i> Avg Rating</div>
            <div class="stat-body">
                <div class="stat-number"><?php echo number_format($s['avg_rating'], 1); ?></div>
                <div style="color: var(--ccs-gold); margin-top: 5px;">
                    <?php for($i=1; $i<=5; $i++) echo ($i <= round($s['avg_rating'])) ? '★' : '☆'; ?>
                </div>
            </div>
        </div>

        <div class="stat-card" style="flex: 1.5;">
            <div class="stat-header"><i class="fas fa-chart-bar"></i> Rating Breakdown</div>
            <div class="stat-body" style="padding: 15px 0;">
                <div class="breakdown-container">
                    <?php 
                    $levels = [5 => $s['s5'], 4 => $s['s4'], 3 => $s['s3'], 2 => $s['s2'], 1 => $s['s1']];
                    foreach($levels as $stars => $count): 
                        $percent = ($s['total'] > 0) ? ($count / $s['total']) * 100 : 0;
                    ?>
                    <div class="bar-item">
                        <span class="bar-label"><?php echo $stars; ?> Star</span>
                        <div class="bar-track"><div class="bar-fill" style="width: <?php echo $percent; ?>%;"></div></div>
                        <span class="bar-count"><?php echo $count; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header"><i class="fas fa-comments"></i> Total Feedback</div>
            <div class="stat-body">
                <div class="stat-number"><?php echo $s['total']; ?></div>
                <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-top: 5px;">Submissions Received</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fas fa-list-ul"></i> Detailed Student Experience</div>
        <div class="card-body" style="padding: 0;">
            <?php if (mysqli_num_rows($feed_query) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($feed_query)): ?>
                    <div class="feedback-item">
                        <?php 
                            $photo = (!empty($row['profile_pic']) && file_exists($row['profile_pic'])) 
                                     ? $row['profile_pic'] 
                                     : "https://ui-avatars.com/api/?name=".urlencode($row['first_name'])."&background=4b2c82&color=fff";
                        ?>
                        <img src="<?php echo $photo; ?>" class="student-photo">
                        
                        <div style="flex-grow: 1;">
                            <div style="display: flex; justify-content: space-between;">
                                <h4 style="color: var(--ccs-purple);"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></h4>
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">
                                    <?php echo date('M d, Y • h:i A', strtotime($row['time_out'])); ?>
                                </span>
                            </div>
                            <div class="stars">
                                <?php for($i=1; $i<=5; $i++) echo ($i <= $row['rating']) ? '★' : '☆'; ?>
                            </div>
                            <p style="font-style: italic; margin: 5px 0; font-size: 0.95rem;">"<?php echo htmlspecialchars($row['feedback_comment'] ?: 'No comment provided.'); ?>"</p>
                            <div style="margin-top: 8px;">
                                <span style="background: #f1f5f9; padding: 3px 10px; border-radius: 6px; font-size: 0.7rem; color: var(--ccs-purple); font-weight: 700;">
                                    Lab <?php echo $row['lab']; ?> • <?php echo $row['purpose']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 60px; text-align: center; color: var(--text-muted);">No feedback found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>