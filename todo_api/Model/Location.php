<?php
require_once "./Model/todo_db.inc.php";
require_once "./Model/User.php";

class Location {
    private $db;
    private $locationId;
    private $name;
    private $createdBy;
    private $latitude;
    private $longitude;
    private $createdByUser = null;

    public function __construct($id = null) {
        $this->db = Todo_DB::gibInstanz();
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        $query = "SELECT * FROM Location WHERE LocationID = ?";
        $this->db->myQuery($query, [$id]);
        $data = $this->db->gibZeilen()[0];
        $this->loadFromData($data);
    }

    private function loadFromData($data) {
        $this->locationId = $data['LocationID'];
        $this->name = $data['Name'];
        $this->createdBy = $data['CreatedBy'];
        $this->latitude = $data['Latitude'];
        $this->longitude = $data['Longitude'];
    }

    public function save() {
        if ($this->locationId === null) {
            $query = "INSERT INTO Location (Name, CreatedBy, Latitude, Longitude) VALUES (?, ?, ?, ?)";
            $this->db->myQuery($query, [$this->name, $this->createdBy, $this->latitude, $this->longitude]);
            $this->locationId = $this->db->lastInsertID();
        } else {
            $query = "UPDATE Location SET Name = ?, CreatedBy = ?, Latitude = ?, Longitude = ? WHERE LocationID = ?";
            $this->db->myQuery($query, [$this->name, $this->createdBy, $this->latitude, $this->longitude, $this->locationId]);
        }
    }

    public function delete() {
        if ($this->locationId !== null) {
            $query = "DELETE FROM Location WHERE LocationID = ?";
            $this->db->myQuery($query, [$this->locationId]);
            $this->locationId = null;
        }
    }

    // Getters
    public function getLocationId() { return $this->locationId; }
    public function getName() { return $this->name; }
    public function getCreatedBy() { return $this->createdBy; }
    public function getLatitude() { return $this->latitude; }
    public function getLongitude() { return $this->longitude; }
    public function getCreatedByUser() {
        if ($this->createdByUser === null && $this->createdBy !== null) {
            $this->createdByUser = new User($this->createdBy);
        }
        return $this->createdByUser;
    }

    // Setters
    public function setName($name, $autoSave = true) {
        $this->name = $name;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setCreatedBy($createdBy, $autoSave = true) {
        $this->createdBy = $createdBy;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setLatitude($latitude, $autoSave = true) {
        $this->latitude = $latitude;
        if ($autoSave) {
            $this->save();
        }
    }
    public function setLongitude($longitude, $autoSave = true) {
        $this->longitude = $longitude;
        if ($autoSave) {
            $this->save();
        }
    }
}
?>