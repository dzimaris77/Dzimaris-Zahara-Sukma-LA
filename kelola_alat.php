<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

// CRUD Operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama_alat']);
    $merk = trim($_POST['merk']);
    $type = trim($_POST['type_model']);
    $seri = trim($_POST['no_seri']);
    $jenis = $_POST['jenis_alat'];
    $id = $_POST['id'] ?? null;
    
    $errors = [];
    
    // VALIDASI WAJIB - Semua field harus diisi
    if (empty($nama)) {
        $errors[] = "Nama alat wajib diisi!";
    }
    
    if (empty($merk)) {
        $errors[] = "Merk alat wajib diisi!";
    }
    
    if (empty($type)) {
        $errors[] = "Tipe/Model alat wajib diisi!";
    }
    
    if (empty($seri)) {
        $errors[] = "Nomor seri alat wajib diisi!";
    }
    
    if (empty($jenis)) {
        $errors[] = "Jenis alat wajib dipilih!";
    }
    
    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        if ($id) {
            // Edit
            $stmt = $conn->prepare("UPDATE alat SET 
                                  nama_alat = ?,
                                  merk = ?,
                                  type_model = ?,
                                  no_seri = ?,
                                  jenis_alat = ?
                                  WHERE id_alat = ?");
            $stmt->bind_param("sssssi", $nama, $merk, $type, $seri, $jenis, $id);
        } else {
            // Tambah
            $stmt = $conn->prepare("INSERT INTO alat (nama_alat, merk, type_model, no_seri, jenis_alat) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $merk, $type, $seri, $jenis);
        }
        
        if ($stmt->execute()) {
            $action = $id ? 'updated' : 'added';
            $_SESSION['success_message'] = "Alat berhasil " . ($id ? 'diperbarui' : 'ditambahkan') . " dengan data lengkap!";
            header("Location: kelola_alat.php?success=" . $action);
            exit();
        } else {
            $errors[] = "Gagal menyimpan data: " . $conn->error;
        }
    }
    
    // Simpan error ke session untuk ditampilkan
    if (!empty($errors)) {
        $_SESSION['error_messages'] = $errors;
    }
} elseif (isset($_GET['hapus'])) {
    // Hapus
    $id = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM alat WHERE id_alat = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Alat berhasil dihapus!";
        header("Location: kelola_alat.php?success=deleted");
        exit();
    }
}

// Filter berdasarkan jenis alat
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query dengan filter dan search
$query = "SELECT * FROM alat WHERE 1=1";
$params = [];
$types = "";

