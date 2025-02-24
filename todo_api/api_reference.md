# Task Management API Reference

This document provides a complete reference for all endpoints in the task management system.

## Authentication

All endpoints (except for registration and login) require authentication using a JWT token.

```
Authorization: Bearer [token]
```

## User Management

### Register a New User

Register a new user account.

```
POST /users/register
```

**Request Body:**
```json
{
  "username": "string",
  "password": "string",
  "email": "string (optional)"
}
```

**Response:**
```json
{
  "message": "User registered successfully",
  "userId": 123,
  "username": "username"
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid input, username already exists, or email already exists

### User Login

Authenticate a user and get a JWT token.

```
POST /users/login
```

**Request Body:**
```json
{
  "username": "string",
  "password": "string"
}
```

**Response:**
```json
{
  "token": "jwt-token-string"
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Missing username or password
- `401 Unauthorized` - Invalid credentials

### Get User List (Admin only)

Get a list of all users (requires admin role).

```
GET /users
```

**Response:**
```json
[
  {
    "userId": 123,
    "username": "username",
    "role": "user"
  }
]
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Not an admin

### Get User Profile

Get the authenticated user's profile.

```
GET /users/me
```

**Response:**
```json
{
  "userId": 123,
  "username": "username",
  "email": "email@example.com",
  "role": "user",
  "streakInfo": {
    "currentStreak": 5,
    "bestStreak": 10,
    "lastCompletedDate": "2025-02-24",
    "needsTaskToday": true
  },
  "preferences": {
    "emailNotifications": true
  }
}
```

**Status Codes:**
- `200 OK` - Success
- `404 Not Found` - User not found

### Update User Profile

Update the authenticated user's profile.

```
PUT /users/me
```

**Request Body:**
```json
{
  "username": "string (optional)",
  "password": "string (optional)",
  "email": "string (optional)",
  "emailNotifications": true|false (optional)
}
```

**Response:**
```json
{
  "userId": 123,
  "username": "username",
  "email": "email@example.com",
  "role": "user",
  "streakInfo": {
    "currentStreak": 5,
    "bestStreak": 10,
    "lastCompletedDate": "2025-02-24",
    "needsTaskToday": true
  },
  "preferences": {
    "emailNotifications": true
  },
  "message": "User updated successfully"
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid input, username already exists, or email already exists
- `404 Not Found` - User not found

### Delete User Account

Delete the authenticated user's account.

```
DELETE /users/me
```

**Response:**
```json
{
  "message": "User deleted successfully"
}
```

**Status Codes:**
- `200 OK` - Success
- `404 Not Found` - User not found

### Get User Streak Information

Get detailed information about the user's task completion streak.

```
GET /users/me/streak
```

**Response:**
```json
{
  "streakInfo": {
    "currentStreak": 5,
    "bestStreak": 10,
    "lastCompletedDate": "2025-02-24",
    "needsTaskToday": true,
    "tasksFinishedToday": 2,
    "pendingTasks": 8
  }
}
```

**Status Codes:**
- `200 OK` - Success
- `404 Not Found` - User not found

## Task Management

### List All Tasks

Get all tasks for the authenticated user.

```
GET /tasks
```

**Response:**
```json
[
  {
    "taskId": 1,
    "title": "Task Title",
    "description": "Task Description",
    "endDate": "2025-03-01 12:00:00",
    "priority": "high",
    "location": "Home",
    "locationId": 2,
    "finished": false
  }
]
```

**Status Codes:**
- `200 OK` - Success

### Create a New Task

Create a new task for the authenticated user.

```
POST /tasks
```

**Request Body:**
```json
{
  "title": "string",
  "description": "string (optional)",
  "endDate": "YYYY-MM-DD HH:MM:SS (optional)",
  "priority": "low|medium|high (optional, default: medium)",
  "location": "string (optional)",
  "locationId": integer (optional)
}
```

**Response:**
```json
{
  "received": {
    // Echo of request data
  }
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid input or title already exists

### Get Task Details

Get details of a specific task.

```
GET /tasks/{taskId}
```

**Response:**
```json
{
  "taskId": 1,
  "title": "Task Title",
  "description": "Task Description",
  "endDate": "2025-03-01 12:00:00",
  "priority": "high",
  "location": "Home",
  "locationId": 2,
  "finished": false
}
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found

### Update a Task

Update a specific task.

```
PUT /tasks/{taskId}
```

**Request Body:**
```json
{
  "title": "string (optional)",
  "description": "string (optional)",
  "endDate": "YYYY-MM-DD HH:MM:SS (optional)",
  "priority": "low|medium|high (optional)",
  "location": "string (optional)",
  "locationId": integer (optional),
  "finished": true|false (optional)
}
```

**Response:**
```json
{
  "taskId": 1,
  "title": "Updated Title",
  "description": "Updated Description",
  "endDate": "2025-03-01 12:00:00",
  "priority": "high",
  "location": "Home",
  "locationId": 2,
  "finished": false,
  "message": "Task updated successfully"
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid input or title already exists
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found

### Delete a Task

Delete a specific task.

```
DELETE /tasks/{taskId}
```

**Response:**
```json
{
  "message": "Task deleted successfully",
  "task": {
    "taskId": 1,
    "title": "Task Title",
    "finished": false
  }
}
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found

### Complete a Task

Mark a task as complete and update the user's streak.

```
POST /tasks/{taskId}/complete
```

**Response:**
```json
{
  "message": "Task completed successfully",
  "taskId": 1,
  "title": "Task Title",
  "streakInfo": {
    "currentStreak": 5,
    "bestStreak": 10,
    "lastCompletedDate": "2025-02-24"
  }
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Task already completed
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found

### Postpone a Task

Add a task to the queue for later completion.

```
POST /tasks/{taskId}/postpone
```

**Response:**
```json
{
  "message": "Task postponed successfully",
  "taskId": 1,
  "title": "Task Title",
  "queuePosition": 3,
  "nextTask": {
    "taskId": 2,
    "title": "Next Recommended Task"
  }
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Task already completed or already in queue
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found

### Get Next Recommended Task

Get the next task the user should work on.

```
GET /tasks/next
```

**Response (when a task is available):**
```json
{
  "hasTask": true,
  "fromQueue": true,
  "task": {
    "taskId": 1,
    "title": "Task Title",
    "description": "Task Description",
    "endDate": "2025-03-01 12:00:00",
    "priority": "high",
    "location": "Home",
    "locationId": 2
  }
}
```

**Response (when no tasks are available):**
```json
{
  "message": "No tasks available",
  "hasTask": false
}
```

**Status Codes:**
- `200 OK` - Success

### List Queue Tasks

Get all tasks in the user's queue.

```
GET /tasks/queue
```

**Response:**
```json
[
  {
    "taskId": 1,
    "title": "Task Title",
    "description": "Task Description",
    "endDate": "2025-03-01 12:00:00",
    "priority": "high",
    "location": "Home",
    "locationId": 2,
    "finished": false
  }
]
```

**Status Codes:**
- `200 OK` - Success

### Add Task to Queue

Add a task to the user's queue.

```
POST /tasks/queue
```

**Request Body:**
```json
{
  "taskId": 1
}
```

**Response:**
```json
{
  "message": "Task added to queue successfully",
  "taskId": 1,
  "title": "Task Title",
  "queuePosition": 3
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Task already in queue, already completed, or invalid task
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found

### Get Task Queue Info

Get information about a task in the queue.

```
GET /tasks/{taskId}/queue
```

**Response:**
```json
{
  "taskId": 1,
  "title": "Task Title",
  "queuePosition": 3,
  "postponedDate": "2025-02-24 10:30:00"
}
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found or not in queue

### Update Task Queue Position

Change a task's position in the queue.

```
PUT /tasks/{taskId}/queue
```

**Request Body:**
```json
{
  "position": 1
}
```

**Response:**
```json
{
  "message": "Task position updated successfully",
  "taskId": 1,
  "title": "Task Title",
  "newPosition": 1
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid position
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found or not in queue

### Remove Task from Queue

Remove a task from the queue.

```
DELETE /tasks/{taskId}/queue
```

**Response:**
```json
{
  "message": "Task removed from queue successfully",
  "taskId": 1,
  "title": "Task Title"
}
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found or not in queue

## Tags and Tasks

### List All Tags

Get all tags for the authenticated user.

```
GET /tags
```

**Response:**
```json
[
  {
    "tagId": 1,
    "name": "Work",
    "priority": "high",
    "userId": 123
  }
]
```

**Status Codes:**
- `200 OK` - Success

### Create a Tag

Create a new tag for the authenticated user.

```
POST /tags
```

**Request Body:**
```json
{
  "name": "string",
  "priority": "low|medium|high (optional, default: medium)"
}
```

**Response:**
```json
{
  "tagId": 1,
  "name": "Work",
  "priority": "high",
  "userId": 123,
  "message": "Tag created successfully"
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid input or tag name already exists

### Get Tag Details

Get details of a specific tag.

```
GET /tags/{tagId}
```

**Response:**
```json
{
  "tagId": 1,
  "name": "Work",
  "priority": "high",
  "userId": 123
}
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Tag doesn't belong to user
- `404 Not Found` - Tag not found

### Update a Tag

Update a specific tag.

```
PUT /tags/{tagId}
```

**Request Body:**
```json
{
  "name": "string (optional)",
  "priority": "low|medium|high (optional)"
}
```

**Response:**
```json
{
  "tagId": 1,
  "name": "Work Updated",
  "priority": "medium",
  "userId": 123,
  "message": "Tag updated successfully"
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid input or tag name already exists
- `403 Forbidden` - Tag doesn't belong to user
- `404 Not Found` - Tag not found

### Delete a Tag

Delete a specific tag.

```
DELETE /tags/{tagId}
```

**Response:**
```json
{
  "message": "Tag deleted successfully",
  "tag": {
    "tagId": 1,
    "name": "Work"
  }
}
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Tag doesn't belong to user
- `404 Not Found` - Tag not found

### List Tasks with Tag

Get all tasks that have a specific tag.

```
GET /tags/{tagId}/tasks
```

**Response:**
```json
[
  {
    "taskId": 1,
    "title": "Task Title",
    "description": "Task Description",
    "endDate": "2025-03-01 12:00:00",
    "priority": "high",
    "location": "Home",
    "locationId": 2,
    "finished": false
  }
]
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Tag doesn't belong to user
- `404 Not Found` - Tag not found

### List Tags for Task

Get all tags assigned to a specific task.

```
GET /tasks/{taskId}/tags
```

**Response:**
```json
[
  {
    "tagId": 1,
    "name": "Work",
    "priority": "high",
    "userId": 123
  }
]
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found

### Assign Tag to Task

Assign a tag to a specific task.

```
POST /tasks/{taskId}/tags
```

**Request Body:**
```json
{
  "tagId": 1
}
```

**Response:**
```json
{
  "message": "Tag assigned to task successfully",
  "taskId": 1,
  "tagId": 1
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Tag already assigned or invalid tag
- `403 Forbidden` - Task or tag doesn't belong to user
- `404 Not Found` - Task or tag not found

### Remove Tag from Task

Remove a tag from a specific task.

```
DELETE /tasks/{taskId}/tags/{tagId}
```

**Response:**
```json
{
  "message": "Tag removed from task successfully",
  "taskId": 1,
  "tagId": 1
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Tag not assigned to task
- `403 Forbidden` - Task or tag doesn't belong to user
- `404 Not Found` - Task or tag not found

## Locations

### List All Locations

Get all locations for the authenticated user.

```
GET /locations
```

**Response:**
```json
[
  {
    "locationId": 1,
    "name": "Home",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "createdBy": 123
  }
]
```

**Status Codes:**
- `200 OK` - Success

## Task Reminders

### List Task Reminders

Get all reminders for a specific task.

```
GET /tasks/{taskId}/reminders
```

**Response:**
```json
[
  {
    "reminderId": 1,
    "taskId": 1,
    "reminderTime": "2025-02-24 08:00:00",
    "isSent": false
  }
]
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found

### Create Task Reminder

Create a new reminder for a specific task.

```
POST /tasks/{taskId}/reminders
```

**Request Body:**
```json
{
  "reminderTime": "YYYY-MM-DD HH:MM:SS"
}
```

**Response:**
```json
{
  "reminderId": 1,
  "taskId": 1,
  "reminderTime": "2025-02-24 08:00:00",
  "isSent": false,
  "message": "Reminder created successfully"
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid input
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task not found

### Update Task Reminder

Update a specific reminder.

```
PUT /tasks/{taskId}/reminders/{reminderId}
```

**Request Body:**
```json
{
  "reminderTime": "YYYY-MM-DD HH:MM:SS"
}
```

**Response:**
```json
{
  "reminderId": 1,
  "taskId": 1,
  "reminderTime": "2025-02-24 10:00:00",
  "isSent": false,
  "message": "Reminder updated successfully"
}
```

**Status Codes:**
- `200 OK` - Success
- `400 Bad Request` - Invalid input
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task or reminder not found

### Delete Task Reminder

Delete a specific reminder.

```
DELETE /tasks/{taskId}/reminders/{reminderId}
```

**Response:**
```json
{
  "message": "Reminder deleted successfully"
}
```

**Status Codes:**
- `200 OK` - Success
- `403 Forbidden` - Task doesn't belong to user
- `404 Not Found` - Task or reminder not found