<?php
// Tentukan path profile berdasarkan role pengguna
$role = $_SESSION['role'] ?? '';
$current_path = $_SERVER['REQUEST_URI'];

// Get user image dari database atau session
$user_image = null;
if (isset($_SESSION['user_image']) && !empty($_SESSION['user_image'])) {
    $user_image = $_SESSION['user_image'];
} elseif (isset($_SESSION['user_id'])) {
    // Ambil dari database jika tidak ada di session
    $stmt = $conn->prepare("SELECT user_image FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $user_image = $user_data['user_image'];
        $_SESSION['user_image'] = $user_image; // Simpan ke session untuk request selanjutnya
    }
}

// Tentukan path gambar berdasarkan struktur folder
if ($user_image && !empty($user_image)) {
    if (strpos($current_path, '/dashboard/') !== false) {
        $image_path = "../../uploads/profile/" . $user_image;
        // Cek apakah file ada, jika tidak gunakan default
        if (!file_exists($image_path)) {
            $image_path = "../../img/default.svg";
        }
    } else {
        $image_path = "uploads/profile/" . $user_image;
        // Cek apakah file ada, jika tidak gunakan default
        if (!file_exists($image_path)) {
            $image_path = "img/default.svg";
        }
    }
} else {
    // Gunakan gambar default jika tidak ada user_image
    if (strpos($current_path, '/dashboard/') !== false) {
        $image_path = "../../img/default.svg";
    } else {
        $image_path = "img/default.svg";
    }
}

// Fungsi untuk menentukan path profile yang benar
function getProfilePath($role, $current_path) {
    // Deteksi level folder berdasarkan path saat ini
    if (strpos($current_path, '/dashboard/admin/') !== false) {
        $base_path = './';
    } elseif (strpos($current_path, '/dashboard/admin_teknisi/') !== false) {
        $base_path = '../admin_teknisi/';
    } elseif (strpos($current_path, '/dashboard/teknisi/') !== false) {
        $base_path = '../teknisi/';
    } elseif (strpos($current_path, '/dashboard/tenaga/') !== false) {
        $base_path = '../tenaga/';
    } else {
        // Default untuk level root atau lainnya
        $base_path = 'dashboard/';
    }
    
    switch ($role) {
        case 'admin':
            if (strpos($current_path, '/dashboard/admin/') !== false) {
                return 'profile.php';
            }
            return $base_path . 'admin/profile.php';
        case 'admin_teknisi':
            return $base_path . 'profile.php';
        case 'teknisi':
            return $base_path . 'profile.php';
        case 'tenaga_kesehatan':
            return $base_path . 'profile.php';
        default:
            return 'profile.php';
    }
}

$profile_path = getProfilePath($role, $current_path);
?>

<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">
        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    <?= htmlspecialchars($_SESSION['nama']) ?>
                    <br>
                    <small class="text-muted">
                        <?php
                        $role_display = [
                            'admin' => 'Administrator',
                            'admin_teknisi' => 'Admin Teknisi',
                            'teknisi' => 'Teknisi',
                            'tenaga_kesehatan' => 'Tenaga Kesehatan'
                        ];
                        echo $role_display[$role] ?? 'User';
                        ?>
                    </small>
                </span>
                <img class="img-profile rounded-circle" 
                     src="<?= $image_path ?>?<?= time() ?>" 
                     alt="Profile" 
                     style="width: 40px; height: 40px; object-fit: cover;"
                     onerror="this.src='<?= strpos($current_path, '/dashboard/') !== false ? '../../img/default.svg' : 'img/default.svg' ?>'">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="userDropdown">
                <div class="dropdown-header text-center">
                    <img class="img-profile rounded-circle mb-2" 
                         src="<?= $image_path ?>?<?= time() ?>" 
                         alt="Profile" 
                         style="width: 60px; height: 60px; object-fit: cover;"
                         onerror="this.src='<?= strpos($current_path, '/dashboard/') !== false ? '../../img/default.svg' : 'img/default.svg' ?>'">
                    <div class="font-weight-bold"><?= htmlspecialchars($_SESSION['nama']) ?></div>
                    <div class="small text-muted"><?= $role_display[$role] ?? 'User' ?></div>
                </div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?= $profile_path ?>">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav>

<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Konfirmasi Logout</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Apakah Anda yakin ingin keluar dari sistem?</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                <a class="btn btn-primary" href="<?= strpos($current_path, '/dashboard/') !== false ? '../../logout.php' : 'logout.php' ?>">Logout</a>
            </div>
        </div>
    </div>
</div>

<style>
.dropdown-header {
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.35rem 0.35rem 0 0;
}
</style>
