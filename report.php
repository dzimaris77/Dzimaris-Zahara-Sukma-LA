<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config/config.php';

// Get list of rooms
$sql = "SELECT id_ruangan, lantai, sayap, nama_ruangan FROM ruangan ORDER BY lantai, nama_ruangan";
$result = mysqli_query($conn, $sql);
$ruangan = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ruangan[] = $row;
}

// Get selected room if form is submitted
$selected_room = isset($_GET['room_id']) ? $_GET['room_id'] : '';
$room_equipment = [];
$equipment_summary = [];
$room_detail = [];

// Process form submission for deleting equipment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_equipment'])) {
    $equipment_id = $_POST['equipment_id'];
    
    // Set the record as inactive (soft delete)
    $delete_sql = "UPDATE equipment_by_room SET status = 'tidak_aktif' WHERE id_equipment = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $equipment_id);
    mysqli_stmt_execute($stmt);
    
    // Redirect to same page to prevent form resubmission
    header("Location: report.php?room_id=" . $_POST['room_id']);
    exit();
}

// Process form submission for updating equipment condition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_equipment'])) {
    $equipment_id = $_POST['equipment_id'];
    $kondisi = $_POST['kondisi'];
    $keterangan = $_POST['keterangan'];
    $id_pelapor = $_SESSION['user_id']; // Get logged in user ID
    $id_ruangan = $_POST['room_id'];
    $id_alat = $_POST['alat_id'];
    
    // VALIDASI: Foto wajib untuk kondisi rusak
    if ($kondisi == 'rusak') {
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] != 0 || empty($_FILES['foto']['name'])) {
            $_SESSION['error_message'] = "Foto wajib diupload untuk kondisi rusak!";
            header("Location: report.php?room_id=" . $id_ruangan);
            exit();
        }
    }
    
    // Set the previous record as inactive
    $update_sql = "UPDATE equipment_by_room SET status = 'tidak_aktif' WHERE id_equipment = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "i", $equipment_id);
    mysqli_stmt_execute($stmt);
    
    // Insert new record
    if ($kondisi == 'rusak') {
        // Handle file upload for damaged equipment - WAJIB
        $foto = NULL;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $upload_dir = "../../uploads/equipment_photos/";
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $file_type = $_FILES['foto']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                $_SESSION['error_message'] = "Format file tidak didukung! Gunakan JPG, PNG, atau GIF.";
                header("Location: report.php?room_id=" . $id_ruangan);
                exit();
            }
            
            // Validate file size (max 5MB)
            if ($_FILES['foto']['size'] > 5242880) {
                $_SESSION['error_message'] = "Ukuran file terlalu besar! Maksimal 5MB.";
                header("Location: report.php?room_id=" . $id_ruangan);
                exit();
            }
            
            $temp_name = $_FILES['foto']['tmp_name'];
            $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $file_name = 'equipment_' . time() . '_' . uniqid() . '.' . $file_extension;
            
            if (move_uploaded_file($temp_name, $upload_dir . $file_name)) {
                $foto = $file_name;
            } else {
                $_SESSION['error_message'] = "Gagal mengupload foto!";
                header("Location: report.php?room_id=" . $id_ruangan);
                exit();
            }
        }
        
        $insert_sql = "INSERT INTO equipment_by_room (id_ruangan, id_alat, kondisi, keterangan, foto, id_pelapor) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "iisssi", $id_ruangan, $id_alat, $kondisi, $keterangan, $foto, $id_pelapor);
    } else {
        // No photo needed for good condition
        $insert_sql = "INSERT INTO equipment_by_room (id_ruangan, id_alat, kondisi, keterangan, id_pelapor) 
                      VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "iissi", $id_ruangan, $id_alat, $kondisi, $keterangan, $id_pelapor);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Data alat berhasil diperbarui!";
    } else {
        $_SESSION['error_message'] = "Gagal memperbarui data alat!";
    }
    
    // Redirect to same page to prevent form resubmission
    header("Location: report.php?room_id=" . $id_ruangan);
    exit();
}

