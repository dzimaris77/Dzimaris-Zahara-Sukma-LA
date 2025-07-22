<?php
session_start();
require '../../config/config.php';
require '../../config/telegram.php';

if ($_SESSION['role'] != 'tenaga_kesehatan') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = $conn->real_escape_string($_GET['id']);

// Cek kepemilikan laporan
$check = $conn->query("SELECT * FROM laporan WHERE id_laporan = $id AND id_pelapor = ".$_SESSION['user_id']);
if ($check->num_rows == 0) {
    header("Location: list.php");
    exit();
}

// Proses konfirmasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $conn->real_escape_string($_POST['status']);
    $catatan = $conn->real_escape_string($_POST['catatan']);

    if ($status === 'Dikonfirmasi') {
        // Jika dikonfirmasi, update status menjadi selesai_total
        $conn->query("UPDATE laporan 
                     SET konfirmasi_status = '$status',
                         catatan_verifikasi = '$catatan',
                         status = 'selesai_total'
                     WHERE id_laporan = $id");

        // Notifikasi konfirmasi
        $message = "✅ LAPORAN #$id TELAH DIKONFIRMASI\n";
        $message .= "Status: $status\n";
        $message .= "Catatan: $catatan\n";
        $message .= "Status laporan: SELESAI TOTAL";
        send_telegram($message);

        $_SESSION['success_message'] = "Laporan berhasil dikonfirmasi. Terima kasih atas konfirmasinya!";
        
    } else if ($status === 'Ditolak') {
        // Jika ditolak, buat laporan ulang otomatis
        
        // Ambil data laporan asli
        $laporan_asli = $conn->query("SELECT * FROM laporan WHERE id_laporan = $id")->fetch_assoc();
        
        // Update laporan lama
        $conn->query("UPDATE laporan 
                     SET konfirmasi_status = '$status',
                         catatan_verifikasi = '$catatan',
                         status = 'ditolak_tenaga'
                     WHERE id_laporan = $id");
        
        // Buat laporan baru otomatis
        $deskripsi_baru = "LAPORAN ULANG - Laporan sebelumnya (#$id) ditolak oleh tenaga kesehatan.\n\n";
        $deskripsi_baru .= "Alasan penolakan: " . $catatan . "\n\n";
        $deskripsi_baru .= "Deskripsi masalah asli:\n" . $laporan_asli['deskripsi'];
        
        $stmt = $conn->prepare("INSERT INTO laporan (id_alat, id_ruangan, id_pelapor, deskripsi, status, catatan_admin) 
                               VALUES (?, ?, ?, ?, 'menunggu_verifikasi', ?)");
        
        $catatan_admin = "Laporan ulang otomatis dari laporan #$id yang ditolak tenaga kesehatan. Perlu verifikasi ulang.";
        
        $stmt->bind_param("iiiss", 
            $laporan_asli['id_alat'], 
            $laporan_asli['id_ruangan'], 
            $laporan_asli['id_pelapor'], 
            $deskripsi_baru,
            $catatan_admin
        );
        
        if ($stmt->execute()) {
            $laporan_baru_id = $conn->insert_id;
            
            // Copy foto dari laporan lama ke laporan baru
            $foto_query = "SELECT * FROM laporan_foto WHERE id_laporan = $id";
            $foto_result = $conn->query($foto_query);
            
            while ($foto = $foto_result->fetch_assoc()) {
                $stmt_foto = $conn->prepare("INSERT INTO laporan_foto (id_laporan, jenis, path_foto) VALUES (?, ?, ?)");
                $stmt_foto->bind_param("iss", $laporan_baru_id, $foto['jenis'], $foto['path_foto']);
                $stmt_foto->execute();
            }
            
            // Notifikasi penolakan dan laporan baru
            $message = "❌ LAPORAN #$id DITOLAK OLEH TENAGA KESEHATAN\n";
            $message .= "Alasan: $catatan\n\n";
            $message .= "🔄 LAPORAN ULANG OTOMATIS DIBUAT\n";
            $message .= "ID Laporan Baru: #$laporan_baru_id\n";
            $message .= "Status: Menunggu Verifikasi Admin\n";
            $message .= "Pelapor: " . $_SESSION['nama'];
            send_telegram($message);
            
            $_SESSION['info_message'] = "Laporan ditolak. Sistem telah otomatis membuat laporan ulang (#$laporan_baru_id) untuk ditinjau kembali oleh admin.";
        } else {
            $_SESSION['error_message'] = "Terjadi kesalahan saat membuat laporan ulang. Silakan hubungi administrator.";
        }
    }

    header("Location: list.php");
    exit();
}

$laporan = $check->fetch_assoc();

// Ambil data alat dan ruangan untuk ditampilkan
$alat_query = "SELECT nama_alat, merk, jenis_alat FROM alat WHERE id_alat = " . $laporan['id_alat'];
$alat_data = $conn->query($alat_query)->fetch_assoc();

$ruangan_query = "SELECT nama_ruangan, lantai, sayap FROM ruangan WHERE id_ruangan = " . $laporan['id_ruangan'];
$ruangan_data = $conn->query($ruangan_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Konfirmasi Laporan - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
    .info-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .warning-box {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
    }
    .badge-medis {
        background-color: #1cc88a;
        color: white;
    }
    .badge-non-medis {
        background-color: #36b9cc;
        color: white;
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-check-double text-success mr-2"></i>
                            Konfirmasi Laporan #<?= $id ?>
                        </h1>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-2"></i>Kembali
                        </a>
                    </div>
                    
                    <!-- Informasi Laporan -->
                    <div class="info-section">
                        <h5 class="font-weight-bold mb-3">
                            <i class="fas fa-info-circle mr-2"></i>Informasi Laporan
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Jenis Alat:</strong> 
                                    <span class="badge badge-<?= $alat_data['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?> ml-2">
                                        <i class="fas fa-<?= $alat_data['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?> mr-1"></i>
                                        <?= $alat_data['jenis_alat'] == 'medis' ? 'Alat Medis' : 'Alat Non-Medis' ?>
                                    </span>
                                </p>
                                <p><strong>Nama Alat:</strong> <?= htmlspecialchars($alat_data['nama_alat']) ?></p>
                                <?php if ($alat_data['merk']): ?>
                                <p><strong>Merk:</strong> <?= htmlspecialchars($alat_data['merk']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ruangan:</strong> <?= htmlspecialchars($ruangan_data['nama_ruangan']) ?></p>
                                <p><strong>Lokasi:</strong> Lantai <?= $ruangan_data['lantai'] ?><?= $ruangan_data['sayap'] ? ' - ' . $ruangan_data['sayap'] : '' ?></p>
                                <p><strong>Tanggal Laporan:</strong> <?= date('d/m/Y H:i', strtotime($laporan['tanggal_laporan'])) ?></p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p><strong>Deskripsi Masalah:</strong></p>
                            <div class="bg-white text-dark p-3 rounded">
                                <?= nl2br(htmlspecialchars($laporan['deskripsi'])) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-success text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="fas fa-clipboard-check mr-2"></i>Form Konfirmasi Akhir
                            </h6>
                        </div>
                        
                        <div class="card-body">
                            <div class="warning-box">
                                <h6 class="font-weight-bold text-warning mb-2">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>Perhatian
                                </h6>
                                <ul class="mb-0 text-muted">
                                    <li><strong>Dikonfirmasi:</strong> Laporan akan ditandai sebagai selesai total</li>
                                    <li><strong>Ditolak:</strong> Sistem akan otomatis membuat laporan ulang untuk ditinjau admin kembali</li>
                                </ul>
                            </div>
                            
                            <form method="POST" id="confirmForm">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">
                                        <i class="fas fa-clipboard-check mr-2"></i>Status Konfirmasi
                                    </label>
                                    <select name="status" id="statusSelect" class="form-control border-success" required>
                                        <option value="">-- Pilih Status --</option>
                                        <option value="Dikonfirmasi" class="text-success">✓ Dikonfirmasi - Perbaikan berhasil</option>
                                        <option value="Ditolak" class="text-danger">✗ Ditolak - Masalah belum teratasi</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">
                                        <i class="fas fa-comment mr-2"></i>Catatan <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="catatan" id="catatanText" class="form-control border-success" rows="4" 
                                              placeholder="Berikan catatan atau keterangan..." 
                                              style="resize: none" required></textarea>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Jika ditolak, jelaskan mengapa masalah belum teratasi untuk membantu teknisi
                                    </small>
                                </div>
                                
                                <div id="confirmationBox" class="alert alert-warning" style="display: none;">
                                    <h6 class="font-weight-bold">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>Konfirmasi Tindakan
                                    </h6>
                                    <p id="confirmationText" class="mb-0"></p>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="list.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                                    </a>
                                    <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                                        <i class="fas fa-check-double mr-2"></i>Submit Konfirmasi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../../js/sb-admin-2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Hover effects
        $('.card').hover(
            function() {
                $(this).addClass('shadow-lg').css('transition', '0.3s');
            },
            function() {
                $(this).removeClass('shadow-lg').css('transition', '0.3s');
            }
        );

        // Status change handler
        $('#statusSelect').change(function() {
            const status = $(this).val();
            const confirmationBox = $('#confirmationBox');
            const confirmationText = $('#confirmationText');
            const submitBtn = $('#submitBtn');
            const catatanText = $('#catatanText');
            
            if (status === 'Dikonfirmasi') {
                confirmationText.html('<strong>Dikonfirmasi:</strong> Laporan akan ditandai sebagai selesai total dan tidak dapat diubah lagi.');
                confirmationBox.removeClass('alert-warning alert-danger').addClass('alert-success').show();
                catatanText.attr('placeholder', 'Berikan catatan konfirmasi (opsional)...');
                submitBtn.removeClass('btn-danger').addClass('btn-success').prop('disabled', false);
                
            } else if (status === 'Ditolak') {
                confirmationText.html('<strong>Ditolak:</strong> Sistem akan otomatis membuat laporan ulang untuk ditinjau admin kembali. Pastikan Anda memberikan alasan yang jelas.');
                confirmationBox.removeClass('alert-success alert-warning').addClass('alert-danger').show();
                catatanText.attr('placeholder', 'Jelaskan mengapa perbaikan ditolak (wajib diisi)...');
                submitBtn.removeClass('btn-success').addClass('btn-danger').prop('disabled', false);
                
            } else {
                confirmationBox.hide();
                submitBtn.prop('disabled', true);
            }
        });

        // Form validation
        $('#confirmForm').submit(function(e) {
            const status = $('#statusSelect').val();
            const catatan = $('#catatanText').val().trim();
            
            if (!status) {
                e.preventDefault();
                alert('Silakan pilih status konfirmasi!');
                return false;
            }
            
            if (!catatan) {
                e.preventDefault();
                alert('Catatan wajib diisi!');
                return false;
            }
            
            if (status === 'Ditolak' && catatan.length < 10) {
                e.preventDefault();
                alert('Untuk penolakan, berikan penjelasan minimal 10 karakter!');
                return false;
            }
            
            // Konfirmasi final
            let confirmMessage = '';
            if (status === 'Dikonfirmasi') {
                confirmMessage = 'Apakah Anda yakin ingin mengkonfirmasi bahwa perbaikan telah berhasil?';
            } else {
                confirmMessage = 'Apakah Anda yakin ingin menolak perbaikan ini? Sistem akan membuat laporan ulang otomatis.';
            }
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading
            $('#submitBtn').html('<i class="fas fa-spinner fa-spin mr-2"></i>Memproses...').prop('disabled', true);
        });

        // Character counter for textarea
        $('#catatanText').on('input', function() {
            const length = $(this).val().length;
            const maxLength = 1000;
            
            if (!$(this).next('.char-counter').length) {
                $(this).after('<small class="char-counter text-muted float-right"></small>');
            }
            
            $(this).next('.char-counter').text(length + '/' + maxLength + ' karakter');
            
            if (length > maxLength * 0.9) {
                $(this).next('.char-counter').removeClass('text-muted').addClass('text-warning');
            } else {
                $(this).next('.char-counter').removeClass('text-warning').addClass('text-muted');
            }
        });
    });
    </script>
</body>
</html>
