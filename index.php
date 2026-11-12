<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MediCare Hospital Management System — Smart, modern healthcare at your fingertips.">
    <title>MediCare Hospital | Smart Healthcare Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- ═══════════════ NAVBAR ═══════════════ -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-hospital-fill"></i> MediCare
        </a>

        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link text-info fw-semibold" href="doctor-login.php"><i class="bi bi-person-badge me-1"></i>Doctor Portal</a></li>
                <li class="nav-item ms-lg-2">
                    <a href="login.php" class="btn btn-primary px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                </li>
                <li class="nav-item ms-lg-1">
                    <a href="register.php" class="btn btn-outline-primary px-3">Register</a>
                </li>
            </ul>
        </div>
    </div>
</nav>


<!-- ═══════════════ HERO ═══════════════ -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">

            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="bi bi-heart-pulse-fill"></i>
                    Smart Healthcare Management
                </div>

                <h1>
                    Your Health,<br>
                    <span class="gradient-text">Our Priority.</span>
                </h1>

                <p class="lead mt-3">
                    A complete hospital management system connecting
                    patients, doctors, and administration — all in one
                    secure, modern platform.
                </p>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="register.php" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-calendar-check me-2"></i>Get Started
                    </a>
                    <a href="#services" class="btn btn-outline-primary btn-lg px-5">
                        Explore Services
                    </a>
                </div>

                <div class="hero-stats">
                    <div>
                        <div class="hero-stat-val">50+</div>
                        <div class="hero-stat-label">Expert Doctors</div>
                    </div>
                    <div>
                        <div class="hero-stat-val">1,000+</div>
                        <div class="hero-stat-label">Happy Patients</div>
                    </div>
                    <div>
                        <div class="hero-stat-val">24/7</div>
                        <div class="hero-stat-label">Support</div>
                    </div>
                    <div>
                        <div class="hero-stat-val">15+</div>
                        <div class="hero-stat-label">Departments</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-card">
                    <div class="hospital-symbol">
                        <i class="bi bi-hospital-fill"></i>
                    </div>
                    <h3 class="fw-bold mt-3 mb-1" style="color:#fff; letter-spacing:-0.5px;">
                        MediCare Hospital
                    </h3>
                    <p style="color:var(--text-muted); font-size:14px;">
                        Advanced healthcare management at your fingertips.
                    </p>

                    <div class="row g-3 mt-3">
                        <div class="col-6">
                            <div class="mini-card">
                                <i class="bi bi-person-badge"></i>
                                <span>Expert Doctors</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-card">
                                <i class="bi bi-clock-history"></i>
                                <span>24/7 Service</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-card">
                                <i class="bi bi-shield-fill-check"></i>
                                <span>Secure Records</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-card">
                                <i class="bi bi-capsule"></i>
                                <span>Pharmacy</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>


<!-- ═══════════════ SERVICES ═══════════════ -->
<section id="services">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-badge">Our Services</div>
            <h2 class="section-title">Everything You Need</h2>
            <p class="section-sub mt-3">
                Manage your entire healthcare journey through our
                integrated, all-in-one hospital system.
            </p>
        </div>

        <div class="row g-4">

            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-person-badge"></i></div>
                    <h4>Doctor Management</h4>
                    <p>Manage doctor profiles, specializations, schedules and availability easily.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-people-fill"></i></div>
                    <h4>Patient Management</h4>
                    <p>Maintain complete patient information, history and profile securely.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-calendar2-check"></i></div>
                    <h4>Appointments</h4>
                    <p>Book and manage appointments with real-time slot conflict prevention.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon" style="background:rgba(16,185,129,0.2); border-color:rgba(16,185,129,0.3); color:#34d399;">
                        <i class="bi bi-file-medical-fill"></i>
                    </div>
                    <h4>Medical Records</h4>
                    <p>Store, retrieve and manage patient medical records with full security.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon" style="background:rgba(6,182,212,0.2); border-color:rgba(6,182,212,0.3); color:#22d3ee;">
                        <i class="bi bi-capsule-pill"></i>
                    </div>
                    <h4>Pharmacy</h4>
                    <p>Request and track medicines directly from your prescriptions — all in one place.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon" style="background:rgba(245,158,11,0.2); border-color:rgba(245,158,11,0.3); color:#fbbf24;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h4>Billing & Payments</h4>
                    <p>Manage hospital bills, invoices and payment statuses from one dashboard.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon" style="background:rgba(239,68,68,0.2); border-color:rgba(239,68,68,0.3); color:#f87171;">
                        <i class="bi bi-eyedropper"></i>
                    </div>
                    <h4>Laboratory Reports</h4>
                    <p>Manage lab tests, track results and access patient lab reports instantly.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon" style="background:rgba(168,85,247,0.2); border-color:rgba(168,85,247,0.3); color:#c084fc;">
                        <i class="bi bi-prescription2"></i>
                    </div>
                    <h4>Prescriptions</h4>
                    <p>Digital prescriptions linked to appointments and pharmacy for seamless care.</p>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon" style="background:rgba(59,130,246,0.2); border-color:rgba(59,130,246,0.3); color:#93c5fd;">
                        <i class="bi bi-building-fill-gear"></i>
                    </div>
                    <h4>Departments</h4>
                    <p>Organize and manage hospital departments for efficient resource allocation.</p>
                </div>
            </div>

        </div>
    </div>
