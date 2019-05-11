<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/New_York');

$scriptStartTime = time();
dump('Script execution started at ' . date('Y-m-d h:i:s A', $scriptStartTime));

function dump() {
	echo '<pre>';
	foreach (func_get_args() as $dump) {
		var_dump($dump);
	}
	echo '</pre>';
}

function curl($url, $postData = null) {
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url);

	if (!empty($postData)) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch); 
	curl_close($ch);  
	return $output;
}

function checkScore() {
	$repeatScoreCheck = false;

	$checkStartTime = time();
	dump('Score check started at ' . date('Y-m-d h:i:s A', $checkStartTime));

	$notify = false;

	$teamId = $_GET['teamId'];
	dump('$teamId=', $teamId);

	$teamDataUrl = 'https://statsapi.web.nhl.com/api/v1/teams/' . $teamId . '?expand=team.schedule.next';
	dump('$teamDataUrl=', $teamDataUrl);

	$teamData = curl($teamDataUrl);

	$teamDataJson = json_decode($teamData);
	if ($teamDataJson !== null) {

		$nextGameSchedule = $teamDataJson->teams[0]->nextGameSchedule;
		dump('$nextGameSchedule=', $nextGameSchedule);

		if ($nextGameSchedule !== null) {

			$nextGame = $nextGameSchedule->dates[0]->games[0];
			dump('$nextGame=', $nextGame);

			$gameId = $nextGame->gamePk;
			dump('$gameId=', $gameId);
			
			$gameStatus = $nextGame->status->statusCode;
			dump('$gameStatus=', $gameStatus);

			$repeatScoreCheck = ($gameStatus === '2' || $gameStatus === '3' || $gameStatus === '4');
			
			$homeTeamId = $nextGame->teams->home->team->id;
			dump('$homeTeamId=', $homeTeamId);
			
			$currentScore = $homeTeamId === $teamId ? $nextGame->teams->home->score : $nextGame->teams->away->score ;
			dump('$currentScore=', $currentScore);

			$fileName = 'games/' . $gameId . '-score.txt';
			dump('$fileName=', $fileName);

			$filePath = str_replace(basename(__FILE__), '', __FILE__);
			dump('$filePath=', $filePath);

			$file = $filePath . $fileName;
			dump('$file=', $file);

			if (!file_exists($file)) {
				file_put_contents($file, 0);
				dump('Score file did not exist, creating!');
			}

			$scoreFile = file_get_contents($file);
			dump('$scoreFile=', $scoreFile);

			$lastScore = (int) $scoreFile;
			dump('$lastScore=', $lastScore);

			if ($currentScore > $lastScore) {
				file_put_contents($file, $currentScore);
				dump('Score changed, updating!');
				$notify = true;
			}

		}

	}

	if ($notify) {
		dump('Trigger notification!');
		$alertData = curl('https://api.particle.io/v1/devices/events', 'name=' . $_GET['eventName'] . '&access_token=7be634f544f6b0f8348308d6b62d01b588453f07&private=true');
		dump('$alertData=', $alertData);
	} else {
		dump('Score not changed, not sending notification.');
	}

	$checkEndTime = time();
	dump('Score check completed at ' . date('Y-m-d h:i:s A', $checkEndTime) . ' and took ' . ($checkEndTime - $checkStartTime) . ' seconds');

	return $repeatScoreCheck;
}

$repeatScoreCheck = checkScore();
if ($repeatScoreCheck) {
	sleep(20);
	checkScore();
	sleep(20);
	checkScore();
}

$scriptEndTime = time();
dump('Script execution completed at ' . date('Y-m-d h:i:s A', $scriptEndTime) . ' and took ' . ($scriptEndTime - $scriptStartTime) . ' seconds');
?>
