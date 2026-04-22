

let notificationsData = [];
let pollInterval = null;
let currentFilter = 'all'; 


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
            updateFilterCounts(data.notifications);
            renderNotifications(data.notifications, currentFilter);
            
           
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


function updateFilterCounts(notifications) {
    const counts = {
        all: notifications.length,
        nouvelle_reclamation: 0,
        nouveau_commentaire: 0,
        demande_info: 0,
        info_fournie: 0
    };
    
    notifications.forEach(notif => {
        if (counts.hasOwnProperty(notif.type)) {
            counts[notif.type]++;
        }
    });
    
    document.getElementById('countAll').textContent = counts.all;
    document.getElementById('countNew').textContent = counts.nouvelle_reclamation;
    document.getElementById('countComment').textContent = counts.nouveau_commentaire;
    document.getElementById('countInfo').textContent = counts.demande_info;
    document.getElementById('countProvided').textContent = counts.info_fournie;
}


function renderNotifications(notifications, filter = 'all') {
    const list = document.getElementById('notificationsList');
    if (!list) return;
    
    // Filtrer les notifications
    let filteredNotifications = notifications;
    if (filter !== 'all') {
        filteredNotifications = notifications.filter(notif => notif.type === filter);
    }
    
    if (filteredNotifications.length === 0) {
        list.innerHTML = `
            <div class="empty-notifications">
                <i class='bx bx-bell-off'></i>
                <p>Aucune notification</p>
            </div>
        `;
        return;
    }
    
    list.innerHTML = filteredNotifications.map(notif => {
        const reclamationId = notif.reference_id || 0;
        const message = notif.contenu || 'Nouvelle notification';
        const titre = notif.reclamation_objet || 'Réclamation';
        
        // Icône selon le type
        let icon = 'bx-bell';
        let iconColor = '#45AECC';
        
        if (notif.type === 'nouvelle_reclamation') {
            icon = 'bx-file-plus';
            iconColor = '#27ae60';
        } else if (notif.type === 'nouveau_commentaire') {
            icon = 'bx-message-square-dots';
            iconColor = '#3498db';
        } else if (notif.type === 'demande_info') {
            icon = 'bx-info-circle';
            iconColor = '#f39c12';
        } else if (notif.type === 'info_fournie') {
            icon = 'bx-check-circle';
            iconColor = '#9b59b6';
        }
        
        return `
            <div class="notification-item ${notif.lu == 0 ? 'unread' : ''}" 
                 onclick="handleNotificationClick(${notif.id}, ${reclamationId})">
                <div class="notification-content">
                    <div class="notification-title">
                        <i class='bx ${icon}' style="color: ${iconColor};"></i>
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
    // Marquer comme lue
    await markAsRead(notifId);
    
    // Fermer la modal
    closeModal();
    
    // Rediriger vers la réclamation
    window.location.href = `detail_reclamation.php?id=${reclamationId}#commentaires`;
}

// Marquer comme lue
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

// Marquer toutes comme lues
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

// Afficher le toast
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
    
    // Icône selon le type
    let iconClass = 'bx-bell';
    if (notif.type === 'nouvelle_reclamation') iconClass = 'bx-file-plus';
    if (notif.type === 'nouveau_commentaire') iconClass = 'bx-message-square-dots';
    if (notif.type === 'demande_info') iconClass = 'bx-info-circle';
    if (notif.type === 'info_fournie') iconClass = 'bx-check-circle';
    
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
    
    // Marquer toutes comme lues
    const markAll = document.getElementById('markAllRead');
    if (markAll) {
        markAll.addEventListener('click', () => {
            markAllAsRead();
        });
    }
    
    // Filtres
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            
            filterButtons.forEach(b => b.classList.remove('active'));
            
            btn.classList.add('active');
            
            currentFilter = btn.getAttribute('data-filter');
            
            renderNotifications(notificationsData, currentFilter);
        });
    });
    
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