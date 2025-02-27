import React, { useState, useEffect } from "react";
import { fetchTasks, completeTask, postponeTask, getNextTask } from "../services/api";
import { toast } from "react-toastify";
import moment from "moment";
import { Link } from "react-router-dom";
import { isPastDue } from "../utils/dateUtils";
import TaskCompletionAnimation from "./TaskCompletionAnimation";
import { useStreak } from "../context/StreakContext";

function Home() {
    const { streakInfo, updateStreakInfo, fetchStreakData } = useStreak();
    const [recommendedTask, setRecommendedTask] = useState(null);
    const [loading, setLoading] = useState(true);
    const [showAnimation, setShowAnimation] = useState(false);
    const [stats, setStats] = useState({
        total: 0,
        completed: 0,
        pending: 0,
        overdue: 0
    });

    const getRecommendedTask = async () => {
        try {
            setLoading(true);

            // Get next recommended task from API
            const response = await getNextTask();

            if (response.hasTask) {
                setRecommendedTask(response.task);
            } else {
                setRecommendedTask(null);
            }

            // Fetch all tasks to calculate stats
            const tasks = await fetchTasks();

            // Calculate stats
            const completed = tasks.filter(task => task.finished).length;
            const pending = tasks.filter(task => !task.finished).length;
            const overdue = tasks.filter(task => !task.finished && isPastDue(task.endDate)).length;

            setStats({
                total: tasks.length,
                completed,
                pending,
                overdue
            });

            // Extract streak info if available in the user profile (included in the response)
            if (response.streakInfo) {
                updateStreakInfo(response.streakInfo);
            }
        } catch (error) {
            toast.error("Failed to load recommended task.");
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        getRecommendedTask();
        // We don't need to fetch streak data here since StreakContext is handling that
    }, []);

    const handleCompleted = async (taskId) => {
        try {
            // Trigger animation
            setShowAnimation(true);

            // Use the dedicated complete task endpoint that updates streaks
            const result = await completeTask(taskId);

            // If the API returns updated streak info, update the context
            if (result.streakInfo) {
                updateStreakInfo(result.streakInfo);
            }

            // We don't immediately refresh the task here - we'll wait for animation to complete
        } catch (error) {
            toast.error("Failed to mark task as completed.");
            console.error(error);
            setShowAnimation(false); // Reset animation state if there's an error
        }
    };

    const handleAnimationComplete = () => {
        // After animation completes, refresh the recommended task
        setShowAnimation(false);
        toast.success("Task marked as completed!");
        getRecommendedTask();
        // Also refresh streak data to ensure it's up-to-date everywhere
        fetchStreakData();
    };

    const handleLater = async () => {
        if (recommendedTask) {
            try {
                setLoading(true);
                // Store the current task ID to verify it changes
                const currentTaskId = recommendedTask.taskId;
                
                // Use the postpone endpoint to add the task to the queue
                await postponeTask(recommendedTask.taskId);
                toast.info("Task postponed for later.");
                
                // Get the next task (which should be different)
                await getRecommendedTask();
                
                // If we still get the same task back, inform the user
                if (recommendedTask && recommendedTask.taskId === currentTaskId) {
                    toast.warning("This is your highest priority task. Try prioritizing other tasks or creating new ones.");
                }
            } catch (error) {
                // For 400 errors which indicate the task is already in queue
                if (error.response?.status === 400) {
                    toast.warning("This task is already in your queue or cannot be postponed.");
                } else {
                    toast.error("Failed to postpone task.");
                }
                console.error(error);
            } finally {
                setLoading(false);
            }
        }
    };

    const getPriorityColor = (priority) => {
        switch (priority) {
            case 'high':
                return 'border-red-500 bg-red-50';
            case 'medium':
                return 'border-yellow-500 bg-yellow-50';
            case 'low':
                return 'border-green-500 bg-green-50';
            default:
                return 'border-gray-300 bg-gray-50';
        }
    };

    // Calculate streak completion percentage for progress bar
    const calculateStreakPercentage = () => {
        // Assuming 10 days is a good milestone to reach 100%
        return Math.min(100, Math.round((streakInfo.currentStreak / 10) * 100));
    };

    return (
        <div className="max-w-4xl mx-auto">
            {/* Task Completion Animation */}
            <TaskCompletionAnimation
                show={showAnimation}
                onComplete={handleAnimationComplete}
            />

            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div className="bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500">
                    <div className="flex items-center">
                        <div className="bg-blue-100 rounded-full p-2 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Total Tasks</p>
                            <p className="text-2xl font-bold">{stats.total}</p>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
                    <div className="flex items-center">
                        <div className="bg-green-100 rounded-full p-2 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Completed</p>
                            <p className="text-2xl font-bold">{stats.completed}</p>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow-md p-4 border-l-4 border-yellow-500">
                    <div className="flex items-center">
                        <div className="bg-yellow-100 rounded-full p-2 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Pending</p>
                            <p className="text-2xl font-bold">{stats.pending}</p>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow-md p-4 border-l-4 border-red-500">
                    <div className="flex items-center">
                        <div className="bg-red-100 rounded-full p-2 mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Overdue</p>
                            <p className="text-2xl font-bold">{stats.overdue}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
                <div className="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                    <h1 className="text-2xl font-bold text-white">Recommended Task</h1>
                </div>

                {loading ? (
                    <div className="flex justify-center items-center p-12">
                        <svg className="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                ) : recommendedTask ? (
                    <div className={`p-6 border-t-4 ${getPriorityColor(recommendedTask.priority)} transition-all duration-300 ${showAnimation ? 'opacity-50' : 'opacity-100'}`}>
                        <div className="mb-4">
                            <h2 className="text-xl font-bold mb-2 text-gray-800">{recommendedTask.title}</h2>
                            <p className="text-gray-600 mb-4">
                                {recommendedTask.description || "No description provided for this task."}
                            </p>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                {recommendedTask.endDate && (
                                    <div className={`flex items-center ${isPastDue(recommendedTask.endDate) ? 'text-red-600' : 'text-gray-600'}`}>
                                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>
                                            {isPastDue(recommendedTask.endDate) ? 'Overdue: ' : 'Due: '}
                                            {moment(recommendedTask.endDate).format("MMM Do YYYY, h:mm a")}
                                        </span>
                                    </div>
                                )}

                                <div className="flex items-center text-gray-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                    </svg>
                                    <span className="capitalize">Priority: {recommendedTask.priority}</span>
                                </div>
                            </div>

                            {recommendedTask.location && (
                                <div className="flex items-center text-gray-600 mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>{recommendedTask.location}</span>
                                </div>
                            )}

                            {recommendedTask.tags && recommendedTask.tags.length > 0 && (
                                <div className="mb-4">
                                    <div className="flex flex-wrap gap-2">
                                        {recommendedTask.tags.map((tag) => (
                                            <span
                                                key={tag.tagId}
                                                className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"
                                            >
                                                {tag.name}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="flex justify-between">
                            <Link
                                to={`/tasks/${recommendedTask.taskId}`}
                                className="text-blue-600 hover:text-blue-800 font-medium"
                            >
                                View Details
                            </Link>

                            <div className="flex space-x-2">
                                <button
                                    onClick={handleLater}
                                    className="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 transition"
                                >
                                    Skip for Now
                                </button>
                                <button
                                    onClick={() => handleCompleted(recommendedTask.taskId)}
                                    className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition transform hover:scale-105 active:scale-95"
                                    disabled={showAnimation}
                                >
                                    {showAnimation ? (
                                        <span className="flex items-center">
                                            <svg className="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Completing...
                                        </span>
                                    ) : "Complete Task"}
                                </button>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center p-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 className="text-xl font-medium text-gray-900 mb-2">All caught up!</h3>
                        <p className="text-gray-500 mb-4">
                            You don't have any pending tasks right now.
                        </p>
                        <Link
                            to="/tasks/new"
                            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" className="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clipRule="evenodd" />
                            </svg>
                            Create a new task
                        </Link>
                    </div>
                )}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div className="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                        <h2 className="text-xl font-bold text-white">Quick Links</h2>
                    </div>
                    <div className="p-6">
                        <div className="grid grid-cols-2 gap-4">
                            <Link
                                to="/tasks"
                                className="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 hover:scale-105 transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-purple-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>All Tasks</span>
                            </Link>
                            <Link
                                to="/tasks/new"
                                className="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 hover:scale-105 transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                </svg>
                                <span>New Task</span>
                            </Link>
                            <Link
                                to="/tags"
                                className="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 hover:scale-105 transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <span>Manage Tags</span>
                            </Link>
                            <Link
                                to="/profile"
                                className="flex items-center p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:scale-105 transition-all duration-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6 text-yellow-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>Profile</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div className="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
                        <h2 className="text-xl font-bold text-white">Task Tips</h2>
                    </div>
                    <div className="p-6">
                        <ul className="space-y-3">
                            <li className="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-indigo-500 mr-2 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                                <span>Focus on one task at a time for maximum productivity</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Achievement stats section */}
            <div className="bg-white shadow-lg rounded-lg overflow-hidden mt-6">
                <div className="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4">
                    <h2 className="text-xl font-bold text-white">Achievements</h2>
                </div>
                <div className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-yellow-50 rounded-lg p-4 text-center border border-yellow-100">
                            <div className="text-3xl font-bold text-yellow-600 mb-1">{stats.completed}</div>
                            <div className="text-sm text-yellow-800">Tasks Completed</div>
                        </div>
                        <div className="bg-blue-50 rounded-lg p-4 text-center border border-blue-100">
                            <div className="text-3xl font-bold text-blue-600 mb-1">{streakInfo.currentStreak}</div>
                            <div className="text-sm text-blue-800">Current Streak</div>
                        </div>
                        <div className="bg-purple-50 rounded-lg p-4 text-center border border-purple-100">
                            <div className="text-3xl font-bold text-purple-600 mb-1">{streakInfo.bestStreak}</div>
                            <div className="text-sm text-purple-800">Best Streak</div>
                        </div>
                    </div>

                    {/* Progress bar */}
                    <div className="mt-6">
                        <div className="flex justify-between mb-1">
                            <span className="text-sm font-medium text-gray-700">Productivity Level</span>
                            <span className="text-sm font-medium text-gray-700">
                                {calculateStreakPercentage()}%
                            </span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-2.5">
                            <div
                                className="bg-gradient-to-r from-green-500 to-blue-500 h-2.5 rounded-full"
                                style={{ width: `${calculateStreakPercentage()}%` }}
                            ></div>
                        </div>
                        <div className="flex justify-between mt-2">
                            <span className="text-xs text-gray-500">
                                Level {Math.floor(streakInfo.currentStreak / 5) + 1}
                            </span>
                            <span className="text-xs text-gray-500">
                                Next: Level {Math.floor(streakInfo.currentStreak / 5) + 2}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default Home;