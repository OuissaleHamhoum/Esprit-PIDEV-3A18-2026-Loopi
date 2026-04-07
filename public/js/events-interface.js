// Events Management Interface - Reusable Functions

// ─── Event Modal Management ───
function openEventModal() {
  const modal = document.getElementById('eventDetailModal');
  if (modal) {
    modal.classList.add('active');
    modal.scrollTop = 0;
    document.getElementById('eventModalTitle').textContent = 'Créer un événement';
    document.getElementById('btnSaveEvent').textContent = '+ Créer';
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
  }
}

function closeEventModal() {
  const modal = document.getElementById('eventDetailModal');
  if (modal) {
    modal.classList.remove('active');
    const form = document.getElementById('eventForm');
    if (form) form.reset();
  }
}

// Close modal on overlay click
document.addEventListener('DOMContentLoaded', function() {
  const modals = document.querySelectorAll('.event-modal-overlay');
  modals.forEach(modal => {
    modal.addEventListener('click', function(e) {
      if (e.target === this) {
        this.classList.remove('active');
      }
    });
  });
});

// ─── Event Card Rendering ───
function renderEventCard(event) {
  const capacity = event.capacite_max || 0;
  const participants = event.participants_count || 0;
  const capacityPercent = Math.min((participants / capacity) * 100, 100);
  
  const statusClass = getEventStatusClass(event.statut);
  const statusText = getEventStatusText(event.statut);
  
  const eventDate = new Date(event.date_evenement);
  const formattedDate = eventDate.toLocaleDateString('fr-FR', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });

  const card = document.createElement('div');
  card.className = 'event-card';
  card.innerHTML = `
    <div class="event-card-header">
      <div>
        <div class="event-card-title">${escapeHtml(event.titre || 'Événement sans titre')}</div>
        <div class="event-card-date">
          <i class="fas fa-calendar"></i>
          ${formattedDate}
        </div>
      </div>
      <span class="event-card-status ${statusClass}">${statusText}</span>
    </div>
    <div class="event-card-body">
      <div class="event-info-row">
        <span class="event-info-label"><i class="fas fa-map-marker-alt"></i> Lieu</span>
        <span class="event-info-value">${escapeHtml(event.lieu || 'Non spécifié')}</span>
      </div>
      <div class="event-info-row">
        <span class="event-info-label"><i class="fas fa-user"></i> Organisateur</span>
        <span class="event-info-value">${escapeHtml(event.organisateur || 'Non spécifié')}</span>
      </div>
      <div class="event-info-row">
        <span class="event-info-label"><i class="fas fa-users"></i> Participants</span>
        <div class="event-capacity">
          <div class="capacity-bar">
            <div class="capacity-fill" style="width: ${capacityPercent}%"></div>
          </div>
          <span class="event-info-value">${participants}/${capacity}</span>
        </div>
      </div>
    </div>
    <div class="event-card-footer">
      <button class="event-btn secondary" onclick="viewEventDetails(${event.id || event.id_evenement})">
        <i class="fas fa-eye"></i> Voir détails
      </button>
      <button class="event-btn primary" onclick="joinEvent(${event.id || event.id_evenement})">
        <i class="fas fa-check"></i> S'inscrire
      </button>
    </div>
  `;
  
  return card;
}

// ─── Event Status Helpers ───
function getEventStatusClass(status) {
  switch (status) {
    case 'valide':
    case 'confirmed':
      return 'confirmed';
    case 'en_attente':
    case 'pending':
      return 'pending';
    case 'rejete':
    case 'rejected':
      return 'rejected';
    default:
      return 'pending';
  }
}

function getEventStatusText(status) {
  switch (status) {
    case 'valide':
    case 'confirmed':
      return '✓ Validé';
    case 'en_attente':
    case 'pending':
      return '⏳ En attente';
    case 'rejete':
    case 'rejected':
      return '✕ Rejeté';
    default:
      return status;
  }
}

// ─── Utility Functions ───
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text || '';
  return div.innerHTML;
}

function formatEventDate(dateString) {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

// ─── Event Actions ───
function viewEventDetails(eventId) {
  console.log('Viewing event details:', eventId);
  // This will be implemented in the specific page (organisateur or participant)
}

function joinEvent(eventId) {
  console.log('Joining event:', eventId);
  // This will be implemented in the specific page (participant)
}

function editEvent(eventId) {
  console.log('Editing event:', eventId);
  // This will be implemented in the specific page (organisateur)
}

function deleteEvent(eventId) {
  console.log('Deleting event:', eventId);
  // This will be implemented in the specific page (organisateur)
}

// ─── Form Validation ───
function validateEventForm(formData) {
  const errors = {};

  if (!formData.titre || formData.titre.trim().length < 3) {
    errors.titre = 'Le titre doit contenir au moins 3 caractères';
  }

  if (!formData.description || formData.description.trim().length < 10) {
    errors.description = 'La description doit contenir au moins 10 caractères';
  }

  if (!formData.date_evenement) {
    errors.date_evenement = 'La date est requise';
  } else {
    const eventDate = new Date(formData.date_evenement);
    const now = new Date();
    if (eventDate <= now) {
      errors.date_evenement = 'La date doit être dans le futur';
    }
  }

  if (!formData.lieu || formData.lieu.trim().length === 0) {
    errors.lieu = 'Le lieu est requis';
  }

  const capacite = parseInt(formData.capacite_max);
  if (!capacite || capacite <= 0) {
    errors.capacite_max = 'La capacité doit être supérieure à 0';
  } else if (capacite > 10000) {
    errors.capacite_max = 'La capacité ne peut pas dépasser 10000';
  }

  return Object.keys(errors).length === 0 ? true : errors;
}

// ─── Form Error Display ───
function displayFormErrors(errors) {
  // Clear previous errors
  document.querySelectorAll('.event-form-error').forEach(el => {
    el.textContent = '';
  });
  document.querySelectorAll('.event-form-group').forEach(el => {
    el.classList.remove('error');
  });

  // Display new errors
  if (typeof errors === 'object') {
    Object.keys(errors).forEach(field => {
      const errorElement = document.getElementById(`error-${field}`);
      const formGroup = errorElement?.closest('.event-form-group');
      
      if (errorElement && formGroup) {
        errorElement.textContent = errors[field];
        formGroup.classList.add('error');
      }
    });
  }
}

// ─── Toast Notifications ───
function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = message;
  toast.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: var(--accent);
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    z-index: 10000;
    font-size: 0.85rem;
    font-weight: 600;
    animation: slideUp 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  `;
  
  document.body.appendChild(toast);
  
  setTimeout(() => {
    toast.style.animation = 'slideDown 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// Add CSS for toast animation if not already present
if (!document.getElementById('eventsToastStyles')) {
  const style = document.createElement('style');
  style.id = 'eventsToastStyles';
  style.textContent = `
    @keyframes slideUp {
      from { transform: translateY(100px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    @keyframes slideDown {
      from { transform: translateY(0); opacity: 1; }
      to { transform: translateY(100px); opacity: 0; }
    }
  `;
  document.head.appendChild(style);
}