if ($filter != 'semua') {
    $query .= " AND jenis_alat = ?";
    $params[] = $filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (nama_alat LIKE ? OR merk LIKE ? OR type_model LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$query .= " ORDER BY jenis_alat, nama_alat";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $alat = $stmt->get_result();
} else {
    $alat = $conn->query($query);
}

// Hitung statistik
$stats_medis = $conn->query("SELECT COUNT(*) as count FROM alat WHERE jenis_alat = 'medis'")->fetch_assoc()['count'];
$stats_non_medis = $conn->query("SELECT COUNT(*) as count FROM alat WHERE jenis_alat = 'non_medis'")->fetch_assoc()['count'];
$stats_total = $stats_medis + $stats_non_medis;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Alat - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <style>
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
            border-left-color: #5a5c69;
        }
        .badge-medis {
            background-color: #1cc88a;
            color: white;
        }
        .badge-non-medis {
            background-color: #36b9cc;
            color: white;
        }
        .filter-section {
            background: #f8f9fc;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .required-field {
            color: #e74a3b;
            font-weight: bold;
        }
        .form-control.is-invalid {
            border-color: #e74a3b;
            box-shadow: 0 0 0 0.2rem rgba(231, 74, 59, 0.25);
        }
        .mandatory-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .form-section {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
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

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Kelola Peralatan Medis & Non-Medis</h1>

                    <!-- Success Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?= $_SESSION['success_message'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <!-- Error Messages -->
                    <?php if (isset($_SESSION['error_messages'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Terjadi kesalahan:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($_SESSION['error_messages'] as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error_messages']); ?>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stats-card medis shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Alat Medis</div>
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
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Alat Non-Medis</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_non_medis ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chair fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-6 mb-4">
                            <div class="card stats-card total shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-gray-800 text-uppercase mb-1">
                                                Total Alat</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats_total ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-section">
                        <form method="GET" class="row align-items-end">
                            <div class="col-md-3">
                                <label for="filter" class="form-label font-weight-bold">Filter Jenis Alat:</label>
                                <select name="filter" id="filter" class="form-control">
                                    <option value="semua" <?= $filter == 'semua' ? 'selected' : '' ?>>Semua Alat</option>
                                    <option value="medis" <?= $filter == 'medis' ? 'selected' : '' ?>>Alat Medis</option>
                                    <option value="non_medis" <?= $filter == 'non_medis' ? 'selected' : '' ?>>Alat Non-Medis</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label font-weight-bold">Cari Alat:</label>
                                <input type="text" name="search" id="search" class="form-control" 
                                       placeholder="Nama alat, merk, atau model..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="kelola_alat.php" class="btn btn-secondary btn-block">
                                    <i class="fas fa-undo"></i> Reset Filter
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Main Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-success">
                                Daftar Peralatan 
                                <?php if ($filter != 'semua'): ?>
                                    - <?= $filter == 'medis' ? 'Medis' : 'Non-Medis' ?>
                                <?php endif; ?>
                                <?php if (!empty($search)): ?>
                                    (Pencarian: "<?= htmlspecialchars($search) ?>")
                                <?php endif; ?>
                            </h6>
                            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#alatModal">
                                <i class="fas fa-plus"></i> Tambah Alat
                            </button>
                        </div>
                        
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Jenis</th>
                                            <th>Nama Alat</th>
                                            <th>Merk</th>
                                            <th>Tipe/Model</th>
                                            <th>No. Seri</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($alat->num_rows > 0): ?>
                                            <?php while($row = $alat->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-<?= $row['jenis_alat'] == 'medis' ? 'medis' : 'non-medis' ?>">
                                                        <?= $row['jenis_alat'] == 'medis' ? 'Medis' : 'Non-Medis' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <i class="fas fa-<?= $row['jenis_alat'] == 'medis' ? 'stethoscope' : 'chair' ?> mr-2 text-muted"></i>
                                                    <?= htmlspecialchars($row['nama_alat']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['merk']) ?></td>
                                                <td><?= htmlspecialchars($row['type_model']) ?></td>
                                                <td><?= htmlspecialchars($row['no_seri']) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning edit-btn" 
                                                            data-id="<?= $row['id_alat'] ?>"
                                                            data-nama="<?= htmlspecialchars($row['nama_alat']) ?>"
                                                            data-merk="<?= htmlspecialchars($row['merk']) ?>"
                                                            data-type="<?= htmlspecialchars($row['type_model']) ?>"
                                                            data-seri="<?= htmlspecialchars($row['no_seri']) ?>"
                                                            data-jenis="<?= $row['jenis_alat'] ?>"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?hapus=<?= $row['id_alat'] ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Yakin ingin menghapus alat ini?')"
                                                       title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i><br>
                                                    <span class="text-muted">Tidak ada data alat yang ditemukan</span>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Alat -->
    <div class="modal fade" id="alatModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="alatForm" novalidate>
                    <input type="hidden" name="id" id="inputId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">
                            <i class="fas fa-plus-circle mr-2"></i>Tambah Alat Baru
                        </h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <!-- Mandatory Notice -->
                        <div class="mandatory-notice">
                            <i class="fas fa-exclamation-triangle mr-2 text-warning"></i>
                            <strong>PERHATIAN:</strong> Semua field di bawah ini <strong class="required-field">WAJIB DIISI</strong> untuk menambahkan alat ke sistem.
                        </div>

                        <!-- Jenis & Nama Alat Section -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="fas fa-tag mr-2"></i>Identifikasi Alat
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            Jenis Alat <span class="required-field">*WAJIB</span>
                                        </label>
                                        <select name="jenis_alat" class="form-control" id="jenisSelect" required>
                                            <option value="">-- Pilih Jenis Alat --</option>
                                            <option value="medis">Alat Medis</option>
                                            <option value="non_medis">Alat Non-Medis</option>
                                        </select>
                                        <small class="form-text text-danger">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            <strong>WAJIB:</strong> Tentukan kategori alat
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            Nama Alat <span class="required-field">*WAJIB</span>
                                        </label>
                                        <input type="text" 
                                               name="nama_alat" 
                                               class="form-control" 
                                               id="namaInput"
                                               placeholder="Contoh: Stetoskop, Kursi Roda" 
                                               required>
                                        <small class="form-text text-danger">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            <strong>WAJIB:</strong> Nama alat harus diisi
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Spesifikasi Alat Section -->
                        <div class="form-section">
                            <h6 class="section-title">
                                <i class="fas fa-cogs mr-2"></i>Spesifikasi Alat
                            </h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            Merk <span class="required-field">*WAJIB</span>
                                        </label>
                                        <input type="text" 
                                               name="merk" 
                                               class="form-control" 
                                               id="merkInput"
                                               placeholder="Contoh: Littmann, GEA Medical" 
                                               required>
                                        <small class="form-text text-danger">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            <strong>WAJIB:</strong> Merk/brand alat
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">
                                            Tipe/Model <span class="required-field">*WAJIB</span>
                                        </label>
                                        <input type="text" 
                                               name="type_model" 
                                               class="form-control" 
                                               id="typeInput"
                                               placeholder="Contoh: Classic III, KR-901" 
                                               required>
                                        <small class="form-text text-danger">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            <strong>WAJIB:</strong> Model/tipe alat
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">
                                    Nomor Seri <span class="required-field">*WAJIB</span>
                                </label>
                                <input type="text" 
                                       name="no_seri" 
                                       class="form-control" 
                                       id="seriInput"
                                       placeholder="Masukkan nomor seri alat" 
                                       required>
                                <small class="form-text text-danger">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <strong>WAJIB:</strong> Nomor seri untuk identifikasi unik
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-success" onclick="return validateForm()">
                            <i class="fas fa-save mr-1"></i>Simpan Alat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../../vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
    $(document).ready(function() {
        // DataTable
        $('#dataTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            },
            "order": [[ 0, "asc" ], [ 1, "asc" ]], // Sort by jenis then nama
            "columnDefs": [
                { "width": "10%", "targets": 0 }, // Jenis column
                { "width": "15%", "targets": 5 }  // Aksi column
            ]
        });

        // Edit Modal Handler
        $('.edit-btn').click(function() {
            $('#modalTitle').html('<i class="fas fa-edit mr-2"></i>Edit Data Alat');
            $('#inputId').val($(this).data('id'));
            $('#namaInput').val($(this).data('nama'));
            $('#merkInput').val($(this).data('merk'));
            $('#typeInput').val($(this).data('type'));
            $('#seriInput').val($(this).data('seri'));
            $('#jenisSelect').val($(this).data('jenis'));
            
            // Reset validation styling
            $('.form-control').removeClass('is-invalid');
            
            $('#alatModal').modal('show');
        });

        // Reset form when modal is closed
        $('#alatModal').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $('#inputId').val('');
            $('#modalTitle').html('<i class="fas fa-plus-circle mr-2"></i>Tambah Alat Baru');
            
            // Reset validation styling
            $('.form-control').removeClass('is-invalid');
        });

        // Real-time validation
        $('.form-control').on('blur', function() {
            validateField(this);
        });

        $('.form-control').on('input', function() {
            if ($(this).hasClass('is-invalid') && $(this).val().trim() !== '') {
                $(this).removeClass('is-invalid');
            }
        });

        // Auto dismiss alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });

    // Validate individual field
    function validateField(field) {
        const $field = $(field);
        const value = $field.val().trim();
        
        if ($field.attr('required') && value === '') {
            $field.addClass('is-invalid');
            return false;
        } else {
            $field.removeClass('is-invalid');
            return true;
        }
    }

    // Validasi form sebelum submit
    function validateForm() {
        const jenis = $('#jenisSelect').val();
        const nama = $('#namaInput').val().trim();
        const merk = $('#merkInput').val().trim();
        const type = $('#typeInput').val().trim();
        const seri = $('#seriInput').val().trim();
        
        let isValid = true;
        let errorMessages = [];
        
        // Reset styling
        $('.form-control').removeClass('is-invalid');
        
        // Validasi jenis alat
        if (jenis === '') {
            $('#jenisSelect').addClass('is-invalid');
            errorMessages.push('Jenis alat wajib dipilih!');
            isValid = false;
        }
        
        // Validasi nama alat
        if (nama === '') {
            $('#namaInput').addClass('is-invalid');
            errorMessages.push('Nama alat wajib diisi!');
            isValid = false;
        }
        
        // Validasi merk
        if (merk === '') {
            $('#merkInput').addClass('is-invalid');
            errorMessages.push('Merk alat wajib diisi!');
            isValid = false;
        }
        
        // Validasi tipe/model
        if (type === '') {
            $('#typeInput').addClass('is-invalid');
            errorMessages.push('Tipe/Model alat wajib diisi!');
            isValid = false;
        }
        
        // Validasi nomor seri
        if (seri === '') {
            $('#seriInput').addClass('is-invalid');
            errorMessages.push('Nomor seri alat wajib diisi!');
            isValid = false;
        }
        
        if (!isValid) {
            alert('❌ FORM BELUM LENGKAP!\n\nSemua field wajib diisi:\n\n' + errorMessages.join('\n'));
            return false;
        }
        
        // Konfirmasi sebelum simpan
        const jenisText = jenis === 'medis' ? 'Medis' : 'Non-Medis';
        return confirm(`✅ KONFIRMASI SIMPAN ALAT\n\nApakah data sudah benar?\n\n• Jenis: ${jenisText}\n• Nama: ${nama}\n• Merk: ${merk}\n• Tipe/Model: ${type}\n• No. Seri: ${seri}\n\nData akan disimpan ke sistem.`);
    }
    </script>
</body>
</html>
