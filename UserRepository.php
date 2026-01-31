<?php

require_once __DIR__ . '/config.php';

class UserRepository {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function createUser($username, $email, $password) {
        
    //dont store raw passwords (lej n url, this is bob joke se e ka lon passwordin n url))
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);  //what he said
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, is_admin) VALUES (?, ?, ?, 0)");  //this prints the stuff here
        return $stmt->execute([$username, $email, $hashedPassword]);
    }
    
    public function getUserByEmailOrUsername($emailOrUsername){

    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$emailOrUsername, $emailOrUsername]);

    return $stmt->fetch(PDO::FETCH_ASSOC);


    }

    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //this should be done

}
