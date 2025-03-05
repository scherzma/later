<?php
require_once "./Model/User.php";

/**
 * Function to check if the session is active and valid.
 * If session has expired due to inactivity, it logs the user out.
 * 
 * @param string $sessionId The session ID
 * @param int $timeout Inactivity timeout in seconds (default 1800 = 30 minutes)
 * @return bool Whether the session is valid
 */
function checkSession($sessionId, $timeout = 1800) {
    if (empty($sessionId)) {
        return false;
    }
    
    // Validate the session
    $userId = User::validateSession($sessionId);
    
    if (!$userId) {
        // Session has expired or doesn't exist
        return false;
    }
    
    // Update session activity
    User::updateSession($sessionId, $timeout);
    
    // Load user and update activity timestamp
    $user = new User($userId);
    $user->updateActivity();
    
    return true;
}

/**
 * Function to end a session (logout).
 * 
 * @param string $sessionId The session ID
 * @return bool Whether the logout was successful
 */
function endSession($sessionId) {
    if (empty($sessionId)) {
        return false;
    }
    
    return User::destroySession($sessionId);
}

/**
 * Clean up expired sessions (to be run as a scheduled task)
 * 
 * @return int Number of expired sessions removed
 */
function cleanupExpiredSessions() {
    $db = Todo_DB::gibInstanz();
    
    $query = "DELETE FROM UserSession WHERE ExpiresAt <= NOW()";
    $db->myQuery($query, []);
    
    return $db->rowCount();
}
?>