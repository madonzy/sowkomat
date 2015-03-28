<?

class mySQLite3 {
        public $conn;

        function __construct(){
            $this->conn = new SQLite3(':memory:');
            $sql = <<<SQL
                CREATE TABLE words (
                    groupid varchar(10) NOT NULL DEFAULT 'lt',
                    word varchar(20) NOT NULL DEFAULT '',
                    question varchar(255) NOT NULL DEFAULT '',
                    PRIMARY KEY (word,groupid)
                );
SQL;
            $this->conn->exec($sql);

        }

		// close connection
        function close(){
            $this->conn->close() or die ('Unable to close.');
        }

        // execute single query w/o returning results or if $ret = 'id' return index of new insertion
        function sql_query($query, $ret=""){
            $this->conn->query($query) or die("query error($query): ". $this->conn->lastErrorMsg());
            if ($ret == 'id') {
                return $this->conn->lastInsertRowID();
            }
        }

        function sql_result($query) {
            $dbR = $this->conn->query($query) or die("query error($query): ". $this->conn->lastErrorMsg());

            return ($dbR);
        }

				// returning one(first) row object type results from DB for pref. $query
        function sql_object($query) {
            $dbR = $this->conn->query($query) or die("object query error($query): ". $this->conn->lastErrorMsg());
            $resL = $dbR->fetchArray();
            return ($resL);
        }

        // returning one(first) row array type results from DB for pref. $query
        function sql_row($query) {
            $dbR = $this->conn->query($query) or die("row query error($query): ". $this->conn->lastErrorMsg());
            $resL = $dbR->fetchArray();
            return ($resL);
        }

        function sql_array($query) {
            $dbR = $this->conn->query($query) or die("object query error($query): ". $this->conn->lastErrorMsg());
            $resL = $dbR->fetchArray();
            return ($resL);
        }

        function sql_all_arrays($query) {
            $dbR = $this->conn->query($query) or die("query error($query): ". $this->conn->lastErrorMsg());
            while ($row = $dbR->fetchArray()) {
                    $rows[] = $row;
            }
            return ($rows);
        }

        function sql_all_rows($query) {
            $dbR = $this->conn->query($query) or die("query error($query): ". $this->conn->lastErrorMsg());
            while ($row = $dbR->fetchArray()) {
                $rows[] = $row;
            }
            return ($rows);
        }

		function sql_all_objects($query, $type="") {
            $dbR = $this->conn->query($query) or die("all obj. error($query): ". $this->conn->lastErrorMsg());

            $i = 1;
            if (mysql_num_rows($dbR) > 0) {
               if(empty($type)){
                while ($resL = $dbR->fetchArray()) {
                   $resArray[$i] = $resL;
                   $i++;
                }
               } else {
                while ($resL = $dbR->fetchArray()) {
                   $resArray[$i] = $resL;
                   $i++;
                }
               }
            } else {
             return (false);
            }

            return ($resArray);
        }

        // returning number of rows of selected query
        function sql_num_rows($query) {
            $dbR = mysql_query($query, $this->conn) or die("num rows obj. error($query): ". mysql_error($this->conn));
            return mysql_num_rows($dbR);
        }
}

?>