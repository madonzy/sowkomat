<?php error_reporting(0); header("Content-Type: text/html; charset=utf-8");

function readCSV($csvFile){
    $file_handle = fopen($csvFile, 'r');
    while (!feof($file_handle) ) {
        $line_of_text[] = fgetcsv($file_handle, 10024, ";");
    }
    fclose($file_handle);
    return $line_of_text;
}

function chooseCurr(&$csv, $trsl, $input) {

	$res = array();

	if($trsl == "en_pl") {
		for ($i = 1; $i < count($csv); $i++) {
			if($input == trim($csv[$i][0])) {
				$res[0] = $csv[$i][0];
				$res[1] = $csv[$i][4];
				break;
			}
		}
	} else {
		for ($i = 1; $i < count($csv); $i++) {

			if($input == trim($csv[$i][4])) {
				$res[0] = $csv[$i][4];
				$res[1] = $csv[$i][0];
				break;
			}

		}
	}

	return $res;

}
?>

<html>
<body>

<style type="text/css">
	body {
		color: #333;
		font-family: Myriad,Helvetica,Tahoma,Arial,clean,sans-serif;
		font-size: 12px;
	}

	* {
		margin: 0;
		padding: 0;
	}

	table {
		border-collapse: collapse;
		font-size: 12px;
		width: 378px;
		border: 1px solid #ddd;
	}
	td,th {
		padding: 5px;
		min-width: 150px;
	}

	input[type=text] {
		width: 100%;
		border: 1px solid #ddd;
		color: #333;
		font-size: 12px;
		height: 25px;
	}
	select {
		width: 100%;
		border: 1px solid #ddd;
		color: #333;
		font-size: 12px;
		height: 25px;
	}
	select:hover {
		background-color: #EBF0F7;
		cursor: pointer;
	}
	input[type=submit] {
		width: 100%;
		border: 1px solid #759dc0;
		padding: 2px 4px 4px 4px;
		color: #000000;
		-moz-border-radius: 4px;
		border-radius: 4px;
		-webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.15);
		-moz-box-shadow: 0 1px 1px rgba(0, 0, 0, 0.15);
		box-shadow: 0 1px 1px rgba(0, 0, 0, 0.15);
		background-color: #bcd8f4;
		background-image: url("form/images/buttonEnabled.png");
		background-repeat: repeat-x;
		background-image: -moz-linear-gradient(#ffffff 0px, rgba(255, 255, 255, 0) 3px, rgba(255, 255, 255, 0.75) 100%);
		background-image: -webkit-linear-gradient(#ffffff 0px, rgba(255, 255, 255, 0) 3px, rgba(255, 255, 255, 0.75) 100%);
		background-image: -o-linear-gradient(#ffffff 0px, rgba(255, 255, 255, 0) 3px, rgba(255, 255, 255, 0.75) 100%);
		background-image: linear-gradient(#ffffff 0px, rgba(255, 255, 255, 0) 3px, rgba(255, 255, 255, 0.75) 100%);
		_background-image: none;
		cursor: pointer;
		font-weight: bold;
	}
	input[type=submit]:hover {
		background-color: #99C1E8;
	}

</style>

<?php
	$let_num = '';
	$let_quant = '';
	$let_all = 'checked';
	$en_pl = "selected";
	$pl_en = '';
	$request = '';
	$isOk = false;
	if(strtoupper($_SERVER["REQUEST_METHOD"]) == "POST") {
		if(isset($_POST["book"]) && isset($_POST["request"]) && isset($_POST["mod"]) && ($_POST["mod"] == "let_num" || $_POST["mod"] == "let_all" || ($_POST["mod"] == "let_quant" && isset($_POST['num_quant']) && strlen(trim(strip_tags($_POST['num_quant'])))))) {

		  	if(isset($_POST["trsl"]))
				if($_POST["trsl"] == "en_pl") $en_pl = "selected";
				else { $pl_en = "selected"; $en_pl = ""; }

		  	$request = trim(strip_tags($_POST["request"]));

		  	switch ($_POST["book"]) {
		  		case 'lead':
				  	$res = chooseCurr(readCSV("http://".$_SERVER['HTTP_HOST']."/sowkomat/public/asystent/leader.csv"), $_POST["trsl"], $request);
		  		break;

                case 'tech':
                    $res = chooseCurr(readCSV("http://".$_SERVER['HTTP_HOST']."/sowkomat/public/asystent/technology.csv"), $_POST["trsl"], $request);
                break;

                case 'newlead':
                    $res = chooseCurr(readCSV("http://".$_SERVER['HTTP_HOST']."/sowkomat/public/asystent/newleader.csv"), $_POST["trsl"], $request);
                break;

		  		case 'cutting':
				  	$res = chooseCurr(readCSV("http://".$_SERVER['HTTP_HOST']."/sowkomat/public/asystent/cutting.csv"), $_POST["trsl"], $request);
		  		break;

				case 'geoenglish':
				  	$res = chooseCurr(readCSV("http://".$_SERVER['HTTP_HOST']."/sowkomat/public/asystent/geoenglish.csv"), $_POST["trsl"], $request);
		  		break;
				
		  		default: exit("No such dictionary!");
		  	}

			if(count($res)) {
				$isOk = true;
				$answer = $res[1];

				switch ($_POST["mod"]) {
					case 'let_num'  :
						$let_num   = "checked";
						$let_all = '';

						$qt = 0;

						$temp_answer = mb_substr($answer, 0, $qt, 'UTF-8');

						$sc = substr_count($temp_answer, ' ');
						for ($i=$qt; $i < $qt + $sc; $i++) {
							$temp_answer .= mb_substr($answer, $i, 1, 'UTF-8');
							if(mb_substr($answer, $i, 1, 'UTF-8') == ' ')
								$temp_answer .= mb_substr($answer, $i+1, 1, 'UTF-8');
						}
						$k = substr_count($temp_answer, ' ') - $sc;
						for ($i=$qt+$sc; $i < mb_strlen($answer, 'UTF-8')-$k; $i++) {
							if(mb_substr($answer, $i, 1, 'UTF-8') == ',') { $temp_answer .= '&nbsp;&nbsp;&nbsp;/'; continue; }
							$temp_answer .= mb_substr($answer, $i, 1, 'UTF-8') != ' ' ? "&nbsp;_" : "&nbsp;&nbsp;&nbsp;";
						}

						$answer = $temp_answer;

					break;

					case 'let_quant':
						$let_quant = 'checked';
						$let_all = '';
						$qt = (int)$_POST['num_quant'];

						$wordCount = substr_count($answer, ' ')+1;

						$word = array();

						$commaPosition = strpos($answer, ',');

						$answer = str_replace(',', '', $answer);
						$word = explode(' ', $answer);

						$temp_answer = '';

						$iter = 0;
						for ($i=0; $i < count($word); $i++) {

							$temp_answer .= mb_substr($word[$i], 0, $qt, 'UTF-8');

							for ($j=$qt; $j < mb_strlen($word[$i], 'UTF-8'); $j++, $iter++)
								$temp_answer .= "&nbsp;_";
							$temp_answer .= $commaPosition == mb_strlen($word[$i], 'UTF-8') && $i < count($word)-1 ? "&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;" :"&nbsp;&nbsp;&nbsp;";

						}

						$answer = $temp_answer;

						/*
						 * IF YOU WANT TO SPLIT NOT EACH ELEMENT - YOU SHOULD USE THIS ALGORITHM

						$let_quant = 'checked';
						$let_all = '';
						$qt = (int)$_POST['num_quant'];

						$temp_answer = mb_substr($answer, 0, $qt, 'UTF-8');

						$sc = substr_count($temp_answer, ' ');
						for ($i=$qt; $i < $qt + $sc; $i++) {
							$temp_answer .= mb_substr($answer, $i, 1, 'UTF-8');
							if(mb_substr($answer, $i, 1, 'UTF-8') == ' ')
								$temp_answer .= mb_substr($answer, $i+1, 1, 'UTF-8');
						}

						$k = substr_count($temp_answer, ' ') - $sc;
						for ($i=$qt+$sc; $i < mb_strlen($answer, 'UTF-8')-$k; $i++) {
							if(mb_substr($answer, $i, 1, 'UTF-8') == ',') { $temp_answer .= '&nbsp;&nbsp;&nbsp;/'; continue; }
							$temp_answer .= mb_substr($answer, $i, 1, 'UTF-8') != ' ' ? "&nbsp;_" : "&nbsp;&nbsp;";
						}

						$answer = $temp_answer;

						*/


					break;
				}
			}

		}
	}


?>

<form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">

      <table>
      	<tr>
      		<td>
      			<select name='book'>
                    <option value="cutting" <?= isset($_POST["book"]) && $_POST["book"] == "cutting" ? "selected" : ''; ?>>Cutting Edge Preintermediate</option>
			        <option value="lead" <?= isset($_POST["book"]) && $_POST["book"] == "lead" ? "selected" : ''; ?>>Language Leader Intermediate</option>
			        <option value="newlead" <?= isset($_POST["book"]) && $_POST["book"] == "newlead" ? "selected" : ''; ?>>New Language Leader Intermediate</option>
                    <option value="tech" <?= isset($_POST["book"]) && $_POST["book"] == "tech" ? "selected" : ''; ?>>Technology 2</option>
					<option value="geoenglish" <?= isset($_POST["book"]) && $_POST["book"] == "tech" ? "selected" : ''; ?>>Geo-English</option>
			    </select>
      		</td>
      	</tr>
      	<tr>
      		<td>
      			<select name='trsl'>
			        <option value="en_pl" <?= $en_pl; ?>>angielsko - polski</option>
			        <option value="pl_en" <?= $pl_en; ?>>polsko - angielski</option>
			    </select>
      		</td>
      	</tr>
      	<tr>
      		<td>
      			<label><input type="radio" name="mod" value="let_all" <?= $let_all; ?>>
      			Pokaż cały wyraz</label>
      		</td>
      	</tr>
      	<tr>
      		<td>
      			<div style="float: left;">
      				<input type="radio" name="mod" value="let_num" id="let_num" <?= $let_num; ?>>
      				<label for="let_num">Pokaż liczbę liter</label>
      			</div>
      			<div style="float: right;margin-top: -5px;">
      				<label><input type="radio" name="mod" value="let_quant" <?= $let_quant; ?>>
      				Pokaż <input type="text" name="num_quant" style="width: 30px; text-align: center;" value="<?= $isOk && $let_quant ? $qt : 2; ?>" <?= $let_quant; ?>> pierwsze litery</label>
      			</div>
      		</td>
      	</tr>
      	<tr>
      		<td>
      			<input type="text" name="request" placeholder="Wprowadź wyraz..." value="<?= $request; ?>" style="padding-left: 5px;<?= !$isOk && isset($_POST["request"]) ? "margin-bottom: 7px;border: 1px solid rgb(255, 132, 132);" : ''; ?>">
      			<?= !$isOk && isset($_POST["request"]) ? "<br><span style='font-weight: bold; color: rgb(255, 132, 132);padding-left: 69%;'>Sprawdź pisownię</span>" : '';?>
      		</td>
      	</tr>
      	<tr>
      		<td><input type="submit" value="Akceptuj"></td>
      	</tr>
      	<tr style="<?= $isOk ? '' : 'display: none'; ?>;background-color: #EBF0F7">
      		<td style="text-align: center;">
      			<b style="word-spacing: 1px;font-size: 15px;"><?= $answer; ?></b>
      		</td>
      	</tr>
      </table>

</form>
</body>
</html>