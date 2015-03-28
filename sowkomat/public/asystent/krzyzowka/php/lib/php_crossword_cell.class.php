<?

class PHP_Crossword_Cell
{
    public $x;
    public $y;
    public $letter;
    public $crossed = 0;

    public $number; // sandy addition

    public $can_cross = array(
                     PC_AXIS_H => TRUE,
                     PC_AXIS_V => TRUE
					 );


	/**
	 * Constructor
	 * @param int $x
	 * @param int $y
	 */
    public function __construct($x, $y) {
        $this->x = (int)$x;
        $this->y = (int)$y;
    }

	/**
	 * Set letter to the cell
	 * @param char $letter
	 * @param int $axis
	 * @param object $grid
	 */
    public function setLetter($letter, $axis, &$grid) {
        if (!$this->canSetLetter($letter, $axis))
        {
            echo "ERROR IN GRID:";
            echo $grid->getHtml();
            die("Can't place letter '{$letter}' to cell [{$this->x}x{$this->y}]");
        }

        $this->letter = $letter;
        $this->crossed++;

        $this->can_cross[$axis] = FALSE;

        $this->__updateNeighbours($axis, $grid);
    }

	/**
	 * Update neigbhour cells
	 * @private
	 * @param int $axis
	 * @param object $grid
	 */
    public function __updateNeighbours($axis, &$grid) {
        $x = $this->x;
        $y = $this->y;

        if ($axis == PC_AXIS_H)
            $n =& $y;
        else
            $n =& $x;

        $n-= 1;

        if ($n >= 0)
            $grid->cells[$x][$y]->setCanCross($axis, FALSE);

        $n+= 2;

        if (is_object($grid->cells[$x][$y]))
            $grid->cells[$x][$y]->setCanCross($axis, FALSE);

    }

	/**
	 * Check if the cell can cross
	 * @param int $axis
	 * @return boolean
	 */
    public function canCross($axis) {
        return $this->can_cross[$axis];
    }

	/**
	 * Set crossing possiblities
	 * @param int $axis
	 * @param boolean $can
	 */
    public function setCanCross($axis, $can) {
        switch ($axis)
        {
            case PC_AXIS_H:
				$this->can_cross[PC_AXIS_H] = $can;
				break;

            case PC_AXIS_V:
				$this->can_cross[PC_AXIS_V] = $can;
				break;

            case PC_AXIS_BOTH:
				$this->can_cross[PC_AXIS_H] = $can;
				$this->can_cross[PC_AXIS_V] = $can;
				break;

            default:
				die("INVALID AXIS FOR setCanCross");
        }
    }

	/**
	 * Check if it's possible to set letter
	 * @param char $letter
	 * @param int $axis
	 * @return boolean
	 */
    public function canSetLetter($letter, $axis) {
        return !(!$this->can_cross[$axis] || ($this->crossed && $this->letter != $letter));
    }

	/**
	 * Get available axis for crossing
	 * @return int
	 */
    public function getCanCrossAxis() {
        if ($this->canCross(PC_AXIS_H) && $this->canCross(PC_AXIS_V)) return PC_AXIS_BOTH;

        elseif ($this->canCross(PC_AXIS_H)) return PC_AXIS_H;

        elseif ($this->canCross(PC_AXIS_V)) return PC_AXIS_V;

        else return PC_AXIS_NONE;
    }

}

?>