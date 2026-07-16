<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - E-Money Kantin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <style>
        body {
            background: linear-gradient(to right, #e0f7fa, #ffffff);
            /* Gradasi biru muda ke putih */
            background-attachment: fixed;
            background-size: cover;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        @keyframes blinkGreen {
            0% {
                background-color: #d4edda;
            }

            50% {
                background-color: #c3e6cb;
            }

            100% {
                background-color: transparent;
            }
        }

        .highlight-green td {
            animation: blinkGreen 3s ease-in-out;
        }

        .card:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        @keyframes blink {

            0%,
            100% {
                background-color: #ffc107;
            }

            50% {
                background-color: #fff3cd;
            }
        }

        .card-blink {
            animation: blink 0.7s infinite;
        }

        .nav-link.active {
            font-weight: bold;
            color: #ffc107 !important;
            border-bottom: 2px solid #ffc107;
        }

        .nav-link:hover {
            color: #adb5bd;
        }

        .nav-tabs .nav-link {
            transition: 0.3s;
            border: none !important;
        }

        .nav-tabs .nav-link:hover {
            background-color: #f0f0f0;
        }

        .nav-tabs .nav-link.active {
            background-color: #0d6efd !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }


        /* .badge {
            width: 100%;
        } */
    </style>
</head>

<body class="bg-light">