<?php

namespace App;

class RPC {
    
    private $socket;
    
    function __construct(string $host, int $port) {
        // Create socket
        $this->socket = fsockopen($host, $port, $errno, $errstr, 5);
        // Check socket
        if (!$this->socket) {
            throw new \Exception('Connection to "' . $host . ':' . $port . '" failed (errno ' . $errno . '): ' . $errstr);
        } 
    }
    
    public function send(string $method, $param = ""){
        // Send request
        fwrite($this->socket, '{"jsonrpc": "2.0", "id": 1, "method": "'.$method.'", "params": ['.$param.']}');
        fwrite($this->socket, "\n");
        fflush($this->socket);

        // Read server respons
        $response = fgets($this->socket);
        if ($response === false) {
            throw new \Exception('Connection to failed');
        }
        $result = json_decode($response, true);
        if(isset($result['error'])){
            throw new \Exception($result['error']['message']);
        }
        
        return $result['result'];
    }
}