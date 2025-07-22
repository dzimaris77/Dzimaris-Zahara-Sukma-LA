<?php
session_start();
require '../../config/config.php';

if ($_SESSION['role'] != 'admin_teknisi') {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: assign.php");
    exit();
}

$laporan_id = $_GET['id'];

// Query untuk mendapatkan detail laporan lengkap dengan jenis alat
$query = "
    SELECT l.*, a.nama_alat, a.merk, a.type_model, a.no_seri, a.jenis_alat, 
           r.nama_ruangan, r.lantai, r.sayap,
           p.nama as nama_pelapor, p.email as email_pelapor, p.no_telepon as telepon_pelapor,
           t.nama as nama_teknisi, t.email as email_teknisi, t.no_telepon as telepon_teknisi,
           l.laporan_perbaikan, l.dokumentasi_perbaikan, l.catatan_admin, l.catatan_teknisi, l.catatan_verifikasi
    FROM laporan l
    JOIN alat a ON l.id_alat = a.id_alat
    JOIN ruangan r ON l.id_ruangan = r.id_ruangan
    JOIN users p ON l.id_pelapor = p.id_user
    LEFT JOIN users t ON l.id_teknisi = t.id_user
    WHERE l.id_laporan = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $laporan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: assign.php");
    exit();
}

$laporan = $result->fetch_assoc();

// Query untuk mendapatkan foto-foto laporan
$foto_query = "SELECT * FROM laporan_foto WHERE id_laporan = ? ORDER BY jenis, id_laporan_foto";
$foto_stmt = $conn->prepare($foto_query);
$foto_stmt->bind_param("i", $laporan_id);
$foto_stmt->execute();
$foto_result = $foto_stmt->get_result();

$fotos = [];
while ($foto = $foto_result->fetch_assoc()) {
    $fotos[$foto['jenis']][] = $foto;
}

// Query untuk mendapatkan daftar teknisi
$teknisi_query = "SELECT * FROM users WHERE role='teknisi' ORDER BY nama";
$teknisi_result = $conn->query($teknisi_query);

