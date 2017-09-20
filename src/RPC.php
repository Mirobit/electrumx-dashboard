<?php

namespace App;

class RPC {
    
    private $socket;
    
    function __construct(string $host, int $port) {
        // Create socket
        $this->socket = fsockopen($host, $port, $errno, $errstr, 5);
        // Check socket
        if ($this->socket === false) {
        throw new Exception('Connection to "' . $host . ':' . $port . '" failed (errno ' . $errno . '): ' . $errstr);
        } 
    }
    
    public function send(string $method, $param = ""){
        // sending request
        fwrite($this->socket, '{"jsonrpc": "2.0", "id": null, "method": "'.$method.'", "params": ['.$param.']}');
        fwrite($this->socket, "\n");
        fflush($this->socket);

        // read server respons
        $response = fgets($this->socket);

        if ($response === false) {
            throw new Exception('Connection to failed');
        }

        $result = json_decode($response, true);
        
        if(isset($result['error'])){
            throw new Exception($result['error']['message']);
        }
        
        $result = $result['result'];
        
        return $result;
    }
}