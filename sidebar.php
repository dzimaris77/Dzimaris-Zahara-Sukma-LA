<!-- Sidebar -->
<ul class="navbar-nav sidebar sidebar-dark accordion bg-gradient-custom" id="accordionSidebar">

    <!-- Sidebar Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="../dashboard/">
        <div class="sidebar-brand-icon">
            <img src="../../img/logo.png" alt="SIMONFAST Logo" class="img-fluid" style="max-height: 40px;">
        </div>
        <div class="sidebar-brand-text mx-3">SIMONFAST</div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard Link -->
    <li class="nav-item">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- Role-based Menu -->
    <?php if($_SESSION['role'] == 'admin'): ?>
        <div class="sidebar-heading">Administrator</div>
        
        <!-- Menu Collapse Admin -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseAdmin" 
                aria-expanded="true" aria-controls="collapseAdmin">
                <i class="fas fa-tasks"></i>
                <span>Manajemen</span>
            </a>
            <div id="collapseAdmin" class="collapse" aria-labelledby="headingAdmin" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
        <a class="collapse-item" href="kelola_user.php">
            <i class="fas fa-user-friends mr-2"></i> Manajemen User
        </a>
        <a class="collapse-item" href="kelola_ruangan.php">
        <i class="fas fa-th-large mr-2"></i> Manajemen Ruangan
        </a>
        <a class="collapse-item" href="kelola_alat.php">
        <i class="fas fa-toolbox mr-2"></i> Manajemen Alat
        </a>
    </div>
</div>
        </li>
        <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseLaporan" 
        aria-expanded="false" aria-controls="collapseLaporan">
        <i class="fas fa-file-alt"></i>
        <span>Laporan</span>
    </a>
    <div id="collapseLaporan" class="collapse" aria-labelledby="headingLaporan" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="verify.php">
                <i class="fas fa-clipboard-check mr-2"></i>Verifikasi Laporan
            </a>
            <a class="collapse-item" href="daftar_laporan.php">
                <i class="fas fa-clipboard-list mr-2"></i>Daftar Laporan
            </a>
            <a class="collapse-item" href="report.php">
               <i class="fas fa-door-open mr-2"></i> Laporan Per Ruangan
            </a>
             <a class="collapse-item" href="analisis.php">
               <i class="fas fa-chart-line mr-2"></i> Analisis Alat
            </a>
        </div>
    </div>
</li>


    <?php elseif($_SESSION['role'] == 'tenaga_kesehatan'): ?>
        <div class="sidebar-heading">Pelaporan Fasilitas</div>
        
        <!-- Menu Collapse Tenaga Kesehatan -->
        <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMedis" 
        aria-expanded="false" aria-controls="collapseMedis">
        <i class="fas fa-building mr-2"></i>
        <span>Pelaporan Fasilitas</span>
    </a>
    <div id="collapseMedis" class="collapse" aria-labelledby="headingMedis" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="tambah_laporan.php">
                <i class="fas fa-file-medical mr-2"></i> Tambah Laporan
            </a>
            <a class="collapse-item" href="confirm.php">
                <i class="fas fa-clipboard-check mr-2"></i> Konfirmasi Laporan
            </a>
            <a class="collapse-item" href="equipment_report.php">
                <i class="fas fa-door-open mr-2"></i>  Laporan Per Ruangan
            </a>
        </div>
    </div>
</li>

    <?php elseif($_SESSION['role'] == 'teknisi'): ?>
        <div class="sidebar-heading">Teknis</div>  
        <li class="nav-item">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" 
        aria-expanded="false" aria-controls="collapsePages">
        <i class="fas fa-tasks"></i>
        <span>Tugas</span>
    </a>
    <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Job Order:</h6>
            <a class="collapse-item" href="process.php">
                <i class="fas fa-file-alt mr-2"></i> Pekerjaan/Tugas
            </a>
        </div>
    </div>
</li>

    
    <?php elseif($_SESSION['role'] == 'admin_teknisi'): ?>
        <div class="sidebar-heading">Manajemen Teknis</div>
        
        <!-- Menu Collapse Teknisi -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTeknis" 
                aria-expanded="true" aria-controls="collapseTeknis">
                <i class="fas fa-tools"></i>
                <span>Teknis</span>
            </a>
            <div id="collapseTeknis" class="collapse" aria-labelledby="headingTeknis" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded">
                    <a class="collapse-item" href="assign.php">
                        <i class="fas fa-toolbox mr-2"></i> Penugasan Pekerjaan
                    </a>
                    <a class="collapse-item" href="inventaris.php">
                        <i class="fas fa-boxes mr-2"></i> Inventaris Alat
                    </a>
                </div>
            </div>
        </li>
    <?php endif; ?>

    <!-- Footer -->
    <hr class="sidebar-divider d-none d-md-block">
    <div class="sidebar-card">
        <div class="text-center">
            <div class="small text-white">Version 1.0.0</div>
            <div class="small text-white-50">Copyright &copy; Sistem Monitoring Fasilitas <?= date('Y'); ?></div>
        </div>
    </div>
</ul>
<style>
.bg-gradient-custom {
  background: linear-gradient(180deg, #1e3c57 0%, #2d8d82 100%);
  background-size: cover;
}
</style>
<!-- End of Sidebar -->