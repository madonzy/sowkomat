<?
setlocale(LC_ALL, 'pl_PL.UTF8');
define("_PC_DIR", dirname(__FILE__) . "/");

require_once _PC_DIR . "php_crossword_grid.class.php";
require_once _PC_DIR . "php_crossword_cell.class.php";
require_once _PC_DIR . "php_crossword_word.class.php";

define("PC_AXIS_H", 1);
define("PC_AXIS_V", 2);
define("PC_AXIS_BOTH", 3);
define("PC_AXIS_NONE", 4);
define("PC_WORDS_FULLY_CROSSED", 10);

class PHP_Crossword {

    public $rows = 20;
    public $cols = 20;
    public $grid;

    public $max_full_tries = 10;
    public $max_words = 20;
    public $max_tries = 50;

    public $table = "words";
	public $groupid = "common";
    public $db;

    public $_match_line;
    public $_full_tries = 0;
    public $_tries = 0;
	public $_debug = FALSE;
	public $_items;

    private $_validCSV;
    private $_utf8CSV;
    public  $_unit;
    public  $_isPolishCrossword;

	/**
	 * Constructor
	 * @param int $rows
	 * @param int $cols
	 */
    public function __construct($rows = 20, $cols = 20) {
        $this->rows = (int)$rows;
        $this->cols = (int)$cols;

		// connect to the database
        $this->db = new mySQLite3;
    }

    public function __destruct() {
        $this->db->close();
    }

	/**
	 * Set words group ID
	 * @param string $groupid
	 */
	public function setGroupID($groupid) {
		$this->groupid = $groupid;
	}

	/**
	 * Enable / disable debugging
	 * @param boolean $debug
	 */
	public function setDebug($debug = TRUE) {
		$this->_debug = (boolean)$debug;
	}

	/**
	 * Set number of words the crossword should have
	 * @param int $max_words
	 */
    public function setMaxWords($max_words) {
        $this->max_words = (int)$max_words;
    }

	/**
	 * Set maximum number of tries to generate full crossword
	 * @param int $max_full_tries
	 */
    public function setMaxFullTries($max_full_tries) {
        $this->max_full_tries = (int)$max_full_tries;
    }

    /**
	 * Set max tries to pick the words
	 * @param int $max_tries
	 */
	public function setMaxTries($max_tries) {
        $this->max_tries = (int)$max_tries;
    }

	/**
	 * Generate crossword
	 * @return boolean TRUE - if succeeded, FALSE - if unable to get required number of words
	 */
    public function generate() {
		// set the number of full tries
		$this->_full_tries = 0;

        // try to generate until we get required number of words
        while ($this->_full_tries < $this->max_full_tries) {
			// reset grid
            $this->reset();

			// count number of tried to generate crossword
			// with required number of words
            $this->_full_tries++;

			// pick and place first word
            $this->__placeFirstWord();

			// try to find other words and place them
            $this->__autoGenerate();

            //dump($this->grid->countWords());

			// if we have enough words -
            if ($this->grid->countWords() == $this->max_words) {
				$this->_items = $this->__getItems();
				return TRUE;
			}
        }

		if ($this->_debug)
            echo "ERROR: unable to generate {$this->max_words} words crossword (tried {$this->_full_tries} times)";

		return FALSE;
    }

	/**
	 * Reset grid
	 */
    public function reset() {
		// create new grid object
        $this->grid = new PHP_Crossword_Grid($this->rows, $this->cols);

		// reset number of tries to pick words
        $this->_tries = 0;

		// reset crossword items
		$this->_items = NULL;
    }

    private function mb_ucfirst($str, $enc = 'utf-8') {
            return mb_strtoupper(mb_substr($str, 0, 1, $enc), $enc).mb_substr($str, 1, mb_strlen($str, $enc), $enc);
    }

	/**
	 * Get crossword HTML (useful for generator debugging)
	 * @param array params
	 * @return string HTML
	 */
    public function getHTML($params = array()) {
        return $this->grid->getHTML($params);
    }

