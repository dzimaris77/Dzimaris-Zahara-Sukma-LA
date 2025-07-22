<?php
session_start();
if ($_SESSION['role'] != 'tenaga_kesehatan') {
    header("Location: ../../login.php");
    exit();
}

require_once '../../config/config.php';

// Get list of rooms
$sql = "SELECT id_ruangan, lantai, sayap, nama_ruangan FROM ruangan ORDER BY lantai, nama_ruangan";
$result = mysqli_query($conn, $sql);
$ruangan = array();
while ($row = mysqli_fetch_assoc($result)) {
    $ruangan[] = $row;
}

// Get list of medical equipment dengan jenis_alat
$sql = "SELECT id_alat, nama_alat, merk, type_model, no_seri, jenis_alat FROM alat ORDER BY jenis_alat, nama_alat";
$result = mysqli_query($conn, $sql);
$alat = array();
while ($row = mysqli_fetch_assoc($result)) {
    $alat[] = $row;
}

// Get selected room if form is submitted
$selected_room = isset($_GET['room_id']) ? $_GET['room_id'] : '';
$room_equipment = array();
$equipment_summary = array();

if ($selected_room) {
    // Get existing equipment reports for the selected room dengan jenis_alat
    $sql = "
        SELECT ebr.id_equipment, ebr.id_alat, a.nama_alat, a.merk, a.no_seri, a.jenis_alat, 
               ebr.kondisi, ebr.keterangan, ebr.tanggal_laporan, ebr.foto
        FROM equipment_by_room ebr
        JOIN alat a ON ebr.id_alat = a.id_alat
        WHERE ebr.id_ruangan = '$selected_room' AND ebr.status = 'aktif'
        ORDER BY a.jenis_alat, ebr.tanggal_laporan DESC
    ";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $room_equipment[] = $row;
    }
    
    // Get room details
    $sql = "SELECT lantai, sayap, nama_ruangan FROM ruangan WHERE id_ruangan = '$selected_room'";
    $result = mysqli_query($conn, $sql);
    $room_detail = mysqli_fetch_assoc($result);
    
    // Prepare equipment summary
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

// Check for messages
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'add') {
        $success_message = 'Data alat berhasil ditambahkan!';
    } else if ($_GET['success'] == 'delete') {
        $success_message = 'Data alat berhasil dihapus!';
    } else if ($_GET['success'] == 'update') {
        $success_message = 'Data alat berhasil diperbarui!';
    }
}

if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
}

