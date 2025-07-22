<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config/config.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Cek apakah user ada
    $check_user = mysqli_query($conn, "SELECT * FROM users WHERE id_user = $user_id");
    if (mysqli_num_rows($check_user) > 0) {
        mysqli_query($conn, "DELETE FROM users WHERE id_user = $user_id");
        header("Location: kelola_user.php?success=hapus");
        exit();
    }
}

// Jika tidak valid redirect
header("Location: kelola_user.php");
exit();
?>
