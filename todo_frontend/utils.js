// utils.js
async function apiFetch(url, options = {}) {
    const token = getToken();
    options.headers = {
        ...options.headers,
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    };
    const response = await fetch(url, options);
    if (response.status === 401) {
        logout();
        showSection('login');
        throw new Error('Unauthorized');
    }
    if (!response.ok) throw new Error(`API error: ${response.status}`);
    return response.json();
}