// Proses penugasan langsung dari halaman detail
if (isset($_POST['assign_teknisi'])) {
    $teknisi_id = $conn->real_escape_string($_POST['teknisi_id']);
    
    $update_query = "UPDATE laporan 
                    SET id_teknisi = '$teknisi_id', 
                        status = 'dalam_perbaikan', 
                        teknisi_status = 'Laporan Diterima' 
                    WHERE id_laporan = '$laporan_id'";
    
    if ($conn->query($update_query)) {
        $teknisi = $conn->query("SELECT * FROM users WHERE id_user='$teknisi_id'")->fetch_assoc();
        
        require '../../config/telegram.php';
        send_telegram("🔧 TUGAS BARU #$laporan_id\nTeknisi: ".$teknisi['nama']."\nStatus: Laporan Diterima");
        
        $_SESSION['success'] = "Penugasan berhasil dikirim ke ".$teknisi['nama']."!";
        header("Location: assign.php");
        exit();
    } else {
        $_SESSION['error'] = "Gagal melakukan penugasan!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Laporan #<?= $laporan_id ?> - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        .timeline {
            border-left: 3px solid #1cc88a;
            position: relative;
            padding-left: 2rem;
            margin-left: 1rem;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        .timeline-marker {
            position: absolute;
            left: -1.3rem;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #1cc88a;
            border: 3px solid white;
        }
        .timeline-content {
            padding: 1rem;
            background: #f8f9fc;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 0.35rem rgba(0,0,0,.03);
        }
        .img-thumbnail {
            border: 2px solid #d1d3e2;
            transition: transform 0.3s;
            cursor: pointer;
        }
        .img-thumbnail:hover {
            transform: scale(1.05);
        }
        .badge-medis {
            background-color: #1cc88a;
            color: white;
        }
        .badge-non-medis {
            background-color: #36b9cc;
            color: white;
        }
        .catatan-box {
            background: #f8f9fc;
            border-left: 4px solid #5a5c69;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 0.15rem 0.35rem rgba(0,0,0,.03);
        }
        .catatan-admin {
            border-left-color: #e74a3b;
            background: #fdf2f2;
        }
        .detail-perbaikan {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .status-progress {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            position: relative;
        }
        .status-step {
            background: #e9ecef;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
        }
        .status-step.active {
            background: #1cc88a;
            color: white;
        }
        .status-step.completed {
            background: #28a745;
            color: white;
        }
        .status-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        .status-line.completed {
            background: #28a745;
        }
        .info-card {
            transition: transform 0.2s;
        }
        .info-card:hover {
            transform: translateY(-5px);
        }
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .photo-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .photo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .photo-label {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
            padding: 10px;
            font-size: 0.8em;
            text-align: center;
        }
        .action-buttons {
            position: sticky;
            top: 20px;
            z-index: 100;
        }
        .priority-indicator {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7em;
            font-weight: bold;
        }
        .priority-high {
            background: #dc3545;
            color: white;
            animation: pulse 2s infinite;
        }
        .priority-normal {
            background: #17a2b8;
            color: white;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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
                            <i class="fas fa-clipboard-list text-primary mr-2"></i>
                            Detail Laporan <span class="text-success">#<?= $laporan_id ?></span>
                            <?php if ($laporan['jenis_alat'] == 'medis'): ?>
                                <span class="priority-indicator priority-high" title="Prioritas Tinggi">!</span>
                            <?php else: ?>
                                <span class="priority-indicator priority-normal" title="Prioritas Normal">N</span>
                            <?php endif; ?>
                        </h1>
                        <div>
                            <a href="assign.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Penugasan
                            </a>
                            <button onclick="window.print()" class="btn btn-info">
                                <i class="fas fa-print mr-2"></i>Cetak
                            </button>
                        </div>
                    </div>
                    
                    <!-- Status Progress -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <h6 class="font-weight-bold text-primary mb-3">
                                <i class="fas fa-tasks mr-2"></i>Progress Laporan
                            </h6>
                            <div class="status-progress">
                                <div class="status-line <?= in_array($laporan['status'], ['menunggu_penugasan', 'dalam_perbaikan', 'selesai', 'selesai_total']) ? 'completed' : '' ?>"></div>
                                
                                <div class="status-step completed" title="Laporan Dibuat">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="status-step completed" title="Verifikasi Admin">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="status-step <?= in_array($laporan['status'], ['dalam_perbaikan', 'selesai', 'selesai_total']) ? 'completed' : ($laporan['status'] == 'menunggu_penugasan' ? 'active' : '') ?>" title="Penugasan Teknisi">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <div class="status-step <?= in_array($laporan['status'], ['selesai', 'selesai_total']) ? 'completed' : ($laporan['status'] == 'dalam_perbaikan' ? 'active' : '') ?>" title="Perbaikan">
                                    <i class="fas fa-tools"></i>
                                </div>
                                <div class="status-step <?= $laporan['status'] == 'selesai_total' ? 'completed' : ($laporan['status'] == 'selesai' ? 'active' : '') ?>" title="Selesai">
                                    <i class="fas fa-flag-checkered"></i>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between text-sm text-muted mt-2">
                                <span>Laporan</span>
                                <span>Verifikasi</span>
                                <span>Penugasan</span>
                                <span>Perbaikan</span>
                                <span>Selesai</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Informasi Utama -->
                        <div class="col-lg-8">
                            <div class="card shadow mb-4 info-card">
                                <div class="card-header py-3 bg-primary text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-info-circle mr-2"></i>Informasi Laporan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th width="40%">ID Laporan</th>
                                                    <td><span class="badge badge-primary p-2">#<?= $laporan['id_laporan'] ?></span></td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Laporan</th>
                                                    <td><?= date('d/m/Y H:i', strtotime($laporan['tanggal_laporan'])) ?></td>
                                                </tr>
                                                <tr>
                                                    <th>Jenis Alat</th>
                                                    <td>
                                                        <span class="badge badge-<?= $laporan['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?> p-2">
                                                            <i class="fas fa-<?= $laporan['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?> mr-1"></i>
                                                            <?= $laporan['jenis_alat'] == 'medis' ? 'Alat Medis' : 'Alat Non-Medis' ?>
                                                        </span>
                                                        <?php if ($laporan['jenis_alat'] == 'medis'): ?>
                                                            <span class="badge badge-danger ml-2">PRIORITAS TINGGI</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-info ml-2">PRIORITAS NORMAL</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Nama Alat</th>
                                                    <td>
                                                        <strong><?= htmlspecialchars($laporan['nama_alat']) ?></strong>
                                                        <?php if ($laporan['merk']): ?>
                                                            <br><small class="text-muted">Merk: <?= htmlspecialchars($laporan['merk']) ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($laporan['type_model']): ?>
                                                            <br><small class="text-muted">Model: <?= htmlspecialchars($laporan['type_model']) ?></small>
                                                        <?php endif; ?>
                                                        <?php if ($laporan['no_seri']): ?>
                                                            <br><small class="text-muted">S/N: <?= htmlspecialchars($laporan['no_seri']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Lokasi</th>
                                                    <td>
                                                        <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                                        <?= htmlspecialchars($laporan['nama_ruangan']) ?>
                                                        <br><small class="text-muted">
                                                            Lantai <?= $laporan['lantai'] ?>
                                                            <?= $laporan['sayap'] ? ' - ' . $laporan['sayap'] : '' ?>
                                                        </small>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-borderless">
                                                <tr>
                                                    <th width="40%">Status Saat Ini</th>
                                                    <td>
                                                        <?php
                                                        $status_colors = [
                                                            'menunggu_verifikasi' => 'warning',
                                                            'ditolak_admin' => 'danger',
                                                            'menunggu_penugasan' => 'info',
                                                            'dalam_perbaikan' => 'primary',
                                                            'selesai' => 'success',
                                                            'selesai_total' => 'dark'
                                                        ];
                                                        
                                                        $status_labels = [
                                                            'menunggu_verifikasi' => 'Menunggu Verifikasi',
                                                            'ditolak_admin' => 'Ditolak Admin',
                                                            'menunggu_penugasan' => 'Menunggu Penugasan',
                                                            'dalam_perbaikan' => 'Dalam Perbaikan',
                                                            'selesai' => 'Selesai',
                                                            'selesai_total' => 'Selesai Total'
                                                        ];
                                                        
                                                        $color = $status_colors[$laporan['status']] ?? 'secondary';
                                                        $label = $status_labels[$laporan['status']] ?? $laporan['status'];
                                                        ?>
                                                        <span class="badge badge-<?= $color ?> p-2">
                                                            <?= $label ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Pelapor</th>
                                                    <td>
                                                        <i class="fas fa-user text-primary mr-1"></i>
                                                        <strong><?= htmlspecialchars($laporan['nama_pelapor']) ?></strong>
                                                        <br><small class="text-muted"><?= htmlspecialchars($laporan['email_pelapor']) ?></small>
                                                        <?php if ($laporan['telepon_pelapor']): ?>
                                                            <br><small class="text-muted">
                                                                <i class="fas fa-phone mr-1"></i><?= htmlspecialchars($laporan['telepon_pelapor']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Teknisi</th>
                                                    <td>
                                                        <?php if ($laporan['nama_teknisi']): ?>
                                                            <i class="fas fa-user-cog text-success mr-1"></i>
                                                            <strong><?= htmlspecialchars($laporan['nama_teknisi']) ?></strong>
                                                            <br><small class="text-muted"><?= htmlspecialchars($laporan['email_teknisi']) ?></small>
                                                            <?php if ($laporan['telepon_teknisi']): ?>
                                                                <br><small class="text-muted">
                                                                    <i class="fas fa-phone mr-1"></i><?= htmlspecialchars($laporan['telepon_teknisi']) ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-warning">
                                                                <i class="fas fa-hourglass-half mr-1"></i>Belum ditugaskan
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Status Teknisi</th>
                                                    <td>
                                                        <?php if ($laporan['teknisi_status']): ?>
                                                            <span class="badge badge-info p-2">
                                                                <?= htmlspecialchars($laporan['teknisi_status']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Deskripsi Kerusakan -->
                            <div class="card shadow mb-4 info-card">
                                <div class="card-header py-3 bg-warning text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>Deskripsi Kerusakan
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-gray-800 mb-0" style="line-height: 1.6;">
                                        <?= nl2br(htmlspecialchars($laporan['deskripsi'])) ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Catatan Admin -->
                            <?php if ($laporan['catatan_admin']): ?>
                            <div class="card shadow mb-4 info-card">
                                <div class="card-header py-3 bg-info text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-comments mr-2"></i>Catatan Admin
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="catatan-box catatan-admin">
                                        <h6 class="font-weight-bold text-danger mb-2">
                                            <i class="fas fa-user-shield mr-2"></i>Catatan dari Admin
                                        </h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($laporan['catatan_admin'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Action Buttons -->
                            <?php if ($laporan['status'] == 'menunggu_penugasan'): ?>
                            <div class="card shadow mb-4 action-buttons">
                                <div class="card-header py-3 bg-success text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-user-cog mr-2"></i>Penugasan Teknisi
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="teknisi_id" class="font-weight-bold">Pilih Teknisi:</label>
                                            <select name="teknisi_id" id="teknisi_id" class="form-control" required>
                                                <option value="">-- Pilih Teknisi --</option>
                                                <?php while($teknisi = $teknisi_result->fetch_assoc()): ?>
                                                <option value="<?= $teknisi['id_user'] ?>">
                                                    <?= htmlspecialchars($teknisi['nama']) ?>
                                                    <?php if($teknisi['no_telepon']): ?>
                                                        (<?= htmlspecialchars($teknisi['no_telepon']) ?>)
                                                    <?php endif; ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" name="assign_teknisi" class="btn btn-success btn-block"
                                                onclick="return confirm('Apakah Anda yakin ingin menugaskan teknisi ini?')">
                                            <i class="fas fa-user-check mr-2"></i>Tugaskan Teknisi
                                        </button>
                                    </form>
                                    
                                    <div class="alert alert-info mt-3">
                                        <small>
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Teknisi akan mendapat notifikasi otomatis dan status laporan akan berubah menjadi "Dalam Perbaikan"
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Dokumentasi Foto -->
                            <div class="card shadow mb-4 info-card">
                                <div class="card-header py-3 bg-secondary text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-camera mr-2"></i>Dokumentasi Foto
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($fotos)): ?>
                                        <?php foreach ($fotos as $jenis => $foto_list): ?>
                                            <h6 class="font-weight-bold text-<?= $jenis == 'bukti' ? 'warning' : 'success' ?> mb-3">
                                                <i class="fas fa-<?= $jenis == 'bukti' ? 'exclamation-triangle' : 'tools' ?> mr-2"></i>
                                                Foto <?= ucfirst($jenis) ?>
                                            </h6>
                                            <div class="photo-grid">
                                                <?php foreach ($foto_list as $foto): ?>
                                                    <div class="photo-item">
                                                        <img src="../../uploads/<?= $foto['jenis'] ?>/<?= $foto['path_foto'] ?>" 
                                                             class="img-thumbnail" 
                                                             data-toggle="modal" 
                                                             data-target="#imageModal"
                                                             data-src="../../uploads/<?= $foto['jenis'] ?>/<?= $foto['path_foto'] ?>"
                                                             title="Klik untuk memperbesar">
                                                        <div class="photo-label">
                                                            <?= ucfirst($foto['jenis']) ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-images fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Tidak ada dokumentasi foto</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Info Prioritas -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 bg-<?= $laporan['jenis_alat'] == 'medis' ? 'danger' : 'info' ?> text-white">
                                    <h6 class="m-0 font-weight-bold">
                                        <i class="fas fa-<?= $laporan['jenis_alat'] == 'medis' ? 'exclamation-triangle' : 'info-circle' ?> mr-2"></i>
                                        Tingkat Prioritas
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($laporan['jenis_alat'] == 'medis'): ?>
                                        <div class="alert alert-danger">
                                            <h6 class="font-weight-bold">
                                                <i class="fas fa-exclamation-triangle mr-2"></i>PRIORITAS TINGGI
                                            </h6>
                                            <p class="mb-0">
                                                Alat medis memerlukan penanganan segera karena berkaitan langsung dengan pelayanan kesehatan pasien.
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <h6 class="font-weight-bold">
                                                <i class="fas fa-info-circle mr-2"></i>PRIORITAS NORMAL
                                            </h6>
                                            <p class="mb-0">
                                                Alat non-medis dapat ditangani sesuai jadwal normal teknisi.
                                            </p>
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

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-camera mr-2"></i>Dokumentasi Foto
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img src="" class="img-fluid" id="modalImage" style="max-height: 70vh">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
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
        // Image modal handler
        $('img[data-toggle="modal"]').click(function() {
            const imgSrc = $(this).data('src');
            $('#modalImage').attr('src', imgSrc);
        });

        // Hover effects
        $('.info-card').hover(
            function() {
                $(this).addClass('shadow-lg').css('transition', '0.3s');
            },
            function() {
                $(this).removeClass('shadow-lg').css('transition', '0.3s');
            }
        );

        // Tooltip untuk status progress
        $('.status-step').tooltip();

        // Form validation
        $('form').submit(function(e) {
            const teknisiId = $('#teknisi_id').val();
            if (!teknisiId) {
                e.preventDefault();
                alert('Silakan pilih teknisi terlebih dahulu!');
                return false;
            }
            
            // Show loading
            $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin mr-2"></i>Menugaskan...').prop('disabled', true);
        });

        // Print functionality
        window.addEventListener('beforeprint', function() {
            $('.action-buttons').hide();
            $('.btn').hide();
        });

        window.addEventListener('afterprint', function() {
            $('.action-buttons').show();
            $('.btn').show();
        });
    });
    </script>

    <style media="print">
        @media print {
            .sidebar, .topbar, .btn, .modal, .action-buttons {
                display: none !important;
            }
            .container-fluid {
                margin: 0 !important;
                padding: 0 !important;
            }
            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                margin-bottom: 20px !important;
            }
            .card-header {
                background-color: #f8f9fa !important;
                color: #000 !important;
            }
        }
    </style>
</body>
</html>
