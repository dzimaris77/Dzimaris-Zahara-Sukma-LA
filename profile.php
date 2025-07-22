<?php
session_start();
require_once '../../config/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'teknisi') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_telepon = trim($_POST['no_telepon']);
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
    
    $errors = [];
    
    // Validasi data wajib
    if (empty($nama)) {
        $errors[] = "Nama lengkap wajib diisi!";
    }
    
    if (empty($email)) {
        $errors[] = "Email wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid!";
    }
    
    if (empty($no_telepon)) {
        $errors[] = "Nomor telepon wajib diisi!";
    }
    
    // Cek email duplikat
    $check_email = $conn->prepare("SELECT id_user FROM users WHERE email = ? AND id_user != ?");
    $check_email->bind_param("si", $email, $user_id);
    $check_email->execute();
    if ($check_email->get_result()->num_rows > 0) {
        $errors[] = "Email sudah digunakan oleh pengguna lain!";
    }
    
    // Handle profile image upload
    $profile_image_name = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_dir = "../../uploads/profile/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_size = $_FILES['profile_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_extensions)) {
            $errors[] = "Format file harus JPG, JPEG, atau PNG!";
        } elseif ($file_size > 2097152) { // 2MB
            $errors[] = "Ukuran file maksimal 2MB!";
        } else {
            // Generate unique filename
            $profile_image_name = "profile_" . $user_id . "_" . time() . "." . $file_ext;
            $target_file = $upload_dir . $profile_image_name;
            
            if (!move_uploaded_file($file_tmp, $target_file)) {
                $errors[] = "Gagal mengupload foto profile!";
                $profile_image_name = null;
            }
        }
    }
    
    // Validasi password jika diisi
    if (!empty($password_lama) || !empty($password_baru) || !empty($konfirmasi_password)) {
        if (empty($password_lama)) {
            $errors[] = "Password lama wajib diisi untuk mengubah password!";
        } else {
            // Verifikasi password lama
            $stmt = $conn->prepare("SELECT password FROM users WHERE id_user = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            if (!password_verify($password_lama, $user_data['password'])) {
                $errors[] = "Password lama tidak sesuai!";
            }
        }
        
        if (empty($password_baru)) {
            $errors[] = "Password baru wajib diisi!";
        } elseif (strlen($password_baru) < 6) {
            $errors[] = "Password baru minimal 6 karakter!";
        }
        
        if ($password_baru !== $konfirmasi_password) {
            $errors[] = "Konfirmasi password tidak cocok!";
        }
    }
    
    // Update data jika tidak ada error
    if (empty($errors)) {
        // Get current profile image to delete old one
        $stmt = $conn->prepare("SELECT user_image FROM users WHERE id_user = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $current_user = $stmt->get_result()->fetch_assoc();
        
        if (!empty($password_baru)) {
            // Update dengan password baru dan foto (jika ada)
            if ($profile_image_name) {
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_telepon = ?, password = ?, user_image = ? WHERE id_user = ?");
                $stmt->bind_param("sssssi", $nama, $email, $no_telepon, $hashed_password, $profile_image_name, $user_id);
            } else {
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_telepon = ?, password = ? WHERE id_user = ?");
                $stmt->bind_param("ssssi", $nama, $email, $no_telepon, $hashed_password, $user_id);
            }
        } else {
            // Update tanpa password
            if ($profile_image_name) {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_telepon = ?, user_image = ? WHERE id_user = ?");
                $stmt->bind_param("ssssi", $nama, $email, $no_telepon, $profile_image_name, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, no_telepon = ? WHERE id_user = ?");
                $stmt->bind_param("sssi", $nama, $email, $no_telepon, $user_id);
            }
        }
        
        if ($stmt->execute()) {
            // Delete old profile image if new one uploaded
            if ($profile_image_name && $current_user['user_image'] && file_exists("../../uploads/profile/" . $current_user['user_image'])) {
                unlink("../../uploads/profile/" . $current_user['user_image']);
            }
            
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            if ($profile_image_name) {
                $_SESSION['user_image'] = $profile_image_name;
            }
            $success_message = "Profile berhasil diperbarui!";
        } else {
            $error_message = "Gagal memperbarui profile!";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get statistics for this technician
$total_assigned = $conn->prepare("SELECT COUNT(*) as count FROM laporan WHERE id_teknisi = ?");
$total_assigned->bind_param("i", $user_id);
$total_assigned->execute();
$total_assigned = $total_assigned->get_result()->fetch_assoc()['count'];

$completed = $conn->prepare("SELECT COUNT(*) as count FROM laporan WHERE id_teknisi = ? AND status IN ('selesai', 'selesai_total')");
$completed->bind_param("i", $user_id);
$completed->execute();
$completed = $completed->get_result()->fetch_assoc()['count'];

$in_progress = $conn->prepare("SELECT COUNT(*) as count FROM laporan WHERE id_teknisi = ? AND status = 'dalam_perbaikan'");
$in_progress->bind_param("i", $user_id);
$in_progress->execute();
$in_progress = $in_progress->get_result()->fetch_assoc()['count'];

$pending = $conn->prepare("SELECT COUNT(*) as count FROM laporan WHERE id_teknisi = ? AND status = 'menunggu_penugasan'");
$pending->bind_param("i", $user_id);
$pending->execute();
$pending = $pending->get_result()->fetch_assoc()['count'];

// Set profile image path
$profile_image = $user['user_image'] ? "../../uploads/profile/" . $user['user_image'] : "../../img/undraw_profile.svg";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile Teknisi - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            object-fit: cover;
        }
        .stats-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-3px);
        }
        .form-section {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .section-title {
            color: #5a5c69;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3e6f0;
        }
        .required-field {
            color: #e74a3b;
            font-weight: bold;
        }
        .profile-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .profile-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }
        .profile-upload:hover .profile-upload-overlay {
            opacity: 1;
        }
        .image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e3e6f0;
            margin-bottom: 15px;
        }
        .upload-section {
            text-align: center;
            padding: 20px;
            border: 2px dashed #e3e6f0;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/topbar.php'; ?>

                <div class="container-fluid">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center">
                                <img src="<?= $profile_image ?>?<?= time() ?>" class="profile-avatar" alt="Profile">
                            </div>
                            <div class="col-md-9">
                                <h2 class="mb-1"><?= htmlspecialchars($user['nama']) ?></h2>
                                <p class="mb-2"><i class="fas fa-tools mr-2"></i>Teknisi Perbaikan</p>
                                <p class="mb-2"><i class="fas fa-envelope mr-2"></i><?= htmlspecialchars($user['email']) ?></p>
                                <p class="mb-0"><i class="fas fa-phone mr-2"></i><?= htmlspecialchars($user['no_telepon']) ?></p>
                                <small class="opacity-75">Bergabung sejak: <?= date('d F Y', strtotime($user['created_at'])) ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card shadow h-100 py-2" style="border-left-color: #4e73df;">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Penugasan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_assigned ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card shadow h-100 py-2" style="border-left-color: #1cc88a;">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Selesai</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $completed ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card shadow h-100 py-2" style="border-left-color: #36b9cc;">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Dalam Perbaikan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $in_progress ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card shadow h-100 py-2" style="border-left-color: #f6c23e;">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Menunggu</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pending ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle mr-2"></i><?= $success_message ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle mr-2"></i><?= $error_message ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <!-- Edit Profile Form -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-warning">
                                        <i class="fas fa-user-edit mr-2"></i>Edit Profile
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="profileForm" enctype="multipart/form-data">
                                        <!-- Upload Foto Profile -->
                                        <div class="form-section">
                                            <h6 class="section-title">
                                                <i class="fas fa-camera mr-2"></i>Foto Profile
                                            </h6>
                                            
                                            <div class="upload-section">
                                                <div class="profile-upload" onclick="document.getElementById('profile_image').click()">
                                                    <img src="<?= $profile_image ?>?<?= time() ?>" class="image-preview" id="imagePreview" alt="Profile Preview">
                                                    <div class="profile-upload-overlay">
                                                        <div class="text-center">
                                                            <i class="fas fa-camera fa-2x mb-2"></i><br>
                                                            <small>Klik untuk ubah</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="file" name="profile_image" id="profile_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                                                <p class="text-muted mt-2">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Format: JPG, JPEG, PNG (Maksimal 2MB)
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Data Pribadi -->
                                        <div class="form-section">
                                            <h6 class="section-title">
                                                <i class="fas fa-user mr-2"></i>Data Pribadi
                                            </h6>
                                            
                                            <div class="form-group">
                                                <label class="font-weight-bold">
                                                    Nama Lengkap <span class="required-field">*</span>
                                                </label>
                                                <input type="text" name="nama" class="form-control" 
                                                       value="<?= htmlspecialchars($user['nama']) ?>" required>
                                            </div>

                                            <div class="form-group">
                                                <label class="font-weight-bold">
                                                    Email <span class="required-field">*</span>
                                                </label>
                                                <input type="email" name="email" class="form-control" 
                                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                                            </div>

                                            <div class="form-group">
                                                <label class="font-weight-bold">
                                                    Nomor Telepon <span class="required-field">*</span>
                                                </label>
                                                <input type="tel" name="no_telepon" class="form-control" 
                                                       value="<?= htmlspecialchars($user['no_telepon']) ?>" required>
                                            </div>

                                            <div class="form-group">
                                                <label class="font-weight-bold">Role</label>
                                                <input type="text" class="form-control" value="Teknisi" readonly>
                                            </div>
                                        </div>

                                        <!-- Ubah Password -->
                                        <div class="form-section">
                                            <h6 class="section-title">
                                                <i class="fas fa-lock mr-2"></i>Ubah Password (Opsional)
                                            </h6>
                                            
                                            <div class="form-group">
                                                <label class="font-weight-bold">Password Lama</label>
                                                <input type="password" name="password_lama" class="form-control">
                                                <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                            </div>

                                            <div class="form-group">
                                                <label class="font-weight-bold">Password Baru</label>
                                                <input type="password" name="password_baru" class="form-control">
                                                <small class="form-text text-muted">Minimal 6 karakter</small>
                                            </div>

                                            <div class="form-group">
                                                <label class="font-weight-bold">Konfirmasi Password Baru</label>
                                                <input type="password" name="konfirmasi_password" class="form-control">
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <button type="submit" class="btn btn-warning btn-lg">
                                                <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-warning">
                                        <i class="fas fa-info-circle mr-2"></i>Informasi Akun
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <img src="<?= $profile_image ?>?<?= time() ?>" class="img-fluid rounded-circle" style="max-width: 100px; height: 100px; object-fit: cover;" alt="Profile">
                                    </div>
                                    
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Role:</strong></td>
                                            <td><span class="badge badge-warning">Teknisi</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td><span class="badge badge-success">Aktif</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Bergabung:</strong></td>
                                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Login:</strong></td>
                                            <td><?= date('d/m/Y H:i') ?></td>
                                        </tr>
                                    </table>

                                    <div class="alert alert-warning">
                                        <i class="fas fa-tools mr-2"></i>
                                        <strong>Hak Akses Teknisi:</strong><br>
                                        • Menerima penugasan perbaikan<br>
                                        • Update status perbaikan<br>
                                        • Upload dokumentasi hasil<br>
                                        • Buat laporan perbaikan
                                    </div>

                                    <!-- Performance Chart -->
                                    <div class="mt-4">
                                        <h6 class="font-weight-bold">Performance Rate</h6>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?= $total_assigned > 0 ? ($completed / $total_assigned) * 100 : 0 ?>%" 
                                                 aria-valuenow="<?= $total_assigned > 0 ? ($completed / $total_assigned) * 100 : 0 ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?= $total_assigned > 0 ? round(($completed / $total_assigned) * 100, 1) : 0 ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?= $completed ?> dari <?= $total_assigned ?> tugas selesai
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Auto dismiss alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Form validation
        $('#profileForm').on('submit', function(e) {
            const passwordLama = $('input[name="password_lama"]').val();
            const passwordBaru = $('input[name="password_baru"]').val();
            const konfirmasiPassword = $('input[name="konfirmasi_password"]').val();
            
            if (passwordLama || passwordBaru || konfirmasiPassword) {
                if (!passwordLama) {
                    alert('Password lama wajib diisi untuk mengubah password!');
                    e.preventDefault();
                    return false;
                }
                
                if (!passwordBaru) {
                    alert('Password baru wajib diisi!');
                    e.preventDefault();
                    return false;
                }
                
                if (passwordBaru !== konfirmasiPassword) {
                    alert('Konfirmasi password tidak cocok!');
                    e.preventDefault();
                    return false;
                }
                
                if (passwordBaru.length < 6) {
                    alert('Password baru minimal 6 karakter!');
                    e.preventDefault();
                    return false;
                }
            }
        });
    });

    // Preview image function
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>
</html>
