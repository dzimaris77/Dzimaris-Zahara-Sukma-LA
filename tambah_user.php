<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config/config.php';

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_telepon = trim($_POST['no_telepon']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // VALIDASI WAJIB - Semua field harus diisi
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
    } elseif (!preg_match('/^[0-9+\-\s()]+$/', $no_telepon)) {
        $errors[] = "Format nomor telepon tidak valid!";
    }
    
    if (empty($role)) {
        $errors[] = "Role pengguna wajib dipilih!";
    }
    
    if (empty($password)) {
        $errors[] = "Password wajib diisi!";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter!";
    }
    
    if (empty($confirm_password)) {
        $errors[] = "Konfirmasi password wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Password dan konfirmasi password tidak cocok!";
    }
    
    // Cek email sudah ada atau belum
    if (empty($errors)) {
        $check_email = mysqli_query($conn, "SELECT id_user FROM users WHERE email = '" . mysqli_real_escape_string($conn, $email) . "'");
        if (mysqli_num_rows($check_email) > 0) {
            $errors[] = "Email sudah terdaftar dalam sistem!";
        }
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (nama, email, no_telepon, role, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $nama, $email, $no_telepon, $role, $hashed_password);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "User berhasil ditambahkan dengan data lengkap!";
            header("Location: kelola_user.php");
            exit();
        } else {
            $errors[] = "Gagal menambahkan user: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Tambah User - SIMONFAST</title>

    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .required-field {
            color: #e74a3b;
            font-weight: bold;
        }
        .form-control.is-invalid {
            border-color: #e74a3b;
            box-shadow: 0 0 0 0.2rem rgba(231, 74, 59, 0.25);
        }
        .form-control:focus {
            border-color: #1cc88a;
            box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);
        }
        .mandatory-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .form-section {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section-title {
            color: #5a5c69;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e3e6f0;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/topbar.php'; ?>

                <!-- Main Content -->
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Tambah Pengguna Baru</h1>
                        <a href="kelola_user.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>

                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-user-plus mr-2"></i>Form Tambah Pengguna
                                    </h6>
                                </div>
                                
                                <div class="card-body">
                                    <!-- Mandatory Notice -->
                                    <div class="mandatory-notice">
                                        <i class="fas fa-exclamation-triangle mr-2 text-warning"></i>
                                        <strong>PERHATIAN:</strong> Semua field di bawah ini <strong class="required-field">WAJIB DIISI</strong> untuk menambahkan pengguna baru ke sistem.
                                    </div>

                                    <form method="POST" action="" id="addUserForm" novalidate>
                                        <!-- Data Pribadi Section -->
                                        <div class="form-section">
                                            <h5 class="section-title">
                                                <i class="fas fa-user mr-2"></i>Data Pribadi
                                            </h5>
                                            
                                            <div class="form-group">
                                                <label for="nama" class="font-weight-bold">
                                                    Nama Lengkap <span class="required-field">*WAJIB</span>
                                                </label>
                                                <input type="text" 
                                                       class="form-control <?= isset($errors) && in_array("Nama lengkap wajib diisi!", $errors) ? 'is-invalid' : '' ?>" 
                                                       id="nama" 
                                                       name="nama" 
                                                       value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>"
                                                       placeholder="Masukkan nama lengkap pengguna"
                                                       required>
                                                <small class="form-text text-danger">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    <strong>WAJIB:</strong> Nama lengkap sesuai identitas
                                                </small>
                                            </div>

                                            <div class="form-group">
                                                <label for="email" class="font-weight-bold">
                                                    Email <span class="required-field">*WAJIB</span>
                                                </label>
                                                <input type="email" 
                                                       class="form-control <?= isset($errors) && (in_array("Email wajib diisi!", $errors) || in_array("Format email tidak valid!", $errors) || in_array("Email sudah terdaftar dalam sistem!", $errors)) ? 'is-invalid' : '' ?>" 
                                                       id="email" 
                                                       name="email" 
                                                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                                       placeholder="contoh@email.com"
                                                       required>
                                                <small class="form-text text-danger">
                                                    <i class="fas fa-envelope mr-1"></i>
                                                    <strong>WAJIB:</strong> Email valid untuk login sistem
                                                </small>
                                            </div>

                                            <div class="form-group">
                                                <label for="no_telepon" class="font-weight-bold">
                                                    Nomor Telepon <span class="required-field">*WAJIB</span>
                                                </label>
                                                <input type="tel" 
                                                       class="form-control <?= isset($errors) && (in_array("Nomor telepon wajib diisi!", $errors) || in_array("Format nomor telepon tidak valid!", $errors)) ? 'is-invalid' : '' ?>" 
                                                       id="no_telepon" 
                                                       name="no_telepon" 
                                                       value="<?= isset($_POST['no_telepon']) ? htmlspecialchars($_POST['no_telepon']) : '' ?>"
                                                       placeholder="08xxxxxxxxxx atau +62xxxxxxxxx"
                                                       required>
                                                <small class="form-text text-danger">
                                                    <i class="fas fa-phone mr-1"></i>
                                                    <strong>WAJIB:</strong> Nomor telepon aktif untuk komunikasi
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Role Section -->
                                        <div class="form-section">
                                            <h5 class="section-title">
                                                <i class="fas fa-user-tag mr-2"></i>Role & Akses
                                            </h5>
                                            
                                            <div class="form-group">
                                                <label for="role" class="font-weight-bold">
                                                    Role Pengguna <span class="required-field">*WAJIB</span>
                                                </label>
                                                <select class="form-control <?= isset($errors) && in_array("Role pengguna wajib dipilih!", $errors) ? 'is-invalid' : '' ?>" 
                                                        id="role" 
                                                        name="role" 
                                                        required>
                                                    <option value="">-- Pilih Role Pengguna --</option>
                                                    <option value="admin" <?= isset($_POST['role']) && $_POST['role'] == 'admin' ? 'selected' : '' ?>>
                                                        Admin - Kelola seluruh sistem
                                                    </option>
                                                    <option value="tenaga_kesehatan" <?= isset($_POST['role']) && $_POST['role'] == 'tenaga_kesehatan' ? 'selected' : '' ?>>
                                                        Tenaga Kesehatan - Buat laporan & konfirmasi
                                                    </option>
                                                    <option value="teknisi" <?= isset($_POST['role']) && $_POST['role'] == 'teknisi' ? 'selected' : '' ?>>
                                                        Teknisi - Perbaikan alat
                                                    </option>
                                                    <option value="admin_teknisi" <?= isset($_POST['role']) && $_POST['role'] == 'admin_teknisi' ? 'selected' : '' ?>>
                                                        Admin Teknisi - Kelola penugasan
                                                    </option>
                                                </select>
                                                <small class="form-text text-danger">
                                                    <i class="fas fa-user-shield mr-1"></i>
                                                    <strong>WAJIB:</strong> Tentukan akses dan hak pengguna
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Password Section -->
                                        <div class="form-section">
                                            <h5 class="section-title">
                                                <i class="fas fa-lock mr-2"></i>Keamanan Akun
                                            </h5>
                                            
                                            <div class="form-group">
                                                <label for="password" class="font-weight-bold">
                                                    Password <span class="required-field">*WAJIB</span>
                                                </label>
                                                <input type="password" 
                                                       class="form-control <?= isset($errors) && (in_array("Password wajib diisi!", $errors) || in_array("Password minimal 6 karakter!", $errors)) ? 'is-invalid' : '' ?>" 
                                                       id="password" 
                                                       name="password" 
                                                       placeholder="Minimal 6 karakter"
                                                       required>
                                                <small class="form-text text-danger">
                                                    <i class="fas fa-key mr-1"></i>
                                                    <strong>WAJIB:</strong> Password minimal 6 karakter untuk keamanan
                                                </small>
                                            </div>

                                            <div class="form-group">
                                                <label for="confirm_password" class="font-weight-bold">
                                                    Konfirmasi Password <span class="required-field">*WAJIB</span>
                                                </label>
                                                <input type="password" 
                                                       class="form-control <?= isset($errors) && (in_array("Konfirmasi password wajib diisi!", $errors) || in_array("Password dan konfirmasi password tidak cocok!", $errors)) ? 'is-invalid' : '' ?>" 
                                                       id="confirm_password" 
                                                       name="confirm_password" 
                                                       placeholder="Ulangi password yang sama"
                                                       required>
                                                <small class="form-text text-danger">
                                                    <i class="fas fa-check-double mr-1"></i>
                                                    <strong>WAJIB:</strong> Harus sama dengan password di atas
                                                </small>
                                            </div>
                                        </div>

                                        <div class="form-group text-center">
                                            <button type="button" class="btn btn-secondary mr-3" onclick="window.location.href='kelola_user.php'">
                                                <i class="fas fa-times mr-1"></i>Batal
                                            </button>
                                            <button type="submit" class="btn btn-success btn-lg" onclick="return validateForm()">
                                                <i class="fas fa-user-plus mr-1"></i>Tambah Pengguna
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>

    <script>
    // Validasi form sebelum submit
    function validateForm() {
        const nama = document.getElementById('nama').value.trim();
        const email = document.getElementById('email').value.trim();
        const no_telepon = document.getElementById('no_telepon').value.trim();
        const role = document.getElementById('role').value;
        const password = document.getElementById('password').value;
        const confirm_password = document.getElementById('confirm_password').value;
        
        // Reset border colors
        document.querySelectorAll('.form-control').forEach(el => {
            el.classList.remove('is-invalid');
        });
        
        let isValid = true;
        let errorMessages = [];
        
        // Validasi nama
        if (nama === '') {
            document.getElementById('nama').classList.add('is-invalid');
            errorMessages.push('Nama lengkap wajib diisi!');
            isValid = false;
        }
        
        // Validasi email
        if (email === '') {
            document.getElementById('email').classList.add('is-invalid');
            errorMessages.push('Email wajib diisi!');
            isValid = false;
        } else if (!isValidEmail(email)) {
            document.getElementById('email').classList.add('is-invalid');
            errorMessages.push('Format email tidak valid!');
            isValid = false;
        }
        
        // Validasi nomor telepon
        if (no_telepon === '') {
            document.getElementById('no_telepon').classList.add('is-invalid');
            errorMessages.push('Nomor telepon wajib diisi!');
            isValid = false;
        }
        
        // Validasi role
        if (role === '') {
            document.getElementById('role').classList.add('is-invalid');
            errorMessages.push('Role pengguna wajib dipilih!');
            isValid = false;
        }
        
        // Validasi password
        if (password === '') {
            document.getElementById('password').classList.add('is-invalid');
            errorMessages.push('Password wajib diisi!');
            isValid = false;
        } else if (password.length < 6) {
            document.getElementById('password').classList.add('is-invalid');
            errorMessages.push('Password minimal 6 karakter!');
            isValid = false;
        }
        
        // Validasi konfirmasi password
        if (confirm_password === '') {
            document.getElementById('confirm_password').classList.add('is-invalid');
            errorMessages.push('Konfirmasi password wajib diisi!');
            isValid = false;
        } else if (password !== confirm_password) {
            document.getElementById('confirm_password').classList.add('is-invalid');
            errorMessages.push('Password dan konfirmasi password tidak cocok!');
            isValid = false;
        }
        
        if (!isValid) {
            alert('❌ FORM BELUM LENGKAP!\n\nSemua field wajib diisi:\n\n' + errorMessages.join('\n'));
            return false;
        }
        
        return confirm('✅ KONFIRMASI TAMBAH PENGGUNA\n\nApakah data yang diisi sudah benar?\n\n• Nama: ' + nama + '\n• Email: ' + email + '\n• No. Telepon: ' + no_telepon + '\n• Role: ' + role + '\n\nData akan disimpan ke sistem.');
    }
    
    // Validasi format email
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Real-time validation
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.form-control');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && this.value.trim() === '') {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid') && this.value.trim() !== '') {
                    this.classList.remove('is-invalid');
                }
            });
        });
        
        // Password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword !== '' && password !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
    </script>
</body>
</html>