if ($selected_room) {
    // Get room details
    $stmt_room_detail = "SELECT lantai, sayap, nama_ruangan FROM ruangan WHERE id_ruangan = ?";
    $stmt = mysqli_prepare($conn, $stmt_room_detail);
    mysqli_stmt_bind_param($stmt, "i", $selected_room);
    mysqli_stmt_execute($stmt);
    $result_room_detail = mysqli_stmt_get_result($stmt);
    $room_detail = mysqli_fetch_assoc($result_room_detail);
    
    // Get equipment reports for the selected room dengan jenis_alat
    $stmt_equipment = "
        SELECT ebr.id_equipment, ebr.id_alat, a.nama_alat, a.merk, a.jenis_alat, ebr.kondisi, ebr.keterangan,
               ebr.tanggal_laporan, ebr.foto, u.nama as pelapor_nama
        FROM equipment_by_room ebr
        JOIN alat a ON ebr.id_alat = a.id_alat
        JOIN users u ON ebr.id_pelapor = u.id_user
        WHERE ebr.id_ruangan = ? AND ebr.status = 'aktif'
        ORDER BY a.jenis_alat, a.nama_alat, ebr.tanggal_laporan DESC
    ";
    $stmt = mysqli_prepare($conn, $stmt_equipment);
    mysqli_stmt_bind_param($stmt, "i", $selected_room);
    mysqli_stmt_execute($stmt);
    $result_equipment = mysqli_stmt_get_result($stmt);
    $room_equipment = [];
    while ($row = mysqli_fetch_assoc($result_equipment)) {
        $room_equipment[] = $row;
    }
    
    // Prepare equipment summary dengan jenis_alat
    $equipment_count = [
        'total' => 0,
        'medis' => 0,
        'non_medis' => 0,
        'baik' => 0,
        'rusak' => 0
    ];
    
    foreach ($room_equipment as $item) {
        $equipment_count['total']++;
        if ($item['jenis_alat'] == 'medis') {
            $equipment_count['medis']++;
        } else {
            $equipment_count['non_medis']++;
        }
        if ($item['kondisi'] == 'baik') {
            $equipment_count['baik']++;
        } else {
            $equipment_count['rusak']++;
        }
    }
    
    $equipment_summary = $equipment_count;
}