</section>


<!-- ═══════════════ ABOUT ═══════════════ -->
<section id="about" class="about-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="about-card">
                    <i class="bi bi-hospital-fill"></i>
                    <h2>Modern Healthcare,<br>Simplified.</h2>
                    <p>
                        MediCare Hospital Management System streamlines daily
                        hospital operations — from appointments to pharmacy — making
                        healthcare smarter and more accessible.
                    </p>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="section-badge">About Our System</div>
                <h2 class="section-title mt-3">
                    One Platform for<br>Complete Hospital Management
                </h2>
                <p class="section-sub text-start mt-3">
                    MediCare provides a centralized, secure platform for administrators,
                    doctors and patients. Everything from scheduling to pharmacy is
                    managed in one intelligent system.
                </p>

                <ul class="feature-list mt-4">
                    <li><i class="bi bi-check-circle-fill"></i> Easy patient registration & profile management</li>
                    <li><i class="bi bi-check-circle-fill"></i> Real-time appointment booking with conflict detection</li>
                    <li><i class="bi bi-check-circle-fill"></i> Secure encrypted medical records & prescriptions</li>
                    <li><i class="bi bi-check-circle-fill"></i> Integrated pharmacy module with dispensing tracking</li>
                    <li><i class="bi bi-check-circle-fill"></i> Doctor & department management system</li>
                    <li><i class="bi bi-check-circle-fill"></i> Billing, invoicing & payment tracking</li>
                </ul>

                <a href="register.php" class="btn btn-primary mt-4 px-5">
                    <i class="bi bi-person-plus me-2"></i>Create Your Account
                </a>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════ CONTACT ═══════════════ -->
<section id="contact">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-badge">Contact Us</div>
            <h2 class="section-title">We're Here to Help</h2>
            <p class="section-sub mt-3">Have questions? Get in touch with our team anytime.</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="contact-card">
                    <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
                    <h5>Phone</h5>
                    <p>+91 9173244133</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card">
                    <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
                    <h5>Email</h5>
                    <p>info@medicare.com</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card">
                    <div class="contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <h5>Location</h5>
                    <p>Gujarat, India</p>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ═══════════════ FOOTER ═══════════════ -->
<footer>
    <div class="container">
        <div class="row align-items-center g-3">
            <div class="col-lg-4">
                <div class="brand">
                    <i class="bi bi-hospital-fill"></i> MediCare
                </div>
                <p class="mt-1" style="font-size:13px;">Smart healthcare management system connecting patients, doctors, and hospital departments.</p>
            </div>
            <div class="col-lg-5 text-center">
                <div class="d-flex justify-content-center gap-3 flex-wrap mb-2" style="font-size:12px;">
                    <a href="doctor-login.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-person-badge text-info me-1"></i>Doctor</a>
                    <a href="pharmacy-login.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-capsule text-primary me-1"></i>Pharmacy</a>
                    <a href="lab-login.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-eyedropper text-danger me-1"></i>Lab</a>
                    <a href="admin-login.php" class="text-white-50 text-decoration-none hover-white"><i class="bi bi-shield-lock text-warning me-1"></i>Admin</a>
                </div>
                <small>© 2026 MediCare Hospital. All Rights Reserved.</small>
            </div>
            <div class="col-lg-3 text-lg-end text-center">
                <a href="register.php" class="btn btn-outline-primary btn-sm me-1">
                    <i class="bi bi-person-plus me-1"></i>Register
                </a>
                <a href="login.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