    /**
     * @return array [0] => word, [1] => unit, [4] => translate_1, [5] => translate_2, [6] => translate_3
     */
    private function readCSV($csvPath){
        $file_handle = fopen($csvPath, 'r');
        fgetcsv($file_handle, 10024, ";"); // black hole
        $i = 0;
        while (!feof($file_handle) ) {
            $line_of_text[$i] = fgetcsv($file_handle, 10024, ";");
            if(is_array($line_of_text[$i]))
                $line_of_text[$i] = array_map('strtolower', $line_of_text[$i]);
            ++$i;
        }
        fclose($file_handle);
        return $line_of_text;
    }

    /*public function str2url( $str, $strtolower = true ) {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);

        $charsArr =  array( '^', '\'', '"', '`', '~');
        $str = str_replace( $charsArr, '', $str );

        $str = preg_replace( "/[^a-z0-1-]{1}/i", ' ', $str );
        return $str;
    }*/

    public function getByUnit($arr) {
        if(null !== $this->_unit)
            foreach ($arr as $key => &$value)
                if(!in_array((int)$value[1], $this->_unit))
                    unset($arr[$key]);

        shuffle($arr);
        return $arr;
    }

    public function convertPolishToNumbers($str) {
        $pattern = [
            "'ę'", "'ó'", "'ą'", "'ś'", "'ł'", "'ż'", "'ź'", "'ć'", "'ń'"
        ];

        $replace = [
            "1", "2", "3", "4", "5", "6", "7", "8", "9"
        ];

        return preg_replace($pattern, $replace, $str);
    }

    public function convertNumbersToPolish($str) {
        $pattern = [
            "'1'", "'2'", "'3'", "'4'", "'5'", "'6'", "'7'", "'8'", "'9'"
        ];

        $replace = [
            "ę", "ó", "ą", "ś", "ł", "ż", "ź", "ć", "ń"
        ];

        return mb_strtoupper(preg_replace($pattern, $replace, $str), 'UTF-8');
    }

    public function getValidCSV($csv) {
        $csvData = $csvUTF8 = $this->getByUnit($this->readCSV($csv));

        if($this->_isPolishCrossword) {
            foreach ($csvData as $key => &$value) {
                $tmpWord = explode(' ', $value[0]);
                    if(count($tmpWord) == 1)
                        $value[0] = $tmpWord[0];
                    else
                        if(count($tmpWord) == 2) {
                            if(!strcasecmp($tmpWord[0], 'to'))
                                $value[0] = $tmpWord[1];
                            else
                                unset($csvData[$key]);
                        } else
                            unset($csvData[$key]);
            }
            $csvUTF8 = $csvData;
        } else {
            foreach ($csvData as $key => &$value) {
                $tmpWord1 = $tmpUTF8Word1 = explode(' ', $value[4]);
                if(count($tmpWord1) == 1) {
                    $tmp = $value[0];
                    $value[0] = trim($this->convertPolishToNumbers($tmpWord1[0]), ' ,.');
                    $value[4] = $tmp;

                    $tmpUTF8 = $value[0];
                    $csvUTF8[$key][0] = trim($tmpUTF8Word1[0], ' ,.');
                    $csvUTF8[$key][4] = $tmpUTF8;
                }
                else {
                    if(strlen(trim($value[5]))) {
                        $tmpWord2 = $tmpUTF8Word2 = explode(' ', $value[5]);
                        if(count($tmpWord2) == 1) {
                            $tmp = $value[0];
                            $value[0] = trim($this->convertPolishToNumbers($tmpWord2[0]), ' ,.');
                            $value[4] = $tmp;

                            $tmpUTF8 = $value[0];
                            $csvUTF8[$key][0] = trim($tmpUTF8Word2[0], ' ,.');
                            $csvUTF8[$key][4] = $tmpUTF8;
                        }
                        else {
                            if(strlen(trim($value[6]))) {
                                $tmpWord3 = $tmpUTF8Word3 = explode(' ', $value[6]);
                                if(count($tmpWord3) == 1) {
                                    $tmp = $value[0];
                                    $value[0] = trim($this->convertPolishToNumbers($tmpWord3[0]), ' ,.');
                                    $value[4] = $tmp;

                                    $tmpUTF8 = $value[0];
                                    $csvUTF8[$key][0] = trim($tmpUTF8Word3[0], ' ,.');
                                    $csvUTF8[$key][4] = $tmpUTF8;
                                } else {
                                    unset($csvData[$key]);
                                    unset($csvUTF8[$key]);
                                }
                            } else {
                                unset($csvData[$key]);
                                unset($csvUTF8[$key]);
                            }
                        }
                    } else {
                        unset($csvData[$key]);
                        unset($csvUTF8[$key]);
                    }
                }
            }
        }

        $this->_validCSV = array_slice($csvData, 0, 50);
        $this->_utf8CSV = array_slice($csvUTF8, 0, 50);

    }

