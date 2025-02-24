<?php

require_once "./Model/todo_db.inc.php";

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

    // Add this to the User class
    public static function findByUsername($username) {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT * FROM User WHERE Username = ?";
        $db->myQuery($query, [$username]);
        $rows = $db->gibZeilen();

        if (count($rows) > 0) {
            $user = new User();
            $user->loadFromData($rows[0]);
            return $user;
        }

        return null;
    }

    public static function getAll()
    {
        $db = Todo_DB::gibInstanz();
        $query = "SELECT * FROM User";
        $db->myQuery($query, []);
        $rows = $db->gibZeilen();

        // Debug: Inspect $rows
        //var_dump($rows);

        $users = [];
        foreach ($rows as $row) {
            // Debug: Inspect each row
            //var_dump($row);
            $user = new User();
            $user->loadFromData($row);
            $users[] = $user;
        }
        // Debug: Inspect final $users
        //var_dump($users);
        return $users;
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
    public function setPassword($password, $autoSave = true) {
        $this->passwordHash = password_hash($password, PASSWORD_DEFAULT);
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
