<?php
include('config.php');

$ann_query = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1");
$announcement = mysqli_fetch_assoc($ann_query);

if ($announcement) {
    $date = date('F d, Y', strtotime($announcement['created_at']));
    echo '
    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
        <i class="fas fa-user-circle" style="font-size:30px; color:#4b2c82;"></i>
        <div>
            <div style="font-weight:700;">CCS Admin</div>
            <div style="font-size:0.8rem; color:#888;">' . $date . '</div>
        </div>
    </div>
    <div style="color:#555; line-height:1.6;">
        ' . htmlspecialchars($announcement['content']) . '
    </div>';
} else {
    echo '<p>No current announcements.</p>';
}
?>