	/**
	 * Get crossword items
	 * @return array
	 */
	public function getWords() {
		return $this->_items;
	}

    /**
	 * Get crossword items array
	 * @private
	 * @return array
	 */
	public function __getItems() {
        $items = [];

        for ($i = 0; $i < count($this->grid->words); $i++) {
            $w =& $this->grid->words[$i];

            $items[] = array(
				"word"		=> $w->word,
				"question"	=> $this->getQuestion($w->word),
				"x"			=> $w->getStartX() + 1,
				"y"			=> $w->getStartY() + 1,
                "axis"      => $w->axis,
				"help"		=> $this->getHelp($w->word),
				);
        }

        return $items;
    }

    public function getStringOfWords() {
        $str = '';
        foreach ($this->_validCSV as $val)
            $str .= $val[0].', ';

        return rtrim($str, ', ');
    }

    /**
     * Get question for the word
     * @param string $word
     * @return string $question
     */
    public function getQuestion($word) {
        foreach ($this->_validCSV as $val)
            if(in_array(strtolower($word), $val))
                return $this->mb_ucfirst($val[4]);
    }

	/**
	 * Get answer for the word
	 * @param string $word
	 * @return string $answer
	 */
    public function getHelp($word) {
        foreach ($this->_utf8CSV as $val)
            if(in_array(strtolower($word), $val) || in_array(strtolower('to '.$word), $val))
                return strtolower($val[0]);
    }

	/**
	 * Try to generate crossword automatically
	 * (until we get enough word or reach number of maximum tries
	 * @private
	 */
    public function __autoGenerate() {
        while ($this->grid->countWords() < $this->max_words && $this->_tries < $this->max_tries) {
            $this->_tries++;

            // dump( "Words: " . $this->grid->countWords() . ", Tries: $this->_tries" );

            $w = $this->grid->getRandomWord();

            if ($w == PC_WORDS_FULLY_CROSSED)
            {
                // echo "NOTE: All words fully crossed...";
                break;
            }

            $axis = $w->getCrossAxis();
            $cells = $w->getCrossableCells();

            // dump( "TRYING WORD: ".$w->word );

            while (count($cells))
            {
                $n = array_rand($cells);
                $cell =& $cells[$n];

                //dump( "TRYING CELL: [$cell->x/$cell->y]:". $cell->letter );
                //dump( "COUNT CELLS: ". count($cells) );

                $list = $this->__getWordWithStart($cell, $axis);
                $word = $list[0];
                $start =& $list[1];

                if ($start)
                {
                    $this->grid->placeWord($word, $start->x, $start->y, $axis);
                    break;
                }

                //dump( "CAN'T FIND CROSSING FOR: ".$cells[$n]->letter );
                $cells[$n]->setCanCross($axis, FALSE);
                unset($cells[$n]);
            }
        }
    }

	/**
	 * Try to pick the word crossing the cell
	 * @private
	 * @param object $cell Cell object to cross
	 * @param int $axis
	 * @return array Array of 2 items - word and start cell object
	 */
    public function __getWordWithStart(&$cell, $axis) {
        $start = $this->grid->getStartCell($cell, $axis);
        $end = $this->grid->getEndCell($cell, $axis);

        $word = $this->__getWord($cell, $start, $end, $axis);

        if (!$word) return NULL;

        $pos = NULL;

        do {
            // dump( $this->_match_line );
            $s_cell = $this->__calcStartCell($cell, $start, $end, $axis, $word, $pos);
            $can = $this->grid->canPlaceWord($word, $s_cell->x, $s_cell->y, $axis);

            //if ( !$can )
            // dump(strtoupper("Wrong start position [{$s_cell->x}x{$s_cell->y}]! Relocating..."));

        }
        while (!$can);

        return array($word, &$s_cell);
    }

