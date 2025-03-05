// src/context/StreakContext.js
import React, { createContext, useState, useContext, useEffect } from 'react';
import { fetchUserStreak } from '../services/api';
import { isLoggedIn } from '../utils/auth';

// Create context
const StreakContext = createContext();

// Context provider component
export const StreakProvider = ({ children }) => {
    const [streakInfo, setStreakInfo] = useState({
        currentStreak: 0,
        bestStreak: 0,
        lastCompletedDate: null,
        needsTaskToday: true,
        tasksFinishedToday: 0,
        pendingTasks: 0
    });
    const [loading, setLoading] = useState(true);

    // Function to fetch streak data
    const fetchStreakData = async () => {
        if (!isLoggedIn()) return;

        try {
            setLoading(true);
            const response = await fetchUserStreak();
            if (response && response.streakInfo) {
                setStreakInfo(response.streakInfo);
            }
        } catch (error) {
            console.error('Failed to fetch streak data:', error);
        } finally {
            setLoading(false);
        }
    };

    // Function to update streak data (will be called after task completion)
    const updateStreakInfo = (newStreakInfo) => {
        if (newStreakInfo) {
            console.log('Updating streak info with:', newStreakInfo);
            setStreakInfo(prev => ({
                ...prev,
                ...newStreakInfo
            }));
        }
    };

    // Load streak data on component mount and when explicitly refreshed
    // Add a state to track when we need to refresh streak data
    const [refreshTrigger, setRefreshTrigger] = useState(0);
    
    // Function to explicitly request a refresh of streak data
    const refreshStreak = async (forceValue = null) => {
        // If we have a force value, immediately update the streak
        if (forceValue !== null) {
            setStreakInfo(prev => ({
                ...prev,
                currentStreak: forceValue
            }));
        }
        
        // Then trigger a refresh from the server
        setRefreshTrigger(prev => prev + 1);
    };
    
    useEffect(() => {
        fetchStreakData();
    }, [refreshTrigger]);

    // The context value that will be provided
    const value = {
        streakInfo,
        updateStreakInfo,
        fetchStreakData,
        refreshStreak,
        loading
    };

    return (
        <StreakContext.Provider value={value}>
            {children}
        </StreakContext.Provider>
    );
};

// Custom hook to use the streak context
export const useStreak = () => {
    const context = useContext(StreakContext);
    if (context === undefined) {
        throw new Error('useStreak must be used within a StreakProvider');
    }
    return context;
};