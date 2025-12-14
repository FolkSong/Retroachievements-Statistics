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

$RAConn = new RetroAchievementsWebApiClient("2BW4GY7IDk8W2vz73zbXSJ9DRxhB1NUa");

//Note: run from linux with php filename.php

//Be careful opening file in excel, make sure it's recognized at UTF-8. Check Pokémon.

//Use full list of console IDs
$ConsoleIDs = $RAConn->GetConsoleIDs();

//Supported consoles as of 2025 (comment both lines out for full dynamic list)
//$ConsoleIDs_json = '[{"ID":"1","Name":"Mega Drive"},{"ID":"2","Name":"Nintendo 64"},{"ID":"3","Name":"SNES"},{"ID":"4","Name":"Game Boy"},{"ID":"5","Name":"Game Boy Advance"},{"ID":"6","Name":"Game Boy Color"},{"ID":"7","Name":"NES"},{"ID":"8","Name":"PC Engine"},{"ID":"9","Name":"Sega CD"},{"ID":"10","Name":"32X"},{"ID":"11","Name":"Master System"},{"ID":"12","Name":"PlayStation"},{"ID":"13","Name":"Atari Lynx"},{"ID":"14","Name":"Neo Geo Pocket"},{"ID":"15","Name":"Game Gear"},{"ID":"17","Name":"Atari Jaguar"},{"ID":"18","Name":"Nintendo DS"},{"ID":"19","Name":"Wii"},{"ID":"21","Name":"PlayStation 2"},{"ID":"23","Name":"Magnavox Odyssey 2"},{"ID":"24","Name":"Pokemon Mini"},{"ID":"25","Name":"Atari 2600"},{"ID":26,"Name":"DOS"},{"ID":"27","Name":"Arcade"},{"ID":"28","Name":"Virtual Boy"},{"ID":"29","Name":"MSX"},{"ID":"33","Name":"SG-1000"},{"ID":37,"Name":"Amstrad CPC"},{"ID":"38","Name":"Apple II"},{"ID":"39","Name":"Saturn"},{"ID":"40","Name":"Dreamcast"},{"ID":"41","Name":"PlayStation Portable"},{"ID":"43","Name":"3DO Interactive Multiplayer"},{"ID":"44","Name":"ColecoVision"},{"ID":"45","Name":"Intellivision"},{"ID":"46","Name":"Vectrex"},{"ID":"47","Name":"PC-8000/8800"},{"ID":"49","Name":"PC-FX"},{"ID":"51","Name":"Atari 7800"},{"ID":"53","Name":"WonderSwan"},{"ID":56,"Name":"Neo Geo CD"},{"ID":57,"Name":"Fairchild Channel F"},{"ID":63,"Name":"Watara Supervision"},{"ID":69,"Name":"Mega Duck"},{"ID":71,"Name":"Arduboy"},{"ID":72,"Name":"WASM-4"},{"ID":73,"Name":"Arcadia 2001"},{"ID":74,"Name":"Interton VC 4000"},{"ID":75,"Name":"Elektor TV Games Computer"},{"ID":76,"Name":"PC Engine CD/TurboGrafx-CD"},{"ID":77,"Name":"Atari Jaguar CD"},{"ID":78,"Name":"Nintendo DSi"},{"ID":80,"Name":"Uzebox"}]';
//$ConsoleIDs = json_decode($ConsoleIDs_json);

//PS2 only
//$ConsoleIDs_json = '[{"ID":"21","Name":"Playstation 2"}]';
//$ConsoleIDs = json_decode($ConsoleIDs_json);

//Gamecube only
//$ConsoleIDs_json = '[{"ID":"16","Name":"GameCube"}]';
//$ConsoleIDs = json_decode($ConsoleIDs_json);

//Wii only
//$ConsoleIDs_json = '[{"ID":"19","Name":"Wii"}]';
//$ConsoleIDs = json_decode($ConsoleIDs_json);

//Standalone only
//$ConsoleIDs_json = '[{"ID":"102","Name":"Standalone"}]';
//$ConsoleIDs = json_decode($ConsoleIDs_json);

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
