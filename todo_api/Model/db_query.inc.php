<?php

abstract class DB_Query
{
    protected $db_server;
    protected $db_name;
    protected $db_user;
    protected $db_passwort;
    private $db_verbindung;
    private $db_stmt;
    private $ergebnis;

    function __construct()
    {
        $this->db_verbindung = new mysqli($this->db_server, $this->db_user, $this->db_passwort, $this->db_name);
    }

    function __destruct()
    {
        $this->db_verbindung->close();
    }

    public function myQuery($p_query, $p_werte)
    {
        $this->db_stmt = $this->db_verbindung->prepare($p_query);
        $this->db_stmt->execute($p_werte);
        $this->ergebnis = $this->db_stmt->get_result();
    }

    public function anzahlZeilen()
    {
        return $this->ergebnis->num_rows;
    }

    public function anzahlBetrZeilen()
    {
        return $this->db_verbindung->affected_rows;
    }

    public function lastInsertID()
    {
        return $this->db_verbindung->insert_id;
    }
    
    // Added rowCount method to match method called in User.php
    public function rowCount()
    {
        return $this->db_verbindung->affected_rows;
    }

    //Lesen der Zeilen aus der Ergebnismenge, die Zeilen werden
    //als Array zurückgegeben
    public function gibZeilen()
    {
        $zeilen = array();
        while ($zeile = $this->ergebnis->fetch_assoc())
            array_push($zeilen, $zeile);
        return $zeilen;
    }

    //Lesen des Objektes aus der Ergebnismenge entsprechend der
    //gewünschten Klasse ($p_entitaet)
    public function gibObjekt($p_entitaet)
    {
        return $this->ergebnis->fetch_object("$p_entitaet");
    }

    //Lesen der Objekte aus der Ergebnismenge entsprechend der
    //gewünschten Klasse ($p_entitaet), die Objekte werden
    //als Array zurückgegeben
    public function gibObjekte($p_entitaet)
    {
        $objekte = array();
        while ($objekt = $this->ergebnis->fetch_object("$p_entitaet"))
            array_push($objekte, $objekt);
        return $objekte;
    }
}