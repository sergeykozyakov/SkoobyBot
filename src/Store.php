<?php
namespace SkoobyBot;

class Store
{
    protected $db = null;
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // TODO: Реализовать работу с базой данных
    protected function select() {
        /*$stmt = $pdo->prepare('SELECT name FROM users WHERE email = ?');
        $stmt->execute(array($email));

        while ($row = $stmt->fetch()) {
            echo $row['name'] . "\n";
        }
        $name = $stmt->fetchColumn(); // одна колонка
        */
    }

    private function __clone() {}
    private function __construct() {
        // TODO: добавить получение и проверку реквизитов
        $dbname = '';
        $host = '';
        $username = '';
        $password = '';

        try {
            $db = new PDO('pgsql:dbname=' . $dbname . ';host=' . $host, $username, $password);
            $this->db = $db;
        } catch (PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }
}
