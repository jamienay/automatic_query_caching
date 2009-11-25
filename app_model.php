<?php
class AppModel extends Model {
/**
   * Adds support for custom find methods (__findXX) and automatic caching.
   * Automatic caching will kick in when 'cache' is passed in the $options
   * array. 
   *
   * If 'cache' is a string, then it will be used to generate the
   * cache name, which takes the format model_alias_cache_name.
   * 
   * If 'cache' is an array, then two arguments are valid: 'name', required,
   * and 'config', optional. 'name' is used as above, while 'config' 
   * determines the cache configuration to use - 'default' if not specified.
   */
  function find($type, $options = array()) {
      $results = $this->_getCachedResults($options);
      if (!$results) {
          $method = null;
          if (is_string($type)) {
              $method = sprintf('__find%s', Inflector::camelize($type));
          }
          
          if ($method && method_exists($this, $method)) {
              $results = $this->{$method}($options);
          } else {
              $args = func_get_args();
              $results = call_user_func_array(array('parent', 'find'), $args);
          }
          if ($this->useCache) {
              Cache::write($this->cacheName, $results, $this->cacheConfig);
          }
      }
      
      return $results;
  }
  
  function _getCachedResults($options) {
      $this->useCache = true;
      if (Configure::read('debug') > 0 || !isset($options['cache']) || $options['cache'] == false) {
          $this->useCache = false;
          return false;
      }
      
      if (is_string($options['cache'])) {
          $this->cacheName = $this->alias . '_' . $options['cache'];
      } else {
          if (!isset($options['cache']['name'])) {
              return false;
          }
          $this->cacheName = $this->alias . '_' . $options['cache']['name'];
          $this->cacheConfig = isset($options['cache']['config']) ? $options['cache']['config'] : 'default';
      }

      $results = Cache::read($this->cacheName, $this->cacheConfig);

      return $results;
  }
}
?>