<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once '../../config/config.php';

// CRUD Operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tambah/Edit data
    if (isset($_POST['simpan'])) {
        $lantai = $_POST['lantai'];
        $sayap = ($lantai == 'Poliklinik Geriatri') ? NULL : ($_POST['sayap'] ?? NULL);
        $nama = trim($_POST['nama_ruangan']);
        $id = $_POST['id'] ?? null;
        
        $errors = [];
        
        // VALIDASI WAJIB: Nama ruangan harus diisi
        if (empty($nama)) {
            $errors[] = "Nama ruangan wajib diisi!";
        }
        
        // VALIDASI WAJIB: Sayap harus diisi jika bukan Poliklinik Geriatri
        if ($lantai != 'Poliklinik Geriatri' && empty($sayap)) {
            $errors[] = "Sayap wajib dipilih untuk lantai 1, 2, dan 3!";
        }
        
        // Jika tidak ada error, simpan data
        if (empty($errors)) {
            if ($id) {
                // Edit
                $stmt = $conn->prepare("UPDATE ruangan SET 
                                      lantai = ?,
                                      sayap = ?,
                                      nama_ruangan = ?
                                      WHERE id_ruangan = ?");
                $stmt->bind_param("sssi", $lantai, $sayap, $nama, $id);
            } else {
                // Tambah
                $stmt = $conn->prepare("INSERT INTO ruangan (lantai, sayap, nama_ruangan) 
                                      VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $lantai, $sayap, $nama);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Data ruangan berhasil disimpan!";
            } else {
                $errors[] = "Gagal menyimpan data: " . $conn->error;
            }
        }
        
        // Simpan error ke session untuk ditampilkan
        if (!empty($errors)) {
            $_SESSION['error_messages'] = $errors;
        }
    }
} elseif (isset($_GET['hapus'])) {
    // Hapus
    $id = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM ruangan WHERE id_ruangan = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Ruangan berhasil dihapus!";
    }
}

