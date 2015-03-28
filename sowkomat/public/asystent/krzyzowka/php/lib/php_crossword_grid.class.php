<?

class PHP_Crossword_Grid {

	public $rows;
	public $cols;
	public $cells 		= array();
	public $words 		= array();

	public $inum 		= 0; // sandy addition
	public $maxinum 	= 0; // sandy addition
	public $totwords 	= 0; // sandy addition

	/**
	 * Constructor
	 * @param int $rows
	 * @param int $cols
	 */
	public function __construct($rows, $cols) {
		$this->rows = (int)$rows;
		$this->cols = (int)$cols;

		$this->__initCells();
	}

	/**
	 * Initialize cells (create celll objects)
	 * @private
	 */
	public function __initCells() {
		for ($y = 0; $y < $this->rows; $y++)
		for ($x = 0; $x < $this->cols; $x++)
		$this->cells[$x][$y] =& new PHP_Crossword_Cell($x, $y);
	}

	/**
	 * Count words in the grid
	 * @return int
	 */
	public function countWords() {
		$this->totwords = count($this->words); // sandy addition
		return $this->totwords;
	}

	/**
	 * Get random word from the grid (not fully crossed)
	 * @return object word object
	 */
	public function getRandomWord() {
		$words = array();

		for ($i = 0; $i < count($this->words); $i++)
		if (!$this->words[$i]->isFullyCrossed())
		$words[] = $i;


		if (!count($words))
		return PC_WORDS_FULLY_CROSSED;

		$n = array_rand($words);
		$n = $words[$n];

		return $this->words[$n];
	}

	/**
	 * Place word
	 * @param string $word
	 * @param int $x
	 * @param int $y
	 * @param int $axis
	 */
	public function placeWord($word, $x, $y, $axis) {
		$w =& new PHP_Crossword_Word($word, $axis, $this->cells[$x][$y]);

		++$this->inum; // sandy addition
		++$this->maxinum; // sandy addition

		$w->inum = $this->inum; // sandy addition

		$this->words[] =& $w;

		$cx = $x;
		$cy = $y;

		if ($axis == PC_AXIS_H) {
			$s = $x;
			$c =& $cx;
			$t =& $cy;
		}
		else {
			$s = $y;
			$c =& $cy;
			$t =& $cx;
		}

		// dump( "PLACING WORD: $cx x $cy - {$w->word}" );

		for ($i = 0; $i < strlen($word); $i++) {
			$c = $s + $i;
			$cell =& $this->cells[$cx][$cy];

			$cell->setLetter($w->word[$i], $axis, $this);
			$w->cells[$i] =& $cell;
		}

		// disable cell before first cell
		$c = $s - 1;
		if ($c >= 0 )
			$this->cells[$cx][$cy]->setCanCross(PC_AXIS_BOTH, FALSE);

		@$this->cells[$cx][$cy]->number = $w->inum; // sandy addition

		// disable cell after last cell
		$c = $s + strlen($word);
		if (is_object($this->cells[$cx][$cy]))
			$this->cells[$cx][$cy]->setCanCross(PC_AXIS_BOTH, FALSE);

		// avoid starting "corner word" - which would use the same
		// number cell as this word
		$c = $s - 1;
		$t = $t + 1;
		if ($c >= 0 && is_object($this->cells[$cx][$cy]))
			$this->cells[$cx][$cy]->setCanCross(PC_AXIS_BOTH, FALSE);
	}

