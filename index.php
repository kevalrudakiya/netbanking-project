<?php
// SecureBank Landing Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureBank - Welcome to the Future of Banking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background-image: url('assets/background.jpg'); 
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-color: #030b1e; 
            min-height: 100vh; 
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        /* Logo Header */
        .logo-header {
            padding: 20px 0;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }
        
        .brand-title {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-title span {
            color: #4364f7;
        }
        
        .brand-title:hover {
            color: #ffffff;
        }

        /* Hero Section */
        .hero-section {
            padding: 120px 0 80px 0;
            text-align: center;
            flex-grow: 1;
            display: flex;
            align-items: center;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 25px;
            background: -webkit-linear-gradient(45deg, #ffffff, #00c6ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 10px 30px rgba(0, 140, 255, 0.2);
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: #a0b2c6;
            margin-bottom: 45px;
            max-width: 650px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.7;
        }

        .btn-custom-primary { 
            background: linear-gradient(90deg, #0072ff, #00c6ff); 
            border: none !important; 
            border-radius: 12px !important;
            font-weight: 600;
            font-size: 1.15rem;
            padding: 14px 35px;
            color: white;
            box-shadow: 0 5px 20px rgba(0, 114, 255, 0.4);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-custom-primary:hover { 
            opacity: 0.95;
            box-shadow: 0 8px 25px rgba(0, 198, 255, 0.6);
            transform: translateY(-2px);
            color: white;
        }

        .btn-custom-outline {
            background: rgba(2, 10, 28, 0.6);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            border-radius: 12px !important;
            font-weight: 600;
            font-size: 1.15rem;
            padding: 14px 35px;
            color: #ffffff;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-custom-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.4) !important;
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Features Section */
        .features-section {
            padding: 40px 0 80px 0;
        }

        .feature-card {
            background: rgba(2, 10, 28, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 140, 255, 0.2); 
            border-radius: 24px; 
            padding: 45px 35px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 114, 255, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover {
            border-color: rgba(0, 140, 255, 0.5);
            box-shadow: 0 15px 40px rgba(0, 140, 255, 0.2);
            transform: translateY(-8px);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon-wrapper {
            position: relative;
            z-index: 1;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0052d4, #4364f7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px auto;
            box-shadow: 0 0 25px rgba(67, 100, 247, 0.5);
        }

        .feature-icon {
            font-size: 2.2rem;
            color: white;
        }

        .feature-title {
            position: relative;
            z-index: 1;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #ffffff;
        }

        .feature-text {
            position: relative;
            z-index: 1;
            color: #8da2bb;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 0;
        }

        /* Stats Section */
        .stats-section {
            padding: 60px 0;
            background: linear-gradient(90deg, rgba(0, 114, 255, 0.05), rgba(0, 198, 255, 0.05));
            border-top: 1px solid rgba(0, 140, 255, 0.1);
            border-bottom: 1px solid rgba(0, 140, 255, 0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: -webkit-linear-gradient(45deg, #00c6ff, #0072ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .stat-text {
            color: #a0b2c6;
            font-size: 1.1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* How it Works */
        .steps-section {
            padding: 80px 0;
            text-align: center;
        }
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 50px;
            color: #ffffff;
        }
        .step-circle {
            width: 60px;
            height: 60px;
            background: rgba(0, 114, 255, 0.1);
            border: 2px solid #0072ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: #00c6ff;
            margin: 0 auto 20px auto;
        }
        .step-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #ffffff;
        }

        /* Footer */
        .footer {
            background: rgba(2, 10, 28, 0.95);
            padding: 30px 0;
            border-top: 1px solid rgba(0, 140, 255, 0.15);
            text-align: center;
            margin-top: auto;
        }

        .footer p {
            color: #64748b;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }

        .footer .highlight {
            color: #0072ff;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.8rem;
            }
            .btn-custom-primary, .btn-custom-outline {
                width: 100%;
                margin-bottom: 15px;
            }
            .d-flex.gap-4 {
                flex-direction: column;
                gap: 0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Logo Header -->
    <header class="logo-header">
        <div class="container">
            <a class="brand-title" href="index.php">
                <i class="fas fa-university me-2" style="color: #4364f7;"></i>Secure<span>Bank</span>
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <h1 class="hero-title">Welcome to the Future of Banking</h1>
                    <p class="hero-subtitle">Experience seamless, secure, and lightning-fast digital banking tailored for your everyday needs. Manage your wealth anytime, anywhere with complete peace of mind.</p>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <a href="user/register.php" class="btn-custom-primary">Get Started Now</a>
                        <a href="user/login.php" class="btn-custom-outline">Access Account</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-shield-alt feature-icon"></i>
                        </div>
                        <h3 class="feature-title">Bank Grade Security</h3>
                        <p class="feature-text">Your funds and data are protected with state-of-the-art encryption and multi-factor authentication, ensuring complete safety.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-bolt feature-icon"></i>
                        </div>
                        <h3 class="feature-title">Instant Transfers</h3>
                        <p class="feature-text">Send and receive money globally in seconds. Our optimized infrastructure guarantees fast processing for all transactions.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-headset feature-icon"></i>
                        </div>
                        <h3 class="feature-title">24/7 Premium Support</h3>
                        <p class="feature-text">Our dedicated support team is available around the clock to assist you with any queries or concerns regarding your account.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stat-number">$10B+</div>
                    <div class="stat-text">Processed Annually</div>
                </div>
                <div class="col-md-4">
                    <div class="stat-number">1M+</div>
                    <div class="stat-text">Active Users</div>
                </div>
                <div class="col-md-4">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-text">Uptime Guarantee</div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="steps-section">
        <div class="container">
            <h2 class="section-title">Get Started in 3 Simple Steps</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-circle">1</div>
                    <h3 class="step-title">Create an Account</h3>
                    <p class="feature-text">Register in less than 2 minutes using our secure online application form.</p>
                </div>
                <div class="col-md-4">
                    <div class="step-circle">2</div>
                    <h3 class="step-title">Verify Your Identity</h3>
                    <p class="feature-text">Complete our automated KYC process to ensure full security and compliance.</p>
                </div>
                <div class="col-md-4">
                    <div class="step-circle">3</div>
                    <h3 class="step-title">Start Banking</h3>
                    <p class="feature-text">Deposit funds and immediately start enjoying lightning-fast transfers globally.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p><i class="fas fa-shield-alt me-1"></i> © 2026 SecureBank. All rights reserved.</p>
            <p class="highlight mb-0">Your security is our priority</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
