<?php
session_start();
require '../../config/config.php';

if ($_SESSION['role'] != 'admin_teknisi') {
    header("Location: ../../login.php");
    exit();
}

// Handle export request
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Get all inventaris data
    $inventaris = $conn->query("SELECT * FROM inventaris_alat ORDER BY nama_alat ASC");
    
    // Generate filename with timestamp
    $filename = "Inventaris_Alat_" . date('Y-m-d_H-i-s') . ".xls";
    
    // Set headers for Excel download
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Add BOM for UTF-8
    echo "\xEF\xBB\xBF";
    
    // Create Excel content with proper formatting
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            .header { 
                font-weight: bold; 
                font-size: 16pt; 
                text-align: center; 
                background-color: #1cc88a; 
                color: white;
                padding: 10px;
            }
            .info { 
                font-weight: bold; 
                font-size: 11pt; 
                background-color: #f8f9fc;
                padding: 5px;
            }
            .table-header { 
                font-weight: bold; 
                background-color: #4CAF50; 
                color: white; 
                text-align: center; 
                border: 2px solid #000;
                padding: 8px;
                font-size: 12pt;
            }
            .table-data { 
                border: 1px solid #000; 
                text-align: left; 
                vertical-align: top;
                padding: 5px;
                font-size: 11pt;
            }
            .table-number { 
                border: 1px solid #000; 
                text-align: center;
                padding: 5px;
                font-size: 11pt;
            }
            .summary { 
                font-weight: bold; 
                background-color: #e3f2fd;
                border: 1px solid #000;
                padding: 5px;
                font-size: 11pt;
            }
            .footer {
                font-size: 9pt;
                color: #666;
                text-align: center;
                font-style: italic;
            }
        </style>
    </head>
    <body>
    
    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <!-- Header Section -->
        <tr>
            <td colspan="4" class="header">LAPORAN INVENTARIS ALAT TEKNISI</td>
        </tr>
        <tr><td colspan="4">&nbsp;</td></tr>
        
        <!-- Information Section -->
        <tr>
            <td class="info">Tanggal Export:</td>
            <td colspan="3" class="info"><?= date('d/m/Y H:i:s') ?></td>
        </tr>
        <tr>
            <td class="info">Diekspor oleh:</td>
            <td colspan="3" class="info"><?= htmlspecialchars($_SESSION['nama']) ?></td>
        </tr>
        <tr>
            <td class="info">Total Data:</td>
            <td colspan="3" class="info"><?= mysqli_num_rows($inventaris) ?> item</td>
        </tr>
        <tr><td colspan="4">&nbsp;</td></tr>
        
        <!-- Table Header -->
        <tr>
            <td class="table-header" width="10%">No</td>
            <td class="table-header" width="40%">Nama Alat</td>
            <td class="table-header" width="30%">Merk</td>
            <td class="table-header" width="20%">Jumlah (Unit)</td>
        </tr>
        
        <!-- Data Rows -->
        <?php
        $no = 1;
        $total_unit = 0;
        
        while ($row = mysqli_fetch_assoc($inventaris)) {
            $total_unit += $row['jumlah_unit'];
            ?>
            <tr>
                <td class="table-number"><?= $no++ ?></td>
                <td class="table-data"><?= htmlspecialchars($row['nama_alat']) ?></td>
                <td class="table-data"><?= htmlspecialchars($row['merk']) ?></td>
                <td class="table-number"><?= $row['jumlah_unit'] ?></td>
            </tr>
        <?php } ?>
        
        <!-- Summary Section -->
        <tr><td colspan="4">&nbsp;</td></tr>
        <tr>
            <td colspan="4" class="header">RINGKASAN INVENTARIS</td>
        </tr>
        <tr><td colspan="4">&nbsp;</td></tr>
        
        <tr>
            <td class="summary">Total Item Alat:</td>
            <td class="summary"><?= $no - 1 ?> jenis</td>
            <td class="summary">Total Unit:</td>
            <td class="summary"><?= $total_unit ?> unit</td>
        </tr>
        
        <!-- Footer -->
        <tr><td colspan="4">&nbsp;</td></tr>
        <tr><td colspan="4">&nbsp;</td></tr>
        <tr>
            <td colspan="4" class="footer">
                Laporan ini dibuat secara otomatis oleh Sistem SIMONFAST<br>
                Dicetak pada: <?= date('d F Y, H:i:s') ?> WIB
            </td>
        </tr>
    </table>
    
    </body>
    </html>
    
    <?php
    mysqli_close($conn);
    exit();
}

