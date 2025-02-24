// tasks.js
async function fetchTasks() {
    return await apiFetch(`${API_URL}/tasks`);
}

async function fetchTask(taskId) {
    return await apiFetch(`${API_URL}/tasks/${taskId}`);
}

async function createTask(taskData) {
    return await apiFetch(`${API_URL}/tasks`, {
        method: 'POST',
        body: JSON.stringify(taskData)
    });
}

async function updateTask(taskId, taskData) {
    return await apiFetch(`${API_URL}/tasks/${taskId}`, {
        method: 'PUT',
        body: JSON.stringify(taskData)
    });
}

async function deleteTask(taskId) {
    return await apiFetch(`${API_URL}/tasks/${taskId}`, {
        method: 'DELETE'
    });
}

async function assignTagToTask(taskId, tagId) {
    return await apiFetch(`${API_URL}/tasks/${taskId}/tags`, {
        method: 'POST',
        body: JSON.stringify({ tagId: parseInt(tagId) })
    });
}

async function removeTagFromTask(taskId, tagId) {
    return await apiFetch(`${API_URL}/tasks/${taskId}/tags/${tagId}`, {
        method: 'DELETE'
    });
}

// UI Functions
function renderTaskList(tasks) {
    const container = document.getElementById('task-list-container');
    container.innerHTML = tasks.map(task => `
        <div class="bg-white shadow-md rounded p-4 mb-4">
            <h3 class="text-lg font-bold">${task.title}</h3>
            <p>${task.description || 'No description'}</p>
            <p>End Date: ${task.endDate || 'None'}</p>
            <p>Priority: ${task.priority}</p>
            <p>Status: ${task.finished ? 'Completed' : 'Pending'}</p>
            <button class="bg-blue-500 text-white px-4 py-2 rounded" onclick="viewTask(${task.taskId})">View/Edit</button>
        </div>
    `).join('');
}

async function loadTaskList() {
    try {
        const tasks = await fetchTasks();
        renderTaskList(tasks);
    } catch (error) {
        console.error('Error loading tasks:', error);
    }
}

async function viewTask(taskId) {
    appState.currentTaskId = taskId;
    showSection('task-detail');
    loadTaskDetail(taskId);
}

async function loadTaskDetail(taskId) {
    try {
        const task = await fetchTask(taskId);
        renderTaskDetail(task);
    } catch (error) {
        console.error('Error loading task detail:', error);
    }
}

function renderTaskDetail(task) {
    const container = document.getElementById('task-detail-container');
    const tagsHtml = task.tags ? task.tags.map(tag => `
        <span class="bg-gray-200 px-2 py-1 rounded">${tag.name} <button onclick="removeTagFromTask(${task.taskId}, ${tag.tagId})">x</button></span>
    `).join('') : 'No tags';
    container.innerHTML = `
        <h2 class="text-xl font-bold mb-4">Task: ${task.title}</h2>
        <form id="task-edit-form">
            <input type="text" id="task-edit-title" value="${task.title}" required class="w-full p-2 border rounded mb-2">
            <textarea id="task-edit-description" class="w-full p-2 border rounded mb-2">${task.description || ''}</textarea>
            <input type="datetime-local" id="task-edit-endDate" value="${task.endDate ? task.endDate.slice(0,16) : ''}" class="w-full p-2 border rounded mb-2">
            <select id="task-edit-priority" class="w-full p-2 border rounded mb-2">
                <option value="low" ${task.priority === 'low' ? 'selected' : ''}>Low</option>
                <option value="medium" ${task.priority === 'medium' ? 'selected' : ''}>Medium</option>
                <option value="high" ${task.priority === 'high' ? 'selected' : ''}>High</option>
            </select>
            <label><input type="checkbox" id="task-edit-finished" ${task.finished ? 'checked' : ''}> Finished</label>
            <button type="submit" class="bg-blue-500 text-white p-2 rounded mt-2">Update Task</button>
        </form>
        <div class="mt-4">
            <h3>Tags:</h3>
            ${tagsHtml}
            <div>
                <select id="tag-select" class="p-2 border rounded"></select>
                <button onclick="assignTagToTask(${task.taskId})" class="bg-green-500 text-white p-2 rounded">Add Tag</button>
            </div>
        </div>
    `;
    populateTagSelect(task.tags);
    document.getElementById('task-edit-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const updatedTask = {
            title: document.getElementById('task-edit-title').value,
            description: document.getElementById('task-edit-description').value,
            endDate: document.getElementById('task-edit-endDate').value || null,
            priority: document.getElementById('task-edit-priority').value,
            finished: document.getElementById('task-edit-finished').checked
        };
        try {
            await updateTask(task.taskId, updatedTask);
            alert('Task updated successfully');
            loadTaskDetail(task.taskId);
        } catch (error) {
            console.error('Error updating task:', error);
        }
    });
}

async function populateTagSelect(currentTags) {
    try {
        const allTags = await fetchTags();
        const availableTags = allTags.filter(tag => !currentTags.some(ct => ct.tagId === tag.tagId));
        const select = document.getElementById('tag-select');
        select.innerHTML = availableTags.map(tag => `<option value="${tag.tagId}">${tag.name}</option>`).join('');
    } catch (error) {
        console.error('Error loading tags for select:', error);
    }
}

// Task creation form listener (assuming #task-create-form exists in HTML)
document.getElementById('task-create-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const taskData = {
        title: document.getElementById('task-create-title').value,
        description: document.getElementById('task-create-description').value,
        endDate: document.getElementById('task-create-endDate').value || null,
        priority: document.getElementById('task-create-priority').value
    };
    try {
        await createTask(taskData);
        alert('Task created successfully');
        showSection('task-list');
        loadTaskList();
    } catch (error) {
        console.error('Error creating task:', error);
    }
});