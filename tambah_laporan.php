<?php
session_start();
require '../../config/config.php';
require '../../config/telegram.php';

if ($_SESSION['role'] != 'tenaga_kesehatan') {
    header("Location: ../../login.php");
    exit();
}

// Query alat dengan jenis_alat
$alat = $conn->query("SELECT * FROM alat ORDER BY jenis_alat, nama_alat");
$ruangan = $conn->query("SELECT * FROM ruangan ORDER BY lantai, nama_ruangan");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_alat = $conn->real_escape_string($_POST['alat']);
    $id_ruangan = $conn->real_escape_string($_POST['ruangan']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
    $id_pelapor = $_SESSION['user_id'];

    // Insert laporan
    $stmt = $conn->prepare("INSERT INTO laporan (id_alat, id_ruangan, id_pelapor, deskripsi) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $id_alat, $id_ruangan, $id_pelapor, $deskripsi);
    $stmt->execute();
    $laporan_id = $stmt->insert_id;

    // Upload foto
    foreach ($_FILES['foto']['tmp_name'] as $key => $tmp_name) {
        if (!empty($tmp_name)) {
            $filename = 'bukti_'.time().'_'.$key.'.jpg';
            move_uploaded_file($tmp_name, '../../uploads/bukti/'.$filename);
            $conn->query("INSERT INTO laporan_foto (id_laporan, jenis, path_foto) VALUES ($laporan_id, 'bukti', '$filename')");
        }
    }

    // Notifikasi ke Admin
    send_telegram("🚨 LAPORAN BARU #$laporan_id\nPelapor: ".$_SESSION['nama']."\nStatus: Menunggu Verifikasi");

    header("Location: list.php?success=1");
    exit();
}

// Hitung statistik alat
$stats_medis = $conn->query("SELECT COUNT(*) as count FROM alat WHERE jenis_alat = 'medis'")->fetch_assoc()['count'];
$stats_non_medis = $conn->query("SELECT COUNT(*) as count FROM alat WHERE jenis_alat = 'non_medis'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buat Laporan - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
    .file-upload {
        border: 2px dashed #1cc88a;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        transition: 0.3s;
    }
    .file-upload:hover {
        background-color: #f8f9fc;
        transform: translateY(-2px);
    }
    .form-control-border {
        border: 1px solid #1cc88a !important;
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
    .alat-option {
        padding: 8px;
        border-bottom: 1px solid #eee;
    }
    .alat-option:last-child {
        border-bottom: none;
    }
    .badge-medis {
        background-color: #1cc88a;
        color: white;
        font-size: 0.7em;
        margin-left: 5px;
    }
    .badge-non-medis {
        background-color: #36b9cc;
        color: white;
        font-size: 0.7em;
        margin-left: 5px;
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
                    <h1 class="h3 mb-4 text-gray-800">Buat Laporan Baru</h1>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card stats-card medis shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Alat Medis Tersedia</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_medis ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-stethoscope fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6 col-md-6 mb-4">
                            <div class="card stats-card non-medis shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Alat Non-Medis Tersedia</div>
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
                    
                    <div class="card shadow-lg border-left-success">
                        <div class="card-header py-3 bg-white">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-plus-circle"></i> Form Laporan Kerusakan
                            </h6>
                        </div>
                        
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-dark">
                                                <i class="fas fa-tools mr-2"></i>Pilih Alat
                                            </label>
                                            <select name="alat" class="form-control form-control-border" required>
                                                <option value="">-- Pilih Alat --</option>
                                                <?php 
                                                $current_jenis = '';
                                                $alat->data_seek(0); // Reset pointer
                                                while($row = $alat->fetch_assoc()): 
                                                    if ($current_jenis != $row['jenis_alat']):
                                                        if ($current_jenis != '') echo '</optgroup>';
                                                        $jenis_label = $row['jenis_alat'] == 'medis' ? 'ALAT MEDIS' : 'ALAT NON-MEDIS';
                                                        echo '<optgroup label="'.$jenis_label.'">';
                                                        $current_jenis = $row['jenis_alat'];
                                                    endif;
                                                ?>
                                                <option value="<?= $row['id_alat'] ?>" class="alat-option">
                                                    <i class="fas fa-<?= $row['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?>"></i>
                                                    <?= $row['nama_alat'] ?> 
                                                    <?= $row['merk'] ? "(".$row['merk'].")" : '' ?>
                                                    <?= $row['no_seri'] ? " - SN: ".$row['no_seri'] : '' ?>
                                                    <span class="badge badge-<?= $row['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?>">
                                                        <?= $row['jenis_alat'] == 'medis' ? 'Medis' : 'Non-Medis' ?>
                                                    </span>
                                                </option>
                                                <?php endwhile; ?>
                                                <?php if ($current_jenis != '') echo '</optgroup>'; ?>
                                            </select>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Alat dikelompokkan berdasarkan jenis: Medis dan Non-Medis
                                            </small>
                                        </div>

                                        <div class="form-group">
                                            <label class="font-weight-bold text-dark">
                                                <i class="fas fa-map-marker-alt mr-2"></i>Lokasi Ruangan
                                            </label>
                                            <select name="ruangan" class="form-control form-control-border" required>
                                                <option value="">-- Pilih Ruangan --</option>
                                                <?php 
                                                $ruangan->data_seek(0); // Reset pointer
                                                while($row = $ruangan->fetch_assoc()): 
                                                ?>
                                                <option value="<?= $row['id_ruangan'] ?>">
                                                    <i class="fas fa-door-open"></i>
                                                    <?= $row['nama_ruangan'] ?> 
                                                    (<?= $row['lantai'] ?><?= $row['sayap'] ? ' - '.$row['sayap'] : '' ?>)
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Pilih lokasi dimana alat berada
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold text-dark">
                                                <i class="fas fa-clipboard-list mr-2"></i>Deskripsi Kerusakan
                                            </label>
                                            <textarea name="deskripsi" 
                                                      class="form-control form-control-border" 
                                                      rows="5" 
                                                      placeholder="Jelaskan secara detail kerusakan yang terjadi...&#10;&#10;Contoh:&#10;- Alat tidak menyala&#10;- Layar berkedip&#10;- Suara tidak keluar&#10;- Tombol tidak berfungsi"
                                                      required></textarea>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Berikan deskripsi yang jelas dan detail untuk mempercepat proses perbaikan
                                            </small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="font-weight-bold text-dark">
                                                <i class="fas fa-camera mr-2"></i>Upload Bukti Foto
                                            </label>
                                            <div class="file-upload">
                                                <div class="mb-3">
                                                    <i class="fas fa-camera fa-3x text-success"></i>
                                                </div>
                                                <input type="file" 
                                                       name="foto[]" 
                                                       class="form-control-file" 
                                                       multiple 
                                                       accept="image/*" 
                                                       required>
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                        <strong>Maksimal 5 file</strong> (JPEG/PNG/JPG)<br>
                                                        <i class="fas fa-lightbulb mr-1"></i>
                                                        Foto yang jelas akan membantu teknisi memahami masalah
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Info Panel -->
                                <div class="alert alert-info" role="alert">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-info-circle mr-2"></i>Informasi Penting
                                    </h6>
                                    <hr>
                                    <p class="mb-0">
                                        <i class="fas fa-check mr-2"></i>Pastikan semua data sudah benar sebelum mengirim laporan<br>
                                        <i class="fas fa-clock mr-2"></i>Laporan akan diverifikasi oleh admin dalam 1x24 jam<br>
                                        <i class="fas fa-bell mr-2"></i>Anda akan mendapat notifikasi melalui sistem tentang status laporan
                                    </p>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button type="reset" class="btn btn-secondary mr-2">
                                        <i class="fas fa-undo"></i> Reset Form
                                    </button>
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-paper-plane"></i> Kirim Laporan
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
    <script src="../../js/sb-admin-2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Efek hover pada card
        $('.card').hover(
            function() {
                $(this).addClass('shadow-lg').css('transition', '0.3s');
            },
            function() {
                $(this).removeClass('shadow-lg').css('transition', '0.3s');
            }
        );

        // Validasi file upload
        $('input[type="file"]').change(function() {
            const files = this.files;
            if(files.length > 5) {
                alert('Maksimal upload 5 file!');
                this.value = '';
                return;
            }

            // Validasi ukuran file (maksimal 5MB per file)
            for(let i = 0; i < files.length; i++) {
                if(files[i].size > 5 * 1024 * 1024) {
                    alert('Ukuran file ' + files[i].name + ' terlalu besar! Maksimal 5MB per file.');
                    this.value = '';
                    return;
                }
            }

            // Preview file names
            let fileNames = [];
            for(let i = 0; i < files.length; i++) {
                fileNames.push(files[i].name);
            }
            
            if(fileNames.length > 0) {
                $('.file-upload').append('<div class="mt-2 text-success"><small><i class="fas fa-check mr-1"></i>File dipilih: ' + fileNames.join(', ') + '</small></div>');
            }
        });

        // Enhanced form validation
        $('form').submit(function(e) {
            const alat = $('select[name="alat"]').val();
            const ruangan = $('select[name="ruangan"]').val();
            const deskripsi = $('textarea[name="deskripsi"]').val().trim();
            const foto = $('input[name="foto[]"]')[0].files;

            if(!alat) {
                alert('Silakan pilih alat yang bermasalah!');
                e.preventDefault();
                return false;
            }

            if(!ruangan) {
                alert('Silakan pilih lokasi ruangan!');
                e.preventDefault();
                return false;
            }

            if(deskripsi.length < 10) {
                alert('Deskripsi kerusakan minimal 10 karakter!');
                e.preventDefault();
                return false;
            }

            if(foto.length === 0) {
                alert('Silakan upload minimal 1 foto bukti!');
                e.preventDefault();
                return false;
            }

            // Konfirmasi sebelum submit
            if(!confirm('Apakah Anda yakin ingin mengirim laporan ini?')) {
                e.preventDefault();
                return false;
            }

            // Show loading
            $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> Mengirim...').prop('disabled', true);
        });

        // Auto-resize textarea
        $('textarea').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Character counter for description
        $('textarea[name="deskripsi"]').on('input', function() {
            const maxLength = 1000;
            const currentLength = $(this).val().length;
            const remaining = maxLength - currentLength;
            
            if(!$(this).next('.char-counter').length) {
                $(this).after('<small class="char-counter text-muted"></small>');
            }
            
            $(this).next('.char-counter').text(currentLength + '/' + maxLength + ' karakter');
            
            if(remaining < 50) {
                $(this).next('.char-counter').removeClass('text-muted').addClass('text-warning');
            } else {
                $(this).next('.char-counter').removeClass('text-warning').addClass('text-muted');
            }
        });
    });
    </script>
</body>
</html>
