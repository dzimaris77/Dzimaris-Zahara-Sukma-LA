<?php include 'includes/header.php'; ?>

<!-- Split Layout for About Page -->
<div class="about-container">
    <!-- Left Side - Gradient Background with Title -->
    <div class="about-sidebar">
        <div class="sidebar-content">
            <h1 class="sidebar-title">Tentang Kami</h1>
            <h2 class="sidebar-subtitle">Sistem Pelaporan<br>Kerusakan Alat Medis</h2>
        </div>
    </div>
    
    <!-- Right Side - Content Area -->
    <div class="about-content-area">
        <div class="content-card">
            <h3 class="content-title">Selamat Datang di Sistem Pelaporan Kerusakan Alat Medis</h3>
            <div class="divider"></div>
            
            <div class="content-section">
                <h4 class="section-title"><i class="fas fa-info-circle me-2"></i>Tentang Sistem</h4>
                <p>Sistem Pelaporan Kerusakan Alat Medis RS Mohammad Hoesin adalah platform terintegrasi yang didesain untuk memudahkan pelaporan dan penanganan kerusakan alat medis di rumah sakit dengan cepat dan efisien.</p>
                <p>Dengan platform ini, proses pelaporan kerusakan alat medis menjadi lebih terstruktur, termonitor, dan ditangani dengan tepat waktu untuk memastikan pelayanan pasien tetap optimal.</p>
            </div>
            
            <div class="content-section">
                <h4 class="section-title"><i class="fas fa-clipboard-list me-2"></i>Fitur Utama</h4>
                <div class="feature-grid">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="feature-text">
                            <h5>Pelaporan Cepat</h5>
                            <p>Laporkan kerusakan alat medis dengan form sederhana dan mudah</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="feature-text">
                            <h5>Pantau Status</h5>
                            <p>Lihat status penanganan secara real-time</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-history"></i></div>
                        <div class="feature-text">
                            <h5>Riwayat Laporan</h5>
                            <p>Akses riwayat pelaporan dan penanganan</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-bell"></i></div>
                        <div class="feature-text">
                            <h5>Notifikasi</h5>
                            <p>Dapatkan pemberitahuan ketika status laporan berubah</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h4 class="section-title"><i class="fas fa-cogs me-2"></i>Cara Kerja</h4>
                <div class="workflow-container">
                    <div class="workflow-item">
                        <div class="workflow-step">1</div>
                        <div class="workflow-content">
                            <h5>Lapor Kerusakan</h5>
                            <p>Pengguna melaporkan kerusakan alat medis melalui sistem</p>
                        </div>
                    </div>
                    <div class="workflow-item">
                        <div class="workflow-step">2</div>
                        <div class="workflow-content">
                            <h5>Verifikasi</h5>
                            <p>Tim teknisi menerima dan memverifikasi laporan</p>
                        </div>
                    </div>
                    <div class="workflow-item">
                        <div class="workflow-step">3</div>
                        <div class="workflow-content">
                            <h5>Penanganan</h5>
                            <p>Teknisi melakukan penanganan kerusakan</p>
                        </div>
                    </div>
                    <div class="workflow-item">
                        <div class="workflow-step">4</div>
                        <div class="workflow-content">
                            <h5>Selesai</h5>
                            <p>Pengguna menerima notifikasi penyelesaian</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="content-section">
                <h4 class="section-title"><i class="fas fa-hospital me-2"></i>Kontak</h4>
                <p>Untuk informasi lebih lanjut mengenai Sistem Pelaporan Kerusakan Alat Medis, silakan hubungi:</p>
                <div class="contact-info">
                    <p><i class="fas fa-map-marker-alt me-2"></i>RS Mohammad Hoesin</p>
                    <p><i class="fas fa-phone me-2"></i>(0711) 123456</p>
                    <p><i class="fas fa-envelope me-2"></i>info@rsmh.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    /* Base styles */
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f8f9fa;
        min-height: 100vh;
    }

    /* Split Layout Container */
    .about-container {
        display: flex;
        min-height: calc(100vh - 72px); /* Adjust based on your header height */
    }

    /* Left Sidebar */
    .about-sidebar {
        width: 40%;
        background: linear-gradient(
            135deg, 
            rgba(16, 57, 95, 0.95) 0%, 
            rgba(42, 157, 143, 0.85) 100%
        );
        color: white;
        padding: 3rem 2rem;
        display: flex;
        align-items: center;
        position: relative;
    }

    .sidebar-content {
        padding: 2rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .sidebar-title {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .sidebar-subtitle {
        font-size: 2.2rem;
        font-weight: 600;
        line-height: 1.3;
    }

    /* Right Content Area */
    .about-content-area {
        width: 60%;
        padding: 3rem 2rem;
        overflow-y: auto;
    }

    .content-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        padding: 2.5rem;
        max-width: 800px;
        margin: 0 auto;
    }

    .content-title {
        color: #555;
        font-weight: 500;
        text-align: center;
        margin-bottom: 1rem;
    }

    .divider {
        height: 3px;
        width: 70px;
        background: #10395f;
        margin: 0 auto 2rem;
    }

    /* Section Styling */
    .content-section {
        margin-bottom: 2.5rem;
    }

    .content-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        color: #10395f;
        font-weight: 600;
        margin-bottom: 1rem;
        border-left: 4px solid #2a9d8f;
        padding-left: 10px;
    }

    /* Features Grid */
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .feature-item {
        display: flex;
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
    }

    .feature-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        border-color: #2a9d8f;
    }

    .feature-icon {
        background: linear-gradient(45deg, #10395f, #2a9d8f);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .feature-text h5 {
        margin-top: 0;
        color: #10395f;
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .feature-text p {
        margin-bottom: 0;
        color: #6c757d;
        font-size: 0.9rem;
    }

    /* Workflow Styling */
    .workflow-container {
        margin-top: 1.5rem;
    }

    .workflow-item {
        display: flex;
        margin-bottom: 1.5rem;
        align-items: flex-start;
    }

    .workflow-item:last-child {
        margin-bottom: 0;
    }

    .workflow-step {
        background: linear-gradient(45deg, #10395f, #2a9d8f);
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .workflow-content h5 {
        margin-top: 0;
        color: #10395f;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .workflow-content p {
        margin-bottom: 0;
        color: #6c757d;
    }

    /* Contact Info */
    .contact-info {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
        border-left: 4px solid #2a9d8f;
    }

    .contact-info p {
        margin-bottom: 0.5rem;
    }

    .contact-info p:last-child {
        margin-bottom: 0;
    }

    .contact-info i {
        color: #2a9d8f;
    }

    /* Responsive Styles */
    @media (max-width: 992px) {
        .about-container {
            flex-direction: column;
        }

        .about-sidebar, .about-content-area {
            width: 100%;
        }

        .about-sidebar {
            padding: 2rem 1rem;
            text-align: center;
        }

        .sidebar-title {
            font-size: 2.2rem;
        }

        .sidebar-subtitle {
            font-size: 1.8rem;
        }
    }

    @media (max-width: 768px) {
        .feature-grid {
            grid-template-columns: 1fr;
        }

        .content-card {
            padding: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .sidebar-title {
            font-size: 1.8rem;
        }

        .sidebar-subtitle {
            font-size: 1.5rem;
        }

        .workflow-item {
            flex-direction: column;
        }

        .workflow-step {
            margin-bottom: 0.5rem;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>