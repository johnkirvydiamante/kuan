<?php
session_start();
include('config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

// Optimized Query: Joining users to get ID Number and Sessions
$query = mysqli_query($conn, "SELECT r.*, u.id_number, u.sessions 
                              FROM reservations r 
                              JOIN users u ON r.student_id = u.id_number 
                              WHERE u.id_number = (SELECT id_number FROM users WHERE id = '$uid' OR id_number = '$uid' LIMIT 1) 
                              ORDER BY r.date DESC, r.time_in DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History | CCS Sit-in Monitoring System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ccs-purple-dark: #3a235c;
            --ccs-purple: #4b2c82;
            --ccs-gold: #ffcc00;
            --text-muted: #64748b;
            --shadow-lg: 0 20px 35px -10px rgba(0,0,0,0.15);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f7fa; min-height: 100vh; }

        .professional-header { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 40px; display: flex; align-items: center; justify-content: space-between; height: 80px; }
        .logo-area { display: flex; align-items: center; gap: 12px; }
        .uc-logo { width: 50px; }
        .nav-links { display: flex; gap: 32px; align-items: center; }
        .nav-links a { text-decoration: none; color: var(--text-muted); font-size: 0.9rem; font-weight: 500; }
        .nav-links a.active { color: var(--ccs-purple); font-weight: 700; }
        .btn-logout { background: var(--ccs-gold); color: var(--ccs-purple-dark) !important; padding: 8px 20px !important; border-radius: 40px; font-weight: 700 !important; }

        .history-container { max-width: 1400px; margin: 40px auto; padding: 0 20px; }
        .history-card { background: white; border-radius: 24px; box-shadow: var(--shadow-lg); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, var(--ccs-purple) 0%, var(--ccs-purple-dark) 100%); color: white; padding: 30px; }
        .card-body { padding: 30px; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #f8fafc; color: var(--text-muted); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; padding: 15px; border-bottom: 2px solid #edf2f7; text-align: left; }
        .data-table td { padding: 20px 15px; border-bottom: 1px solid #eef2f6; font-size: 0.85rem; vertical-align: middle; }

        .id-text { font-family: 'Courier New', monospace; font-weight: 700; color: #475569; }
        .session-badge { background: #f3f0ff; color: var(--ccs-purple); font-weight: 800; padding: 6px 12px; border-radius: 8px; border: 1px solid #dcd3ff; }
        
        .time-box { background: #fdfcf6; border: 1px solid #fef3c7; padding: 8px 12px; border-radius: 12px; font-size: 0.8rem; display: inline-block; line-height: 1.5; }
        .time-box i { color: var(--ccs-purple); width: 18px; }

        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-block; min-width: 90px; text-align: center; }
        .status-completed { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-active { background: #fff9db; color: #856404; border: 1px solid #ffeeba; }

        .btn-feedback { padding: 8px 18px; border-radius: 20px; border: 1.5px solid #10b981; color: #10b981; font-weight: 700; cursor: pointer; font-size: 0.75rem; background: transparent; transition: 0.2s; }
        .btn-feedback:hover { background: #10b981; color: white; }
        .feedback-done { color: #64748b; font-weight: 600; font-size: 0.8rem; display: flex; align-items: center; gap: 5px; }

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background: white; width: 450px; border-radius: 24px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 10px; margin: 20px 0; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2.5rem; color: #cbd5e1; cursor: pointer; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: var(--ccs-gold); }
        .feedback-textarea { width: 100%; height: 100px; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 12px; resize: none; margin-bottom: 20px; }
        .btn-submit-feedback { width: 100%; background: var(--ccs-purple); color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>

<header class="professional-header">
    <div class="header-container">
        <div class="logo-area">
            <img src="uc.logo.png" alt="Logo" class="uc-logo">
            <h1 style="font-size: 1.1rem; color: var(--ccs-purple);">College of Computer Studies | <span style="font-weight: 400; color: var(--text-muted);">Sit-in Monitoring System</span></h1>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Home</a>
            <a href="edit_profile.php">Edit Profile</a>
            <a href="history.php" class="active">History</a>
            <a href="reservation.php">Reservation</a>
            <a href="logout.php" class="btn-logout">Log out</a>
        </div>
    </div>
</header>

<div class="history-container">
    <div class="history-card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> History</h2>
            <p style="opacity: 0.8; font-size: 0.85rem; margin-top: 5px;">View your past laboratory activities and session usage.</p>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Purpose / Lab</th>
                        <th>Date</th>
                        <th>Log</th>
                        <th style="text-align: center;">Sessions</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)): 
                        $status = $row['status'];
                        $hasTimeOut = (!empty($row['time_out']) && $row['time_out'] != '00:00:00');
                        $isCompleted = ($status == 'Completed' || $hasTimeOut);
                        $hasFeedback = (!empty($row['rating']) && $row['rating'] > 0);
                    ?>
                    <tr>
                        <td><span class="id-text"><?php echo htmlspecialchars($row['id_number']); ?></span></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['purpose']); ?></strong><br>
                            <span style="color:var(--text-muted); font-size: 0.75rem;"><?php echo htmlspecialchars($row['lab']); ?></span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        <td>
                            <div class="time-box">
                                <div><i class="far fa-clock"></i> In: <?php echo date('h:i A', strtotime($row['time_in'])); ?></div>
                                <?php if($hasTimeOut): ?>
                                    <div style="color:var(--text-muted);"><i class="fas fa-sign-out-alt"></i> Out: <?php echo date('h:i A', strtotime($row['time_out'])); ?></div>
                                <?php else: ?>
                                    <div style="color:#10b981; font-weight:600;"><i class="fas fa-spinner fa-spin"></i> Active</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="text-align: center;"><span class="session-badge"><?php echo $row['sessions']; ?></span></td>
                        <td>
                            <span class="status-badge <?php echo $isCompleted ? 'status-completed' : 'status-active'; ?>">
                                <?php echo $isCompleted ? 'Logged Out' : 'Active'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if($isCompleted): ?>
                                <?php if($hasFeedback): ?>
                                    <span class="feedback-done"><i class="fas fa-check-circle" style="color:#10b981;"></i> Done</span>
                                <?php else: ?>
                                    <button class="btn-feedback" onclick="openFeedbackModal(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-star"></i> Feedback
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--text-muted); font-size: 0.75rem;">Session Ongoing</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <h3 style="text-align:center; color: var(--ccs-purple); margin-bottom: 10px;">Rate Your Session</h3>
        <form action="submit_feedback.php" method="POST">
            <input type="hidden" name="reservation_id" id="modal_reservation_id">
            <div class="star-rating">
                <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" class="fas fa-star"></label>
                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" class="fas fa-star"></label>
                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" class="fas fa-star"></label>
                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" class="fas fa-star"></label>
                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" class="fas fa-star"></label>
            </div>
            <textarea name="comment" class="feedback-textarea" placeholder="How were the facilities?"></textarea>
            <button type="submit" class="btn-submit-feedback">Submit Feedback</button>
            <button type="button" onclick="closeModal()" style="width:100%; background:none; border:none; margin-top:10px; color:var(--text-muted); cursor:pointer;">Cancel</button>
        </form>
    </div>
</div>

<script>
    function openFeedbackModal(id) {
        document.getElementById('modal_reservation_id').value = id;
        document.getElementById('feedbackModal').style.display = 'flex';
    }
    function closeModal() { document.getElementById('feedbackModal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target == document.getElementById('feedbackModal')) closeModal(); }
</script>
</body>
</html>