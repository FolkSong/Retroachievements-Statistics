<?php

namespace RA;

class RetroAchievementsWebApiClient
{
    public const API_URL = 'https://retroachievements.org/API/';

    public $ra_user;

    public $ra_api_key;

    public function __construct($user, $api_key)
    {
        $this->ra_user = $user;
        $this->ra_api_key = $api_key;
    }

    private function AuthQS()
    {
        return "?z=" . $this->ra_user . "&y=" . $this->ra_api_key;
    }

    private function GetRAURL($target, $params = "")
    {
        return file_get_contents(self::API_URL . $target . self::AuthQS() . "&$params");
    }

    public function GetTopTenUsers()
    {
        return json_decode(self::GetRAURL('API_GetTopTenUsers.php'));
    }

    public function GetGameInfo($gameID)
    {
        return json_decode(self::GetRAURL("API_GetGame.php", "i=$gameID"));
    }

    public function GetGameInfoExtended($gameID)
    {
        return json_decode(self::GetRAURL("API_GetGameExtended.php", "i=$gameID"));
    }

    public function GetConsoleIDs()
    {
        return json_decode(self::GetRAURL('API_GetConsoleIDs.php'));
    }

    public function GetGameList($consoleID)
    {
        return json_decode(self::GetRAURL("API_GetGameList.php", "i=$consoleID"), true);
    }

    public function GetFeedFor($user, $count, $offset = 0)
    {
        return json_decode(self::GetRAURL("API_GetFeed.php", "u=$user&c=$count&o=$offset"));
    }

    public function GetUserRankAndScore($user)
    {
        return json_decode(self::GetRAURL("API_GetUserRankAndScore.php", "u=$user"));
    }

    public function GetUserProgress($user, $gameIDCSV)
    {
        $gameIDCSV = preg_replace('/\s+/', '', $gameIDCSV);    //	Remove all whitespace
        return json_decode(self::GetRAURL("API_GetUserProgress.php", "u=$user&i=$gameIDCSV"));
    }

    public function GetUserRecentlyPlayedGames($user, $count, $offset = 0)
    {
        return json_decode(self::GetRAURL("API_GetUserRecentlyPlayedGames.php", "u=$user&c=$count&o=$offset"));
    }

    public function GetUserSummary($user, $numRecentGames)
    {
        return json_decode(self::GetRAURL("API_GetUserSummary.php", "u=$user&g=$numRecentGames&a=5"));
    }

    public function GetGameInfoAndUserProgress($user, $gameID)
    {
        return json_decode(self::GetRAURL("API_GetGameInfoAndUserProgress.php", "u=$user&g=$gameID"));
    }

    public function GetAchievementsEarnedOnDay($user, $dateInput)
    {
        return json_decode(self::GetRAURL("API_GetAchievementsEarnedOnDay.php", "u=$user&d=$dateInput"));
    }

    public function GetAchievementsEarnedBetween($user, $dateStart, $dateEnd)
    {
        $dateFrom = strtotime($dateStart);
        $dateTo = strtotime($dateEnd);
        return json_decode(self::GetRAURL("API_GetAchievementsEarnedBetween.php", "u=$user&f=$dateFrom&t=$dateTo"));
    }

    public function GetUserGamesCompleted($user)
    {
        return json_decode(self::GetRAURL("API_GetUserCompletedGames.php", "u=$user"));
    }
}

//Note: run from linux with php filename.php

$RAConn = new RetroAchievementsWebApiClient("Username", "APIKEY");

//Use full console IDs (slower, goes through unsupported consoles)
$ConsoleIDs = $RAConn->GetConsoleIDs();

