<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<head>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Praise&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>


<button class="menu-toggle" onclick="toggleSidebar()">
    <i class='bx bx-menu'></i>
</button>

<div class="sidebar">
    <div class="logo logo-flex">
        <h2>
            <span class="recla">Recla</span><span class="nova">Nova</span>
        </h2>
    </div>
    <div class="sidebar-divider"></div>

    <ul class="menu">
        <li class="menu-item <?php if($current_page == 'dashboard.php'){ echo 'active'; } ?>">
            <a href="dashboard.php">
                <i class='bx bx-home'></i>
                <span>Dashboard</span>
            </a>
        </li>

        

        <li class="menu-item <?php if($current_page == 'reclamation.php' ||
        $current_page == 'detail_reclamation.php'){ echo 'active'; } ?>">
            <a href="reclamation.php">
                <i class='bx bx-file'></i>
                <span>réclamations</span>
            </a>
        </li>

        <li class="menu-item <?php if($current_page == 'profil.php'){ echo 'active'; } ?>">
            <a href="profil.php">
                <i class='bx bx-user'></i>
                <span>Mon profil</span>
            </a>
        </li>

        <li class="menu-item logout">
            <a href="logout.php">
                <i class='bx bx-log-out'></i>
                <span>Déconnexion</span>
            </a>
        </li>
    </ul>

   
    <div class="sidebar-footer">
        <p>© 2025 ReclaNova</p>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('open');
    
    
    if (!overlay) {
        const newOverlay = document.createElement('div');
        newOverlay.className = 'sidebar-overlay';
        document.body.appendChild(newOverlay);
        
        
        newOverlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            newOverlay.remove();
        });
    } else {
        sidebar.classList.remove('open');
        overlay.remove();
    }
}


document.addEventListener('DOMContentLoaded', () => {
    const menuItems = document.querySelectorAll('.menu-item a');
    
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                
                sidebar.classList.remove('open');
                if (overlay) overlay.remove();
            }
        });
    });
});
</script>