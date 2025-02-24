// auth.js
const API_URL = 'http://localhost:8000';

async function login(username, password) {
    try {
        const response = await fetch(`${API_URL}/users/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        if (!response.ok) throw new Error('Login failed');
        const data = await response.json();
        localStorage.setItem('token', data.token);
        return true;
    } catch (error) {
        console.error('Login error:', error);
        return false;
    }
}

async function register(username, password) {
    try {
        const response = await fetch(`${API_URL}/users/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        if (!response.ok) throw new Error('Registration failed');
        return true;
    } catch (error) {
        console.error('Registration error:', error);
        return false;
    }
}

function logout() {
    localStorage.removeItem('token');
    showSection('login');
}

function getToken() {
    return localStorage.getItem('token');
}

function isLoggedIn() {
    return !!getToken();
}

// Event listeners for login and registration forms
document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    if (await login(username, password)) {
        showSection('home');
        loadHome();
    } else {
        alert('Login failed');
    }
});

document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('register-username').value;
    const password = document.getElementById('register-password').value;
    if (await register(username, password)) {
        alert('Registration successful, please log in.');
        showSection('login');
    } else {
        alert('Registration failed');
    }
});