// PROSES CRUD (kode yang sudah ada tetap sama)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Tambah Alat
        if (isset($_POST['tambah_alat'])) {
            $nama_alat = trim($_POST['nama_alat']);
            $merk = trim($_POST['merk']);
            $jumlah = trim($_POST['jumlah']);

            // Validasi field wajib diisi
            $errors = array();
            
            if (empty($nama_alat)) {
                $errors[] = "Nama alat wajib diisi!";
            }
            
            if (empty($merk)) {
                $errors[] = "Merk alat wajib diisi!";
            }
            
            if (empty($jumlah) || !is_numeric($jumlah) || $jumlah <= 0) {
                $errors[] = "Jumlah alat wajib diisi dengan angka yang valid!";
            }
            
            // Jika ada error, tampilkan pesan error
            if (!empty($errors)) {
                $_SESSION['error'] = implode("<br>", $errors);
            } else {
                // Escape string untuk keamanan
                $nama_alat = $conn->real_escape_string($nama_alat);
                $merk = $conn->real_escape_string($merk);
                $jumlah = $conn->real_escape_string($jumlah);

                $conn->query("INSERT INTO inventaris_alat (nama_alat, merk, jumlah_unit) 
                            VALUES ('$nama_alat', '$merk', '$jumlah')");
                $_SESSION['success'] = "Alat berhasil ditambahkan!";
            }
        }

        // Edit Alat
        if (isset($_POST['edit_alat'])) {
            $id = trim($_POST['id']);
            $nama_alat = trim($_POST['nama_alat']);
            $merk = trim($_POST['merk']);
            $jumlah = trim($_POST['jumlah']);

            // Validasi field wajib diisi
            $errors = array();
            
            if (empty($nama_alat)) {
                $errors[] = "Nama alat wajib diisi!";
            }
            
            if (empty($merk)) {
                $errors[] = "Merk alat wajib diisi!";
            }
            
            if (empty($jumlah) || !is_numeric($jumlah) || $jumlah <= 0) {
                $errors[] = "Jumlah alat wajib diisi dengan angka yang valid!";
            }
            
            // Jika ada error, tampilkan pesan error
            if (!empty($errors)) {
                $_SESSION['error'] = implode("<br>", $errors);
            } else {
                // Escape string untuk keamanan
                $id = $conn->real_escape_string($id);
                $nama_alat = $conn->real_escape_string($nama_alat);
                $merk = $conn->real_escape_string($merk);
                $jumlah = $conn->real_escape_string($jumlah);

                $conn->query("UPDATE inventaris_alat 
                            SET nama_alat = '$nama_alat', 
                                merk = '$merk', 
                                jumlah_unit = '$jumlah' 
                            WHERE id_inventaris = '$id'");
                $_SESSION['success'] = "Alat berhasil diperbarui!";
            }
        }

        // Hapus Alat
        if (isset($_POST['delete_alat'])) {
            $id = $conn->real_escape_string($_POST['id']);
            $conn->query("DELETE FROM inventaris_alat WHERE id_inventaris = '$id'");
            $_SESSION['success'] = "Alat berhasil dihapus!";
        }

    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header("Location: inventaris.php");
    exit();
}

// Ambil data inventaris
$inventaris = $conn->query("SELECT * FROM inventaris_alat ORDER BY nama_alat ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventaris Alat Teknisi - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
    .inventaris-card { transition: transform 0.2s; }
    .inventaris-card:hover { transform: translateY(-3px); }
    .export-btn {
        background: linear-gradient(45deg, #1cc88a, #17a673);
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        color: white;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(28, 200, 138, 0.3);
        font-weight: 600;
    }
    .export-btn:hover { 
        background: linear-gradient(45deg, #17a673, #13855c);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(28, 200, 138, 0.4);
        color: white;
        text-decoration: none;
    }
    .action-btns .btn { margin: 0 3px; }
    .modal-header { border-bottom: 2px solid #1cc88a; }
    .required-field { color: red; font-weight: bold; }
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }
    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }
    .stats-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include '../../includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include '../../includes/topbar.php'; ?>

                <!-- Notifikasi -->
                <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show m-3">
                    <i class="fas fa-check-circle mr-2"></i><?= $_SESSION['success'] ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show m-3">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Inventaris Alat Teknisi</h1>
                    
                    <!-- Statistics Summary -->
                    <?php 
                    $total_items = mysqli_num_rows($inventaris);
                    $total_units_query = $conn->query("SELECT SUM(jumlah_unit) as total_units FROM inventaris_alat");
                    $total_units = mysqli_fetch_assoc($total_units_query)['total_units'] ?? 0;
                    ?>
                    <div class="stats-summary">
                        <div class="row">
                            <div class="col-md-6">
                                <h4><i class="fas fa-tools mr-2"></i>Total Jenis Alat: <strong><?= $total_items ?></strong></h4>
                            </div>
                            <div class="col-md-6">
                                <h4><i class="fas fa-cubes mr-2"></i>Total Unit: <strong><?= $total_units ?></strong></h4>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-lg mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                            <h6 class="m-0 font-weight-bold text-success">
                                <i class="fas fa-toolbox"></i> Daftar Inventaris Alat
                            </h6>
                            <div>
                                <a href="?export=excel" class="export-btn mr-2">
                                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                                </a>
                                <button class="btn btn-success" data-toggle="modal" data-target="#tambahAlatModal">
                                    <i class="fas fa-plus"></i> Tambah Alat
                                </button>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Alat</th>
                                            <th>Merk</th>
                                            <th>Jumlah</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        mysqli_data_seek($inventaris, 0); // Reset pointer
                                        while($row = $inventaris->fetch_assoc()): 
                                        ?>
                                        <tr class="inventaris-card">
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['nama_alat']) ?></td>
                                            <td><?= htmlspecialchars($row['merk']) ?></td>
                                            <td><span class="badge badge-primary"><?= $row['jumlah_unit'] ?> unit</span></td>
                                            <td class="action-btns">
                                                <button class="btn btn-sm btn-warning edit-btn"
                                                        data-id="<?= $row['id_inventaris'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_alat']) ?>"
                                                        data-merk="<?= htmlspecialchars($row['merk']) ?>"
                                                        data-jumlah="<?= $row['jumlah_unit'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Hapus alat ini?')">
                                                    <input type="hidden" name="id" value="<?= $row['id_inventaris'] ?>">
                                                    <button type="submit" name="delete_alat" 
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah (sama seperti sebelumnya) -->
    <div class="modal fade" id="tambahAlatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-success"><i class="fas fa-toolbox"></i> Tambah Alat Baru</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" id="tambahAlatForm">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Perhatian:</strong> Semua field wajib diisi dengan lengkap!
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Alat <span class="required-field">*</span></label>
                            <input type="text" name="nama_alat" id="nama_alat" class="form-control" required>
                            <div class="invalid-feedback" id="nama_alat_error"></div>
                        </div>
                        <div class="form-group">
                            <label>Merk <span class="required-field">*</span></label>
                            <input type="text" name="merk" id="merk" class="form-control" required>
                            <div class="invalid-feedback" id="merk_error"></div>
                        </div>
                        <div class="form-group">
                            <label>Jumlah/Unit <span class="required-field">*</span></label>
                            <input type="number" name="jumlah" id="jumlah" class="form-control" required min="1">
                            <div class="invalid-feedback" id="jumlah_error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_alat" class="btn btn-success">
                            <i class="fas fa-save mr-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit (sama seperti sebelumnya) -->
    <div class="modal fade" id="editAlatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-success"><i class="fas fa-edit"></i> Edit Alat</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST" id="editAlatForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Perhatian:</strong> Semua field wajib diisi dengan lengkap!
                        </div>
                        
                        <div class="form-group">
                            <label>Nama Alat <span class="required-field">*</span></label>
                            <input type="text" name="nama_alat" id="edit_nama" class="form-control" required>
                            <div class="invalid-feedback" id="edit_nama_error"></div>
                        </div>
                        <div class="form-group">
                            <label>Merk <span class="required-field">*</span></label>
                            <input type="text" name="merk" id="edit_merk" class="form-control" required>
                            <div class="invalid-feedback" id="edit_merk_error"></div>
                        </div>
                        <div class="form-group">
                            <label>Jumlah/Unit <span class="required-field">*</span></label>
                            <input type="number" name="jumlah" id="edit_jumlah" class="form-control" required min="1">
                            <div class="invalid-feedback" id="edit_jumlah_error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_alat" class="btn btn-success">
                            <i class="fas fa-save mr-1"></i>Simpan Perubahan
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
    <script src="../../js/sb-admin-2.min.js"></script>

    <script>
    $(document).ready(function() {
        // Handle Edit Button
        $('.edit-btn').click(function() {
            const data = $(this).data();
            $('#edit_id').val(data.id);
            $('#edit_nama').val(data.nama);
            $('#edit_merk').val(data.merk);
            $('#edit_jumlah').val(data.jumlah);
            $('#editAlatModal').modal('show');
        });

        // DataTable
        $('#dataTable').DataTable({
            language: {
                search: "Cari alat:",
                zeroRecords: "Tidak ada data alat",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ alat",
                paginate: {
                    previous: "‹",
                    next: "›"
                }
            }
        });

        // Form Validation untuk Tambah Alat
        $('#tambahAlatForm').on('submit', function(e) {
            let isValid = true;
            
            // Reset previous validation
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            
            // Validasi Nama Alat
            const namaAlat = $('#nama_alat').val().trim();
            if (namaAlat === '') {
                $('#nama_alat').addClass('is-invalid');
                $('#nama_alat_error').text('Nama alat wajib diisi!');
                isValid = false;
            }
            
            // Validasi Merk
            const merk = $('#merk').val().trim();
            if (merk === '') {
                $('#merk').addClass('is-invalid');
                $('#merk_error').text('Merk alat wajib diisi!');
                isValid = false;
            }
            
            // Validasi Jumlah
            const jumlah = $('#jumlah').val().trim();
            if (jumlah === '' || isNaN(jumlah) || parseInt(jumlah) <= 0) {
                $('#jumlah').addClass('is-invalid');
                $('#jumlah_error').text('Jumlah alat wajib diisi dengan angka yang valid!');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Mohon lengkapi semua field yang wajib diisi.');
            }
        });

        // Form Validation untuk Edit Alat
        $('#editAlatForm').on('submit', function(e) {
            let isValid = true;
            
            // Reset previous validation
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            
            // Validasi Nama Alat
            const namaAlat = $('#edit_nama').val().trim();
            if (namaAlat === '') {
                $('#edit_nama').addClass('is-invalid');
                $('#edit_nama_error').text('Nama alat wajib diisi!');
                isValid = false;
            }
            
            // Validasi Merk
            const merk = $('#edit_merk').val().trim();
            if (merk === '') {
                $('#edit_merk').addClass('is-invalid');
                $('#edit_merk_error').text('Merk alat wajib diisi!');
                isValid = false;
            }
            
            // Validasi Jumlah
            const jumlah = $('#edit_jumlah').val().trim();
            if (jumlah === '' || isNaN(jumlah) || parseInt(jumlah) <= 0) {
                $('#edit_jumlah').addClass('is-invalid');
                $('#edit_jumlah_error').text('Jumlah alat wajib diisi dengan angka yang valid!');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Mohon lengkapi semua field yang wajib diisi.');
            }
        });

        // Real-time validation
        $('#nama_alat, #edit_nama').on('input', function() {
            if ($(this).val().trim() !== '') {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('');
            }
        });

        $('#merk, #edit_merk').on('input', function() {
            if ($(this).val().trim() !== '') {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('');
            }
        });

        $('#jumlah, #edit_jumlah').on('input', function() {
            const value = $(this).val().trim();
            if (value !== '' && !isNaN(value) && parseInt(value) > 0) {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('');
            }
        });

        // Reset form when modal is closed
        $('#tambahAlatModal').on('hidden.bs.modal', function() {
            $('#tambahAlatForm')[0].reset();
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
        });

        $('#editAlatModal').on('hidden.bs.modal', function() {
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');
        });
    });
    </script>
</body>
</html>
