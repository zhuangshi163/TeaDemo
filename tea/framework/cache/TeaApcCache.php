<?php
/**
 * TeaApcCache class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.Tea.com/
 * @copyright Copyright &copy; 2009 Leng Sheng Hong
 * @license http://www.Tea.com/license
 */


/**
 * TeaApcCache provides caching methods utilizing the APC extension.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @version $Id: TeaFrontCache.php 1000 2009-08-22 19:36:10
 * @package Tea.cache
 * @since 1.1
 */

class TeaApcCache{

    /**
     * Adds a cache with an unique Id.
     *
     * @param string $id Cache Id
     * @param mixed $data Data to be stored
     * @param int $expire Seconds to expired
     * @return bool True if success
     */
    public function set($id, $data, $expire=0){
        return apc_store($id, $data, $expire);
    }

    /**
     * Retrieves a value from cache with an Id.
     *
     * @param string|array $id A unique key identifying the cache or a list of keys.
     * @return mixed The value stored in cache. Return false if no cache found or already expired.
     */
    public function get($id){
        return apc_fetch($id);
    }

    /**
     * Deletes an APC data cache with an identifying Id
     *
     * @param string $id Id of the cache
     * @return bool True if success
     */
    public function flush($id){
        return apc_delete($id);
    }

    /**
     * Deletes all APC data cache
     * @return bool True if success
     */
    public function flushAll(){
        return apc_clear_cache('user');
    }

}