	/**
	 * Calculate starting cell for the word
	 * @private
	 * @param object $cell crossing cell
	 * @param object $start minimum starting cell
	 * @param object $end maximum ending cell
	 * @param int $axis
	 * @param string $word
	 * @param int $pos last position
	 * return object|FALSE starting cell object or FALSE ir can't find
	 */
    public function &__calcStartCell(&$cell, &$start, &$end, $axis, $word, &$pos) {
        $x = $cell->x;
        $y = $cell->y;

        if ($axis == PC_AXIS_H) {
            $t =& $x;
            $s = $cell->x - $start->x;
            $e = $end->x - $cell->x;
        }
        else {
            $t =& $y;
            $s = $cell->y - $start->y;
            $e = $end->y - $cell->y;
        }

        $l = strlen($word);

        do {
            $offset = isset($pos) ? $pos+1 : 0;
            $pos = strpos($word, $cell->letter, $offset);
            $a = $l-$pos-1;
            if ($pos <= $s && $a <= $e)
            {
                $t-= $pos;
                return $this->grid->cells[$x][$y];
            }
        }
        while ($pos !== FALSE);

        return FALSE;
    }

	/**
	 * Try to get the word
	 * @private
	 * @param object $cell crossing cell
	 * @param object $start minimum starting cell
	 * @param object $end maximum ending cell
	 * @param int $axis
	 * @return string word
	 */
    public function __getWord(&$cell, &$start, &$end, $axis) {
        $this->_match_line = $this->__getMatchLine($cell, $start, $end, $axis);
        $match = $this->__getMatchLike($this->_match_line);
        $min = $this->__getMatchMin($this->_match_line);
        $max = strlen($this->_match_line);
        $regexp = $this->__getMatchRegexp($this->_match_line);

        $rs = $this->__loadWords($match, $min, $max);

        return $this->__pickWord($rs, $regexp);
    }

	/**
	 * Pick the word from the resultset
	 * @private
	 * @param mysql_resultset $rs
	 * @param string $regexp Regexp to match
	 * return string|NULL word or NULL if couldn't find
	 */
    public function __pickWord(&$rs, $regexp) {
        $n = 0;
        $rs->reset();
        while ($rs->fetchArray())
            $n++;
        $rs->reset();
        if (!$n) return NULL;

        $list = range(0, $n-1);

        while (count($list)) {
            $i = array_rand($list);
            $row = $rs->fetchArray();

            if (preg_match("/{$regexp}/", $row[0]))
            {
                return $row[0];
            }

            unset($list[$i]);
        }


        return NULL;
    }

	/**
	 * Generate word matching line
	 * @private
	 * @param object $cell crossing cell
	 * @param object $start minimum starting cell
	 * @param object $end maximum ending cell
	 * @param int $axis
	 * @return string matching line
	 */
    public function __getMatchLine(&$cell, &$start, &$end, $axis) {
        $x = $start->x;
        $y = $start->y;

        if ($axis == PC_AXIS_H) {
            $n =& $x;
            $max = $end->x;
        }
        else {
            $n =& $y;
            $max = $end->y;
        }

        $str = '';

        while ($n <= $max) {
            $cell =& $this->grid->cells[$x][$y];
            $str.= isset($cell->letter) ? $cell->letter : '_';
            $n++;
        }

        return $str;
    }

	/**
	 * Get minimum match string
	 * @private
	 * @param string $str match string
	 * @return string
	 */
    public function __getMatchMin($str) {
        $str = preg_replace("/^_+/", "", $str, 1);
        $str = preg_replace("/_+$/", "", $str, 1);
        return strlen($str);
    }

	/**
	 * Get SQL LIKE match for the match string
	 * @private
	 * @param string $str match string
	 * @return string
	 */
    public function __getMatchLike($str) {
        $str = preg_replace("/^_+/", "%", $str, 1);
        $str = preg_replace("/_+$/", "%", $str, 1);
        return $str;
    }

