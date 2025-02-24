// profile.js
async function fetchProfile() {
    return await apiFetch(`${API_URL}/users/me`);
}

async function updateProfile(profileData) {
    return await apiFetch(`${API_URL}/users/me`, {
        method: 'PUT',
        body: JSON.stringify(profileData)
    });
}

async function loadProfile() {
    try {
        const profile = await fetchProfile();
        document.getElementById('profile-username').value = profile.username;
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

document.getElementById('profile-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('profile-username').value;
    const password = document.getElementById('profile-password').value;
    const profileData = { username };
    if (password) profileData.password = password;
    try {
        await updateProfile(profileData);
        alert('Profile updated successfully');
    } catch (error) {
        console.error('Error updating profile:', error);
    }
});