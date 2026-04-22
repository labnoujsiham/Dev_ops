
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
.topbar {
    position: fixed;
    top: 0;
    left: 240px;
    right: 0;
    height: 80px;
    background: white;
    border-bottom: 1px solid #e8e8e8;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 40px;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}

.topbar-left h2 {
    font-size: 26px;
    font-weight: 600;
    color: #2c3e50;
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 25px;
}

.notification-icon {
    position: relative;
    width: 42px;
    height: 42px;
    background: #f5f7fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: 0.3s;
}

.notification-icon:hover {
    background: #e8f6ff;
}

.notification-icon i {
    font-size: 22px;
    color: #45AECC;
}

.notification-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    min-width: 16px;
    height: 16px;
    background: #e74c3c;
    border-radius: 8px;
    color: white;
    font-size: 10px;
    font-weight: 700;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}

.notification-badge.show {
    display: flex;
}

.user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #45AECC, #614AA9);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    cursor: pointer;
    transition: 0.3s;
}

.user-avatar:hover {
    transform: scale(1.05);
}

/* Modal Notifications */
.notifications-modal {
    position: fixed;
    top: 90px;
    right: 40px;
    width: 400px;
    max-height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    display: none;
    flex-direction: column;
    z-index: 2000;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notifications-modal.show {
    display: flex;
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.notifications-header h3 {
    font-size: 18px;
    color: #2d3748;
    margin: 0;
}

.mark-all-read {
    color: #45AECC;
    font-size: 13px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
}

.mark-all-read:hover {
    color: #3a9bb5;
}

.notifications-list {
    flex: 1;
    overflow-y: auto;
    max-height: 400px;
}

.notification-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f5f5f5;
    cursor: pointer;
    transition: background 0.2s;
    position: relative;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #e8f6ff;
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    left: 20px;
    width: 8px;
    height: 8px;
    background: #45AECC;
    border-radius: 50%;
}

.notification-content {
    margin-left: 15px;
}

.notification-title {
    font-size: 14px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 4px;
}

.notification-message {
    font-size: 13px;
    color: #666;
    margin-bottom: 6px;
}

.notification-time {
    font-size: 11px;
    color: #999;
}

.empty-notifications {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-notifications i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 15px;
    display: block;
}

/* Toast Notification */
.toast-notification {
    position: fixed;
    top: 100px;
    right: 40px;
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    display: none;
    align-items: center;
    gap: 15px;
    min-width: 350px;
    z-index: 3000;
    animation: slideIn 0.3s ease;
    border-left: 4px solid #45AECC;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(400px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.toast-notification.show {
    display: flex;
}

.toast-icon {
    width: 48px;
    height: 48px;
    background: #e8f6ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #45AECC;
    font-size: 24px;
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-size: 15px;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 4px;
}

.toast-message {
    font-size: 13px;
    color: #666;
}

.toast-close {
    cursor: pointer;
    color: #999;
    font-size: 20px;
    transition: 0.3s;
}

.toast-close:hover {
    color: #45AECC;
}

.toast-action {
    margin-top: 10px;
}

.toast-action a {
    color: #45AECC;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s;
}

.toast-action a:hover {
    color: #3a9bb5;
}
</style>

<div class="topbar">
    <div class="topbar-left">
        
    </div>
    <div class="topbar-right">
        <div class="notification-icon" id="notificationBell">
            <i class='bx bx-bell'></i>
            <span class="notification-badge" id="notificationBadge"></span>
        </div>
        <div class="user-avatar" onclick="window.location.href='profil.php'" title="<?php echo htmlspecialchars($user['nom'] ?? $_SESSION['user_name'] ?? 'Profil'); ?>">
            <i class='bx bx-user'></i>
        </div>
    </div>
</div>


<div class="notifications-modal" id="notificationsModal">
    <div class="notifications-header">
        <h3>Notifications</h3>
        <span class="mark-all-read" id="markAllRead">Tout marquer comme lu</span>
    </div>
    <div class="notifications-list" id="notificationsList">
       
    </div>
</div>


<div class="toast-notification" id="toastNotification">
    <div class="toast-icon">
        <i class='bx bx-bell' id="toastIcon"></i>
    </div>
    <div class="toast-content">
        <div class="toast-title" id="toastTitle">Nouvelle notification</div>
        <div class="toast-message" id="toastMessage">Vous avez une nouvelle notification</div>
        <div class="toast-action">
            <a href="#" id="toastLink">Voir la réclamation →</a>
        </div>
    </div>
    <div class="toast-close" onclick="closeToast()">
        <i class='bx bx-x'></i>
    </div>
</div>

<script src="user_notifications.js"></script>