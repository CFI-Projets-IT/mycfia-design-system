/**
 * Campaign Schedule - Planification Publication
 * Gestion du calendrier FullCalendar et du drag & drop des assets
 */

document.addEventListener('DOMContentLoaded', function () {
    // Elements
    const calendarEl = document.getElementById('calendar');
    const externalEventsEl = document.getElementById('external-events');
    const removeAfterDropCheckbox = document.getElementById('remove-after-drop');
    const timeModal = new bootstrap.Modal(document.getElementById('timeModal'));
    const noAssetModal = new bootstrap.Modal(document.getElementById('noAssetModal'));
    const confirmValidateModal = new bootstrap.Modal(document.getElementById('confirmValidateModal'));
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    const removeEventModal = new bootstrap.Modal(document.getElementById('removeEventModal'));
    const confirmTimeBtn = document.getElementById('confirm-time');
    const scheduledCountEl = document.getElementById('scheduled-count');
    const scheduledThisWeekEl = document.getElementById('scheduled-this-week');
    const scheduledThisMonthEl = document.getElementById('scheduled-this-month');
    const viewButtons = document.querySelectorAll('[data-view]');

    // State
    let scheduledAssets = [];
    let pendingDrop = null;
    let pendingRemove = null;

    // Initialize FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        buttonText: {
            today: "Aujourd'hui"
        },
        firstDay: 1, // Monday
        droppable: true,
        editable: true,
        eventDurationEditable: false,

        // Handle external event received (after auto-creation)
        eventReceive: function (info) {
            const event = info.event;
            const assetType = event.extendedProps.assetType;
            const assetId = event.extendedProps.assetId;

            // Find the original asset card
            const assetCard = document.querySelector(`[data-asset-id="${assetId}"]`);

            // Store pending drop info with reference to the auto-created event
            pendingDrop = {
                event: event,
                date: event.start,
                assetType: assetType,
                assetId: assetId,
                assetTitle: event.title,
                element: assetCard
            };

            // Update modal content
            updateModalContent(pendingDrop);

            // Show time selection modal
            timeModal.show();
        },

        // Event render
        eventDidMount: function (info) {
            // Add asset type data attribute for styling
            if (info.event.extendedProps.assetType) {
                info.el.setAttribute('data-asset-type', info.event.extendedProps.assetType);
            }

            // Build popover content
            const assetType = info.event.extendedProps.assetType;
            const time = info.event.extendedProps.time || '12:00';
            const format = info.event.extendedProps.format || '';
            const platformName = getPlatformName(assetType);
            const platformIcon = getAssetIcon(assetType);
            const platformColor = getAssetColor(assetType);

            const popoverContent = `
                <div class="event-popover">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="${platformIcon}" style="color: ${platformColor}; font-size: 1.25rem;"></i>
                        <strong>${platformName}</strong>
                    </div>
                    <p class="mb-1"><i class="bi bi-clock text-secondary"></i> Publication : <strong>${time}</strong></p>
                    ${format ? `<p class="mb-1"><i class="bi bi-file-earmark text-secondary"></i> Format : ${format}</p>` : ''}
                    <hr class="my-2">
                    <small class="text-muted">Cliquez pour retirer du planning</small>
                </div>
            `;

            // Initialize Bootstrap popover
            new bootstrap.Popover(info.el, {
                title: info.event.title,
                content: popoverContent,
                html: true,
                trigger: 'hover',
                placement: 'top',
                container: 'body'
            });
        },

        // Event click - allow removal
        eventClick: function (info) {
            // Hide popover first
            const popover = bootstrap.Popover.getInstance(info.el);
            if (popover) popover.hide();

            // Store event info for removal
            pendingRemove = {
                event: info.event,
                assetId: info.event.extendedProps.assetId,
                assetType: info.event.extendedProps.assetType,
                title: info.event.title
            };

            // Update modal and show
            document.getElementById('remove-event-title').textContent = info.event.title;
            removeEventModal.show();
        }
    });

    calendar.render();

    // Initialize Draggable for external events
    new FullCalendar.Draggable(externalEventsEl, {
        itemSelector: '.fc-event',
        eventData: function (eventEl) {
            return {
                title: eventEl.querySelector('.asset-card-title').textContent,
                backgroundColor: getAssetColor(eventEl.dataset.assetType),
                borderColor: getAssetColor(eventEl.dataset.assetType),
                extendedProps: {
                    assetType: eventEl.dataset.assetType,
                    assetId: eventEl.dataset.assetId
                }
            };
        }
    });

    // Confirm time selection
    confirmTimeBtn.addEventListener('click', function () {
        if (!pendingDrop) return;

        const selectedTime = document.getElementById('publication-time').value;
        const [hours, minutes] = selectedTime.split(':');

        // Create a fresh date object to avoid reference issues
        const originalDate = pendingDrop.event.start;
        const dateTime = new Date(
            originalDate.getFullYear(),
            originalDate.getMonth(),
            originalDate.getDate(),
            parseInt(hours),
            parseInt(minutes),
            0
        );

        // Update the existing event (don't create a new one)
        pendingDrop.event.setAllDay(false);
        pendingDrop.event.setStart(dateTime);
        pendingDrop.event.setExtendedProp('time', selectedTime);

        // Add to scheduled list
        scheduledAssets.push({
            assetId: pendingDrop.assetId,
            assetType: pendingDrop.assetType,
            date: dateTime,
            time: selectedTime
        });

        // Handle asset card visibility
        if (removeAfterDropCheckbox.checked && pendingDrop.element) {
            pendingDrop.element.style.display = 'none';
        }
        if (pendingDrop.element) {
            pendingDrop.element.classList.add('scheduled');
        }

        // Update badge count for this asset type
        updateAssetGroupCount(pendingDrop.assetType, -1);

        // Update summary
        updateSummary();

        // Close modal and reset
        timeModal.hide();
        pendingDrop = null;
    });

    // Cancel drop when modal is closed without confirming
    document.getElementById('timeModal').addEventListener('hidden.bs.modal', function () {
        if (pendingDrop) {
            // Remove the auto-created event using direct reference
            if (pendingDrop.event) {
                pendingDrop.event.remove();
            }
            pendingDrop = null;
        }
    });

    // View toggle buttons
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const view = this.dataset.view;
            calendar.changeView(view);
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Validate schedule button
    document.getElementById('btn-validate-schedule').addEventListener('click', function () {
        if (scheduledAssets.length === 0) {
            noAssetModal.show();
            return;
        }

        // Update confirmation modal with count
        document.getElementById('confirm-asset-count').textContent = scheduledAssets.length;
        confirmValidateModal.show();
    });

    // Confirm validation button in modal
    document.getElementById('confirm-validate').addEventListener('click', function () {
        confirmValidateModal.hide();
        // In real app: send to backend here
        successModal.show();
    });

    // Confirm remove event button in modal
    document.getElementById('confirm-remove-event').addEventListener('click', function () {
        if (!pendingRemove) return;

        // Re-enable the asset card
        const assetCard = document.querySelector(`[data-asset-id="${pendingRemove.assetId}"]`);
        if (assetCard) {
            assetCard.classList.remove('scheduled');
            assetCard.style.display = '';
        }

        // Remove from scheduled list
        scheduledAssets = scheduledAssets.filter(a => a.assetId !== pendingRemove.assetId);

        // Update badge count for this asset type
        updateAssetGroupCount(pendingRemove.assetType, 1);

        // Remove event
        pendingRemove.event.remove();

        // Update summary
        updateSummary();

        // Close modal and reset
        removeEventModal.hide();
        pendingRemove = null;
    });

    // Helper: Get color by asset type
    function getAssetColor(assetType) {
        const colors = {
            linkedin: '#0077B5',
            facebook: '#1877F2',
            instagram: '#E4405F',
            google: '#4285F4',
            bing: '#00A4EF',
            iab: '#6c757d'
        };
        return colors[assetType] || '#6c757d';
    }

    // Helper: Get icon class by asset type
    function getAssetIcon(assetType) {
        const icons = {
            linkedin: 'bi-linkedin',
            facebook: 'bi-facebook',
            instagram: 'bi-instagram',
            google: 'bi-google',
            bing: 'bi-microsoft',
            iab: 'bi-badge-ad'
        };
        return icons[assetType] || 'bi-file-earmark';
    }

    // Helper: Get platform display name
    function getPlatformName(assetType) {
        const names = {
            linkedin: 'LinkedIn',
            facebook: 'Facebook',
            instagram: 'Instagram',
            google: 'Google Ads',
            bing: 'Bing Ads',
            iab: 'IAB Display'
        };
        return names[assetType] || assetType;
    }

    // Helper: Update asset group badge count
    function updateAssetGroupCount(assetType, delta) {
        // Map asset type to icon class
        const iconMap = {
            linkedin: 'bi-linkedin',
            facebook: 'bi-facebook',
            instagram: 'bi-instagram',
            google: 'bi-google',
            bing: 'bi-microsoft',
            iab: 'bi-badge-ad'
        };

        const iconClass = iconMap[assetType];
        if (!iconClass) return;

        // Find all asset group headers and look for the matching icon
        const headers = document.querySelectorAll('.asset-group-header');
        for (const header of headers) {
            const icon = header.querySelector(`.${iconClass}`);
            if (icon) {
                const badge = header.querySelector('.badge');
                if (badge) {
                    const currentCount = parseInt(badge.textContent) || 0;
                    const newCount = Math.max(0, currentCount + delta);
                    badge.textContent = newCount;

                    // Update group opacity
                    const assetGroup = header.closest('.asset-group');
                    if (assetGroup) {
                        assetGroup.style.opacity = newCount === 0 ? '0.5' : '1';
                    }
                }
                break;
            }
        }
    }

    // Helper: Update modal content
    function updateModalContent(drop) {
        const assetInfo = document.getElementById('selected-asset-info');
        const dateInput = document.getElementById('selected-date');

        assetInfo.innerHTML = `
            <i class="${getAssetIcon(drop.assetType)}" style="color: ${getAssetColor(drop.assetType)}"></i>
            <strong>${drop.assetTitle}</strong>
        `;

        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateInput.value = drop.date.toLocaleDateString('fr-FR', dateOptions);
    }

    // Helper: Update summary counts
    function updateSummary() {
        const total = document.querySelectorAll('.asset-card').length;
        const scheduled = scheduledAssets.length;

        scheduledCountEl.textContent = scheduled;
        document.getElementById('total-count').textContent = total;

        // Count this week and this month
        const now = new Date();
        const startOfWeek = new Date(now);
        startOfWeek.setDate(now.getDate() - now.getDay() + 1); // Monday
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);

        const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
        const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        let thisWeek = 0;
        let thisMonth = 0;

        scheduledAssets.forEach(asset => {
            const date = new Date(asset.date);
            if (date >= startOfWeek && date <= endOfWeek) thisWeek++;
            if (date >= startOfMonth && date <= endOfMonth) thisMonth++;
        });

        scheduledThisWeekEl.textContent = thisWeek;
        scheduledThisMonthEl.textContent = thisMonth;
    }

    // Initial summary update
    updateSummary();
});
