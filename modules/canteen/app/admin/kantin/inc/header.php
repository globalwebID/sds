<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Kantin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../../images/favicon.ico" type="image/x-icon">
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"> -->
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            margin: 0;
            /* padding-bottom: 2.5rem; */
            /* biar konten tidak tertutup footer */
            font-family: sans-serif;
        }

        .max-w-xl {
            max-width: 36rem;
            height: 100vh;
        }

        .mobile-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background-color: rgb(0, 0, 125);
            display: flex;
            justify-content: space-around;
            align-items: center;
            border-top: 1px solid #ccc;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
            z-index: 999;
        }

        .mobile-footer a {
            flex: 1;
            text-align: center;
            text-decoration: none;
            color: #f7f7f7;
            font-size: 12px;
            padding-top: 6px;
        }

        .mobile-footer a.active {
            color: orange;
            font-weight: bold;
        }

        .mobile-footer i {
            font-size: 20px;
            display: block;
            margin-bottom: 2px;
        }

        .kantin-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            margin-right: 15px;
        }

        .card-icon {
            font-size: 2rem;
        }

        .profile-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }

        .card.cursor-pointer:hover {
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
            transform: scale(1.02);
        }

        /* Modal styling */
        .modern-modal {
            background: #fff;
            border-radius: 1rem;
            overflow: hidden;
        }

        .modal-header.bg-gradient {
            background: rgb(0, 0, 125);
            border-bottom: none;
        }

        .modal-title {
            font-size: 1.25rem;
        }

        .modal-footer {
            background-color: #f9f9f9;
        }

        /* Responsive icon spacing */
        .modal-body .bi {
            min-width: 32px;
            text-align: center;
        }

        .btn-yellow {
            --bs-btn-bg: rgb(255, 255, 0);
            --bs-btn-color: rgb(0, 0, 125);
        }

        .btn-yellow:active {
            background-color: rgb(255, 255, 0);
        }

        .btn-yellow:hover {
            --bs-btn-hover-bg: rgb(255, 255, 0);
            --bs-btn-hover-color: rgb(0, 0, 125);
        }

        .bg-darkblue {
            background: rgb(0, 0, 125);
        }

        .bg-grey {
            background: #f7f7f7;
        }

        .text-yellow {
            color: rgb(255, 255, 0);
        }

        .text-grey {
            color: #eee;
        }

        .styles_search-box__aevfx {
            height: 12rem;
            --tw-bg-opacity: 1;
            background-color: rgb(0 0 125 / var(--tw-bg-opacity));
            padding: 1.75rem 1rem .75rem;
            /* background-image: url(img/bg.jpg); */
            background-size: cover;
            background-repeat: no-repeat;
            background-position: 50%;
        }

        .bg-darkblue {
            background-color: #002147;
        }

        .table-transaksi th,
        .table-transaksi td {
            font-size: 1.1rem;
            padding: 0.85rem;
        }

        .table-transaksi th {
            background-color: #f8f9fa;
        }

        .table-transaksi tbody tr:hover {
            background-color: #f1f1f1;
        }

        .text-right {
            text-align: right;
        }

        a.position-relative {
            position: relative;
        }

        a.position-relative .bg-danger {
            width: 15px;
            height: 15px;
            display: inline-block;
        }

        .blink {
            animation: pulseBlink 1s infinite;
            color: red;
        }

        @keyframes pulseBlink {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(2);
                opacity: 0.6;
            }
        }
    </style>
</head>

<body>
    <div class="m-auto relative bg-grey">