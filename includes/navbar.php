<?php
$in_pages = (basename(getcwd()) == 'pages');
$prefix   = $in_pages ? '../' : '';
$p_link   = $in_pages ? '' : 'pages/'; 
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top" style="border-bottom: 3px solid #ffc107;">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= $prefix ?>index.php">
            <i class="bi bi-mortarboard-fill text-warning fs-4"></i>
            <div style="line-height: 1.2;">
                <span class="d-block text-white" style="font-size: 0.9rem;">DSS LEARNING MODUL</span>
                <small class="text-white-50" style="font-size: 0.7rem;">Modul Pembelajaran SPK</small>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $prefix ?>index.php"><i class="bi bi-house-door"></i> Beranda</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-collection-play"></i> Simulasi Metode
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="<?= $prefix . $p_link ?>pages/input_kriteria.php?metode=SAW">Metode SAW</a></li>
                        <li><a class="dropdown-item" href="<?= $prefix . $p_link ?>pages/input_kriteria.php?metode=WP">Metode WP</a></li>
                        <li><a class="dropdown-item" href="<?= $prefix . $p_link ?>pages/input_kriteria.php?metode=TOPSIS">Metode TOPSIS</a></li>
                        <li><a class="dropdown-item" href="<?= $prefix . $p_link ?>pages/input_kriteria.php?metode=AHP">Metode AHP</a></li>
                    </ul>
                </li>
                <li class="nav-item ms-lg-2">
                    <button class="btn btn-outline-warning btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#helpModal">
                        <i class="bi bi-question-circle-fill"></i> Panduan
                    </button>
                </li>
                <?php if(isset($_SESSION['alt_nilai']) && !empty($_SESSION['alt_nilai'])): ?>
                <li class="nav-item ms-lg-2">
                    <a href="<?= $prefix ?>logout.php" class="btn btn-danger btn-sm rounded-pill px-3" onclick="return confirm('Reset semua data pembelajaran?')">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>