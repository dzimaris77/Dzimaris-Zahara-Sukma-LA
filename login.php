<?php include 'includes/header.php'; ?>

<!-- Halaman Login -->
<div class="login-container">
    <div class="container">
        <!-- Outer Row -->
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="card-body">
                        <!-- Logo Area -->
                        <div class="logo-area">
                            <img src="img/logo.png" alt="RS Mohammad Hoesin">
                            <h6 class="text-muted">Sistem Monitoring Fasilitas</h6>
                        </div>
                        
                        <div class="login-header text-center">
                            <h1>Masuk</h1>
                            <?php if(isset($_GET['error'])): ?>
                                <div class="error-alert">
                                    <i class="fas fa-exclamation-circle mr-2"></i>Email atau password salah
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <form class="user" action="login_proses.php" method="post">
                            <div class="form-group">
                                <input type="text" class="form-control" 
                                    name="email" placeholder="Masukkan email" required>
                                <i class="fas fa-user"></i>
                            </div>
                            
                            <div class="form-group">
                                <input type="password" class="form-control" 
                                    name="password" placeholder="Password" required>
                                <i class="fas fa-lock"></i>
                            </div>
                            
                            <button type="submit" class="btn btn-block login-btn">
                                <i class="fas fa-sign-in-alt mr-2"></i>Login
                            </button>
                            
                            <div class="divider"></div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">RSUP Dr. Mohammad Hoesin © 2025</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS Styling -->
<style>
    /* Reset untuk menghindari konflik dengan style lain */
    .login-container * {
        box-sizing: border-box;
    }
    
    /* Container utama login */
    .login-container {
        width: 100%;
        min-height: calc(100vh - 180px); /* Menyesuaikan dengan header & footer */
        padding: 40px 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 5;
        overflow: hidden;
        margin-top: 20px;
        margin-bottom: 20px;
    }
    
    /* Memastikan footer tidak menggangu */
    footer {
        position: relative;
        z-index: 1;
    }
    
    /* Login Card */
    .login-card {
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        border: none;
        background: #ffffff;
        max-width: 100%;
        margin: 0 auto;
    }
    
    .login-header {
        margin-bottom: 2rem;
    }
    
    .login-header h1 {
        font-weight: 600;
        color: #10395f;
        font-size: 1.75rem;
    }
    
    .logo-area {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .logo-area img {
        max-height: 70px;
        margin-bottom: 0.5rem;
    }
    
    /* Form styling */
    .form-control {
        border-radius: 8px;
        padding: 0.75rem 1.25rem;
        height: auto;
        background-color: #f8f9fc;
        border: 1px solid #edf2f9;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    
    .form-control:focus {
        border-color: #2a9d8f;
        box-shadow: 0 0 0 0.2rem rgba(42, 157, 143, 0.25);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .form-group i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #b1b7c1;
    }
    
    .login-btn {
        background: #2a9d8f;
        border: none;
        border-radius: 8px;
        padding: 0.75rem;
        font-weight: 500;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 10px rgba(42, 157, 143, 0.2);
        transition: all 0.3s ease;
        color: #ffffff;
    }
    
    .login-btn:hover {
        background: #238b7e;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(42, 157, 143, 0.3);
        color: #ffffff;
    }
    
    .divider {
        height: 1px;
        background-color: #edf2f9;
        margin: 1.5rem 0;
    }
    
    .error-alert {
        background-color: #f8d7da;
        border-color: #f5c6cb;
        color: #721c24;
        border-radius: 8px;
        padding: 0.6rem 1rem;
        margin-bottom: 1.5rem;
        font-size: 0.85rem;
    }
    
    .login-card .card-body {
        padding: 2rem;
    }
    
    /* Responsive fixes */
    @media (max-width: 768px) {
        .login-container {
            padding: 20px 10px;
        }
        
        .login-card .card-body {
            padding: 1.5rem;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>