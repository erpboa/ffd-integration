<?php

 abstract class FFD_File_Cache_Abstract {

    abstract function fetch($key);
    abstract function store($key,$data,$ttl);
    abstract function delete($key);

}

class FFD_File_Cache_Filesystem extends FFD_File_Cache_Abstract {

  // This is the function you store information with
  function store($key,$data,$ttl) {

    // Opening the file in read/write mode
    $h = fopen($this->getFileName($key),'a+');
    if (!$h) throw new Exception('Could not write to cache');

    flock($h,LOCK_EX); // exclusive lock, will get released when the file is closed

    fseek($h,0); // go to the start of the file

    // truncate the file
    ftruncate($h,0);

    // Serializing along with the TTL
    $data = serialize(array(time()+$ttl,$data));
    if (fwrite($h,$data)===false) {
      throw new Exception('Could not write to cache');
    }
    fclose($h);

  }

  // The function to fetch data returns false on failure
  function fetch($key, $delete_expired=false) {

      $filename = $this->getFileName($key);
      if (!file_exists($filename)) return false;
      $h = fopen($filename,'r');

      if (!$h) return false;

      // Getting a shared lock 
      flock($h,LOCK_SH);

      $data = file_get_contents($filename);
      fclose($h);

      $data = @unserialize($data);
      if (!$data) {

         // If unserializing somehow didn't work out, we'll delete the file
         unlink($filename);
         return false;

      }

      if ($delete_expired && time() > $data[0]) {

         // Unlinking when the file was expired
         unlink($filename);
         return false;

      }
      
      return $data[1];
   }

   function delete( $key ) {

      $filename = $this->getFileName($key);
      if (file_exists($filename)) {
          return unlink($filename);
      } else {
          return false;
      }

   }

  private function getFileName($key) {

      return ini_get('session.save_path') . '/s_cache' . md5($key);

  }

}




 class FFD_File_Cache_APC extends FFD_File_Cache_Abstract {

        function fetch($key) {
            return apc_fetch($key);
        }

        function store($key,$data,$ttl) {

            return apc_store($key,$data,$ttl);

        }

        function delete($key) {

            return apc_delete($key);

        }

}



 class FFD_File_Cache_MemCache extends FFD_File_Cache_Abstract {

        // Memcache object
        public $connection;

        function __construct() {

            $this->connection = new MemCache;

        }

        function store($key, $data, $ttl) {

            return $this->connection->set($key,$data,0,$ttl);

        }

        function fetch($key) {

            return $this->connection->get($key);

        }

        function delete($key) {

            return $this->connection->delete($key);

        }

        function addServer($host,$port = 11211, $weight = 10) {

            $this->connection->addServer($host,$port,true,$weight);

        }

    }