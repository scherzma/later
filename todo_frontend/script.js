// script.js
const appState = {
    currentTaskId: null,
    currentTagId: null
};

function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(section => section.classList.add('hidden'));
    document.getElementById(sectionId).classList.remove('hidden');

    const nav = document.getElementById('nav');
    if (sectionId === 'login' || sectionId === 'register') {
        nav.classList.add('hidden');
    } else {
        nav.classList.remove('hidden');
    }

    // Load data for specific sections if needed
    switch (sectionId) {
        case 'home':
            loadHome().catch(error => console.error('Error loading home:', error));
            break;
        case 'task-list':
            loadTaskList().catch(error => console.error('Error loading task list:', error));
            break;
        case 'tags':
            loadTags().catch(error => console.error('Error loading tags:', error));
            break;
        case 'profile':
            loadProfile().catch(error => console.error('Error loading profile:', error));
            break;
    }
}

// Initial load with error handling
document.addEventListener('DOMContentLoaded', () => {
    try {
        if (isLoggedIn()) {
            showSection('home');
        } else {
            showSection('login');
        }
    } catch (error) {
        console.error('Error initializing app:', error);
        showSection('login'); // Fallback to login on error
    }
});