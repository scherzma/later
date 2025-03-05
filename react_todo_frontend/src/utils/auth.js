// src/utils/auth.js

export const login = async (username, password) => {
    try {
        const response = await import("../services/api").then((m) => m.login(username, password));
        const { token, sessionId, user } = response;
        
        console.log("Login response:", response); // Debug output
        
        // Store token and session information
        localStorage.setItem("token", token);
        localStorage.setItem("sessionId", sessionId);
        
        // Store last login info for display
        if (user) {
            // Make sure we handle failedAttempts correctly
            const userData = {
                id: user.id,
                username: user.username,
                lastLogin: user.lastLogin,
                failedAttempts: user.failedAttempts || 0 // Ensure we set 0 if undefined
            };
            console.log("Storing user data:", userData); // Debug output
            localStorage.setItem("userData", JSON.stringify(userData));
        } else {
            console.warn("No user data in login response");
        }
        
        return { success: true, user };
    } catch (error) {
        console.error("Login error:", error);
        return { success: false, error };
    }
};

export const register = async (username, password) => {
    try {
        // Just call the API and wait for the response
        // We're not setting any token here since we'll require users to log in separately
        const response = await import("../services/api").then((m) => m.register(username, password));
        // Return true to indicate successful registration
        return true;
    } catch (error) {
        console.error("Registration error:", error);
        throw error; // Re-throw the error to be handled by the component
    }
};

export const logout = async () => {
    try {
        // Call the logout API endpoint with session ID
        const sessionId = getSessionId();
        if (sessionId) {
            await import("../services/api").then((m) => m.logout(sessionId));
        }
    } catch (error) {
        console.error("Logout error:", error);
    } finally {
        // Always clear local storage regardless of API success
        localStorage.removeItem("token");
        localStorage.removeItem("sessionId");
        localStorage.removeItem("userData");
    }
};

export const getToken = () => localStorage.getItem("token");
export const getSessionId = () => localStorage.getItem("sessionId");
export const getUserData = () => {
    const data = localStorage.getItem("userData");
    return data ? JSON.parse(data) : null;
};
export const isLoggedIn = () => !!getToken();

// Session check function that verifies if session is still valid
export const checkSession = async () => {
    const sessionId = getSessionId();
    if (!sessionId) return false;
    
    try {
        const response = await import("../services/api").then((m) => m.checkSession(sessionId));
        return response.valid;
    } catch (error) {
        console.error("Session check error:", error);
        return false;
    }
};