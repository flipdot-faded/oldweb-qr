<?php    
/*
 * PHP QR Code encoder
 *
 * Exemplatory usage
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
    
	// Convert AAA -> 1, etc.
	function numberToAlphabet($n) {
		// start at AAA for 1
		$n = $n + 701;//25 + 26 * 26;
		$r = '';
		for ($i = 1; $n >= 0 && $i < 10; $i++) {
			$r = chr(0x41 + ($n % pow(26, $i) / pow(26, $i - 1))) . $r;
			$n -= pow(26, $i);
		}
		return $r;
	}

	// Convert 1 -> AAA, etc.
	function alphabetToNumber($a) {
		$r = 0;
		$l = strlen($a);
		for ($i = 0; $i < $l; $i++) {
			$r += pow(26, $i) * (ord($a[$l - $i - 1]) - 0x40);
		}
		// expect AAA to be 1
		return $r - 702;//-(26 + 26 * 26);
	}
	
	// items per page (10 x 10 = 100)
	$ipp = 100;
	// items per line
	$ipl = 10;
	
	function renderCodes($page = 1, $showCodes = false) {
		global $ipp;
		//set it to writable location, a place for temp generated PNG files
		$PNG_TEMP_DIR = dirname(__FILE__).DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR;
		
		//html PNG location prefix
		$PNG_WEB_DIR = 'temp/';

		include "qrlib.php";
		
		//ofcourse we need rights to create temp dir
		if (!file_exists($PNG_TEMP_DIR))
			mkdir($PNG_TEMP_DIR);
		
		
		$filename = $PNG_TEMP_DIR.'error.png';
		
		//processing form input
		//remember to sanitize user input in real-life solution !!!
		//$errorCorrectionLevel = 'L';
		$errorCorrectionLevel = 'H';
		if (isset($_GET['level']) && in_array($_GET['level'], array('L','M','Q','H')))
			$errorCorrectionLevel = $_GET['level'];    

		//$matrixPointSize = 4;
		$matrixPointSize = 7;

		$from = ($page-1)*$ipp+1;
		$to = $page*$ipp;
		$qrDataArray = array();
		for($i = $from; $i <= $to; $i++) {
			array_push($qrDataArray, numberToAlphabet($i));
			//echo "<br/>... $i";
		}
		
		foreach ($qrDataArray as &$qr) {
			if ($qr != numberToAlphabet(alphabetToNumber($qr)) || $qr == "") {
				continue;
			}
			// user data
			$qrData = "http://flipdot.org/".$qr;
			//$qrData = $qr;
			//$filename = $PNG_TEMP_DIR.'test'.md5($qr.'|'.$errorCorrectionLevel.'|'.$matrixPointSize).'.png';
			$filename = $PNG_TEMP_DIR.$qr.'.png';
			if (!file_exists($filename))
				QRcode::png($qrData, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
			
			//display generated file
			if ($showCodes)
				echo '<p><img src="'.$PNG_WEB_DIR.basename($filename).'" /></p>';
		}
		
		echo "QR-Codes wurden erstellt.<br>Hier gehts zur druckbaren PDF:<br>";
		echo "<form action=\"".htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "utf-8")."\" method='post'>
		<input type='hidden' name='pdf' value='$page'>
		<input type='submit' name='submit' value='Drucken'>
		</form>";
	}

	function render_form () {
		echo "<h2>QR-Codes drucken</h2>
		<form action=\"".htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, "utf-8")."\" method='post'>
		<label for='pagenum'>Seitennummer:</label><input type='text' name='pagenum'></input>
		<input type='submit' name='submit' value='Druckvorlage'><br>
		<i>Es kann einige Sekunden dauern, bis die Druckvorlage erstellt wurde.</i><br>
		</form>";
	}
	
	function renderPDF($page = 1) {
		global $ipp;
		global $ipl;
		require_once 'fpdf/fpdf.php';
		$pdf = new FPDF("P", "mm", "A4");
		$pdf->AddPage();
		
		$from = ($page-1)*$ipp+1;
		$to = $page*$ipp;
		
		// in pixels
		//$w = 248;
		//$h = 351;
		
		// in mm
		$w = 21;
		$h = 29.7;
		for($i = $from; $i <= $to; $i++) {
			$x = ($i-1)%$ipl;
			$y = floor(($i-$from)/$ipl);
			//$pdf->Image('temp/'.numberToAlphabet($i).'.png', 5, 70, 33.78);
			$pdf->Image('temp/'.numberToAlphabet($i).'.png', $x*$w, $y*$h, $w, $h);
		}
		
		$pdf->Output();
	}
	
	function renderHeader() {
		echo <<<END
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
		<html>
		<head>
		<title>QR Code Generator</title>
		<style type="text/css">
			/** {
				padding: 0!important;
				margin: 0!important;
			}
			p {
				float: left;
				width: 248px;
				height: 351px;
			}*/
		</style>
		</head>
		<body>
END;
	}
	
	function renderFooter() {
		echo <<<END
		</body>
		</html>    
END;
	}


	$pagenum = false;
	if (isset($_GET['data'])) {
		// ze trimmage
		if (trim($_GET['data']) == '')
			die('Data cannot be empty.');
		if ($_GET['data'] != (int)$_GET['data'])
			die('Page number must be... yeah you guessed it... a number, duh.');
		$pagenum = $_GET['data'];
	} else if ($_POST['pagenum'] == (int)$_POST['pagenum']) {
		$pagenum = $_POST['pagenum'];
	}
	
	
	if($_POST['pdf']) {
		renderPDF($_POST['pdf']);
	} else if(!$pagenum) {
		renderHeader();
		render_form();
		renderFooter();
	} else if ($pagenum > 0) {
		renderHeader();
		renderCodes($pagenum);
		renderFooter();
	} else {
		renderHeader();
		echo "Bitte eine Nummer eingeben...";
		renderFooter();
	}

?>
