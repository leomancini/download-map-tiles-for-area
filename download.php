<?php
	require('secrets.php');

	set_time_limit(0);

	// These are in Apple Notes app, copy/paste into secrets.php in the same directory
	$mapbox = [
		'accessToken' => $_SECRETS['mapboxAccessToken'],
		'username' => $_SECRETS['mapboxUsername'],
		'style' => $_SECRETS['mapboxStyle']
	];

	function getTile($lat, $lon, $zoom) {
		$xtile = floor((($lon + 180) / 360) * pow(2, $zoom));
		$ytile = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) /2 * pow(2, $zoom));

		return [
			'x' => $xtile,
			'y' => $ytile
		];
	}

	function getUrl($x, $y, $zoom, $size) {
		global $mapbox;

		return "https://api.mapbox.com/styles/v1/".$mapbox['username']."/".$mapbox['style']."/tiles/".$size."/".$zoom."/".$x."/".$y."@2x?access_token=".$mapbox['accessToken'];
	}

	function saveImage($url, $location) {
		$curl = curl_init($url);

		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);

		$data = curl_exec($curl);

		curl_close($curl);

		if(file_exists($location)) { unlink($location); }
		
		$file = fopen($location, 'x');
		
		fwrite($file, $data);
		fclose($file);
	}

	function getAllTiles($boundingBox) {
		$topLeftTile = getTile($boundingBox['topLeft']['lat'], $boundingBox['topLeft']['lng'], 16);
		$bottomRightTile = getTile($boundingBox['bottomRight']['lat'], $boundingBox['bottomRight']['lng'], 16);

		return [
			'size' => 256,
			'xStart' => $topLeftTile['x'],
			'xEnd' => $bottomRightTile['x'],
			'yStart' => $topLeftTile['y'],
			'yEnd' => $bottomRightTile['y'],
			'zoom' => 16,
			'numTiles' => abs($bottomRightTile['x'] - $topLeftTile['x']) * abs($bottomRightTile['y'] - $topLeftTile['y'])
		];
	}

	$name = $_GET['name'];

	// Use https://www.keene.edu/campus/maps/tool/ for getting lat/lng
	$boundingBox = [
		'topLeft' => [
			'lat' => '42.3762204',
			'lng' => '-71.0877371'
		],
		'bottomRight' => [
			'lat' => '42.3424792',
			'lng' => '-71.0357379'
		]
	];

	$tileInfo = getAllTiles($boundingBox);

	$tilesDirectory = './tiles/'.$name.'@'.$tileInfo['zoom'].'zoom';

	if (!file_exists($tilesDirectory)) {
		mkdir($tilesDirectory, 0777, true);
	}
?>
<pre><?php
	print_r($tileInfo);

	echo '<br><br>';

	echo 'Downloading map tiles...';

	echo '<br><br>';

	for ($x = $tileInfo['xStart']; $x <= $tileInfo['xEnd']; $x++) {
		for ($y = $tileInfo['yStart']; $y <= $tileInfo['yEnd']; $y++) {
			$url = getUrl($x, $y, $tileInfo['zoom'], $tileInfo['size']);

			if (!file_exists($tilesDirectory.'/'.$x)) {
				mkdir($tilesDirectory.'/'.$x, 0777, true);
			}

			saveImage($url, $tilesDirectory.'/'.$x.'/'.$y.'.jpg');

			echo $url.'<br>';
		}

		echo '<br>';
	}
?></pre>
