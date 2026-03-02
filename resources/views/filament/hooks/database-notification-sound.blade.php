<script>
(() => {
    if (window.__filamentDbNotificationSoundInitialized) {
        return;
    }

    window.__filamentDbNotificationSoundInitialized = true;

    const storageKey = 'filament_db_notification_unread_count';
    const buttonSelector = '.fi-topbar-database-notifications-btn';
    const badgeSelector = `${buttonSelector} .fi-icon-btn-badge-ctn`;

    let lastUnreadCount = Number(sessionStorage.getItem(storageKey));

    if (Number.isNaN(lastUnreadCount)) {
        lastUnreadCount = null;
    }

    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    const audioContext = AudioContextClass ? new AudioContextClass() : null;

    const resumeAudioContext = () => {
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume().catch(() => {});
        }
    };

    const playNotificationSound = () => {
        if (!audioContext) {
            return;
        }

        resumeAudioContext();

        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(880, audioContext.currentTime);

        gainNode.gain.setValueAtTime(0.0001, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.12, audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.20);

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.22);
    };

    const getUnreadCount = () => {
        const badgeElement = document.querySelector(badgeSelector);

        if (!badgeElement) {
            return 0;
        }

        const numericValue = Number.parseInt((badgeElement.textContent || '').replace(/\D+/g, ''), 10);

        return Number.isNaN(numericValue) ? 0 : numericValue;
    };

    const checkForNewDatabaseNotifications = () => {
        const currentUnreadCount = getUnreadCount();

        if (lastUnreadCount === null) {
            lastUnreadCount = currentUnreadCount;
            sessionStorage.setItem(storageKey, String(currentUnreadCount));
            return;
        }

        if (currentUnreadCount > lastUnreadCount) {
            playNotificationSound();
        }

        lastUnreadCount = currentUnreadCount;
        sessionStorage.setItem(storageKey, String(currentUnreadCount));
    };

    document.addEventListener('click', resumeAudioContext, { once: true });
    document.addEventListener('keydown', resumeAudioContext, { once: true });
    document.addEventListener('livewire:navigated', () => setTimeout(checkForNewDatabaseNotifications, 300));

    checkForNewDatabaseNotifications();
    setInterval(checkForNewDatabaseNotifications, 4000);
})();
</script>
