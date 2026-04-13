<?php
session_start();
include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $res_id = mysqli_real_escape_string($conn, $_POST['reservation_id']);
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $student_id = $_SESSION['user_id'];

    // Update the reservations table or a separate feedbacks table
    // Assuming you add 'rating' and 'comment' columns to your 'reservations' table:
    $sql = "UPDATE reservations SET rating = '$rating', feedback_comment = '$comment' 
            WHERE id = '$res_id' AND student_id = '$student_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Thank you for your feedback!'); window.location.href='history.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>