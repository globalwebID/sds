<?php
session_start(); // Mulai sesi

// Hapus hanya sesi mKantin. Sesi login SDS tetap dipertahankan.
foreach (['user_id','username','role','id_kantin','central_admin'] as $key) unset($_SESSION[$key]);

// Arahkan kembali ke halaman login
header("Location: login.php");
exit();
