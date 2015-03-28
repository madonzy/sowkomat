<?

set_time_limit(0);

require 'lib/sqlite3.class.php';
require 'lib/php_crossword.class.php';

$cols = $rows = 20;
$max_words =  20;
$max_tries =  10;
$groupid = 'demo';

$pc =& new PHP_Crossword($rows, $cols);

$pc->setGroupID($groupid);
$pc->setMaxWords($max_words);

$lang = [
    'z polskiego na angielski',
    'z angielskiego na polski',
];

$isPolishCrossword = true;
if(isset($_GET['translation']))
    if(!strcasecmp($_GET['translation'], $lang[1]))
        $isPolishCrossword = false;

$pc->_isPolishCrossword = $isPolishCrossword;

$trslFromTo = $pc->_isPolishCrossword ? ucfirst($lang[0]) : ucfirst($lang[1]);



$units = [];
for ($i=1; $i < 30; $i++)
    if(isset($_GET['unit_'.$i]))
        $units[] = $i;

$pc->_unit = count($units) ? $units : null;

$unitsInStr = 'all';
if(null !== $pc->_unit)
    $unitsInStr = implode(', ', $pc->_unit);

$books = [
    'Language Leader Intermediate' => '../leader.csv',
    'New Language Leader Intermediate' => '../newleader.csv',
    'Technology 2' => '../technology.csv',
    'Cutting Edge Preintermediate' => '../cutting.csv',
];

$bookTitle = 'Technology 2';
if( isset($_GET['book']) && isset($books[$_GET['book']])) {
    $pc->getValidCSV($books[$_GET['book']]);
    $bookTitle = $_GET['book'];
} else
    $pc->getValidCSV($books['Technology 2']);

// just support for Lithuanian charset
$charset = 'UTF-8';








?>