// src/utils/auth.js

export const login = async (username, password) => {
    try {
        const { token } = await import("../services/api").then((m) => m.login(username, password));
        localStorage.setItem("token", token);
        return true;
    } catch (error) {
        console.error("Login error:", error);
        return false;
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

export const logout = () => localStorage.removeItem("token");
export const getToken = () => localStorage.getItem("token");
export const isLoggedIn = () => !!getToken();