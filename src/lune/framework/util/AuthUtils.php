<?php

namespace app\lune\framework\util;

class AuthUtils {

    public static function login($user) {
        $authUser = new AuthUser($user);
        SessionUtils::setAttribute("user", $authUser);
        return $authUser;
    }

    public static function isLoggedIn() {
        return SessionUtils::hasAttribute("user");
    }

    public static function logout() {
        SessionUtils::removeAttribute("user");
    }
    
    public static function getAuthUser() {
        if ($authUser = SessionUtils::getAttribute("user")) {
            return $authUser;
        } else { 
            throw new \Exception("Current user is not set yet!");
        }
    }
    
    public static function setRoles(string ...$role) {
        $authUser = AuthUtils::getAuthUser();
        $roles = [];
        foreach ($role as $_role) {
            $roles[] = $_role;
        }
        $authUser->roles = $roles;
        SessionUtils::setAttribute("user", $authUser);
    }

    public static function getRoles() {
        return AuthUtils::getAuthUser()->roles;
    }
    
}

class AuthUser {

    public $user;
    public $roles;
    public $permissions;

    public function __construct($user) {
        $this->user = $user;
    }
}
