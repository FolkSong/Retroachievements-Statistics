<?php

namespace RA;

class RetroAchievementsWebApiClient
{
    public const API_URL = 'https://retroachievements.org/API/';

	public $ra_api_key;

	public function __construct($api_key)
	{
		$this->ra_api_key = $api_key;
	}

	private function AuthQS()
	{
		return "?y=" . $this->ra_api_key;
	}

    private function GetRAURL($target, $params = "")
    {
        return file_get_contents(self::API_URL . $target . self::AuthQS() . "&$params");
    }

    public function GetGameInfo($gameID)
    {
        return json_decode(self::GetRAURL("API_GetGame.php", "i=$gameID"));
    }

    public function GetGameInfoExtended($gameID)
    {
        return json_decode(self::GetRAURL("API_GetGameExtended.php", "i=$gameID"));
    }

    public function GetGameProgression($gameID)
    {
        return json_decode(self::GetRAURL("API_GetGameProgression.php", "i=$gameID"));
    }

    public function GetConsoleIDs()
    {
        return json_decode(self::GetRAURL('API_GetConsoleIDs.php'));
    }

    public function GetGameList($consoleID)
    {
        return json_decode(self::GetRAURL("API_GetGameList.php", "i=$consoleID"), true);
    }
}

$RAConn = new RetroAchievementsWebApiClient("APIKEY");

//Note: run from linux with php filename.php

//Be careful opening file in excel, make sure it's recognized at UTF-8. Check Pokémon.

//Use full list of console IDs
$ConsoleIDs = $RAConn->GetConsoleIDs();

$file = fopen('output.csv', 'w');
// Try to force Excel to recognize UTF-8
fprintf($file, "\xEF\xBB\xBF");
 
$header_data = array(
    'GameID',
    'Title',
    'Total Players',
    'Beats',
    'Masteries',
    'Time to Beat',
    'Time to Master',
    'Date',
    'Year',
    'Console',
    'Genre',
    'Developer',
    'Publisher'
);
fputcsv($file,$header_data);

$ConsoleIDArray = []; 
foreach ($ConsoleIDs as $key => $value) {
    if (property_exists($value, 'Active') && !$value->Active) {
        echo "Skipping console {$value->Name} (ID {$value->ID}): not active\n";
        continue;
    }

    if (property_exists($value, 'IsGameSystem') && !$value->IsGameSystem) {
        echo "Skipping entry {$value->Name} (ID {$value->ID}): not a game system\n";
        continue;
    }
		
	// Get List of games for specified console //
	$GameArray = $RAConn->GetGameList( $value->ID ); // had to add 'true' in the class to make this an array
	
	foreach ($GameArray as $game) {
		$gameID       = $game["ID"];
		$title        = $game["Title"];
		$consoleName  = $game["ConsoleName"];
		$numAchievements = $game["NumAchievements"];

		echo "($consoleName) $title\n";
		flush();		

		//Skip if game has no achievements
		if ($numAchievements) {
			$Progress = $RAConn->GetGameProgression( $gameID );
			$Game = $RAConn->GetGameInfo( $gameID ); //extended info no longer needed, basic info should be faster
	
			// Pull out year from date
            if (preg_match('/\b(\d{4})\b/', $Game->Released, $matches)) {
                $year = $matches[1];
            } else {
                $year = ""; // Fallback for hacks/demos with no date
            }

            // Write row for current game
			$row = array(
				$gameID,
				$title,
				$Progress->NumDistinctPlayers,
				$Progress->TimesUsedInHardcoreBeatMedian,
				$Progress->TimesUsedInMasteryMedian,
				round($Progress->MedianTimeToBeatHardcore/3600, 2),
				round($Progress->MedianTimeToMaster/3600, 2),
				$Game->Released,
				$year,
				$consoleName,
				$Game->Genre,
				$Game->Developer,
				$Game->Publisher
			);
			
			fputcsv($file, $row);
			
			break; // debug - uncomment to exit loop after first supported game of each console
			
		} // end of if ($numAchievements)
			
	  } // end of foreach ($GameArray as $game)
} // end of foreach ($ConsoleIDs as $key => $value)
fclose($file);  
 
?> 
