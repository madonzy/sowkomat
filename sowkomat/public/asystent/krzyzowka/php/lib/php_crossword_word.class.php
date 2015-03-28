<?

class PHP_Crossword_Word
{
    public $word;
    public $axis;
    public $cells = array();
    public $fully_crossed = FALSE;
    public $inum = 0; // sandy addition

	/**
	 * Constructor
	 * @param string $word
	 * @param int $axis
	 */
    public function __construct($word, $axis) {
        $this->word = $word;
        $this->axis = $axis;
    }

	/**
	 * Get word start X
	 * @return int
	 */
    public function getStartX() {
        return $this->cells[0]->x;
    }

	/**
	 * Get word start Y
	 * @return int
	 */
    public function getStartY() {
        return $this->cells[0]->y;
    }

	/**
	 * Get crossable cells in the word
	 * @return array
	 */
    public function &getCrossableCells() {
        $axis = $this->getCrossAxis();

        $cells = array();

        for ($i = 0; $i < strlen($this->word); $i++)
        	if ($this->cells[$i]->canCross($axis))
            	$cells[] =&  $this->cells[$i];

        if (!count($cells) )
            $this->fully_crossed = TRUE;

        return $cells;
    }

	/**
	 * Check if word is fully crossed
	 * @return boolean
	 */
    public function isFullyCrossed() {
        if ($this->fully_crossed )
            return TRUE;

        $this->getCrossableCells();

        return $this->fully_crossed;
    }

	/**
	 * Get crossing axis
	 * @return int
	 */
    public function getCrossAxis() {
        return $this->axis == PC_AXIS_H ? PC_AXIS_V : PC_AXIS_H;
    }

}
?>