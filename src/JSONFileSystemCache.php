<?php

namespace Sarahman\JSONCache;

use Psr\SimpleCache\CacheInterface;

/**
 * Class JSONFileSystemCache
 *
 * JSON File System Cache using PSR simple cache interface implementation.
 *
 * @package    Sarahman\JSONCache
 * @author     Syed Abidur Rahman <aabid048@gmail.com>
 * @copyright  2019 Syed Abidur Rahman
 * @license    https://opensource.org/licenses/mit-license.php MIT
 * @version    1.0.0
 */
class JSONFileSystemCache implements CacheInterface
{
    /**
     * Cached json file with full path
     * @var string
     */
    private $cachedJsonFile;

    /**
     * Cached data of json file
     * @var array
     */
    private $cachedData;

    /**
     * Create a cache instance
     *
     * @param string $jsonFilename
     * @param string $cacheDirectory
     * @throws \Exception
     */
    public function __construct($jsonFilename = 'filesystem-cache.json', $cacheDirectory = null)
    {
        if (empty($cacheDirectory)) {
            $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . __CLASS__;
        } else {
            if (preg_match('#^\./#', $cacheDirectory)) {
                $cacheDirectory = preg_replace('#^\./#', '', $cacheDirectory);
                $cacheDirectory = getcwd() . DIRECTORY_SEPARATOR . ltrim($cacheDirectory, DIRECTORY_SEPARATOR);
            }

            if (!is_dir($cacheDirectory)) {
                $uMask = umask(0);
                @mkdir($cacheDirectory, 0755, true);
                umask($uMask);
            }

            if (!is_dir($cacheDirectory) || !is_readable($cacheDirectory)) {
                throw new \Exception('The root path ' . $cacheDirectory . ' is not readable.');
            }
        }

        $this->cachedJsonFile = rtrim($cacheDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($jsonFilename, DIRECTORY_SEPARATOR);

        $cacheData = @file_get_contents($this->cachedJsonFile);
        !empty($cacheData) || $cacheData = '';
        $this->cachedData = json_decode($cacheData, true);
        !empty($this->cachedData) || $this->cachedData = array();
    }

    /**
     * Stores an item
     *
     * @param string $key The key under which to store the value.
     * @param mixed $value The value to store.
     * @param integer $lifetime The expiration time, defaults to 3600
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $lifetime = 3600)
    {
        return $this->storeData($key, $value, $lifetime, true);
    }

    /**
     * Sets a new expiration on an item
     *
     * @param string $key The key under which to store the value.
     * @param integer $lifetime The expiration time, defaults to 3600
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function touch($key, $lifetime = 3600)
    {
        if ($data = $this->get($key)) {
            return $this->set($key, $data, $lifetime);
        }

        return false;
    }

    /**
     * Returns the item that was previously stored under the key
     *
     * @param string $key The key of the item to retrieve.
     * @param  mixed $default The default value (see @return)
     * @return mixed Returns the value stored in the cache or $default otherwise
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get($key, $default = null)
    {
        if (!$this->isKey($key)) return false;

        if (isset($this->cachedData[$key])) {
            $data = $this->cachedData[$key]['data'];
        } else {
            $data = $default;
        }

        return $data;
    }

    /**
     * Deletes an item
     *
     * @param string $key The key to be deleted.
     * @return bool Returns TRUE on success or FALSE on failure
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key)
    {
        return $this->removeData($key, true);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        $this->cachedData = [];
        return $this->saveDataIntoFile();
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        $values = array();
        foreach ($keys AS $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values AS $key => $value) {
            $this->storeData($key, $value, $ttl);
        }
        return $this->saveDataIntoFile();
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys AS $key) {
            $this->removeData($key);
        }
        return $this->saveDataIntoFile();
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key)
    {
        $value = $this->get($key);
        return !empty($value);
    }

    /**
     * Check if $key is valid key name
     *
     * @param string $key The key to validate
     * @return bool Returns TRUE if valid key or FALSE otherwise
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    private function isKey($key)
    {
        try {
            return !preg_match('/[^a-z_\-0-9]/i', $key);
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Stores an item into cache and also in the cache json file.
     *
     * @param string $key The key under which to store the value.
     * @param mixed $value The value to store.
     * @param integer $lifetime The expiration time, defaults to 3600
     * @param bool $saveIntoFile
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    private function storeData($key, $value, $lifetime = 3600, $saveIntoFile = false)
    {
        if (!$this->isKey($key)) return false;

        $this->cachedData[$key] = array('lifetime' => time() + $lifetime, 'data' => $value);
        return empty($saveIntoFile) ? true : $this->saveDataIntoFile();
    }

    /**
     * Removes an item from cache and also saves the cached data into the cache json file.
     *
     * @param string $key The key under which to store the value.
     * @param bool $saveIntoFile
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    private function removeData($key, $saveIntoFile = false)
    {
        if (!$this->isKey($key)) return false;

        unset($this->cachedData[$key]);
        return empty($saveIntoFile) ? true : $this->saveDataIntoFile();
    }

    /**
     * Saves all the cached data into the cache json file.
     *
     * @return bool Returns TRUE if valid key or FALSE otherwise
     */
    private function saveDataIntoFile()
    {
        $result = file_put_contents($this->cachedJsonFile, $this->cachedData);
        return !empty($result);
    }
}
