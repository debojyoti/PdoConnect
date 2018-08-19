<?php

namespace Debojyoti\PdoConnect;

/**
Author: Debojyoti Saha
Email: dsahapersonal@gmail.com
Version: 1.2
Update Date: 24-03-2018
Description: Handle complex database queries easily through single line code.
**/


class Handler extends DbPDO
{
       private $pdo;

    #   Optional parameter ($db_info) can be passed to switch database and user accounts
    #   $db_info = ['key'=>'value'], expecting "username", "password", "hostname", "database"
    public function __construct(array $db_info = null)
    {
        $this->pdo = $this->setPDO($db_info);
        return $this->pdo;
    }

    #   To run prepared statements with/without parameters
    private function runQuery($query, $obj = null)
    {
        // file_put_contents('sql.log', $query."\n", FILE_APPEND);
        $stmt = $this->pdo->prepare($query);
        if (is_array($obj)) {
            $stmt->execute($obj);
        }
        else {
            $stmt->execute();
        }
        $e = new \Exception();
        $trace = $e->getTrace();
        $last_call = $trace[1];
        $called_from = $last_call['function'];
        if ($called_from=='updateData' || $called_from=='insertData') {
            return $stmt->rowCount();
        }
        return $stmt->fetchAll();
    }

    #   TO prepare the data
    private function prepareData($data, $type, &$str)
    {
        //  Get caller function name
        $e = new \Exception();
        $trace = $e->getTrace();
        $last_call = $trace[1];
        $called_from = $last_call['function'];

        $type2_methods = ["getData", "updateData", "getCount"];

        if ($called_from == "getData" && $type==1) {

            //  Fetch the column names to be searched
            $i = 0;
            if (isset($data)) {
                foreach ($data as $key => $value) {
                    $colSet[$i++] = $value;
                }
            }
            
            if ($i == 0) {
                $str .= "*";
            } else {
                $colSet = implode(",",$colSet);
                $str .= $colSet;
            }
        }
        else if ((in_array($called_from, $type2_methods) && $type == 2) || ($called_from == "deleteData" && $type == 1)) {  //  Preparing where clause
            if (isset($data)) {
                // walking by each key
                foreach ($data as $column => $value) {
                    // initialise OR list
                    $OR_list = array();
                    // check if array is multidimensional
                    if (is_array($value)) {
                        // walking by each value of sub array
                        foreach ($value as $param) {
                            $param = str_replace("'", "\'", $param);
                            // check if first character is an exclamation
                            if (substr($param, 0,1) == '!') {
                                // check if the value has only one character
                                if (strlen($param) <= 1) {
                                    // if the word contains only the exclamation
                                    $OR_list[] = $column.' IS NOT NULL';
                                } else {
                                    // if the word contains more than exclamation
                                    $OR_list[] = $column." <> ? ";
                                    $obj[]=substr($param, 1);
                                }
                            } else if (($param == '') || ($param == NULL)) {
                                // if there are no characters
                                $OR_list[] = $column.' IS NULL OR '.$column.' = ?';
                                $obj[] = '';
                            } else if (substr($param, 0,1) == '~') {
                                // if there is a tilde
                                $OR_list[] = $column." LIKE ?";
                                $obj[] = "%".substr($param, 1)."%";
                            } else if (substr($param, 0, 1) == '<') {
                                // if there is a greater than sign
                                $OR_list[] = is_numeric(substr($param, 1)) ? $column." < ? " : $column." = ? ";
                                $obj[] = substr($param, 1);
                            } else if (substr($param, 0,1) == '>') {
                                // if there is a lesser than sign
                                $OR_list[] = is_numeric(substr($param, 1)) ? $column." > ? " : $column." = ? ";
                                $obj[] = substr($param, 1);
                            } else {
                                // if it has got a normal word create OR list
                                $OR_list[] = $column." = ? ";
                                $obj[] = $param;
                            }
                        }
                        // create OR string
                        $OR_list = implode(' OR ', $OR_list);
                    } else {
                        $value = str_replace("'", "\'", $value);
                        // check if first character is an exclamation
                        if (substr($value, 0,1) == '!') {
                            // check if the value has only one character
                            if (strlen($value) <= 1) {
                                // if the word is contains only the exclamation
                                $OR_list = $column.' IS NOT NULL';
                            } else {
                                // if the word contains more than exclamation
                                $OR_list = $column." <> ? ";
                                    $obj[] = substr($value, 1);
                            }
                        } else if (($value == '') || ($value == NULL)) {
                            // if there are no characters
                            $OR_list = $column.' IS NULL OR '.$column.' = ?';
                            $obj[] = '';
                        } else if (substr($value, 0,1) == '~') {
                            // if there is a tilde
                            $OR_list = $column." LIKE ?";
                            $obj[] = "%".substr($value, 1)."%";
                        }   else if (substr($value, 0,1) == '<') {
                            // if there is a greater than sign
                            $OR_list = is_numeric(substr($value, 1)) ? $column." < ? " : $column." = ? ";
                            $obj[]=substr($value, 1);
                        } else if (substr($value, 0,1) == '>') {
                            // if there is a lesser than sign
                            $OR_list = is_numeric(substr($value, 1)) ? $column." > ? " : $column." = ? ";
                            $obj[] = substr($value, 1);
                        } else {
                            // if it has got a normal word
                            $OR_list = $column." = ? ";
                            $obj[] = $value;
                        }
                    }
                    // create AND list
                    $AND_list[] = '('.$OR_list.')';
                }
            }
            if (isset($AND_list)) {
                $str .= "WHERE ".implode(' AND ', $AND_list);
            }
            // create AND string
            if (isset($obj)) {
                return $obj;
            }
        }
        else if ($called_from == "updateData" && $type == 1) {
            $i = 0;
            foreach ($data as $key => $value)  {
                if (($value != "") || is_null($value)) {
                    $params[$i] = $key."=?";
                    $obj[$i] = $value;
                    $i++;
                }
                
            }

            if ($i == 0) {
                return false;
            } else {
                $str .= implode(",", $params);
                return $obj;
            }
        } else if (($called_from == "insertData" && $type == 1)) {
            $i=0;
            foreach ($data as $key=>$value) {   //  Preparing general query with nammed parameters
                if (($value != "") || is_null($value)) {
                    $npSet[$i] = "?";
                    $colSet[$i] = $key;
                    $obj[$i] = $value;
                    $i++;
                }
            }
            if ($i == 0) {
                return false;
            } else {
                $colSet = implode(",", $colSet);
                $npSet = implode(",", $npSet);
                $str .=  "(".$colSet.") VALUES (".$npSet.")";
                return $obj;
            }
        }

    }

