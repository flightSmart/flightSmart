<?php

include 'api/api.php';

$xmllink = $_GET['ofp_id'];

// --- Header and Navigation Menu ---
print <<<END
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="images/flightSmartLogoTransparent.png">
<title>flightSmart</title>
<link href='https://fonts.googleapis.com/css?family=B612 Mono' rel='stylesheet'>
<link rel="stylesheet"  href="mainstyle.css" type="text/css" />
<script type="text/javascript" src="api/api.js"></script>
</head>
<body>

<div class="ldgDIV" style="display: flex; flex-direction: column;">
    <a href="/"><img src="images/flightSmartTitleOG.png" width="200" alt="flightSmart"></a>
    <a class="ldgBtton" href="aircraftCalculator.html" title="Aircraft Calculator">Aircraft Calculator (using Community Data!)</a>
    <a class="ldgBtton" href="kmlToIF.html" title="KML to IF coordinates">KML -> IF Coordinates</a> 
    <a class="ldgBtton" href="descent.html" title="TOD Calculator">Descent Calculator</a>
    <a class="ldgBttonSelected" href="flightplan.php" title="Flightplan Generator">Flightplan Generator (Simbrief)</a>
</div>
END;


// --- Form View (If no data is requested yet) ---
if ($xmllink == null || $xmllink == false) {
    print <<<END
    <table class="flightData">
        <tr>
            <td>
                <div id="manufacturerContainer">
                    <form id="sbapiform">
                        
                        <div id="manufacturerButtons" style="margin-bottom: 15px;">
                            <button type="button" id="airbusButton">Airbus</button>
                            <button type="button" id="boeingButton">Boeing</button>
                            <button type="button" id="bombardierButton">Bombardier</button>
                            <button type="button" id="embraerButton">Embraer</button>
                            <button type="button" id="McDonnellDouglasButton">McDonnell Douglas</button>
                        </div>

                        <div id="aircraftTypeDropdown" style="margin-bottom: 15px;">
                            <label for="aircraftType">Aircraft Type:</label>
                            <select id="aircraftType" name="type" required>
                                <option value="" disabled selected>Select Manufacturer</option>
                            </select>
                        </div>

                        <div id="aircraftLoadInput" style="margin-bottom: 15px;"> 
                            <label for="orig">Depart (ICAO):</label>
                            <input name="orig" size="5" type="text" placeholder="KLAX" maxlength="4" >
                            <br><br>
                            <label for="dest">Arrive (ICAO):</label>
                            <input name="dest" size="5" type="text" placeholder="EGLL" maxlength="4" >
                        </div>  
                        
                        <button type="button" onclick="simbriefsubmit('flightplan.php');" class="mainButton">--Generate--</button>
                    </form>
                </div>
            </td>
        </tr>
    </table>

    <script>
        function toggleInfo() {
            const infoBlock = document.getElementById("instructionText");
            if(infoBlock) {
                infoBlock.style.display = (infoBlock.style.display === "none" || infoBlock.style.display === "") ? "block" : "none";
            }
        }

        // --- Dropdown Update Function ---
        function updateAircraftDropdown(aircraftList) {
            const select = document.getElementById('aircraftType');
            
            // Clear any existing options
            select.innerHTML = '';
            
            // Create and add the default placeholder
            let defaultOpt = document.createElement('option');
            defaultOpt.text = "Select Type";
            defaultOpt.value = "";
            defaultOpt.disabled = true;
            defaultOpt.selected = true;
            select.add(defaultOpt);

            // --- SimBrief Code Overrides ---
            const simbriefOverrides = {
                'DC1F': 'DC10',
                'CL350': 'CL35'
            };

            // Loop through the provided array
            aircraftList.forEach(type => {
                let option = document.createElement('option');
                option.text = type;  
                option.value = simbriefOverrides[type] || type; 
                select.add(option);
            });
        }

        document.getElementById('airbusButton').addEventListener('click', function() {
            updateAircraftDropdown(['A220', 'A318', 'A319', 'A320', 'A321', 'A333', 'A339', 'A346', 'A359', 'A388']);
        });

        document.getElementById('boeingButton').addEventListener('click', function() {
            updateAircraftDropdown(['B712', 'B737', 'B38M', 'B738', 'B739', 'B742', 'B744', 'B748', 'B752', 'B763', 'B772', 'B77L', 'B77W', 'B77F', 'B788', 'B789', 'B78X']);
        });

        document.getElementById('bombardierButton').addEventListener('click', function() {
            updateAircraftDropdown(['CL350', 'CRJ2', 'CRJ7', 'CRJ9', 'CRJX', 'DH8D']);
        });

        document.getElementById('embraerButton').addEventListener('click', function() {
            updateAircraftDropdown(['E175', 'E190']);
        });

        document.getElementById('McDonnellDouglasButton').addEventListener('click', function() {
            updateAircraftDropdown(['DC10', 'DC1F', 'MD11', 'MD1F']);
        });
    </script>
    END;
} 
// --- Results View (If data has been generated) ---
else {
    $route = trim(preg_replace('/\/\S+/', '', implode(" ", array_slice(explode(" ", $simbrief->ofp_array['atc']['route']), 1))));
    $originName = $simbrief->ofp_array['origin']['name'];
    $originICAO = $simbrief->ofp_array['origin']['icao_code'];
    $originRunway = $simbrief->ofp_array['origin']['plan_rwy'];
    $navLog = $simbrief->ofp_array['navlog']['fix'];
    $sidObject = reset(array_filter($navLog, function ($item) {
        return $item['is_sid_star'] === '1';
    })) ?: false;
    $sid = $sidObject ? $sidObject['name'] : 'N/A';
    $destinationName = $simbrief->ofp_array['destination']['name'];
    $destinationICAO = $simbrief->ofp_array['destination']['icao_code'];
    $destinationRunway = $simbrief->ofp_array['destination']['plan_rwy'];
    $filteredArray = array_filter($navLog, function ($item) {
        return $item['is_sid_star'] === '1';
    });
    $secondToLastObject = array_reverse($filteredArray);
    $sidObject = isset($secondToLastObject[1]) ? $secondToLastObject[1] : false;
    $star = $sidObject ? $sidObject['name'] : 'N/A';
    $flightMapDirectory = $simbrief->ofp_array['images']['directory'];
    $flightMap = $simbrief->ofp_array['images']['map'][0]['link'];
    
    print <<<END
    <table class="flightData">
        <tr>
            <td>
                <div id="flightDataContainer">

                    <span class="dataHeader">Route</span>
                    <br>
                    <span class="data">$route</span>
                    <br>
                    <br>

                    <span class="dataHeader">Departure:</span>
                    <br>
                    <span class="data" style="color: rgb(224,225,226);">$originName ($originICAO)</span>
                    <br>
                    Runway: <span class="data">$originRunway</span>
                    <br>
                    SID: <span class="data">$sid</span>
                    <br>
                    <br>
                    
                    <span class="dataHeader">Arrival: </span> <br>
                    <span class="data" style="color: rgb(224,225,226);">$destinationName ($destinationICAO)</span>
                    <br>
                    Runway: <span class="data">$destinationRunway</span>
                    <br>
                    SID: <span class="data">$star</span><br>
                    <br>
                    
                    <span class="dataHeader">Flight Map: </span> <br>
                    <img src="$flightMapDirectory$flightMap" width="500" />

                </div>
            </td>
        </tr>
    </table>
    END;
}

// --- Footer and Credits ---
print <<<END
<div class="WebsiteCredits">
    <br>
    <a href="https://community.infiniteflight.com/t/the-unofficial-infinite-aircraft-calculator-using-community-data/869648" target="_blank" class="myCredit">website by darkeyes ↗</a>
</div>
</body>
</html>
END;
?>