// Get list of equipment for adding new equipment dengan jenis_alat
$sql_alat = "SELECT id_alat, nama_alat, merk, jenis_alat FROM alat ORDER BY jenis_alat, nama_alat";
$result_alat = mysqli_query($conn, $sql_alat);
$alat_list = [];
while ($row = mysqli_fetch_assoc($result_alat)) {
    $alat_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan Alat Per Ruangan - SIMONFAST</title>

    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .condition-form-container {
            display: none;
        }
        
        .equipment-image {
            max-width: 150px;
            max-height: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }
        
        .photo-upload {
            display: none;
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
        .stats-card.baik {
            border-left-color: #28a745;
        }
        .stats-card.rusak {
            border-left-color: #dc3545;
        }
        .badge-medis {
            background-color: #1cc88a;
            color: white;
        }
        .badge-non-medis {
            background-color: #36b9cc;
            color: white;
        }
        .required {
            color: red;
        }
        .photo-required {
            border: 2px solid #dc3545 !important;
            background-color: #fff5f5;
        }
        .photo-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
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
                    <h1 class="h3 mb-4 text-gray-800">Laporan Alat Per Ruangan</h1>
                    
                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i><?= $_SESSION['success_message'] ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['success_message']); endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i><?= $_SESSION['error_message'] ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['error_message']); endif; ?>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-search me-1"></i>
                                Pilih Ruangan
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <select name="room_id" class="form-control" required>
                                            <option value="">-- Pilih Ruangan --</option>
                                            <?php foreach ($ruangan as $room): ?>
                                                <option value="<?= $room['id_ruangan'] ?>" <?= ($selected_room == $room['id_ruangan']) ? 'selected' : '' ?>>
                                                    <?= $room['nama_ruangan'] ?> 
                                                    (Lantai <?= $room['lantai'] ?> <?= $room['sayap'] ? '- ' . $room['sayap'] : '' ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                                        <?php if ($selected_room): ?>
                                        <a href="export_report.php?room_id=<?= $selected_room ?>" class="btn btn-success">
                                            <i class="fas fa-file-excel me-1"></i> Export Excel
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <?php if ($selected_room): ?>
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stats-card total shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Alat</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $equipment_summary['total'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stats-card medis shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Alat Medis</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $equipment_summary['medis'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-stethoscope fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4 mb-4">
                            <div class="card stats-card non-medis shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Non-Medis</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $equipment_summary['non_medis'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chair fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card baik shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kondisi Baik</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $equipment_summary['baik'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card rusak shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Kondisi Rusak</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $equipment_summary['rusak'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Equipment Button -->
                    <div class="mb-3">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEquipmentModal">
                            <i class="fas fa-plus"></i> Tambah Alat
                        </button>
                    </div>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-clipboard-list me-1"></i>
                                Laporan Alat di <?= $room_detail['nama_ruangan'] ?> (Lantai <?= $room_detail['lantai'] ?> <?= $room_detail['sayap'] ? '- ' . $room_detail['sayap'] : '' ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Equipment List Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered" id="equipmentTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Jenis</th>
                                            <th>Nama Alat</th>
                                            <th>Merk</th>
                                            <th>Kondisi</th>
                                            <th>Keterangan</th>
                                            <th>Foto</th>
                                            <th>Pelapor</th>
                                            <th>Tanggal Laporan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($room_equipment) > 0): ?>
                                        <?php $no = 1; foreach ($room_equipment as $equipment): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <span class="badge badge-<?= $equipment['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?>">
                                                    <i class="fas fa-<?= $equipment['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?> mr-1"></i>
                                                    <?= $equipment['jenis_alat'] == 'medis' ? 'Medis' : 'Non-Medis' ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($equipment['nama_alat']) ?></td>
                                            <td><?= htmlspecialchars($equipment['merk']) ?></td>
                                            <td>
                                                <?php if ($equipment['kondisi'] == 'baik'): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check mr-1"></i>Baik
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times mr-1"></i>Rusak
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($equipment['keterangan'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($equipment['foto'] && $equipment['kondisi'] == 'rusak'): ?>
                                                    <img src="../../uploads/equipment_photos/<?= $equipment['foto'] ?>" 
                                                         class="equipment-image" 
                                                         alt="Foto Kondisi"
                                                         data-toggle="modal" 
                                                         data-target="#photoModal"
                                                         data-src="../../uploads/equipment_photos/<?= $equipment['foto'] ?>"
                                                         style="cursor: pointer;">
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($equipment['pelapor_nama']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($equipment['tanggal_laporan'])) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning update-btn" 
                                                        data-equipment-id="<?= $equipment['id_equipment'] ?>"
                                                        data-alat-id="<?= $equipment['id_alat'] ?>"
                                                        data-room-id="<?= $selected_room ?>"
                                                        data-toggle="modal" 
                                                        data-target="#updateEquipmentModal"
                                                        title="Update Kondisi">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-btn" 
                                                        data-equipment-id="<?= $equipment['id_equipment'] ?>"
                                                        data-room-id="<?= $selected_room ?>"
                                                        data-equipment-name="<?= htmlspecialchars($equipment['nama_alat']) ?>"
                                                        title="Hapus Alat">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i><br>
                                                <span class="text-muted">Belum ada data alat untuk ruangan ini</span>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Equipment Modal -->
    <div class="modal fade" id="addEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="addEquipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEquipmentModalLabel">
                        <i class="fas fa-plus mr-2"></i>Tambah Alat
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" id="addEquipmentForm">
                    <input type="hidden" name="room_id" value="<?= $selected_room ?>">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="alat_id">Pilih Alat <span class="required">*</span></label>
                            <select name="alat_id" id="alat_id" class="form-control" required>
                                <option value="">-- Pilih Alat --</option>
                                <?php 
                                $current_jenis = '';
                                foreach ($alat_list as $alat): 
                                    if ($current_jenis != $alat['jenis_alat']):
                                        if ($current_jenis != '') echo '</optgroup>';
                                        $jenis_label = $alat['jenis_alat'] == 'medis' ? 'ALAT MEDIS' : 'ALAT NON-MEDIS';
                                        echo '<optgroup label="'.$jenis_label.'">';
                                        $current_jenis = $alat['jenis_alat'];
                                    endif;
                                ?>
                                    <option value="<?= $alat['id_alat'] ?>">
                                        <?= $alat['nama_alat'] ?> 
                                        <?= $alat['merk'] ? '(' . $alat['merk'] . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if ($current_jenis != '') echo '</optgroup>'; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kondisi <span class="required">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input condition-radio" type="radio" name="kondisi" id="kondisi_baik" value="baik" checked required>
                                <label class="form-check-label" for="kondisi_baik">
                                    <i class="fas fa-check text-success mr-1"></i>Baik
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input condition-radio" type="radio" name="kondisi" id="kondisi_rusak" value="rusak" required>
                                <label class="form-check-label" for="kondisi_rusak">
                                    <i class="fas fa-times text-danger mr-1"></i>Rusak
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="keterangan">Keterangan <span class="required">*</span></label>
                            <textarea class="form-control" name="keterangan" id="keterangan" rows="3" required placeholder="Masukkan keterangan kondisi alat"></textarea>
                        </div>
                        <div class="form-group photo-upload" id="photo_upload_div">
                            <div class="photo-info">
                                <i class="fas fa-info-circle text-warning mr-2"></i>
                                <strong>Foto WAJIB diupload untuk kondisi rusak!</strong>
                            </div>
                            <label for="foto">Upload Foto <span class="required">*</span></label>
                            <input type="file" class="form-control-file" name="foto" id="foto" accept="image/*">
                            <small class="form-text text-muted">
                                Format: JPG, PNG, GIF | Maksimal: 5MB<br>
                                <strong>Foto wajib untuk dokumentasi kondisi rusak</strong>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" name="update_equipment" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Equipment Modal -->
    <div class="modal fade" id="updateEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="updateEquipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateEquipmentModalLabel">
                        <i class="fas fa-sync-alt mr-2"></i>Update Kondisi Alat
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" id="updateEquipmentForm">
                    <input type="hidden" name="equipment_id" id="update_equipment_id">
                    <input type="hidden" name="room_id" id="update_room_id">
                    <input type="hidden" name="alat_id" id="update_alat_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Kondisi <span class="required">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input update-condition-radio" type="radio" name="kondisi" id="update_kondisi_baik" value="baik" checked required>
                                <label class="form-check-label" for="update_kondisi_baik">
                                    <i class="fas fa-check text-success mr-1"></i>Baik
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input update-condition-radio" type="radio" name="kondisi" id="update_kondisi_rusak" value="rusak" required>
                                <label class="form-check-label" for="update_kondisi_rusak">
                                    <i class="fas fa-times text-danger mr-1"></i>Rusak
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="update_keterangan">Keterangan <span class="required">*</span></label>
                            <textarea class="form-control" name="keterangan" id="update_keterangan" rows="3" required placeholder="Masukkan keterangan kondisi alat"></textarea>
                        </div>
                        <div class="form-group photo-upload" id="update_photo_upload_div">
                            <div class="photo-info">
                                <i class="fas fa-info-circle text-warning mr-2"></i>
                                <strong>Foto WAJIB diupload untuk kondisi rusak!</strong>
                            </div>
                            <label for="update_foto">Upload Foto <span class="required">*</span></label>
                            <input type="file" class="form-control-file" name="foto" id="update_foto" accept="image/*">
                            <small class="form-text text-muted">
                                Format: JPG, PNG, GIF | Maksimal: 5MB<br>
                                <strong>Foto wajib untuk dokumentasi kondisi rusak</strong>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" name="update_equipment" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Equipment Modal -->
    <div class="modal fade" id="deleteEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="deleteEquipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteEquipmentModalLabel">
                        <i class="fas fa-trash mr-2"></i>Konfirmasi Hapus Alat
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="equipment_id" id="delete_equipment_id">
                    <input type="hidden" name="room_id" id="delete_room_id">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Peringatan!</strong> Anda akan menghapus alat berikut:
                        </div>
                        <p><strong>Nama Alat:</strong> <span id="delete_equipment_name"></span></p>
                        <p class="text-danger"><strong>Data yang dihapus tidak dapat dikembalikan!</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" name="delete_equipment" class="btn btn-danger">
                            <i class="fas fa-trash mr-1"></i>Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">
                        <i class="fas fa-camera mr-2"></i>Foto Kondisi Alat
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img src="" class="img-fluid" id="modalImage" style="max-height: 70vh">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Tutup
                    </button>
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
    
    <!-- DataTables JavaScript -->
    <script src="../../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            $('#equipmentTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                order: [[1, 'asc'], [2, 'asc']], // Sort by jenis then nama_alat
                columnDefs: [
                    { "width": "5%", "targets": 0 },   // No
                    { "width": "8%", "targets": 1 },   // Jenis
                    { "width": "15%", "targets": 2 },  // Nama Alat
                    { "width": "10%", "targets": 3 },  // Merk
                    { "width": "8%", "targets": 4 },   // Kondisi
                    { "width": "15%", "targets": 5 },  // Keterangan
                    { "width": "8%", "targets": 6 },   // Foto
                    { "width": "10%", "targets": 7 },  // Pelapor
                    { "width": "12%", "targets": 8 },  // Tanggal
                    { "width": "12%", "targets": 9 }   // Aksi
                ]
            });
            
            // Show photo modal when clicked
            $('.equipment-image').click(function() {
                const imgSrc = $(this).data('src');
                $('#modalImage').attr('src', imgSrc);
            });
            
            // Show/hide photo upload based on condition selection - ADD MODAL
            $('.condition-radio').change(function() {
                if ($('#kondisi_rusak').is(':checked')) {
                    $('#photo_upload_div').show();
                    $('#foto').prop('required', true);
                    $('#foto').addClass('photo-required');
                } else {
                    $('#photo_upload_div').hide();
                    $('#foto').prop('required', false);
                    $('#foto').removeClass('photo-required');
                }
            });
            
            // Same for update modal - UPDATE MODAL
            $('.update-condition-radio').change(function() {
                if ($('#update_kondisi_rusak').is(':checked')) {
                    $('#update_photo_upload_div').show();
                    $('#update_foto').prop('required', true);
                    $('#update_foto').addClass('photo-required');
                } else {
                    $('#update_photo_upload_div').hide();
                    $('#update_foto').prop('required', false);
                    $('#update_foto').removeClass('photo-required');
                }
            });
            
            // Set equipment ID in update modal
            $('.update-btn').click(function() {
                var equipmentId = $(this).data('equipment-id');
                var alatId = $(this).data('alat-id');
                var roomId = $(this).data('room-id');
                
                $('#update_equipment_id').val(equipmentId);
                $('#update_alat_id').val(alatId);
                $('#update_room_id').val(roomId);
                
                // Reset form
                $('#update_keterangan').val('');
                $('#update_kondisi_baik').prop('checked', true);
                $('#update_photo_upload_div').hide();
                $('#update_foto').prop('required', false);
                $('#update_foto').removeClass('photo-required');
            });

            // Set equipment ID in delete modal
            $('.delete-btn').click(function() {
                var equipmentId = $(this).data('equipment-id');
                var roomId = $(this).data('room-id');
                var equipmentName = $(this).data('equipment-name');
                
                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    html: `Apakah Anda yakin ingin menghapus alat:<br><strong>${equipmentName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '<i class="fas fa-trash-alt mr-1"></i> Ya, Hapus!',
                    cancelButtonText: '<i class="fas fa-times mr-1"></i> Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Create and submit form
                        var form = $('<form method="POST" action="">' +
                                   '<input type="hidden" name="equipment_id" value="' + equipmentId + '">' +
                                   '<input type="hidden" name="room_id" value="' + roomId + '">' +
                                   '<input type="hidden" name="delete_equipment" value="1">' +
                                   '</form>');
                        $('body').append(form);
                        form.submit();
                    }
                });
            });

            // Hover effects for stats cards
            $('.stats-card').hover(
                function() {
                    $(this).addClass('shadow-lg');
                },
                function() {
                    $(this).removeClass('shadow-lg');
                }
            );

            // Form validation - ENHANCED untuk foto wajib
            $('#addEquipmentForm, #updateEquipmentForm').submit(function(e) {
                var form = $(this);
                var isValid = true;
                var errorMessage = '';
                
                // Check required fields
                form.find('[required]').each(function() {
                    if ($(this).val() === '') {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
                
                // Special validation for photo when condition is rusak
                var kondisiRusak = form.find('input[name="kondisi"]:checked').val() === 'rusak';
                var fotoInput = form.find('input[name="foto"]')[0];
                
                if (kondisiRusak) {
                    if (!fotoInput.files || fotoInput.files.length === 0) {
                        isValid = false;
                        errorMessage = 'Foto wajib diupload untuk kondisi rusak!';
                        $(fotoInput).addClass('is-invalid');
                    } else {
                        // Validate file type
                        var file = fotoInput.files[0];
                        var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        
                        if (!allowedTypes.includes(file.type)) {
                            isValid = false;
                            errorMessage = 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF.';
                            $(fotoInput).addClass('is-invalid');
                        } else if (file.size > 5242880) { // 5MB
                            isValid = false;
                            errorMessage = 'Ukuran file terlalu besar! Maksimal 5MB.';
                            $(fotoInput).addClass('is-invalid');
                        } else {
                            $(fotoInput).removeClass('is-invalid');
                        }
                    }
                } else {
                    $(fotoInput).removeClass('is-invalid');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    if (errorMessage) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: errorMessage,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: 'Mohon lengkapi semua field yang wajib diisi!',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });

            // Remove invalid class on input
            $('[required]').on('input change', function() {
                if ($(this).val() !== '') {
                    $(this).removeClass('is-invalid');
                }
            });
            
            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>
</html>
