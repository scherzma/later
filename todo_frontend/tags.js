// tags.js
async function fetchTags() {
    return await apiFetch(`${API_URL}/tags`);
}

async function createTag(tagData) {
    return await apiFetch(`${API_URL}/tags`, {
        method: 'POST',
        body: JSON.stringify(tagData)
    });
}

async function updateTag(tagId, tagData) {
    return await apiFetch(`${API_URL}/tags/${tagId}`, {
        method: 'PUT',
        body: JSON.stringify(tagData)
    });
}

async function deleteTag(tagId) {
    return await apiFetch(`${API_URL}/tags/${tagId}`, {
        method: 'DELETE'
    });
}

function renderTagList(tags) {
    const container = document.getElementById('tag-list');
    container.innerHTML = tags.map(tag => `
        <div class="bg-white shadow-md rounded p-4 mb-2 flex justify-between">
            <span>${tag.name} - ${tag.priority}</span>
            <div>
                <button class="bg-yellow-500 text-white px-2 py-1 rounded" onclick="editTag(${tag.tagId})">Edit</button>
                <button class="bg-red-500 text-white px-2 py-1 rounded" onclick="deleteTag(${tag.tagId})">Delete</button>
            </div>
        </div>
    `).join('');
}

async function loadTags() {
    try {
        const tags = await fetchTags();
        renderTagList(tags);
    } catch (error) {
        console.error('Error loading tags:', error);
    }
}

async function editTag(tagId) {
    // Simple edit: prompt for new name and priority, enhance with a form if needed
    const name = prompt('Enter new tag name:');
    const priority = prompt('Enter priority (low, medium, high):');
    if (name && priority) {
        try {
            await updateTag(tagId, { name, priority });
            loadTags();
        } catch (error) {
            console.error('Error editing tag:', error);
        }
    }
}

async function deleteTag(tagId) {
    if (confirm('Are you sure you want to delete this tag?')) {
        try {
            await deleteTag(tagId);
            loadTags();
        } catch (error) {
            console.error('Error deleting tag:', error);
        }
    }
}

document.getElementById('add-tag-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const tagData = {
        name: document.getElementById('tag-name').value,
        priority: document.getElementById('tag-priority').value
    };
    try {
        await createTag(tagData);
        alert('Tag created successfully');
        loadTags();
    } catch (error) {
        console.error('Error creating tag:', error);
    }
});