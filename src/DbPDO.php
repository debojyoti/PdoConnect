<?php

namespace Debojyoti\PdoConnect;

class DbPDO {

    protected $database;

    private function config ($value = null)
    {
        if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/config.php')) {
            return false;
        } else {
            require_once $_SERVER['DOCUMENT_ROOT'].'/config.php';
        }
        if (!isset($value)) {
            $config = json_decode(CONFIG, true);
        } else {
            $config = $this->config();
            $values = explode('.', $value);
            for ($i=0; $i < count($values); $i++) { 
                $config = $config[$values[$i]];
            }
        }
        return $config;
    }

    public function setPDO($db_info) 
    {
        

        $username = $this->config('mysql.username');
        $password = $this->config('mysql.password');
        $hostname = $this->config('mysql.hostname');
        $database = $this->config('mysql.database');

        if ($db_info) {
            foreach ($db_info as $key => $value) {
                switch ($key) {
                    case 'username':
                        $username = $value;
                        break;
                    case 'password':
                        $password = $value;
                        break;
                    case 'hostname':
                        $hostname = $value;
                        break;
                    case 'database':
                        $database = $value;
                }
            }
        }
        

        $this->database = $database;

        $pdo = new \PDO("mysql:host=" . $hostname . ";dbname=" . $database, $username, $password, [\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_EMULATE_PREPARES => false, \PDO::ATTR_PERSISTENT => true]);
        $pdo->exec('set names UTF8');
        return $pdo;

    }
    
}