$ruangan = $conn->query("SELECT * FROM ruangan ORDER BY lantai, sayap, nama_ruangan");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Ruangan - SIMONFAST</title>
    
    <!-- Custom fonts -->
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="../../css/sb-admin-2.min.css" rel="stylesheet">
    <link href="../../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    
    <style>
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
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
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
                    <h1 class="h3 mb-4 text-gray-800">Kelola Ruangan</h1>

                    <!-- Success Message -->
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

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-success">Daftar Ruangan</h6>
                            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#ruanganModal">
                                <i class="fas fa-plus"></i> Tambah Ruangan
                            </button>
                        </div>
                        
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Lokasi</th>
                                            <th>Nama Ruangan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $ruangan->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?= $row['lantai'] ?>
                                                <?= ($row['lantai'] != 'Poliklinik Geriatri' && $row['sayap']) ? 
                                                    ' - '.$row['sayap'] : '' ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['nama_ruangan']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning edit-btn" 
                                                        data-id="<?= $row['id_ruangan'] ?>"
                                                        data-lantai="<?= $row['lantai'] ?>"
                                                        data-sayap="<?= $row['sayap'] ?>"
                                                        data-nama="<?= htmlspecialchars($row['nama_ruangan']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?hapus=<?= $row['id_ruangan'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Hapus ruangan ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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

    <!-- Ruangan Modal -->
    <div class="modal fade" id="ruanganModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="ruanganForm" novalidate>
                    <input type="hidden" name="id" id="inputId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah Ruangan Baru</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <!-- Mandatory Notice -->
                        <div class="mandatory-notice">
                            <i class="fas fa-exclamation-triangle mr-2 text-warning"></i>
                            <strong>PERHATIAN:</strong> Field bertanda <span class="required-field">*WAJIB</span> harus diisi!
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">
                                Lantai/Poliklinik <span class="required-field">*WAJIB</span>
                            </label>
                            <select name="lantai" class="form-control" id="lantaiSelect" required>
                                <option value="">-- Pilih Lantai/Poliklinik --</option>
                                <option value="Poliklinik Geriatri">Poliklinik Geriatri</option>
                                <option value="1">Lantai 1</option>
                                <option value="2">Lantai 2</option>
                                <option value="3">Lantai 3</option>
                            </select>
                            <small class="form-text text-danger">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>WAJIB:</strong> Pilih lokasi lantai atau poliklinik
                            </small>
                        </div>
                        
                        <div class="form-group" id="sayapGroup">
                            <label class="font-weight-bold">
                                Sayap <span class="required-field">*WAJIB</span>
                            </label>
                            <select name="sayap" class="form-control" id="sayapSelect">
                                <option value="">-- Pilih Sayap --</option>
                                <option value="Sayap A">Sayap A</option>
                                <option value="Sayap B">Sayap B</option>
                            </select>
                            <small class="form-text text-danger">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>WAJIB:</strong> Pilih sayap untuk lantai 1, 2, dan 3
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">
                                Nama Ruangan <span class="required-field">*WAJIB</span>
                            </label>
                            <input type="text" 
                                   name="nama_ruangan" 
                                   class="form-control" 
                                   id="namaInput" 
                                   placeholder="Masukkan nama ruangan"
                                   required>
                            <small class="form-text text-danger">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>WAJIB:</strong> Nama ruangan harus diisi
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="simpan" class="btn btn-success" onclick="return validateForm()">
                            <i class="fas fa-save mr-1"></i>Simpan
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
        $('#dataTable').DataTable();

        // Modal Logic
        $('.edit-btn').click(function() {
            $('#modalTitle').text('Edit Ruangan');
            $('#inputId').val($(this).data('id'));
            $('#lantaiSelect').val($(this).data('lantai'));
            $('#sayapSelect').val($(this).data('sayap') || '');
            $('#namaInput').val($(this).data('nama'));
            toggleSayap($(this).data('lantai'));
            
            // Reset validation styling
            $('.form-control').removeClass('is-invalid');
            
            $('#ruanganModal').modal('show');
        });

        $('#ruanganModal').on('show.bs.modal', function() {
            if(!$('#inputId').val()) {
                $('#modalTitle').text('Tambah Ruangan Baru');
                $('#lantaiSelect').val('');
                $('#sayapSelect').val('');
                $('#namaInput').val('');
                toggleSayap('');
                
                // Reset validation styling
                $('.form-control').removeClass('is-invalid');
            }
        });

        // Toggle Sayap Visibility and Required
        function toggleSayap(lantai) {
            if(lantai === 'Poliklinik Geriatri') {
                $('#sayapGroup').hide();
                $('#sayapSelect').val('').removeAttr('required');
            } else {
                $('#sayapGroup').show();
                $('#sayapSelect').attr('required', 'required');
            }
        }

        $('#lantaiSelect').change(function() {
            toggleSayap($(this).val());
            // Reset sayap selection when lantai changes
            $('#sayapSelect').val('').removeClass('is-invalid');
        });

        // Real-time validation
        $('.form-control').on('blur', function() {
            validateField(this);
        });

        $('.form-control').on('input change', function() {
            if ($(this).hasClass('is-invalid') && $(this).val().trim() !== '') {
                $(this).removeClass('is-invalid');
            }
        });
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
        const lantai = $('#lantaiSelect').val();
        const sayap = $('#sayapSelect').val();
        const nama = $('#namaInput').val().trim();
        
        let isValid = true;
        let errorMessages = [];
        
        // Reset styling
        $('.form-control').removeClass('is-invalid');
        
        // Validasi lantai
        if (lantai === '') {
            $('#lantaiSelect').addClass('is-invalid');
            errorMessages.push('Lantai/Poliklinik wajib dipilih!');
            isValid = false;
        }
        
        // Validasi sayap (jika bukan Poliklinik Geriatri)
        if (lantai !== 'Poliklinik Geriatri' && lantai !== '' && sayap === '') {
            $('#sayapSelect').addClass('is-invalid');
            errorMessages.push('Sayap wajib dipilih untuk lantai 1, 2, dan 3!');
            isValid = false;
        }
        
        // Validasi nama ruangan
        if (nama === '') {
            $('#namaInput').addClass('is-invalid');
            errorMessages.push('Nama ruangan wajib diisi!');
            isValid = false;
        }
        
        if (!isValid) {
            alert('❌ FORM BELUM LENGKAP!\n\nField yang wajib diisi:\n\n' + errorMessages.join('\n'));
            return false;
        }
        
        // Konfirmasi sebelum simpan
        const lokasi = lantai === 'Poliklinik Geriatri' ? lantai : `${lantai} - ${sayap}`;
        return confirm(`✅ KONFIRMASI SIMPAN RUANGAN\n\nApakah data sudah benar?\n\n• Lokasi: ${lokasi}\n• Nama Ruangan: ${nama}\n\nData akan disimpan ke sistem.`);
    }
    </script>
</body>
</html>
