<?php

namespace app\lune\util;
    
class SessionUtils {
    
    public static function setAttribute($key, $value) {
        $_SESSION[$key] = serialize($value);
    }

    public static function getAttribute($key) {
        if (isset($_SESSION[$key])) {
            return unserialize($_SESSION[$key]);
        } else {
            return null;        
        }
    }

    public static function hasAttribute($key) {
        return isset($_SESSION[$key]);
    }

    public static function removeAttribute($key) {
        if(SessionUtils::hasAttribute($key)) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroySession() {
        unset($_SESSION);
        session_destroy();    
    }

}
