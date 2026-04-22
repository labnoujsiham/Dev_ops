
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
    position: relative;
}

.notification-item.unread {
    background: #e8f6ff;
}

.notification-item.urgent {
    background: #ffebee !important;
    border-left: 4px solid #e74c3c;
}

.notification-content {
    margin-left: 15px;
}

.notification-title {
    font-size: 14px;
    font-weight: 600;
    color: #2d3748;
}

.notification-message {
    font-size: 13px;
    color: #666;
}

.notification-time {
    font-size: 11px;
    color: #999;
}


.urgent-popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.urgent-popup-overlay.show {
    display: flex;
}

.urgent-popup {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    overflow: hidden;
}

.urgent-popup-header {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 30px;
    text-align: center;
}

.urgent-popup-header i {
    font-size: 64px;
    margin-bottom: 15px;
}

.urgent-popup-header h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
}

.urgent-popup-body {
    padding: 30px;
}

.urgent-info-box {
    background: #fff3cd;
    border-left: 4px solid #f39c12;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.urgent-info-box p {
    margin: 8px 0;
    color: #856404;
    font-size: 15px;
}

.urgent-info-box strong {
    color: #2c3e50;
    font-weight: 700;
}

.priority-badge {
    display: inline-block;
    background: #e74c3c;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    margin-left: 10px;
}

.urgent-popup-footer {
    padding: 0 30px 30px 30px;
    text-align: center;
}

.btn-urgent-ok {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    border: none;
    padding: 15px 40px;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
    font-family: 'Afacad', sans-serif;
}

.btn-urgent-ok:hover {
    transform: translateY(-2px);
}
</style>

<div class="topbar">
    <div class="topbar-left"></div>
    <div class="topbar-right">
        <div class="notification-icon" id="notificationBell">
            <i class='bx bx-bell'></i>
            <span class="notification-badge" id="notificationBadge"></span>
        </div>
        <div class="user-avatar" onclick="window.location.href='profil.php'">
            <i class='bx bx-user'></i>
        </div>
    </div>
</div>

<div class="notifications-modal" id="notificationsModal">
    <div class="notifications-header">
        <h3>Notifications</h3>
        <span class="mark-all-read" id="markAllRead">Tout marquer comme lu</span>
    </div>
    <div class="notifications-list" id="notificationsList"></div>
</div>

<div class="urgent-popup-overlay" id="urgentPopup">
    <div class="urgent-popup">
        <div class="urgent-popup-header">
            <i class='bx bx-error-alt'></i>
            <h2>RÉCLAMATION URGENTE !</h2>
        </div>
        <div class="urgent-popup-body">
            <div class="urgent-info-box">
                <p><strong>Vous avez été assigné à :</strong></p>
                <p id="urgentTitle" style="font-size: 16px; font-weight: 600;">Chargement...</p>
                <p style="margin-top: 15px;">
                    <strong>Priorité :</strong>
                    <span class="priority-badge">⚠️ HAUTE</span>
                </p>
                <p><strong>Délai :</strong> Traiter immédiatement</p>
            </div>
        </div>
        <div class="urgent-popup-footer">
            <button class="btn-urgent-ok" onclick="handleUrgentOk()">
                <i class='bx bx-check-circle'></i> OK, je m'en occupe maintenant
            </button>
        </div>
    </div>
</div>

<script src="gestionnaire_notifications.js"></script>