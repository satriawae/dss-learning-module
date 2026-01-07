<?php 
session_start(); 
// Panggil Header & Navbar
include 'includes/header.php'; 
include 'includes/navbar.php'; 
?>

<div class="bg-primary text-white py-5 mb-5" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="fw-bold display-5">Selamat Datang di Modul Pembelajaran SPK (DSS Learning Modul)</h1>
                <p class="lead mb-4">
                    Platform simulasi interaktif yang membantu memahami algoritma Sistem Pendukung Keputusan (SPK) dengan lebih mudah. 
                    Pelajari bagaimana metode SPK bekerja untuk membantu pengambilan keputusan yang objektif.
                </p>
                <button class="btn btn-warning btn-lg fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#helpModal">
                    <i class="bi bi-book-half"></i> Mulai Panduan Singkat
                </button>
            </div>
            <div class="col-lg-4 d-none d-lg-block text-center">
                <i class="bi bi-diagram-3-fill" style="font-size: 8rem; opacity: 0.8;"></i>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h3 class="fw-bold text-secondary">Pilih Metode Pembelajaran</h3>
            <p class="text-muted">Silakan pilih algoritma yang ingin Anda pelajari hari ini:</p>
            <hr class="w-25 mx-auto text-primary" style="opacity: 1; height: 3px;">
        </div>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-md-3">
            <div class="card h-100 card-dinus text-center p-3 border-0 shadow-sm hover-up">
                <div class="card-body">
                    <div class="badge bg-primary mb-3">Simple</div>
                    <h4 class="fw-bold text-dark">SAW</h4>
                    <p class="small text-muted fst-italic">Simple Additive Weighting</p>
                    <p class="card-text small text-secondary mt-3">Metode penjumlahan terbobot. Paling dasar dan mudah dipahami untuk pemula.</p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="pages/input_kriteria.php?metode=SAW" class="btn btn-outline-primary w-100 stretched-link">Mulai Simulasi</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100 card-dinus text-center p-3 border-0 shadow-sm hover-up">
                <div class="card-body">
                    <div class="badge bg-success mb-3">Multiplication</div>
                    <h4 class="fw-bold text-dark">WP</h4>
                    <p class="small text-muted fst-italic">Weighted Product</p>
                    <p class="card-text small text-secondary mt-3">Menggunakan perkalian. Lebih ketat, menghindari alternatif dengan nilai 0.</p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="pages/input_kriteria.php?metode=WP" class="btn btn-outline-success w-100 stretched-link">Mulai Simulasi</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 card-dinus text-center p-3 border-0 shadow-sm hover-up">
                <div class="card-body">
                    <div class="badge bg-danger mb-3">Complex</div>
                    <h4 class="fw-bold text-dark">TOPSIS</h4>
                    <p class="small text-muted fst-italic">Ideal Solution</p>
                    <p class="card-text small text-secondary mt-3">Mencari solusi terdekat dengan nilai ideal positif dan terjauh dari negatif.</p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="pages/input_kriteria.php?metode=TOPSIS" class="btn btn-outline-danger w-100 stretched-link">Mulai Simulasi</a>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card h-100 card-dinus text-center p-3 border-0 shadow-sm hover-up">
                <div class="card-body">
                    <div class="badge bg-warning text-dark mb-3">Hierarchy</div>
                    <h4 class="fw-bold text-dark">AHP</h4>
                    <p class="small text-muted fst-italic">Analytic Hierarchy Process</p>
                    <p class="card-text small text-secondary mt-3">Fokus pada konsistensi logika dalam menentukan bobot prioritas.</p>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="pages/input_kriteria.php?metode=AHP" class="btn btn-outline-warning text-dark w-100 stretched-link">Mulai Simulasi</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>