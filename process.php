<?php
session_start();
require '../../config/config.php';
require '../../config/telegram.php';

if ($_SESSION['role'] != 'teknisi') {
    header("Location: ../../login.php");
    exit();
}

if (isset($_POST['update_status'])) {
    $status = $conn->real_escape_string($_POST['status']);
    $laporan_id = $conn->real_escape_string($_POST['laporan_id']);
    
    // Jika status adalah Selesai, proses laporan perbaikan
    if ($status === 'Selesai') {
        $laporan_perbaikan = $conn->real_escape_string($_POST['laporan_perbaikan']);
        
        // VALIDASI WAJIB: Laporan perbaikan harus diisi
        if (empty($laporan_perbaikan)) {
            $_SESSION['error_message'] = "Laporan perbaikan wajib diisi ketika menyelesaikan tugas!";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
        
        // VALIDASI WAJIB: Dokumentasi perbaikan harus diupload
        if (!isset($_FILES['dokumentasi_perbaikan']) || $_FILES['dokumentasi_perbaikan']['error'] != 0) {
            $_SESSION['error_message'] = "Dokumentasi perbaikan (foto) wajib diupload ketika menyelesaikan tugas!";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
        
        // Handle file upload untuk dokumentasi perbaikan
        $dokumentasi_filename = NULL;
        $upload_dir = "../../uploads/perbaikan/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $temp_name = $_FILES['dokumentasi_perbaikan']['tmp_name'];
        $file_name = 'perbaikan_' . time() . '_' . $_FILES['dokumentasi_perbaikan']['name'];
        $target_file = $upload_dir . $file_name;
        
        // Check file type
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            $_SESSION['error_message'] = "Format file dokumentasi harus JPG, JPEG, atau PNG!";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
        
        // Check file size (max 5MB)
        if ($_FILES['dokumentasi_perbaikan']['size'] > 5242880) {
            $_SESSION['error_message'] = "Ukuran file dokumentasi maksimal 5MB!";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
        
        // Upload file
        if (move_uploaded_file($temp_name, $target_file)) {
            $dokumentasi_filename = $file_name;
            
            // Insert ke tabel laporan_foto untuk dokumentasi perbaikan
            $stmt_foto = $conn->prepare("INSERT INTO laporan_foto (id_laporan, jenis, path_foto) VALUES (?, 'perbaikan', ?)");
            $stmt_foto->bind_param("is", $laporan_id, $dokumentasi_filename);
            $stmt_foto->execute();
        } else {
            $_SESSION['error_message'] = "Gagal mengupload dokumentasi perbaikan!";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
        
        // Update laporan dengan laporan perbaikan dan dokumentasi
        $stmt = $conn->prepare("UPDATE laporan SET teknisi_status = ?, status = 'selesai', laporan_perbaikan = ?, dokumentasi_perbaikan = ? WHERE id_laporan = ?");
        $stmt->bind_param("sssi", $status, $laporan_perbaikan, $dokumentasi_filename, $laporan_id);
        $stmt->execute();
        
        send_telegram("🛠 PERBAIKAN SELESAI #$laporan_id\nTeknisi: ".$_SESSION['nama']."\nLaporan: ".substr($laporan_perbaikan, 0, 100)."...\n📸 Dokumentasi: Tersedia");
        
        $_SESSION['success_message'] = "Perbaikan berhasil diselesaikan dengan laporan dan dokumentasi lengkap!";
        
    } else {
        // Update status biasa (tanpa laporan dan dokumentasi)
        $stmt = $conn->prepare("UPDATE laporan SET teknisi_status = ? WHERE id_laporan = ?");
        $stmt->bind_param("si", $status, $laporan_id);
        $stmt->execute();
        
        send_telegram("🛠 STATUS UPDATE #$laporan_id\nStatus: $status\nTeknisi: ".$_SESSION['nama']);
        
        $_SESSION['success_message'] = "Status berhasil diperbarui!";
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Query untuk mendapatkan laporan dengan jenis alat
$query = "SELECT l.*, a.nama_alat, a.jenis_alat, r.nama_ruangan 
          FROM laporan l
          JOIN alat a ON l.id_alat = a.id_alat
          JOIN ruangan r ON l.id_ruangan = r.id_ruangan
          WHERE l.id_teknisi=".$_SESSION['user_id']." 
          ORDER BY l.tanggal_laporan DESC";

$laporan = $conn->query($query);
$total_laporan = $laporan->num_rows;

// Hitung statistik berdasarkan jenis alat
$stats_medis = $conn->query("SELECT COUNT(*) as count FROM laporan l JOIN alat a ON l.id_alat = a.id_alat WHERE l.id_teknisi=".$_SESSION['user_id']." AND a.jenis_alat='medis'")->fetch_assoc()['count'];
$stats_non_medis = $conn->query("SELECT COUNT(*) as count FROM laporan l JOIN alat a ON l.id_alat = a.id_alat WHERE l.id_teknisi=".$_SESSION['user_id']." AND a.jenis_alat='non_medis'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proses Perbaikan - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
    .status-badge {
        min-width: 120px;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.9em;
    }
    .status-select {
        border: 2px solid #1cc88a;
        border-radius: 8px;
        transition: all 0.3s;
    }
    .status-select:focus {
        box-shadow: 0 0 0 0.2rem rgba(28, 200, 138, 0.25);
    }
    .alert-flash {
        animation: fadeOut 4s forwards;
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        min-width: 300px;
    }
    @keyframes fadeOut {
        0% { opacity: 1; }
        70% { opacity: 1; }
        100% { opacity: 0; visibility: hidden; }
    }
    .stats-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-2px);
    }
    .stats-card.medis {
        border-left-color: #1cc88a;
    }
    .stats-card.non-medis {
        border-left-color: #36b9cc;
    }
    .stats-card.total {
        border-left-color: #f6c23e;
    }
    .badge-medis {
        background-color: #1cc88a;
        color: white;
    }
    .badge-non-medis {
        background-color: #36b9cc;
        color: white;
    }
    .laporan-form {
        background: linear-gradient(135deg, #f8f9fc 0%, #e3f2fd 100%);
        border: 3px solid #1cc88a;
        border-radius: 15px;
        padding: 25px;
        margin-top: 15px;
        display: none;
        box-shadow: 0 8px 25px rgba(28, 200, 138, 0.15);
    }
    .required-field {
        color: #e74a3b;
        font-weight: bold;
        font-size: 1.1em;
    }
    .required-alert {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border: 2px solid #ffc107;
        color: #856404;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2);
    }
    .form-control-file {
        border: 2px dashed #1cc88a;
        padding: 15px;
        border-radius: 10px;
        background: #f8f9fc;
        transition: all 0.3s;
    }
    .form-control-file:hover {
        background: #e3f2fd;
        border-color: #17a2b8;
    }
    .mandatory-section {
        background: #fff;
        border: 2px solid #dc3545;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .mandatory-header {
        color: #dc3545;
        font-weight: bold;
        font-size: 1.2em;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    .mandatory-header i {
        margin-right: 10px;
        font-size: 1.3em;
    }
    </style>
</head>

<body id="page-top">
    <!-- Flash Messages -->
    <?php if(isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-flash shadow-lg">
        <i class="fas fa-check-circle mr-2"></i> <?= $_SESSION['success_message'] ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-flash shadow-lg">
        <i class="fas fa-exclamation-circle mr-2"></i> <?= $_SESSION['error_message'] ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/topbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Proses Perbaikan</h1>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stats-card total shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Penugasan</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_laporan ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stats-card medis shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Alat Medis</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_medis ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-stethoscope fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stats-card non-medis shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Alat Non-Medis</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_non_medis ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chair fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow-lg border-left-success mb-4">
                                <div class="card-header py-3 bg-white d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-success">
                                        <i class="fas fa-tools"></i> Daftar Penugasan (<?= $total_laporan ?>)
                                    </h6>
                                </div>
                                
                                <div class="card-body">
                                    <?php if($total_laporan > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Jenis</th>
                                                    <th>Alat</th>
                                                    <th>Ruangan</th>
                                                    <th>Deskripsi</th>
                                                    <th>Tanggal</th>
                                                    <th>Status Laporan</th>
                                                    <th>Status Pekerjaan</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $laporan->data_seek(0); // Reset pointer
                                                while($row = $laporan->fetch_assoc()): 
                                                ?>
                                                <tr>
                                                    <td class="font-weight-bold">#<?= $row['id_laporan'] ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $row['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?>">
                                                            <i class="fas fa-<?= $row['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?> mr-1"></i>
                                                            <?= $row['jenis_alat'] == 'medis' ? 'Medis' : 'Non-Medis' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['nama_alat']) ?></td>
                                                    <td><?= htmlspecialchars($row['nama_ruangan']) ?></td>
                                                    <td>
                                                        <span data-toggle="tooltip" title="<?= htmlspecialchars($row['deskripsi']) ?>">
                                                            <?= substr(htmlspecialchars($row['deskripsi']), 0, 30) ?><?= strlen($row['deskripsi']) > 30 ? '...' : '' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal_laporan'])) ?></td>
                                                    <td>
                                                        <?php
                                                        $status_labels = [
                                                            'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                                            'ditolak_admin' => 'Ditolak Admin',
                                                            'menunggu_penugasan' => 'Menunggu Penugasan', 
                                                            'dalam_perbaikan' => 'Dalam Perbaikan',
                                                            'selesai' => 'Selesai',
                                                            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                                                            'ditolak_tenaga' => 'Ditolak Tenaga',
                                                            'selesai_total' => 'Selesai Total'
                                                        ];
                                                        
                                                        $status_colors = [
                                                            'menunggu_verifikasi' => 'secondary',
                                                            'ditolak_admin' => 'danger',
                                                            'menunggu_penugasan' => 'info',
                                                            'dalam_perbaikan' => 'primary',
                                                            'selesai' => 'success',
                                                            'menunggu_konfirmasi' => 'warning',
                                                            'ditolak_tenaga' => 'danger',
                                                            'selesai_total' => 'dark'
                                                        ];
                                                        
                                                        $display_status = $status_labels[$row['status']] ?? $row['status'];
                                                        $display_color = $status_colors[$row['status']] ?? 'secondary';
                                                        ?>
                                                        <span class="badge badge-<?= $display_color ?>">
                                                            <?= $display_status ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $teknisi_status = $row['teknisi_status'] ?? 'Belum Diproses';
                                                        $badge_color = match($teknisi_status) {
                                                            'Laporan Diterima' => 'success',
                                                            'Sedang Diperiksa' => 'warning',
                                                            'Sedang Diperbaiki' => 'primary',
                                                            'Selesai' => 'info',
                                                            default => 'secondary'
                                                        };
                                                        ?>
                                                        <span class="badge badge-<?= $badge_color ?> status-badge">
                                                            <?= $teknisi_status ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if($row['status'] == 'menunggu_penugasan' || $row['status'] == 'dalam_perbaikan'): ?>
                                                        <div id="form-container-<?= $row['id_laporan'] ?>">
                                                            <select class="form-control status-select" 
                                                                    onchange="handleStatusChange(this, <?= $row['id_laporan'] ?>)"
                                                                    data-toggle="tooltip" 
                                                                    title="Ubah status">
                                                                <option value="" disabled selected>Pilih Status</option>
                                                                <option value="Laporan Diterima">📥 Laporan Diterima</option>
                                                                <option value="Sedang Diperiksa">🔍 Sedang Diperiksa</option>
                                                                <option value="Sedang Diperbaiki">🛠 Sedang Diperbaiki</option>
                                                                <option value="Selesai">✅ Selesai</option>
                                                            </select>
                                                            
                                                            <!-- Form Laporan Perbaikan -->
                                                            <div id="laporan-form-<?= $row['id_laporan'] ?>" class="laporan-form">
                                                                <!-- Alert wajib diisi -->
                                                                <div class="required-alert">
                                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                                    <strong>PERHATIAN PENTING:</strong> Untuk menyelesaikan perbaikan, Anda <strong>WAJIB</strong> mengisi:
                                                                    <ul class="mt-2 mb-0">
                                                                        <li><strong>Laporan Perbaikan</strong> (deskripsi detail)</li>
                                                                        <li><strong>Dokumentasi Foto</strong> (bukti hasil perbaikan)</li>
                                                                    </ul>
                                                                </div>
                                                                
                                                                <form method="POST" enctype="multipart/form-data" id="form-selesai-<?= $row['id_laporan'] ?>">
                                                                    <input type="hidden" name="laporan_id" value="<?= $row['id_laporan'] ?>">
                                                                    <input type="hidden" name="status" value="Selesai">
                                                                    
                                                                    <!-- Laporan Perbaikan Section -->
                                                                    <div class="mandatory-section">
                                                                        <div class="mandatory-header">
                                                                            <i class="fas fa-clipboard-check"></i>
                                                                            Laporan Perbaikan <span class="required-field">*WAJIB DIISI</span>
                                                                        </div>
                                                                        
                                                                        <div class="form-group">
                                                                            <label for="laporan_perbaikan_<?= $row['id_laporan'] ?>" class="font-weight-bold">
                                                                                Deskripsi Detail Perbaikan <span class="required-field">*WAJIB</span>
                                                                            </label>
                                                                            <textarea name="laporan_perbaikan" 
                                                                                      id="laporan_perbaikan_<?= $row['id_laporan'] ?>"
                                                                                      class="form-control" 
                                                                                      rows="5" 
                                                                                      placeholder="WAJIB DIISI - Jelaskan secara detail:&#10;&#10;1. MASALAH YANG DITEMUKAN:&#10;   - Kondisi awal alat&#10;   - Kerusakan yang teridentifikasi&#10;&#10;2. LANGKAH PERBAIKAN:&#10;   - Metode yang digunakan&#10;   - Komponen yang diganti/diperbaiki&#10;&#10;3. HASIL PERBAIKAN:&#10;   - Kondisi setelah perbaikan&#10;   - Pengujian yang dilakukan&#10;&#10;4. SARAN PEMELIHARAAN:&#10;   - Rekomendasi perawatan rutin"
                                                                                      required
                                                                                      style="border: 2px solid #dc3545;"></textarea>
                                                                            <small class="form-text text-danger">
                                                                                <i class="fas fa-info-circle mr-1"></i>
                                                                                <strong>MANDATORY:</strong> Laporan harus detail dan lengkap untuk dokumentasi dan referensi
                                                                            </small>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <!-- Dokumentasi Section -->
                                                                    <div class="mandatory-section">
                                                                        <div class="mandatory-header">
                                                                            <i class="fas fa-camera"></i>
                                                                            Dokumentasi Perbaikan <span class="required-field">*WAJIB UPLOAD</span>
                                                                        </div>
                                                                        
                                                                        <div class="form-group">
                                                                            <label for="dokumentasi_perbaikan_<?= $row['id_laporan'] ?>" class="font-weight-bold">
                                                                                Upload Foto Hasil Perbaikan <span class="required-field">*WAJIB</span>
                                                                            </label>
                                                                            <input type="file" 
                                                                                   name="dokumentasi_perbaikan" 
                                                                                   id="dokumentasi_perbaikan_<?= $row['id_laporan'] ?>"
                                                                                   class="form-control-file" 
                                                                                   accept="image/jpeg,image/png,image/jpg"
                                                                                   required
                                                                                   onchange="previewImage(this, <?= $row['id_laporan'] ?>)">
                                                                            <small class="form-text text-danger">
                                                                                <i class="fas fa-camera mr-1"></i>
                                                                                <strong>MANDATORY:</strong> Upload foto hasil perbaikan (JPG, JPEG, PNG - Maksimal 5MB)
                                                                            </small>
                                                                            
                                                                            <!-- Preview Image -->
                                                                            <div id="image-preview-<?= $row['id_laporan'] ?>" class="mt-3" style="display: none;">
                                                                                <p class="font-weight-bold text-success">Preview Dokumentasi:</p>
                                                                                <img id="preview-img-<?= $row['id_laporan'] ?>" src="" alt="Preview" class="img-fluid rounded shadow" style="max-height: 200px;">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="d-flex justify-content-end">
                                                                        <button type="button" 
                                                                                class="btn btn-secondary mr-3" 
                                                                                onclick="cancelLaporan(<?= $row['id_laporan'] ?>)">
                                                                            <i class="fas fa-times mr-1"></i>Batal
                                                                        </button>
                                                                        <button type="submit" 
                                                                                name="update_status" 
                                                                                class="btn btn-success btn-lg"
                                                                                onclick="return validateForm(<?= $row['id_laporan'] ?>)">
                                                                            <i class="fas fa-check mr-1"></i>Selesaikan Perbaikan
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php elseif($row['status'] == 'selesai'): ?>
                                                        <span class="text-success">
                                                            <i class="fas fa-check-circle"></i> Laporan selesai
                                                        </span>
                                                        <?php elseif($row['status'] == 'menunggu_konfirmasi'): ?>
                                                        <span class="text-warning">
                                                            <i class="fas fa-clock"></i> Menunggu konfirmasi
                                                        </span>
                                                        <?php else: ?>
                                                        <span class="text-muted">
                                                            <i class="fas fa-info-circle"></i> Status: <?= $status_labels[$row['status']] ?? $row['status'] ?>
                                                        </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-inbox fa-5x text-muted mb-3"></i>
                                        <h5 class="text-muted">Belum Ada Penugasan</h5>
                                        <p class="text-muted">Belum ada laporan yang ditugaskan kepada Anda.</p>
                                    </div>
                                    <?php endif; ?>
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
        // Inisialisasi tooltip
        $('[data-toggle="tooltip"]').tooltip();
        
        // Efek hover pada baris tabel
        $('.table-hover tbody tr').hover(
            function() {
                $(this).css('transform', 'translateX(5px)');
                $(this).css('transition', 'transform 0.2s ease');
            },
            function() {
                $(this).css('transform', 'none');
            }
        );
        
        // Auto-hide flash message
        setTimeout(function() {
            $('.alert-flash').fadeOut('slow');
        }, 4000);

        // Hover effects for stats cards
        $('.stats-card').hover(
            function() {
                $(this).addClass('shadow-lg');
            },
            function() {
                $(this).removeClass('shadow-lg');
            }
        );
    });

    function handleStatusChange(selectElement, laporanId) {
        const selectedValue = selectElement.value;
        
        if (selectedValue === 'Selesai') {
            // Tampilkan form laporan perbaikan
            document.getElementById('laporan-form-' + laporanId).style.display = 'block';
            selectElement.disabled = true;
            
            // Scroll ke form
            document.getElementById('laporan-form-' + laporanId).scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        } else if (selectedValue !== '') {
            // Submit form untuk status selain Selesai
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="laporan_id" value="${laporanId}">
                <input type="hidden" name="status" value="${selectedValue}">
                <input type="hidden" name="update_status" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function cancelLaporan(laporanId) {
        // Sembunyikan form dan reset select
        document.getElementById('laporan-form-' + laporanId).style.display = 'none';
        const selectElement = document.querySelector(`#form-container-${laporanId} select`);
        selectElement.value = '';
        selectElement.disabled = false;
        
        // Reset form
        document.getElementById('form-selesai-' + laporanId).reset();
        document.getElementById('image-preview-' + laporanId).style.display = 'none';
    }

    // Preview image function
    function previewImage(input, laporanId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-img-' + laporanId).src = e.target.result;
                document.getElementById('image-preview-' + laporanId).style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Validasi form sebelum submit dengan penekanan pada kedua field wajib
    function validateForm(laporanId) {
        const laporan = document.getElementById('laporan_perbaikan_' + laporanId).value.trim();
        const dokumentasi = document.getElementById('dokumentasi_perbaikan_' + laporanId).files.length;
        
        // Validasi laporan perbaikan
        if (laporan === '') {
            alert('❌ LAPORAN PERBAIKAN WAJIB DIISI!\n\nAnda harus mengisi deskripsi detail perbaikan sebelum menyelesaikan tugas.');
            document.getElementById('laporan_perbaikan_' + laporanId).focus();
            document.getElementById('laporan_perbaikan_' + laporanId).style.borderColor = '#dc3545';
            return false;
        }
        
        if (laporan.length < 50) {
            alert('❌ LAPORAN PERBAIKAN TERLALU SINGKAT!\n\nLaporan harus minimal 50 karakter dan berisi detail lengkap perbaikan.');
            document.getElementById('laporan_perbaikan_' + laporanId).focus();
            return false;
        }
        
        // Validasi dokumentasi perbaikan
        if (dokumentasi === 0) {
            alert('❌ DOKUMENTASI PERBAIKAN WAJIB DIUPLOAD!\n\nAnda harus mengupload foto hasil perbaikan sebagai bukti.');
            document.getElementById('dokumentasi_perbaikan_' + laporanId).focus();
            return false;
        }
        
        // Validasi ukuran file
        const file = document.getElementById('dokumentasi_perbaikan_' + laporanId).files[0];
        if (file.size > 5242880) { // 5MB
            alert('❌ UKURAN FILE TERLALU BESAR!\n\nUkuran file maksimal 5MB. Silakan kompres atau pilih file lain.');
            return false;
        }
        
        // Validasi tipe file
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            alert('❌ FORMAT FILE TIDAK VALID!\n\nFormat file harus JPG, JPEG, atau PNG.');
            return false;
        }
        
        // Konfirmasi final
        return confirm('✅ KONFIRMASI PENYELESAIAN PERBAIKAN\n\nApakah Anda yakin ingin menyelesaikan perbaikan ini?\n\n• Laporan perbaikan: Sudah diisi\n• Dokumentasi foto: Sudah diupload\n\nData tidak dapat diubah setelah disimpan.');
    }
    </script>
</body>
</html>
