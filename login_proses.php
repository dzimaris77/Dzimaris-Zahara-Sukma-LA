<?php
session_start();
require 'config/config.php';

// Validasi input
if (empty($_POST['email']) || empty($_POST['password'])) {
    header("Location: login.php?error=1");
    exit();
}

$email = $_POST['email'];
$password = $_POST['password'];

// Mencari user berdasarkan email
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Verifikasi password
    if (password_verify($password, $user['password'])) {
        // Regenerasi session ID untuk keamanan
        session_regenerate_id(true);
        // After successful authentication, add this line:
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['user_image'] = $user['user_image'];
        
        // Redirect berdasarkan role
        switch ($user['role']) {
            case 'admin':
                header("Location: dashboard/admin/");
                break;
            case 'tenaga_kesehatan':
                header("Location: dashboard/tenaga/");
                break;
            case 'teknisi':
                header("Location: dashboard/teknisi/");
                break;
            case 'admin_teknisi': // Tambahan role baru
                header("Location: dashboard/admin_teknisi/");
                break;
            default:
                header("Location: login.php?error=1");
        }
        exit();
    }
}

// Jika gagal verifikasi
header("Location: login.php?error=1");
exit();
?>