    #   TO execute insert query with nammed parameters
    public function insertData($tname, $data)
    {
        try {
            $query = "INSERT INTO $tname ";
            $obj = $this->prepareData($data,1,$query);  
            $posts = $this->runQuery($query,$obj);
            return true;
        }
        catch(\PDOException $e) {
            return false;
        }
    }

    public function deleteData($tname, $data)
    {
        try {
            $query = "DELETE FROM $tname ";
            $obj = $this->prepareData($data, 1, $query);    
            $posts = $this->runQuery($query, $obj);
            return true;
        }
        catch(\PDOException $e) {
            return false;
        }
    }

    #   To execute SELECT query with nammed parameters
    public function getData($tname, array $data = null, array $result_format = null, array $limit = null)
    {
        try {
            $query="SELECT ";
            $this->prepareData($result_format, 1, $query);  //  1
            $query .= " FROM ".$tname." ";
        
            $obj = $this->prepareData($data, 2, $query);    //  2
            //print_r($obj);
            if ($limit && count($limit)>0) {
                $count = $limit[0];
                
                $query .= " LIMIT ".$count;
                if (isset($limit[1])) {
                    $offset = $limit[1];
                    $query .= " OFFSET ".$offset;
                } 
            }
            // echo $query;
            $post = $this->runQuery($query, $obj);
            //print_r($post);
            return $post;
        }
        catch(\PDOException $e) {
            return false;
        }
    }

    #   To execute UPDATE query with nammed parameters
    public function updateData($tname, $filters, $what_to_set)
    {
        try {
            #   update data
            $query = "UPDATE $tname SET ";
            $obj1 = $this->prepareData($what_to_set, 1, $query);
            if ($obj1 != false) {
                $query .= " ";
                $obj2 = $this->prepareData($filters, 2, $query);
                if (count($obj2) > 0) {
                    $obj = array_merge($obj1, $obj2);
                }
                else {
                    $obj = $obj1;
                }
                //print_r($obj);
                $this->runQuery($query, $obj);
                return true;
            } else {
                //  No values provided to set
                return false;
            }

        }
        catch(\PDOException $e) {
            return false;
        }
    }

    #   To count no of rows
    public function getCount($tname, array $columns = null)
    {
        try {
            $query="SELECT COUNT(*) FROM ".$tname." ";
        
            $obj = $this->prepareData($columns, 2, $query); //  2
            //print_r($obj);

            $post = $this->runQuery($query, $obj);
            //print_r($post);
            return $post[0]["COUNT(*)"];
        }
        catch(\PDOException $e) {
            return false;
        }   
        return true;
    }

    public function getRows($tname, array $columns = null) 
    {
        return $this->getData($tname, [], $columns);
    }

    # Parameter list :  "table", ["data","to","insert"], ["duplicate","keys"]
    public function pushData($tname, $data, array $key_list = null) 
    {
        try {
            //  Extract keys and their corresponding values
            foreach ($key_list as $key => $value) {
                $where[$value] = $data[$value];
            }
            //  If row(s) exist, update
            if ($this->getCount($tname, $where)) {
                // echo "updated";
                return $this->updateData($tname, $where, $data);
            } 
            else {  //  If row(s) don't exist, insert
                // echo "inserted";
                return $this->insertData($tname, $data);
            }
        }
        catch(\PDOException $e) {
            return false;
        }
    }    
    
    #   Run queries directly 
    public function query($quertyString ) {
        try {
            // Get the database action from string
            $action = explode(" ", $quertyString)[0];
            $action = strtolower($action);
            $whiteListActions = ['select','update','insert'];
            // Check if action is allowed
            if(in_array($action, $whiteListActions)) {
                $stmt = $this->pdo->query($quertyString);
                if ($action == 'select') {
                    return $stmt->fetchAll();    
                }
                return $stmt->rowCount();
            }
        } catch(\PDOException $e) {
            return false;
        }
    }

}