//Supported consoles as of Jan 2022
//$ConsoleIDs_json = '[{"ID":"1","Name":"Mega Drive"},{"ID":"2","Name":"Nintendo 64"},{"ID":"3","Name":"SNES"},{"ID":"4","Name":"Game Boy"},{"ID":"5","Name":"Game Boy Advance"},{"ID":"6","Name":"Game Boy Color"},{"ID":"7","Name":"NES"},{"ID":"8","Name":"PC Engine"},{"ID":"9","Name":"Sega CD"},{"ID":"10","Name":"32X"},{"ID":"11","Name":"Master System"},{"ID":"12","Name":"PlayStation"},{"ID":"13","Name":"Atari Lynx"},{"ID":"14","Name":"Neo Geo Pocket"},{"ID":"15","Name":"Game Gear"},{"ID":"17","Name":"Atari Jaguar"},{"ID":"18","Name":"Nintendo DS"},{"ID":"23","Name":"Magnavox Odyssey 2"},{"ID":"24","Name":"Pokemon Mini"},{"ID":"25","Name":"Atari 2600"},{"ID":"27","Name":"Arcade"},{"ID":"28","Name":"Virtual Boy"},{"ID":"29","Name":"MSX"},{"ID":"33","Name":"SG-1000"},{"ID":"38","Name":"Apple II"},{"ID":"39","Name":"Saturn"},{"ID":"40","Name":"Dreamcast"},{"ID":"41","Name":"PlayStation Portable"},{"ID":"43","Name":"3DO Interactive Multiplayer"},{"ID":"44","Name":"ColecoVision"},{"ID":"45","Name":"Intellivision"},{"ID":"46","Name":"Vectrex"},{"ID":"47","Name":"PC-8000/8800"},{"ID":"49","Name":"PC-FX"},{"ID":"51","Name":"Atari 7800"},{"ID":"53","Name":"WonderSwan"},{"ID":"63","Name":"Watara Supervision"}]';
//$ConsoleIDs = json_decode($ConsoleIDs_json);
  
$file = fopen('output.csv', 'w');
 
$header_data=array('GameID', 'Total Players (HC)','Total Players','Rarest Cheevo (HC)','Rarest Cheevo','Title','Date','Year','Console','Genre','Developer','Publisher','Total Cheevs (HC)','Total Cheevs');
fputcsv($file,$header_data);

$ConsoleIDArray = []; 
foreach ($ConsoleIDs as $key => $value) {
	if ($value->ID > 99) {
		break; //avoid hubs and events
	}
	
	// Get List of games for specified console //
	$GameArray = $RAConn->GetGameList( $value->ID ); // had to add 'true' in the class to make this an array
	$GameIDArray = []; 
	foreach ($GameArray as $key => $value) {
		array_push($GameIDArray, $value["ID"]); //array of Game IDs
		//echo $value["ID"] . "\n";
	  }

	foreach ($GameIDArray as $key => $value) {
		$Game = $RAConn->GetGameInfoExtended( $value );
		echo "(" . $Game->ConsoleName . ") " . $Game->Title . "\n";
		flush();
		
		//Skip if game has no achievements
		if ($Game->NumAchievements) {
			
			// Find rarest and total cheevos
			$rarest=9999;
			$rarestHC=9999;
			$totalcheevs=0;
			$totalcheevsHC=0;
			foreach ($Game->Achievements as $key => $value) {
				if ($value->NumAwarded < $rarest) {
					$rarest = $value->NumAwarded;
				}
				if ($value->NumAwardedHardcore < $rarestHC) {
					$rarestHC = $value->NumAwardedHardcore;
				}
				$totalcheevs=$totalcheevs+$value->NumAwarded;
				$totalcheevsHC=$totalcheevsHC+$value->NumAwardedHardcore;

			}	
			
			// Pull out year from date (only works for games released 1900-2099)
			preg_match("/.*((?:19|20)..).*/",$Game->Released,$matches); 
			
			// Write row for current game
			$row=array($Game->ID,$Game->NumDistinctPlayersHardcore,$Game->NumDistinctPlayersCasual,$rarestHC,$rarest,$Game->Title,$Game->Released,$matches[1],$Game->ConsoleName,$Game->Genre,$Game->Developer,$Game->Publisher,$totalcheevsHC,$totalcheevs); 
			fputcsv($file, $row);
			
			//break; // debug - uncomment to exit loop after first supported game of each console
			
		} // end of if ($Game->NumAchievements)
			
	  } // end of foreach ($GameIDArray as $key => $value)
} // end of foreach ($ConsoleIDs as $key => $value)
fclose($file);  
 
?> 
