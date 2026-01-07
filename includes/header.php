<?php
// Deteksi path otomatis (apakah di root atau di dalam folder pages)
$in_pages = (basename(getcwd()) == 'pages');
$prefix   = $in_pages ? '../' : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSS Learning Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= $prefix ?>assets/css/mystyle.css">
</head>
<body class="d-flex flex-column min-vh-100">