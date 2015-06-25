<? header('Content-type: text/html; charset=utf8'); ?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->

<? include 'php/init.php';



	$w = $pc->getStringOfWords();
	$success = $pc->generateFromWords($w);


	if (!$success)
		die ('SORRY, UNABLE TO GENERATE CROSSWORD FROM WORDS');

	$words = $pc->getWords();

	$res = [];

	$i = 0;
	foreach ($words as $word) {
		$res[] = [
			'clue' =>   $word['question'],
			'answer' => $pc->convertNumbersToPolish($word['word']),
			'position' => ++$i,
			'orientation' => $word['axis'] == 1 ? 'across' : 'down',
			'startx' => $word['x'],
			'starty' => $word['y'],
			'help' => $word['help'],
		];
	}


	?>

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>Crossword</title>
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
	<script src="js/jquery.crossword.js"></script>
	<script>

		(function($) {
			$(function() {
				// provide crossword entries in an array of objects like the following example
				// Position refers to the numerical startx of an entry. Each position can have
				// two entries: an across entry and a down entry
				var puzzleData = eval(<?= json_encode($res) ?>);

				$('#puzzle-wrapper').crossword(puzzleData);


				for(var i = 0; ; ++i) {
					if($('table#puzzle tr:eq(' + i + ') td').find('input').length) {
						for(var j = 0; j < i; ++j) {
							$('table#puzzle tr:eq(' + j+ ')').css('display', 'none');
						}
						break;
					}
				}

				var res = [];

				for(var i = 0; $('table#puzzle tr:eq(' + i + ')').length; ++i)
					for(var j = 0; $('table#puzzle tr:eq(' + i + ') td:eq(' + j + ')').length; ++j)
						if($('table#puzzle tr:eq(' + i + ') td:eq(' + j + ')').find('input').length) {
							res.push(j); break;
						}

				if(Math.min.apply(null, res) > 0) {
					for(var i = 0; $('table#puzzle tr:eq(' + i + ')').length; ++i) {
						for(var j = 0; j < Math.min.apply(null, res); ++j) {
							$('table#puzzle tr:eq(' + i + ') td:eq(' + j + ')').css('display', 'none');
						}
					}
				}

				if($(window).width() < 1400) {
					console.log($(window).width());
					$('table td').css('height', 'inherit');
				}

			})

		})(jQuery);


	</script>
	<style type="text/css">
	/*
		Default puzzle styling
	*/
	body {
		font: 62.5%/1.3em Helvetica, sans-serif;
		width: 90.3%;
		margin: 40px auto;
	}
		table {
			border-collapse: collapse;
			border-spacing: 0;
			max-width: 100%;
		}
		table tr{
			width: 100%;
		}
		table td {
			width: 45px;
			height: 45px;
			border: 1px solid #cdcdcd;
			padding: 0;
			margin: 0;
			background-color: #333;
			position: relative;
		}

		a.disable-link {
			pointer-events: none;
			text-decoration: blink;
			color: #898989;
		}

		a.enabled-link {
			color: #898989;
		}

		a.enabled-link:hover {
			text-decoration: blink;
		}

		td input {
			width: 100%;
			height: 100%;
			padding: 0em;
			border: none;
			text-align: center;
			font-size: 3em;
			color: #666;
			background-color: #f4f4f4;
		}

		td input:focus {
			background-color: #fff;
		}

		td span {
			color: #444;
			font-size: 0.8em;
			position: absolute;
			top: -1px;
			left: 1px;
		}

		input.done {
			font-weight: bold;
			color: green;
			pointer-events:none;
		}

		.active,
		.clues-active {
			background-color: #ddd;
		}
		.clue-done {
			color: #999;
			text-decoration: line-through;
		}

		#puzzle-wrapper {
			float: left;
			width: 54%;
			margin-right: 3%;
		}
		#puzzle-clues {
			float: left;
			width: 40%;
			margin-top: -15px;
		}

		#puzzle-clues li{
			padding: 5px;
			border-bottom: 1px solid #ddd;
			font-size: 1.2em;
			margin: .3em;
			line-height: 1.6em;
		}

		div.info {
			text-align: center;
			margin-bottom: 30px;
		}

	</style>

</head>

<body>

<div class="info">
	<h1><?= $bookTitle; ?></h1>
	<h2>Units: <?= $unitsInStr; ?></h2>
	<h3><?= $trslFromTo; ?></h3>
</div>

<div id="puzzle-wrapper"><!-- crossword puzzle appended here --></div>

</body>
</html>