	/**
	 * Check if it's possible to place the word
	 * @param string $word
	 * @param int $x
	 * @param int $y
	 * @param int $axis
	 * @return boolean
	 */
	public function canPlaceWord($word, $x, $y, $axis) {
		for ($i = 0; $i < strlen($word); $i++) {
			if ($axis == PC_AXIS_H )
			$cell = $this->cells[$x+$i][$y];
			else
			$cell = $this->cells[$x][$y+$i];

			if (!is_object($cell)) {
				echo "ERROR!!! Word: $word, x=$x, y=$y, axis=$axis";
				echo $this->getHTML(1);
			}

			if (!$cell->canSetLetter($word[$i], $axis))
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Get number of columns in the grid
	 * @return int
	 */
	public function getCols() {
		return $this->cols;
	}

	/**
	 * Get number of rows in the grid
	 * @return int
	 */
	public function getRows() {
		return $this->rows;
	}

	/**
	 * Get random position
	 * @param int $axis
	 * @param string $word
	 * @return int
	 */
	public function getRandomPos($axis, $word = NULL) {
		$n = $axis == PC_AXIS_H ? $this->cols : $this->rows;

		if (!is_null($word))
		$length = strlen($word);

		if ($n == $length) return 0;

		return rand(0, $n-$length-1);
	}

	/**
	 * Get center position
	 * @param int $axis
	 * @param string $word
	 * @return int
	 */
	public function getCenterPos($axis, $word = '') {
		$n = $axis == PC_AXIS_H ? $this->cols : $this->rows;
		$n-= strlen($word);
		$n = floor($n / 2);
		return $n;
	}

	/**
	 * Get minimum starting cell on the axis
	 * @param object $cell crossing cell
	 * @param int $axis
	 * @return object cell
	 */
	public function getStartCell(&$cell, $axis ) {
		$x = $cell->x;
		$y = $cell->y;

		if ($axis == PC_AXIS_H)
		$n =& $x;
		else
		$n =& $y;

		while ($n >= 0) {
			if (!$this->cells[$x][$y]->canCross($axis))
			break;

			$n--;

			if (isset($this->cells[$x][$y]->letter)) {
				$n++;
				break;
			}
		}

		$n++;

		return $this->cells[$x][$y];
	}

	/**
	 * Get maximum ending cell on the axis
	 * @param object $cell crossing cell
	 * @param int $axis
	 * @return object cell
	 */
	public function getEndCell(&$cell, $axis) {
		$x = $cell->x;
		$y = $cell->y;

		if ($axis == PC_AXIS_H) {
			$n =& $x;
			$max = $this->getCols() - 1;
		}
		else {
			$n =& $y;
			$max = $this->getRows() - 1;
		}

		while ($n <= $max) {
			if (!$this->cells[$x][$y]->canCross($axis))
			break;

			$n++;

			if (isset($this->cells[$x][$y]->letter)) {
				$n--;
				break;
			}
		}

		$n--;

		return $this->cells[$x][$y];
	}

	/**
	 * Get HTML (for debugging)
	 * @param array params
	 * @return string HTML
	 */
	public function getHTML($params = array()) {
		extract((array)$params);

		$html = "<table border=0 class='crossTable' align='center'>";

		for ($y = -1; $y < $this->rows; $y++) {
			$html.= "<tr align='center'>";

			for ($x = -1; $x < $this->cols; $x++) {
				if ($x > -1 && $y > -1) {
					switch ($this->cells[$x][$y]->getCanCrossAxis())
					{
						case PC_AXIS_H:
						$color = "yellow";
						break;

						case PC_AXIS_V:
						$color = "brown";
						break;

						case PC_AXIS_NONE:
						$color = "red";
						break;

						case PC_AXIS_BOTH:
						$color = "lightgreen";
						break;
					}
				}

				$class = isset($this->cells[$x][$y]->letter) ? 'cellLetter' : 'cellEmpty';

				if (!$colors) $color = "white";
				else $class = 'cellDebug';

				$html .= "\n";

				if (isset($this->cells[$x][$y]->number)) {
					//global $maxinum, $totwords, $wc, $fillflag, $cellflag; // sandy addition
					$tempinum = $this->cells[$x][$y]->number; // sandy addition
					//$tempinum = $tempinum + 10 - $maxinum - $wc;
					//dump($tempinum);
					//$tempinum = $tempinum + 10 - $this->maxinum - $this->totwords;

					$html.= "<td class='cellNumber$cellflag' align=right valign=bottom><b>$tempinum</b></td>"; // sandy addition
				}
				elseif ($y == -1)
				//                    $html.= "<td width='20' class='cellDebug'><b>".($x+1)."</b></td>";
				$html.= "<td bgcolor='{$color}' class='{$class}$cellflag'>&nbsp;</td>";
				elseif ($x == -1)
				//                    $html.= "<td class='cellDebug'><b>".($y+1)."</b></td>";
				$html.= "<td bgcolor='{$color}' class='{$class}$cellflag'>&nbsp;</td>";
				elseif (isset($this->cells[$x][$y]->letter)) {
					if ($fillflag) {
						$letter=$this->cells[$x][$y]->letter;
					} else {
						$letter="&nbsp;";
					}
					$html.= "<td bgcolor='{$color}' class='{$class}$cellflag'>$letter</td>";
					//                    $html.= "<td bgcolor='{$color}' class='{$class}'>".$this->cells[$x][$y]->letter."</td>";
				}
				else
				$html.= "<td bgcolor='{$color}' class='{$class}$cellflag'>&nbsp;</td>";
			}
			$html.= "</tr>";
		}

		$html.= "</table>";

		return $html;
	}
}
?>