	/**
	 * Get REGEXP for the match string
	 * @private
	 * @param string $str match string
	 * @return string
	 */
    public function __getMatchRegexp($str) {
        $str = preg_replace("/^_*/e", "'^.{0,'.strlen('\\0').'}'", $str, 1);
        $str = preg_replace("/_*$/e", "'.{0,'.strlen('\\0').'}$'", $str, 1);
        $str = preg_replace("/_+/e", "'.{'.strlen('\\0').'}'", $str);
        return $str;
    }

	/**
	 * Place first word to the cell
	 * @private
	 */
    public function __placeFirstWord() {
        $word = $this->__getRandomWord($this->grid->getCols());

        $x = $this->grid->getCenterPos(PC_AXIS_H, $word);
        $y = $this->grid->getCenterPos(PC_AXIS_V);

        $this->grid->placeWord($word, $x, $y, PC_AXIS_H);
    }

	/**
	 * Load words for the match
	 * @private
	 * @param string $match SQL LIKE match
	 * @param int $len_min minimum length of the word
	 * @param int $len_max maximum length of the word
	 * @return result SQL result
	 */
    public function __loadWords($match, $len_min, $len_max) {
        $used_words_sql = $this->__getUsedWordsSql();

        $sql = "SELECT word FROM {$this->table} WHERE
			groupid='{$this->groupid}' AND
            LENGTH(word)<={$len_max} AND
            LENGTH(word)>={$len_min} AND
            word LIKE '{$match}'
            {$used_words_sql}
            ";

        // dump($sql);
        return $this->db->sql_result($sql);
    }

	/**
	 * Get used word SQL
	 * @private
	 * return string
	 */
    public function __getUsedWordsSql() {
        $sql = '';

        for ($i = 0; $i < count($this->grid->words); $i++)
        	$sql .= "AND word!='" . $this->grid->words[$i]->word . "' ";

        return $sql;
    }

	/**
	 * Get random word
	 * @private
	 * @param int $max_length maximum word length
	 * @return string word
	 */
    public function __getRandomWord($max_length) {
        $where = "LENGTH(word)<={$max_length}";

        $count = $this->__getWordsCount($where);

        if (!$count)
            die("ERROR: there is no words to fit in this grid" );

        $n = rand(0, $count-1);

        $sql = "SELECT word FROM {$this->table}
            WHERE groupid='{$this->groupid}' AND {$where}
            LIMIT {$n}, 1";

        $row = $this->db->sql_row($sql);

        return $row[0];
    }

	/**
	 * Count words
	 * @private
	 * @param string $where SQL where
	 * @return int
	 */
    public function __getWordsCount($where = NULL) {
        $where_sql = $where ? "AND {$where}" : "";

        $sql = "SELECT COUNT(word) FROM {$this->table}
			WHERE groupid='{$this->groupid}' {$where_sql}";

        $row = $this->db->sql_row($sql);
        return $row[0];
    }

	/**
	 * Check if the word already exists in the database
	 * @param string $word
	 * @return boolean
	 */
    public function existsWord($word) {
        $sql = "SELECT word FROM {$this->table} WHERE
			groupid = '{$this->groupid}' AND
			UPPER(word) = UPPER('{$word}')";
        $obj = $this->db->sql_object($sql);
        return $obj['word'] ? TRUE : FALSE;
    }

	/**
	 * Insert word into database
	 * @param string $word
	 * @param string $question
	 */
    public function insertWord($word, $question) {
        $word = trim($word);
		$word = preg_replace("/[\_\'\"\%\*\+\\\\\/\[\]\(\)\.\{\}\$\^\,\<\>\;\:\=\?\#]/", '', $word);
        if (empty($word)) return FALSE;
        if ($this->existsWord($word)) return FALSE;

        $sql = "INSERT INTO {$this->table}(groupid, word, question)
			VALUES('{$this->groupid}', UPPER('{$word}'),'{$question}')";

        $this->db->sql_query($sql);
    }

