<?php

function zwt_cache_get($key) {
    $zwt_cache = get_option('_zwt_cache');
    if (isset($zwt_cache[$key])) {
        return $zwt_cache[$key];
    } else {
        return false;
    }
}

function zwt_cache_set($key, $value=null) {

    global $switched;
    if (!empty($switched))
        return;

    $zwt_cache = get_option('_zwt_cache');
    if (false === $zwt_cache) {
        delete_option('_zwt_cache');
    }
    if (!is_null($value)) {
        $zwt_cache[$key] = $value;
    } else {
        if (isset($zwt_cache[$key])) {
            unset($zwt_cache[$key]);
        }
    }
    update_option('_zwt_cache', $zwt_cache);
}

function zwt_cache_clear($key = false){
    if($key === false){
        delete_option('_zwt_cache');    
    }else{
        $zwt_cache = get_option('_zwt_cache');
        if(isset($zwt_cache[$key])){
            unset($zwt_cache[$key]);
        }     
        
        // special cache of 'per language' - clear different statuses
        if(false !== strpos($key, '_per_language')){
            foreach($zwt_cache as $k => $v){
                if(false !== strpos($k, $key . '#')){
                    unset($zwt_cache[$k]);
                }    
            }        
        }
           
        update_option('_zwt_cache', $zwt_cache);
    }
}

define('zwt_DISABLE_CACHE', false);

class zwt_cache {

    private $data;

    function __construct($name = "", $cache_to_option = false) {
        $this->data = array();
        $this->name = $name;
        $this->cache_to_option = $cache_to_option;

        if ($cache_to_option) {
            $this->data = zwt_cache_get($name . '_cache_class');
            if ($this->data == false) {
                $this->data = array();
            }
        }
    }

    function get($key) {
        if (zwt_DISABLE_CACHE) {
            return null;
        }
        return isset($this->data[$key]) ? $this->data[$key] : false;
    }

    function has_key($key) {
        if (zwt_DISABLE_CACHE) {
            return false;
        }
        return array_key_exists($key, (array) $this->data);
    }

    function set($key, $value) {
        if (zwt_DISABLE_CACHE) {
            return;
        }
        $this->data[$key] = $value;
        if ($this->cache_to_option) {
            zwt_cache_set($this->name . '_cache_class', $this->data);
        }
    }

    function clear() {
        $this->data = array();
        if ($this->cache_to_option) {
            zwt_cache_clear($this->name . '_cache_class');
        }
    }

}

?>
