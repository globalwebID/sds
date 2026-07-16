<?php
function checkRole($allowed_roles = [])
{
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        echo "Akses ditolak.";
        exit;
    }
}