	/**
	 * Get generated crossword XML
	 * @return string XML
	 */
	public function getXML() {
    	$words = $this->getWords();

		if (!count($words))
			return "<error>There are no words in the grid.</error>";

    	$xml = array();
    	$xml[] = "<crossword>";

    	$xml[] = "	<grid>";
		$xml[] = "		<cols>{$this->cols}</cols>";
		$xml[] = "		<rows>{$this->rows}</rows>";
		$xml[] = "		<words>" . count($words) . "</words>";
    	$xml[] = "	</grid>";

    	$xml[] = "	<items>";

    	foreach ((array)$words as $item)
    		$xml[] = $this->__wordItem2XML($item, "\t\t");

    	$xml[] = "	</items>";

		if ($this->_debug)
			$xml[] = "	<html>" . htmlspecialchars($this->grid->getHTML()) . "</html>";

    	$xml[] = "</crossword>";

    	$xml = implode("\n", $xml);

    	return $xml;
	}

	/**
	 * Get XML of the word item
	 * @private
	 * @param object $item word item
	 * @param string $ident
	 * @return string XML
	 */
	public function __wordItem2XML($item, $ident) {
		$xml = array();
        $xml[] = $ident . "<item>";

		foreach ((array)$item as $key=>$val) {
			$key = htmlspecialchars($key);
			$val = htmlspecialchars($val);
			$xml[] = "	<{$key}>{$val}</{$key}>";
		}

        $xml[] = "</item>";

    	$xml = implode("\n{$ident}", $xml);

    	return $xml;
	}

	/**
	 * Get number of words in the group
	 * @param string $groupid
	 * @return int
	 */
	public function countWordsInGroup($groupid = NULL) {
		if (empty($groupid)) $groupid = $this->groupid;
		$sql = "SELECT COUNT(*) FROM {$this->table} WHERE groupid='{$groupid}'";
		$row = $this->db->sql_row($sql);
		return (int)$row[0];
	}

	/**
	 * Get list of available words' group ids
	 * @return array
	 */
	public function getGroupIDs() {
		$sql = "SELECT groupid FROM {$this->table} GROUP BY groupid ORDER BY groupid";
		$list = $this->db->sql_all_rows($sql);

		$ids = array();

		for ($i=0; $i<count($list); $i++)
			$ids[] = $list[$i][0];

		return $ids;
	}

		/**
	 * Check if the group id already exists in the database
	 * @param string $groupid
	 * @return boolean
	 */
    public function existsGroupID($groupid) {
        $sql = "SELECT groupid FROM {$this->table} WHERE groupid = '{$groupid}'";
        $row = $this->db->sql_row($sql);
        return !empty($row[0]) ? TRUE : FALSE;
    }

	/**
	 * Generate temporary group id
	 * @return string group id
	 */
	public function createTempGroupID() {
		do {
			$groupid = rand(100000, 999999);
		}
		while ($this->existsGroupID($groupid));

		return $groupid;
	}

	/*
	 * Remove all words from the group
	 * @param string $groupid

	public function removeGroup($groupid = NULL) {
		if (is_null($groupid))
			$groupid = $this->groupid;

		$sql = "DELETE FROM {$this->table} WHERE groupid='{$groupid}'";

		$this->db->sql_query($sql);

		$sql = "OPTIMIZE TABLE {$this->table}";

		$this->db->sql_query($sql);
	}*/

	/**
	 * Generate crossword from provided words list
	 * @param string $words_list
	 * @return boolean TRUE on success
	 */
	public function generateFromWords($words_list) {
		// save current settings
		$_tmp_groupid = $this->groupid;
		$_max_words = $this->max_words;

		// create temporary group
		$groupid = $this->createTempGroupID();

		// set temp group as current group
		$this->setGroupID($groupid);

		// split words list and  insert into temp group
		foreach (explode("\n", $words_list) as $line)
			foreach (explode(" ", $line) as $word)
				$this->insertWord($word, '');

		// try to generate crossword from all passed words
		$required_words = $this->countWordsInGroup();

		// if user entered more words then max_words - require max_words...
		if ($required_words > $_max_words)
			$required_words = $_max_words;

		$success = FALSE;

		while ($required_words > 1) {
			$this->setMaxWords($required_words);

			if ($success = $this->generate())
				break;

			$required_words--;
		}

		// remove temporary group
//		$this->removeGroup($groupid);

		// restore previous settings
		$this->setGroupID($_tmp_groupid);
		$this->setMaxWords($_max_words);

		return $success;
	}

}
?>
