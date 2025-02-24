<?php

require_once "./todo_db.inc.php";

class User
{
    private $db;
    private $userId;
    private $username;
    private $passwordHash;
    private $role;

    public function __construct($id = null)
    {
        $this->db = Todo_DB::gibInstanz();
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id)
    {
        $query = "SELECT * FROM User WHERE UserID = ?";
        $this->db->myQuery($query, [$id]);
        $data = $this->db->gibZeilen()[0];
        $this->loadFromData($data);
    }

    private function loadFromData($data)
    {
        $this->userId = $data['UserID'];
        $this->username = $data['Username'];
        $this->passwordHash = $data['PasswordHash'];
        $this->role = $data['Role'];
    }

    public function save()
    {
        if ($this->userId === null) {
            $query = "INSERT INTO User (Username, PasswordHash, Role) VALUES (?, ?, ?)";
            $this->db->myQuery($query, [$this->username, $this->passwordHash, $this->role]);
            $this->userId = $this->db->lastInsertID();
        } else {
            $query = "UPDATE User SET Username = ?, PasswordHash = ?, Role = ? WHERE UserID = ?";
            $this->db->myQuery($query, [$this->username, $this->passwordHash, $this->role, $this->userId]);
        }
    }

    public function delete()
    {
        if ($this->userId !== null) {
            $query = "DELETE FROM User WHERE UserID = ?";
            $this->db->myQuery($query, [$this->userId]);
            $this->userId = null;
        }
    }

    public function getTasks() {
        return Task::getTasksByUserId($this->getUserId());
    }

    // Getters
    public function getUserId()
    {
        return $this->userId;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPasswordHash()
    {
        return $this->passwordHash;
    }

    public function getRole()
    {
        return $this->role;
    }

    // Setters
    public function setUsername($username, $autoSave = true) {
        $this->username = $username;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setPasswordHash($passwordHash, $autoSave = true) {
        $this->passwordHash = $passwordHash;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setRole($role, $autoSave = true) {
        if (in_array($role, ['admin', 'user'])) {
            $this->role = $role;
            if ($autoSave) {
                $this->save();
            }
        } else {
            throw new InvalidArgumentException("Role must be 'admin' or 'user'");
        }
    }
}