// Get edit data if edit_id is provided
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $sql = "SELECT ebr.*, a.nama_alat, a.merk, a.no_seri, a.jenis_alat 
            FROM equipment_by_room ebr 
            JOIN alat a ON ebr.id_alat = a.id_alat 
            WHERE ebr.id_equipment = '$edit_id'";
    $result = mysqli_query($conn, $sql);
    $edit_data = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="id">
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
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
        .form-section {
            margin-bottom: 30px;
        }
        .badge-condition {
            padding: 0.5em 0.75em;
            border-radius: 20px;
        }
        .badge-medis {
            background-color: #1cc88a;
            color: white;
        }
        .badge-non-medis {
            background-color: #36b9cc;
            color: white;
        }
        .equipment-photo {
            max-width: 100px;
            max-height: 100px;
            cursor: pointer;
        }
        .modal-image {
            width: 100%;
            max-height: 80vh;
            object-fit: contain;
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
        .edit-mode {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
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
                    
                    <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success_message ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error_message ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-search me-1"></i> Pilih Ruangan
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
                                    <div class="col-md-6">
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

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-clipboard-list me-1"></i>
                                Data Alat di <?= $room_detail['nama_ruangan'] ?> (Lantai <?= $room_detail['lantai'] ?> <?= $room_detail['sayap'] ? '- ' . $room_detail['sayap'] : '' ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <!-- Add/Edit Equipment Form - DIPERBAIKI -->
                            <div class="form-section <?= $edit_data ? 'edit-mode' : '' ?>">
                                <h5 class="text-primary">
                                    <i class="fas fa-<?= $edit_data ? 'edit' : 'plus-circle' ?> mr-2"></i>
                                    <?= $edit_data ? 'Edit Alat' : 'Tambah Alat ke Ruangan' ?>
                                    <?php if ($edit_data): ?>
                                        <a href="?room_id=<?= $selected_room ?>" class="btn btn-sm btn-secondary float-right">
                                            <i class="fas fa-times"></i> Batal Edit
                                        </a>
                                    <?php endif; ?>
                                </h5>
                                <form method="POST" action="submit_equipment_report.php" enctype="multipart/form-data" id="equipmentForm">
                                    <input type="hidden" name="room_id" value="<?= $selected_room ?>">
                                    <?php if ($edit_data): ?>
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="equipment_report_id" value="<?= $edit_data['id_equipment'] ?>">
                                        <input type="hidden" name="equipment_id" value="<?= $edit_data['id_alat'] ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="add">
                                    <?php endif; ?>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="equipment" class="form-label">Alat <span class="text-danger">*</span></label>
                                                <?php if ($edit_data): ?>
                                                    <input type="text" class="form-control" readonly 
                                                           value="<?= $edit_data['nama_alat'] ?> - <?= $edit_data['merk'] ?> <?= $edit_data['no_seri'] ? '(SN: ' . $edit_data['no_seri'] . ')' : '' ?>">
                                                <?php else: ?>
                                                    <select name="equipment_id" id="equipment" class="form-control" required>
                                                        <option value="">-- Pilih Alat --</option>
                                                        <?php 
                                                        $current_jenis = '';
                                                        foreach ($alat as $equipment): 
                                                            if ($current_jenis != $equipment['jenis_alat']):
                                                                if ($current_jenis != '') echo '</optgroup>';
                                                                $jenis_label = $equipment['jenis_alat'] == 'medis' ? 'ALAT MEDIS' : 'ALAT NON-MEDIS';
                                                                echo '<optgroup label="'.$jenis_label.'">';
                                                                $current_jenis = $equipment['jenis_alat'];
                                                            endif;
                                                        ?>
                                                            <option value="<?= $equipment['id_alat'] ?>" 
                                                                    data-jenis="<?= $equipment['jenis_alat'] ?>"
                                                                    data-noseri="<?= htmlspecialchars($equipment['no_seri']) ?>">
                                                                <?= $equipment['nama_alat'] ?> 
                                                                <?= $equipment['merk'] ? '- ' . $equipment['merk'] : '' ?>
                                                                <?= $equipment['no_seri'] ? '(SN: ' . $equipment['no_seri'] . ')' : '' ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                        <?php if ($current_jenis != '') echo '</optgroup>'; ?>
                                                    </select>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="mb-3">
                                                <label for="condition" class="form-label">Kondisi <span class="text-danger">*</span></label>
                                                <select name="condition" id="condition" class="form-control" required>
                                                    <option value="">-- Pilih Kondisi --</option>
                                                    <option value="baik" <?= ($edit_data && $edit_data['kondisi'] == 'baik') ? 'selected' : '' ?>>Baik</option>
                                                    <option value="rusak" <?= ($edit_data && $edit_data['kondisi'] == 'rusak') ? 'selected' : '' ?>>Rusak</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="notes" class="form-label">Keterangan <span class="text-danger">*</span></label>
                                                <input type="text" name="notes" id="notes" class="form-control" 
                                                       placeholder="Masukkan keterangan" 
                                                       value="<?= $edit_data ? htmlspecialchars($edit_data['keterangan']) : '' ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="foto" class="form-label">
                                                    Foto <span class="text-danger" id="foto-required" style="display: none;">*</span>
                                                    <?php if ($edit_data && $edit_data['foto']): ?>
                                                        <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small>
                                                    <?php endif; ?>
                                                </label>
                                                <input type="file" name="foto" id="foto" class="form-control" accept="image/jpeg,image/png,image/jpg">
                                                <small class="form-text text-muted">Format: JPG, JPEG, PNG (Wajib untuk kondisi rusak)</small>
                                                <?php if ($edit_data && $edit_data['foto']): ?>
                                                    <div class="mt-2">
                                                        <img src="../../uploads/equipment_photos/<?= $edit_data['foto'] ?>" 
                                                             class="img-thumbnail" style="max-width: 100px;">
                                                        <small class="d-block text-muted">Foto saat ini</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-<?= $edit_data ? 'warning' : 'success' ?> mr-2">
                                                <i class="fas fa-<?= $edit_data ? 'save' : 'plus' ?> mr-1"></i>
                                                <?= $edit_data ? 'Update Data' : 'Tambah Data' ?>
                                            </button>
                                            <?php if ($edit_data): ?>
                                                <a href="?room_id=<?= $selected_room ?>" class="btn btn-secondary">
                                                    <i class="fas fa-times mr-1"></i>Batal
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Equipment List Table -->
                            <div class="table-responsive">
                                <table class="table table-bordered" id="equipmentTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Jenis</th>
                                            <th>Nama Alat</th>
                                            <th>Merk</th>
                                            <th>No. Seri</th>
                                            <th>Kondisi</th>
                                            <th>Keterangan</th>
                                            <th>Foto</th>
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
                                            <td><?= htmlspecialchars($equipment['no_seri'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($equipment['kondisi'] == 'baik'): ?>
                                                    <span class="badge badge-success badge-condition">
                                                        <i class="fas fa-check mr-1"></i>Baik
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger badge-condition">
                                                        <i class="fas fa-times mr-1"></i>Rusak
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($equipment['keterangan'] ?? '-') ?></td>
                                            <td>
                                                <?php if (!empty($equipment['foto'])): ?>
                                                <img src="../../uploads/equipment_photos/<?= $equipment['foto'] ?>" 
                                                     class="equipment-photo" 
                                                     alt="Foto alat"
                                                     data-toggle="modal" 
                                                     data-target="#photoModal"
                                                     data-src="../../uploads/equipment_photos/<?= $equipment['foto'] ?>">
                                                <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="fas fa-image mr-1"></i>Tidak ada
                                                </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($equipment['tanggal_laporan'])) ?></td>
                                            <td>
                                                <a href="?room_id=<?= $selected_room ?>&edit_id=<?= $equipment['id_equipment'] ?>" 
                                                   class="btn btn-sm btn-warning mr-1" title="Edit data alat">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" action="submit_equipment_report.php" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="report_id" value="<?= $equipment['id_equipment'] ?>">
                                                    <input type="hidden" name="room_id" value="<?= $selected_room ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Apakah Anda yakin ingin menghapus data alat ini?')"
                                                            title="Hapus data alat">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
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

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" role="dialog" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">
                        <i class="fas fa-camera mr-2"></i>Foto Alat
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img src="" class="modal-image" id="modalImage" alt="Foto Alat">
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
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // DataTable initialization
            $('#equipmentTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
                    emptyTable: "Belum ada data alat untuk ruangan ini",
                    zeroRecords: "Tidak ada data yang sesuai dengan pencarian"
                },
                order: [[1, 'asc'], [2, 'asc']], // Sort by jenis then nama_alat
                columnDefs: [
                    { "width": "5%", "targets": 0 },   // No
                    { "width": "8%", "targets": 1 },   // Jenis
                    { "width": "15%", "targets": 2 },  // Nama Alat
                    { "width": "10%", "targets": 3 },  // Merk
                    { "width": "8%", "targets": 4 },   // No Seri
                    { "width": "8%", "targets": 5 },   // Kondisi
                    { "width": "15%", "targets": 6 },  // Keterangan
                    { "width": "8%", "targets": 7 },   // Foto
                    { "width": "12%", "targets": 8 },  // Tanggal
                    { "width": "10%", "targets": 9 }   // Aksi
                ],
                "info": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "paging": true,
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
                "retrieve": true,
                "destroy": true
            });
            
            // Show photo modal when clicked
            $('.equipment-photo').click(function() {
                const imgSrc = $(this).data('src');
                $('#modalImage').attr('src', imgSrc);
            });
            
            // PERBAIKAN: Toggle photo requirement berdasarkan kondisi
            function togglePhotoRequirement() {
                const condition = $('#condition').val();
                const isEdit = <?= $edit_data ? 'true' : 'false' ?>;
                const hasExistingPhoto = <?= ($edit_data && $edit_data['foto']) ? 'true' : 'false' ?>;
                
                if (condition === 'rusak') {
                    $('#foto-required').show();
                    // Hanya required jika bukan edit atau tidak ada foto existing
                    if (!isEdit || !hasExistingPhoto) {
                        $('#foto').prop('required', true);
                    } else {
                        $('#foto').prop('required', false);
                    }
                } else {
                    // Kondisi baik - foto tidak wajib
                    $('#foto-required').hide();
                    $('#foto').prop('required', false);
                }
            }
            
            // Trigger saat kondisi berubah
            $('#condition').change(togglePhotoRequirement);
            
            // Trigger saat halaman dimuat
            togglePhotoRequirement();

            // PERBAIKAN: Form validation yang lebih tepat
            $('#equipmentForm').submit(function(e) {
                let isValid = true;
                let errorMessage = '';
                
                // Check required fields
                const requiredFields = [
                    { field: '#condition', name: 'Kondisi' },
                    { field: '#notes', name: 'Keterangan' }
                ];
                
                // Tambahkan validasi equipment hanya jika bukan edit
                const isEdit = <?= $edit_data ? 'true' : 'false' ?>;
                if (!isEdit) {
                    requiredFields.unshift({ field: '#equipment', name: 'Alat' });
                }
                
                requiredFields.forEach(function(item) {
                    if ($(item.field).val() === '' || $(item.field).val() === null) {
                        isValid = false;
                        errorMessage += item.name + ' wajib diisi!\n';
                        $(item.field).addClass('is-invalid');
                    } else {
                        $(item.field).removeClass('is-invalid');
                    }
                });
                
                // PERBAIKAN: Validasi foto untuk kondisi rusak
                const condition = $('#condition').val();
                const hasExistingPhoto = <?= ($edit_data && $edit_data['foto']) ? 'true' : 'false' ?>;
                const hasNewPhoto = $('#foto').val() !== '';
                
                if (condition === 'rusak') {
                    // Jika edit dan sudah ada foto existing, tidak wajib upload foto baru
                    // Jika edit tapi tidak ada foto existing, wajib upload foto baru
                    // Jika tambah baru, wajib upload foto
                    if (!isEdit || (!hasExistingPhoto && !hasNewPhoto)) {
                        if (!hasNewPhoto) {
                            isValid = false;
                            errorMessage += 'Foto wajib diupload untuk kondisi rusak!\n';
                            $('#foto').addClass('is-invalid');
                        }
                    }
                } else {
                    // Kondisi baik - hapus error foto jika ada
                    $('#foto').removeClass('is-invalid');
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert(errorMessage);
                }
            });

            // Auto dismiss alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

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
    </script>
</body>
</html>
