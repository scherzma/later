import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { fetchTasks, completeTask, updateTask } from "../services/api";
import moment from "moment";
import { fromNow, isPastDue } from "../utils/dateUtils";
import { toast } from "react-toastify";
import TaskCompletionAnimation from "./TaskCompletionAnimation";
import { useStreak } from "../context/StreakContext";

function TaskList() {
    const { updateStreakInfo, fetchStreakData } = useStreak();
    const [tasks, setTasks] = useState([]);
    const [filter, setFilter] = useState("all");
    const [sortBy, setSortBy] = useState("endDate");
    const [search, setSearch] = useState("");
    const [loading, setLoading] = useState(true);
    const [completedTaskId, setCompletedTaskId] = useState(null);

    const loadTasks = async () => {
        setLoading(true);
        try {
            const fetchedTasks = await fetchTasks();
            setTasks(fetchedTasks);
        } catch (error) {
            toast.error("Failed to load tasks");
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadTasks();
    }, []);

    const handleStatusToggle = async (taskId, currentStatus) => {
        try {
            if (!currentStatus) {
                // Marking as completed - use the dedicated endpoint
                setCompletedTaskId(taskId);
                const response = await completeTask(taskId);

                // Update streak info if it's returned
                if (response.streakInfo) {
                    updateStreakInfo(response.streakInfo);
                }

                // Task will be updated when animation completes
            } else {
                // Marking as pending - use the update endpoint
                await updateTask(taskId, { finished: false });

                // Update the task in the local state
                setTasks(tasks.map(task =>
                    task.taskId === taskId ? { ...task, finished: false } : task
                ));

                toast.success("Task marked as pending");
            }
        } catch (error) {
            toast.error("Failed to update task status");
            console.error(error);
            setCompletedTaskId(null);
        }
    };

    const handleAnimationComplete = () => {
        // After animation completes, update the completed task in the local state
        setTasks(tasks.map(task =>
            task.taskId === completedTaskId ? { ...task, finished: true } : task
        ));

        // Also refresh streak data to ensure it's up-to-date everywhere
        fetchStreakData();

        toast.success("Task marked as completed!");
        setCompletedTaskId(null);
    };

    const filteredTasks = tasks
        .filter((task) => {
            if (filter === "completed") return task.finished;
            if (filter === "pending") return !task.finished;
            if (filter === "overdue") return !task.finished && isPastDue(task.endDate);
            return true;
        })
        .filter((task) =>
            task.title.toLowerCase().includes(search.toLowerCase()) ||
            (task.description && task.description.toLowerCase().includes(search.toLowerCase())) ||
            (task.tags && task.tags.some(tag => tag.name.toLowerCase().includes(search.toLowerCase())))
        )
        .sort((a, b) => {
            if (sortBy === "endDate") {
                if (!a.endDate && !b.endDate) return 0;
                if (!a.endDate) return 1;
                if (!b.endDate) return -1;
                return new Date(a.endDate) - new Date(b.endDate);
            }
            if (sortBy === "priority") {
                const priorityMap = { high: 3, medium: 2, low: 1 };
                return priorityMap[b.priority] - priorityMap[a.priority];
            }
            if (sortBy === "title") {
                return a.title.localeCompare(b.title);
            }
            return 0;
        });

    const getPriorityBadgeClass = (priority) => {
        switch (priority) {
            case 'high':
                return 'bg-red-100 text-red-800 border-red-200';
            case 'medium':
                return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            case 'low':
                return 'bg-green-100 text-green-800 border-green-200';
            default:
                return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    return (
        <div className="max-w-4xl mx-auto">
            {/* Task Completion Animation */}
            <TaskCompletionAnimation
                show={completedTaskId !== null}
                onComplete={handleAnimationComplete}
            />

            <div className="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                <div className="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 flex justify-between items-center">
                    <h1 className="text-2xl font-bold text-white">Your Tasks</h1>
                    <Link
                        to="/tasks/new"
                        className="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition focus:outline-none focus:ring-2 focus:ring-white"
                    >
                        Add Task
                    </Link>
                </div>

                <div className="p-6 bg-gray-50">
                    <div className="flex flex-wrap gap-3 mb-4">
                        <div className="flex-1 min-w-[200px]">
                            <div className="relative">
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Search tasks..."
                                    className="w-full p-3 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                />
                                <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-gray-400 absolute left-3 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <div>
                            <select
                                value={filter}
                                onChange={(e) => setFilter(e.target.value)}
                                className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition appearance-none bg-white"
                            >
                                <option value="all">All Tasks</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>

                        <div>
                            <select
                                value={sortBy}
                                onChange={(e) => setSortBy(e.target.value)}
                                className="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition appearance-none bg-white"
                            >
                                <option value="endDate">Sort by Date</option>
                                <option value="priority">Sort by Priority</option>
                                <option value="title">Sort by Title</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {loading ? (
                <div className="flex justify-center items-center py-12">
                    <svg className="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            ) : filteredTasks.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {filteredTasks.map((task) => (
                        <div
                            key={task.taskId}
                            className={`bg-white border-l-4 ${task.finished ? 'border-green-500' : isPastDue(task.endDate) ? 'border-red-500' : 'border-blue-500'} rounded-lg shadow-md overflow-hidden hover:shadow-lg transition ${completedTaskId === task.taskId ? 'animate-pulse' : ''}`}
                        >
                            <div className="p-5">
                                <div className="flex justify-between items-start mb-3">
                                    <h3 className={`text-lg font-bold ${task.finished ? 'line-through text-gray-500' : ''}`}>
                                        {task.title}
                                    </h3>
                                    <div className="flex items-center">
                                        <span
                                            className={`text-xs font-semibold px-2.5 py-0.5 rounded-full border ${getPriorityBadgeClass(task.priority)}`}
                                        >
                                            {task.priority}
                                        </span>
                                    </div>
                                </div>

                                {task.description && (
                                    <p className={`mb-3 text-gray-600 ${task.finished ? 'line-through text-gray-400' : ''}`}>
                                        {task.description.length > 100
                                            ? `${task.description.substring(0, 100)}...`
                                            : task.description}
                                    </p>
                                )}

                                <div className="grid grid-cols-2 gap-2 mb-3">
                                    {task.endDate && (
                                        <div className={`text-sm ${isPastDue(task.endDate) && !task.finished ? 'text-red-600 font-medium' : 'text-gray-500'}`}>
                                            <span className="block">Due {fromNow(task.endDate)}</span>
                                            <span className="block text-xs">{moment(task.endDate).format("MMM Do YYYY, h:mm a")}</span>
                                        </div>
                                    )}

                                    {task.tags && task.tags.length > 0 && (
                                        <div className="flex flex-wrap gap-1 justify-end">
                                            {task.tags.slice(0, 3).map((tag) => (
                                                <span
                                                    key={tag.tagId}
                                                    className="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded"
                                                >
                                                    {tag.name}
                                                </span>
                                            ))}
                                            {task.tags.length > 3 && (
                                                <span className="bg-gray-100 text-gray-800 text-xs px-2 py-0.5 rounded">
                                                    +{task.tags.length - 3}
                                                </span>
                                            )}
                                        </div>
                                    )}
                                </div>

                                <div className="flex justify-between items-center pt-2 border-t border-gray-100">
                                    <Link
                                        to={`/tasks/${task.taskId}`}
                                        className="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                    >
                                        View Details
                                    </Link>
                                    <button
                                        onClick={() => handleStatusToggle(task.taskId, task.finished)}
                                        className={`px-3 py-1 rounded-md text-sm font-medium ${
                                            task.finished
                                                ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'
                                                : 'bg-green-100 text-green-800 hover:bg-green-200'
                                        } transition-all transform hover:scale-105`}
                                        disabled={completedTaskId === task.taskId}
                                    >
                                        {task.finished ? 'Mark Pending' : 'Mark Complete'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow-md p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 className="text-lg font-medium text-gray-900 mb-2">No tasks found</h3>
                    <p className="text-gray-500 mb-4">
                        {search
                            ? `No tasks match "${search}"`
                            : filter !== "all"
                                ? `No ${filter} tasks found`
                                : "You don't have any tasks yet"}
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
    );
}

export default TaskList;