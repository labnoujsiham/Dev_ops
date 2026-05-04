<?php

function getDashboardPathForRole(?string $role): string {
    if ($role === 'administrateur') {
        return '../admin/dashboard.php';
    }

    if ($role === 'gestionnaire') {
        return '../gestionnaire/dashboard.php';
    }

    return '../user/dashboard.php';
}