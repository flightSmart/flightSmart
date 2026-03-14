<?php

include 'api/api.php';

$xmllink = $_GET['ofp_id'];

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

if ($xmllink == null || $xmllink == false) {
    print <<<END
    <table class="flightData">
        <tr>
            <td>
                <div id="manufacturerButtons">
                    <form id="sbapiform">
                        <table>
                            <tr>
                                <td>Aircraft:</td>
                                <td>
                                    <select name="type">
                                        <option value="a320">A320</option>
                                        <option value="b738">B738</option>
                                    </select>
                                </td>
                            <tr>
                            <tr>
                                <td>Origin:</td>
                                <td><input name="orig" size="5" type="text" placeholder="ZZZZ" maxlength="4" value="KLAX"></td>
                            <tr>
                            <tr>
                                <td>Destination:</td>
                                <td><input name="dest" size="5" type="text" placeholder="ZZZZ" maxlength="4" value="KIAD"></td>
                            <tr>
                        </table>
                        <button type="button" onclick="simbriefsubmit('flightplan.php');" class="mainButton">--Generate--</button>
                    </form>
                </div>
            </td>
        </tr>
    </table>
    END;
} else {
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
                <div id="manufacturerButtons">

                    <span class="dataHeader">Route</span>
                    <br>
                    <span class="data">$route</span>
                    <br>
                    <br>

                    <span class="dataHeader">Departure:</span>
                    <br>
                    <span class="data" style="color: rgb(224,225,226);">$originName ($originICAO)</span>
                    <br>
                    RUNWAY: <span class="data">$originRunway</span>
                    <br>
                    SID: <span class="data">$sid</span>
                    <br>
                    <br>
                    
                    <span class="dataHeader">Arrival: </span> <br>
                    <span class="data" style="color: rgb(224,225,226);">$destinationName ($destinationICAO)</span>
                    <br>
                    RUNWAY: <span class="data">$destinationRunway</span>
                    <br>
                    SID: <span class="data">$star</span>
                    <br>
                    
                    <span class="dataHeader">Flight Map: </span> <br>
                    <img src="$flightMapDirectory$flightMap" width="500" />

                </div>
            </td>
        </tr>
    </table>
    END;
}

print <<<END
<div class="WebsiteCredits">
    <br>
    <a href="https://community.infiniteflight.com/t/the-unofficial-infinite-aircraft-calculator-using-community-data/869648" target="_blank" class="myCredit">website by darkeyes ↗</a>
</div>
</body>
</html>
END;
