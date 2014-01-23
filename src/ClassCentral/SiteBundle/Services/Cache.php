<?php

namespace ClassCentral\SiteBundle\Services;

class Cache {
    
    private $doctrineCache;
    
    private $prefix;
    
    public function setCache( \Doctrine\Common\Cache\Cache $doctrineCache)
    {
        $this->doctrineCache = $doctrineCache;
    }
    
    public function setCacheKeyPrefix($prefix){
        $this->prefix = $prefix;
    }


    public function get($cacheKey, $callback, $params = array())
    {
        $cache = $this->doctrineCache;
        
        // Append the key with the host
        $key = $this->prefix . '_' . $cacheKey;
        
        if($cache->contains($key))
        {
            return unserialize($cache->fetch($key));
        } 
        else 
        {
            $this->registerKey($key);
            $data = call_user_func_array($callback,$params);
            $cache->save($key, serialize($data), 3600);
            return $data;
        }
    }
    
    /**
     * The key stores all keys used for caching.
     * This is useful to clear all caches
     * @param type $key
     */
    private function registerKey($key)
    {
        $cache = $this->doctrineCache;
        $keys = $this->getKeys();
        
        if($cache->contains($keys))
        {
            $keysArray = unserialize($cache->fetch($keys));
            if( !in_array($key, $keysArray) )
            {
                $keysArray[] = $key;
            }
            
        }
        else 
        {
            $keysArray[] = $key;
        }
        
        // Save back the keys array
        $cache->save($keys, serialize($keysArray));
        
    }
    
    public function clear()
    {
        $cache = $this->doctrineCache;
        $keys = $this->getKeys();        
        if($cache->contains($keys))
        {
            $keysArray = unserialize($cache->fetch($keys));            
            foreach($keysArray as $key)
            {
                $cache->delete($key);
            }
            
        } else {
            //echo "does not contain keys";
        }
    } 
    
    private function getKeys()
    {
        return $this->prefix . '_' . 'keys';
    }
    
}

?>
