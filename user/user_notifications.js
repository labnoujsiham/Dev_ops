let notificationsData = [];
let pollInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    loadNotifications();
    startPolling();
    setupEventListeners();
});

async function loadNotifications() {
    try {
        const response = await fetch('get_notifications.php');
        const data = await response.json();
        
        if (data.success) {
            notificationsData = data.notifications;
            updateBadge(data.count_non_lues);
            renderNotifications(data.notifications);
            checkForNewNotifications(data.notifications);
        }
    } catch (error) {
        console.error('Erreur chargement notifications:', error);
    }
}

function updateBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.add('show');
        } else {
            badge.classList.remove('show');
        }
    }
}

function renderNotifications(notifications) {
    const list = document.getElementById('notificationsList');
    if (!list) return;
    
    if (notifications.length === 0) {
        list.innerHTML = `
            <div class="empty-notifications">
                <i class='bx bx-bell-off'></i>
                <p>Aucune notification</p>
            </div>
        `;
        return;
    }
    
    list.innerHTML = notifications.map(notif => {
        const reclamationId = notif.reference_id || 0;
        const message = notif.contenu || 'Nouvelle notification';
        const titre = notif.reclamation_objet || 'Réclamation';
        
        let icon = 'bx-bell';
        if (notif.type === 'demande_info') icon = 'bx-info-circle';
        if (notif.type === 'statut_ferme') icon = 'bx-check-circle';
        if (notif.type === 'nouveau_commentaire') icon = 'bx-message-square-dots';
        
        return `
            <div class="notification-item ${notif.lu == 0 ? 'unread' : ''}" 
                 onclick="handleNotificationClick(${notif.id}, ${reclamationId})">
                <div class="notification-content">
                    <div class="notification-title">
                        <i class='bx ${icon}' style="margin-right: 5px;"></i>
                        ${escapeHtml(titre)}
                    </div>
                    <div class="notification-message">
                        ${escapeHtml(message)}
                    </div>
                    <div class="notification-time">
                        ${formatDate(notif.date_creation)}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

async function handleNotificationClick(notifId, reclamationId) {
   
    await markAsRead(notifId);
    
    closeModal();
    
    window.location.href = `detail_reclamation.php?id=${reclamationId}#commentaires`;
}

async function markAsRead(notifId) {
    try {
        const response = await fetch('mark_as_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notifId })
        });
        
        if (response.ok) {
            await loadNotifications();
        }
    } catch (error) {
        console.error('Erreur marquage comme lu:', error);
    }
}

async function markAllAsRead() {
    try {
        const response = await fetch('mark_as_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mark_all: true })
        });
        
        if (response.ok) {
            await loadNotifications();
        }
    } catch (error) {
        console.error('Erreur marquage toutes comme lues:', error);
    }
}

let lastNotificationId = 0;

function checkForNewNotifications(notifications) {
    if (notifications.length === 0) return;
    
    const latestNotif = notifications[0];
    
    if (latestNotif.id > lastNotificationId && latestNotif.lu == 0) {
        showToast(latestNotif);
        lastNotificationId = latestNotif.id;
    } else if (lastNotificationId === 0 && notifications.length > 0) {
        
        lastNotificationId = latestNotif.id;
    }
}

function showToast(notif) {
    const toast = document.getElementById('toastNotification');
    const title = document.getElementById('toastTitle');
    const message = document.getElementById('toastMessage');
    const link = document.getElementById('toastLink');
    const icon = document.getElementById('toastIcon');
    
    if (!toast || !title || !message || !link || !icon) return;
    
    const reclamationId = notif.reference_id || 0;
    const messageText = notif.contenu || 'Nouvelle notification';
    const titre = notif.reclamation_objet || 'Réclamation';
    
    let iconClass = 'bx-bell';
    if (notif.type === 'demande_info') iconClass = 'bx-info-circle';
    if (notif.type === 'statut_ferme') iconClass = 'bx-check-circle';
    if (notif.type === 'nouveau_commentaire') iconClass = 'bx-message-square-dots';
    
    icon.className = 'bx ' + iconClass;
    title.textContent = titre;
    message.textContent = messageText;
    link.href = `detail_reclamation.php?id=${reclamationId}#commentaires`;
    
    toast.classList.add('show');
    
    setTimeout(() => {
        closeToast();
    }, 7000);
}

function closeToast() {
    const toast = document.getElementById('toastNotification');
    if (toast) {
        toast.classList.remove('show');
    }
}

function toggleModal() {
    const modal = document.getElementById('notificationsModal');
    if (modal) {
        modal.classList.toggle('show');
    }
}

function closeModal() {
    const modal = document.getElementById('notificationsModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

function startPolling() {
    pollInterval = setInterval(() => {
        loadNotifications();
    }, 30000); 
}


function setupEventListeners() {
    const bell = document.getElementById('notificationBell');
    if (bell) {
        bell.addEventListener('click', toggleModal);
    }
    
    const markAll = document.getElementById('markAllRead');
    if (markAll) {
        markAll.addEventListener('click', () => {
            markAllAsRead();
        });
    }
    
    // Fermer la modal si clic en dehors
    document.addEventListener('click', (e) => {
        const modal = document.getElementById('notificationsModal');
        const bell = document.getElementById('notificationBell');
        
        if (modal && bell && modal.classList.contains('show') && 
            !modal.contains(e.target) && 
            !bell.contains(e.target)) {
            closeModal();
        }
    });
}

// Utilitaires
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); 
    
    if (diff < 60) return 'À l\'instant';
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
    if (diff < 604800) return `Il y a ${Math.floor(diff / 86400)} j`;
    
    return date.toLocaleDateString('fr-FR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric' 
    });
}

window.addEventListener('beforeunload', () => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});