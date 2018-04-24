<?php

require_once 'basic_model.php';

class AccountModel extends BasicModel {

    function __construct($db)
    {
        parent::__construct($db);
        $this->tableName = 'accounts';
        $this->fields = array(
            'id',
            'salt',
            'provider',
            'username',
            'email',
            'password',
            'role',
            'token',
            'created_on',
            'last_login',
            'uuid',
        );
    }

    public function authenticate($email, $password) {
        $result = $this->db->{$this->tableName}->where([
            'email' => $email,
            'password' => $password
        ]);

        if ($account = $result->fetch()) {
            return $this->entity($account);
        } else {
            return false;
        }
    }

    public function login($user) {
        $user['last_login'] = date('Y-m-d H:i:s');
        return $this->update($user);
    }

    public function addLocalUser($username, $email, $password, $role) {
        $user = $this->entity();

        // TODO: check if there are any user who has same email and password
        $user['username'] = $username;
        $user['email'] = $email;
        $user['password'] = $password;
        $user['provider'] = 'local';
        $user['role'] = $role;
        $user['created_on'] = date('Y-m-d H:i:s');
        $user['uuid'] = uniqid();

        return $this->insert($user);
    }

    public function addLDAPUser($username, $password, $role) {
        $user = $this->entity();

        // TODO: check if there are any user who has same email and password

        $user['username'] = $username;
        $user['password'] = $password;
        $user['provider'] = 'LDAP';
        $user['role'] = $role;
        $user['created_on'] = date('Y-m-d H:i:s');
        $user['uuid'] = uniqid();

        return $this->insert($user);
    }
}
