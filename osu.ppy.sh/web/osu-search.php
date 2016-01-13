<?php
	// Return a osu!direct-like string for a specific song
	// from a bloodcat song array
	function bloodcatDirectString($arr)
	{
		$s = $arr['id']."|".$arr['artist']."|".$arr['title']."|".$arr['creator']."|".$arr['status']."|10.00000|".$arr['synced']."|".$arr['id']."|".$arr['beatmaps'][0]['id']."|0|0|0||";
		foreach ($arr['beatmaps'] as $diff)
			$s .= $diff['name']."@".$diff['mode'].",";
		$s = substr($s, 0, -1);
		return $s;
	}

	// Make sure mode and ranked status are set
	if (!isset($_GET["m"]) || !isset($_GET["r"]))
		die();

	// Default values for bloodcat query
	$bcM = "0";
	$bcS = "1,2,3,0";
	$bcQ = "";
	$bcPopular = false;
	$bcP = 1;

	// Modes
	if ($_GET["m"] == -1)
		$bcM = "0,1,2,3";	// All
	else
		$bcM = $_GET["m"];	// Specific mode

	// Ranked status
	// Bloodcat and osu! use differend
	// ranked status ids for beatmap
	switch ($_GET["r"])
	{
		// Ranked/Ranked played (Ranked)
		case 0: case 7: $bcS = "1"; break;
		// Qualified (Qualified)
		case 3: $bcS = "3"; break;
		// Pending/Help (Approved)
		case 2: $bcS = "2"; break;
		// Graveyard (Unranked)
		case 5: $bcS = "0"; break;
		// All
		case 4: $bcS = "1,2,3,0"; break;
	}

	// Search query
	// To search for Top rated, most played and newest beatmaps,
	// osu! sends a specific query to osu! direct search script.
	// Bloodcat uses a popular.php file instead to show all popular maps
	// If we have selected top rated/most played, we'll fetch popular.php's content
	// If we have selected newest, we'll fetch index.php content with no search query
	// Otherwise, we've searched for a specific map, so we pass the search query
	// to bloodcat
	if (isset($_GET["q"]) && !empty($_GET["q"]))
	{
		if ($_GET["q"] == "Top Rated" || $_GET["q"] == "Most Played")
			$bcPopular = true;
		else if($_GET["q"] == "Newest")
			$bcQ = "";
		else
			$bcQ = $_GET["q"];
	}
	else
	{
		$bcQ = "";
	}

	// Page
	// Osu's first page is 0
	// Bloodcat's first page is 1
	if (isset($_GET["p"]))
		$bcP = $_GET["p"]+1;

	// Replace spaces with + in query
	str_replace(' ', '+', $bcQ);

	// Build the URL with popular.php or normal bloodcat API
	$bcURL = $bcPopular ? "http://bloodcat.com/osu/popular.php?mod=json&m=".$bcM."&p=".$bcP : "http://bloodcat.com/osu/?mod=json&m=".$bcM."&s=".$bcS."&q=".$bcQ."&p=".$bcP;

	// Get API response and save it in an array
	$bcData = json_decode(file_get_contents($bcURL), true);

	// Output variable
	$output = "";

	// The first line is how many beatmaps
	// have been found. If it's >= 101, osu! will send a new query when
	// we reach the end of the list to load more beatmaps.
	// Bloodcat returns 40 beatmaps per page, so to load more maps when
	// we reach the end of the list on osu!, we need to set the number
	// of found variables to 101 instead of 40.
	// If this is not the first page or we have searched for something,
	// show the actual number of songs.
	if ($bcQ == "" && $bcP == 1)
		$output = 101;
	else
		$output = count($bcData);

	// Separator
	$output .= "\r\n";

	// Add to output beatmap info for each song
	foreach ($bcData as $song)
		$output .= bloodcatDirectString($song)."\r\n";

	// Done, output everything
	echo($output);
	// bmapid.osz|Artist|Song name|mapper|ranked(1/0)|idk(prob star rating)|last update|bmap id again|topic id tho|has video(0/1)|0|0||Diff 1@mode,Diff 2@mode
?>