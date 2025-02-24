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
            setStreakInfo(prev => ({
                ...prev,
                ...newStreakInfo
            }));
        }
    };

    // Load streak data on component mount
    useEffect(() => {
        fetchStreakData();
    }, []);

    // The context value that will be provided
    const value = {
        streakInfo,
        updateStreakInfo,
        fetchStreakData,
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