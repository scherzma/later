// home.js
async function getRecommendedTask() {
    try {
        const tasks = await fetchTasks();
        const unfinishedTasks = tasks.filter(task => !task.finished);
        unfinishedTasks.sort((a, b) => {
            const priorityMap = { high: 3, medium: 2, low: 1 };
            if (a.endDate && b.endDate) {
                return new Date(a.endDate) - new Date(b.endDate) || priorityMap[b.priority] - priorityMap[a.priority];
            } else if (a.endDate) return -1;
            else if (b.endDate) return 1;
            else return priorityMap[b.priority] - priorityMap[a.priority];
        });
        return unfinishedTasks[0] || null;
    } catch (error) {
        console.error('Error fetching tasks for recommendation:', error);
        return null;
    }
}

async function loadHome() {
    try {
        const task = await getRecommendedTask();
        if (task) {
            renderRecommendedTask(task);
        } else {
            document.getElementById('recommended-task').innerHTML = '<p>No tasks available. <a href="#" onclick="showSection(\'task-create\')" class="text-blue-500 hover:underline">Create a task now</a>.</p>';
        }
    } catch (error) {
        document.getElementById('recommended-task').innerHTML = '<p>Error loading tasks. Please try again or check your connection.</p>';
        console.error('Error loading home:', error);
    }
}

// ... rest of home.js remains the same

function renderRecommendedTask(task) {
    document.getElementById('recommended-task').innerHTML = `
        <h2 class="text-xl font-bold">${task.title}</h2>
        <p>${task.description || 'No description'}</p>
        <p>End Date: ${task.endDate || 'None'}</p>
        <p>Priority: ${task.priority}</p>
        <div>Tags: ${task.tags ? task.tags.map(tag => tag.name).join(', ') : 'None'}</div>
        <div class="mt-4">
            <button class="bg-blue-500 text-white px-4 py-2 rounded" onclick="handleLater(${task.taskId})">Later</button>
            <button class="bg-green-500 text-white px-4 py-2 rounded ml-2" onclick="handleCompleted(${task.taskId})">Completed</button>
        </div>
    `;
}

async function handleCompleted(taskId) {
    try {
        await updateTask(taskId, { finished: true });
        loadHome();
    } catch (error) {
        console.error('Error completing task:', error);
    }
}

async function handleLater(taskId) {
    loadHome(); // Simply refreshes to next task
}