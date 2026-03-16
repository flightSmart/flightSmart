<?php

include 'api/api.php';

$xmllink = $_GET['ofp_id'] ?? null;

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
<style>
    @keyframes sharp-blink {
        50% { visibility: hidden; }
    }
    .blink-animation {
        animation: sharp-blink 0.8s step-end infinite;
    }

    /* --- INFO ICON & INSTRUCTION STYLING --- */
    .action-row {
      display: flex;
      justify-content: space-between; 
      align-items: center;
      margin-top: 15px;
      width: 100%; 
    }

    .info-icon {
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      border: 1px solid rgb(131, 131, 131);
      color: rgb(131, 131, 131);
      font-family: serif;
      font-size: 12px;
      transition: 0.3s;
      user-select: none;
    }
    .info-icon:hover { background-color: rgba(194, 194, 194, 0.2); }

    #instructionText {
      display: none;
      margin-top: 15px;
      margin-bottom: 15px;
      padding: 10px;
      background-color: rgba(50, 50, 50, 0.5);
      border-left: 2px solid rgb(100, 195, 153);
    }
</style>


</head>
<body>

<div class="ldgDIV" style="display: flex; flex-direction: column;">
    <a href="/index.html"><img src="images/flightSmartTitleOG.png" width="200" alt="flightSmart"></a>
    <a class="ldgBtton" href="/aircraftCalculator.html" title="Aircraft Calculator">Aircraft Calculator (using Community Data!)</a>
    <a class="ldgBttonSelected" href="flightplan.php" title="Flightplan Generator">Flightplan Generator (via Simbrief)</a>
    <div class="button-container">
        <a class="ldgBtton" href="/kmlToIF.html" title="KML to IF">KML -> IF</a> 
        <a class="ldgBtton" href="/descent.html" title="TOD Calculator">TOD Calculator</a>
    </div>
    <div class="button-container">
        <a class="ldgBtton" href="/autohold.html" title="Auto Hold">Auto Hold</a> 
    </div>
</div>

<table class="flightData">
    <tr>
        <td>
            <div id="manufacturerContainer">
                <form id="sbapiform">
                    <div id="manufacturerButtons" style="margin-bottom: 0px;">
                        <button type="button" id="airbusButton">Airbus</button>
                        <button type="button" id="boeingButton">Boeing</button>
                        <button type="button" id="bombardierButton">Bombardier</button>
                        <button type="button" id="embraerButton">Embraer</button>
                        <button type="button" id="McDonnellDouglasButton">McDonnell Douglas</button>
                    </div>

                    <div id="aircraftTypeDropdown" style="margin-bottom: 0px;">
                        <label for="aircraftType">Aircraft Type:</label>
                        <select id="aircraftType" name="type" required>
                            <option value="" disabled selected>Select Manufacturer</option>
                        </select>
                        <br><br>
                    </div>

                    <div id="aircraftLoadInput" style="margin-bottom: 0px;"> 
                        <label for="orig">Depart (ICAO):</label>
                        <input name="orig" size="5" type="text" placeholder="KLAX" maxlength="4" 
                               style="background-color: rgb(50, 50, 50); color: rgb(224, 225, 226); border: 1px solid rgb(131, 131, 131); font-family: 'B612 Mono', monospace; font-size: 12px; padding: 3px; outline: none;">
                        <br>
                        <label for="dest">Arrive (ICAO):</label>
                        <input name="dest" size="5" type="text" placeholder="EGLL" maxlength="4" 
                               style="background-color: rgb(50, 50, 50); color: rgb(224, 225, 226); border: 1px solid rgb(131, 131, 131); font-family: 'B612 Mono', monospace; font-size: 12px; padding: 3px; outline: none;">
                        
                        <div class="action-row">
                            <button type="button" onclick="showLoading(); simbriefsubmit('flightplan.php');" class="mainButton">--Generate--</button>
                            <div class="info-icon" onclick="toggleInfo()" title="Click for instructions">i</div>
                        </div>
                    </div>
                    
                    <div id="instructionText">
                        <p class="dataHeader" style="margin: 0; font-size: 13px; font-weight:350;"> 
                          Select an aircraft manufacturer and model, then enter your <strong>Departure and Arrival</strong> ICAO codes. <br> <br>
                          This tool connects directly to the <strong>Simbrief API</strong> to generate a realistic flight plan optimized for your route. <br><br>
                          Once generated, you will be provided with estimated enroute times, departure/arrival procedures, and a map preview.
                        </p>
                    </div>

                    <p id="loadingMessage" class="blink-animation" style="display:none; color: rgb(224, 225, 226);">Generating...</p>

                </form>
            </div>
    </td>
</tr>
END;

// --- RESULTS LOGIC (Inserts into the same TD if data exists) ---
if ($xmllink != null && $xmllink != false) {
    
    // --- Origin & Destination Data ---
    $originName = $simbrief->ofp_array['origin']['name'];
    $originICAO = $simbrief->ofp_array['origin']['icao_code'];
    $originRunway = $simbrief->ofp_array['origin']['plan_rwy'];
    
    $destinationName = $simbrief->ofp_array['destination']['name'];
    $destinationICAO = $simbrief->ofp_array['destination']['icao_code'];
    $destinationRunway = $simbrief->ofp_array['destination']['plan_rwy'];

    // --- Route Formatting ---
    $baseRoute = trim(preg_replace('/\/\S+/', '', implode(" ", array_slice(explode(" ", $simbrief->ofp_array['atc']['route']), 1))));
    $cleanRoute = trim(preg_replace('/\s+/', ' ', str_replace('DCT', '', $baseRoute)));
    $route = $originICAO . " " . $cleanRoute . " " . $destinationICAO;

    // --- Time & Enroute Data ---
    $eteSeconds = $simbrief->ofp_array['times']['est_time_enroute'];
    $eteHours = floor($eteSeconds / 3600);
    $eteMinutes = floor(($eteSeconds % 3600) / 60);
    $eteFormatted = sprintf('%02d:%02d', $eteHours, $eteMinutes);
    $distance = $simbrief->ofp_array['general']['route_distance'];

    // --- SID & STAR ---
    $sid_ident = $simbrief->ofp_array['general']['sid_ident'];
    $sid = (is_string($sid_ident) && trim($sid_ident) !== '') ? $sid_ident : 'N/A';

    $star_ident = $simbrief->ofp_array['general']['star_ident'];
    $star = (is_string($star_ident) && trim($star_ident) !== '') ? $star_ident : 'N/A';
    
    // --- Remarks Logic ---
    $sysRmks = $simbrief->ofp_array['general']['sys_rmk'] ?? [];

    $remarksHtml = "";

    // Handle System Remarks (Looping through the array)
    if (is_array($sysRmks) && !empty($sysRmks)) {
        $remarksHtml .= '<span class="dataHeader" style="color: rgb(224,225,226)">System Remarks:</span><br>';
        foreach ($sysRmks as $msg) {
            $remarksHtml .= '<span class="data" style="color: #fa9822;">' . htmlspecialchars($msg) . '</span><br>';
        }
        $remarksHtml .= '<br>';
    }

    // --- Alternate Data ---
    $alternate = $simbrief->ofp_array['alternate'] ?? null;
    $altDisplay = "";

    if ($alternate && !empty($alternate['icao_code'])) {
        $altICAO = $alternate['icao_code'];
        $altName = $alternate['name'];
        $altDist = $alternate['distance'];
        
        // Convert Alternate ETE
        $altEteSec = $alternate['ete'] ?? 0;
        $altH = floor($altEteSec / 3600);
        $altM = floor(($altEteSec % 3600) / 60);
        $altTime = sprintf('%02d:%02d', $altH, $altM);
    } else {
        $altICAO = "N/A";
        $altName = "";
        $altDist = "";
        $altTime = "";
    }

    // --- Images & PHP Proxy ---
    $flightMapDirectory = $simbrief->ofp_array['images']['directory'];
    $flightMapName = $simbrief->ofp_array['images']['map'][0]['link'];
    $mapUrl = $flightMapDirectory . $flightMapName;

    // Fetch the image in PHP to bypass browser CORS restrictions for the Canvas
    $mapContext = stream_context_create(['http' => ['timeout' => 5]]);
    $mapImageData = @file_get_contents($mapUrl, false, $mapContext);

    if ($mapImageData !== false) {
        $mapSrc = 'data:image/png;base64,' . base64_encode($mapImageData);
    } else {
        // Fallback to direct URL if the server blocks file_get_contents
        $mapSrc = $mapUrl;
    }

    //            <hr style="border: 1px solid #444; margin: 15px 0;">

    print <<<END
    <tr>
        <td>
            <div id="resultsContainer">
                <span class="dataHeader">Route:</span><br>
                <span class="data">$route</span><br><br>

                <span class="dataHeader">Time and Distance:: </span> <br>
                ETE: <span class="data">$eteFormatted</span><br>
                Route Distance: <span class="data">$distance nm</span><br><br>


                <span class="dataHeader">Departure:</span><br>
                <span class="data" style="color: rgb(224,225,226);">$originName ($originICAO)</span><br>
                Runway: <span class="data">$originRunway</span><br>
                SID: <span class="data">$sid</span><br><br>
                
                <span class="dataHeader">Arrival: </span><br>
                <span class="data" style="color: rgb(224,225,226);">$destinationName ($destinationICAO)</span><br>
                Runway: <span class="data">$destinationRunway</span><br>
                STAR: <span class="data">$star</span><br><br>
                
                <span class="dataHeader">Alternate: </span><br>
                <span class="data" style="color: rgb(224,225,226);">$altName ($altICAO)</span><br>
                ETE: <span class="data">$altTime</span><br>
                Route Distance: <span class="data">$altDist nm</span><br><br>
                


                $remarksHtml
                
                <span class="dataHeader">Flight Map: </span><br>
                <canvas id="mapCanvas" style="width: 100%;"></canvas>
                <img id="sourceMap" src="$mapSrc" crossorigin="anonymous" style="display:none;" onload="recolorMap()" />

                <span class="dataHeader">Data from Simbrief </span><br>
                <a class="data" target="_blank" href="https://dispatch.simbrief.com/briefing/latest">Data from Simbrief</a>
            </div>
END;
}

// --- CLOSE THE TABLE AND ADD JAVASCRIPT ---
print <<<END
        </td>
    </tr>
</table>

<script>
        // --- Toggle Info Function ---
        function toggleInfo() {
            const infoBlock = document.getElementById("instructionText");
            infoBlock.style.display = (infoBlock.style.display === "none" || infoBlock.style.display === "") ? "block" : "none";
        }

        function showLoading() {
            const loader = document.getElementById('loadingMessage');
            if (loader) {
                loader.style.display = 'block';
            }
        }

        const img = document.getElementById('sourceMap');

        // We must wait for the image to load before processing
        if (img) {
             img.onload = function() {
              processAndRecolorMap();
         };
        }


        function processAndRecolorMap() {
            const canvas = document.getElementById('mapCanvas');
            if (!img || !canvas) return;

            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            
            // --- NEW CODE: Calculate dimensions for cropping ---
            const originalWidth = img.naturalWidth;
            const originalHeight = img.naturalHeight;
            const pixelsToCropFromBottom = 35;
            
            // The canvas will be shorter than the original image
            const newHeight = originalHeight - pixelsToCropFromBottom;
            
            // Set canvas dimensions to the new, cropped size
            canvas.width = originalWidth;
            canvas.height = newHeight;
            
            // --- UPDATED drawImage: Draw the source onto the destination, effectively cropping ---
            // drawImage(source, sx, sy, sWidth, sHeight, dx, dy, dWidth, dHeight)
            // We are using the top-left (0,0) of the source and destination
            ctx.drawImage(img, 
                0, 0, originalWidth, newHeight, // Source rectangle (the part we want to keep)
                0, 0, originalWidth, newHeight  // Destination rectangle (where it goes on the canvas)
            );

            try {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;

                // Loop through every pixel (RGBA format)
                for (let i = 0; i < data.length; i += 4) {
                    let r = data[i];
                    let g = data[i + 1];
                    let b = data[i + 2];


                    // 1. Water Detection (SimBrief Blue is ~ 164, 206, 237)
                    if (Math.abs(r - 134) < 70 && Math.abs(g - 205) < 70 && Math.abs(b - 249) < 70) {
                        data[i] = 30;      // #1e
                        data[i + 1] = 30;
                        data[i + 2] = 30;
                    }
                    // 2. Land Detection (SimBrief Tan is ~ 244, 236, 195)
                    else if (Math.abs(r - 249) < 60 && Math.abs(g - 249) < 60 && Math.abs(b - 209) < 60) {
                        data[i] = 93;      // #5d
                        data[i + 1] = 93;
                        data[i + 2] = 93;
                    }
                    // 3. Route/Line Detection (Dark pixels)
                    else if (Math.abs(r - 101) < 80 && Math.abs(g - 108) < 80 && Math.abs(b - 111) < 80) {
                        data[i] = 100;     // #64
                        data[i + 1] = 196; // #c4
                        data[i + 2] = 154; // #9a
                    }

                    else if (Math.abs(r - 0) < 80 && Math.abs(g - 0) < 80 && Math.abs(b - 0) < 80) {
                        data[i] = 100;     // #64
                        data[i + 1] = 196; // #c4
                        data[i + 2] = 154; // #9a
                    }
                    
                    else if (Math.abs(r - 233) < 80 && Math.abs(g - 233) < 80 && Math.abs(b - 83) < 80) {
                        data[i] = 30;      // #1e
                        data[i + 1] = 30;
                        data[i + 2] = 30;
                    }

                }

                // Put the modified pixels back onto the canvas
                ctx.putImageData(imageData, 0, 0);
            } catch (e) {
                console.error("CORS Error: Ensure you are running this on a local server.", e);
            }
        }

    // --- AIRCRAFT DROPDOWN LOGIC ---
    function updateAircraftDropdown(aircraftList) {
        const select = document.getElementById('aircraftType');
        select.innerHTML = '';
        let defaultOpt = document.createElement('option');
        defaultOpt.text = "Select Type";
        defaultOpt.value = "";
        defaultOpt.disabled = true;
        defaultOpt.selected = true;
        select.add(defaultOpt);

        const simbriefOverrides = { 'DC1F': 'DC10', 'CL350': 'CL35' };

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

    document.getElementById('aircraftType').addEventListener('change', function() {
        const selectedText = this.options[this.selectedIndex].text;
        localStorage.setItem('savedAircraft', selectedText);
    });

    function restoreSavedAircraft() {
        const saved = localStorage.getItem('savedAircraft');
        if (!saved) return;

        const fleet = {
            'airbusButton': ['A220', 'A318', 'A319', 'A320', 'A321', 'A333', 'A339', 'A346', 'A359', 'A388'],
            'boeingButton': ['B712', 'B737', 'B38M', 'B738', 'B739', 'B742', 'B744', 'B748', 'B752', 'B763', 'B772', 'B77L', 'B77W', 'B77F', 'B788', 'B789', 'B78X'],
            'bombardierButton': ['CL350', 'CRJ2', 'CRJ7', 'CRJ9', 'CRJX', 'DH8D'],
            'embraerButton': ['E175', 'E190'],
            'McDonnellDouglasButton': ['DC10', 'DC1F', 'MD11', 'MD1F']
        };

        for (const [btnId, list] of Object.entries(fleet)) {
            if (list.includes(saved)) {
                document.getElementById(btnId).click(); 
                const select = document.getElementById('aircraftType');
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].text === saved) {
                        select.selectedIndex = i;
                        break;
                    }
                }
                break;
            }
        }
    }

    window.addEventListener('DOMContentLoaded', restoreSavedAircraft);
</script>

<div class="WebsiteCredits">
    <br>
    <a href="https://community.infiniteflight.com/t/the-unofficial-infinite-aircraft-calculator-using-community-data/869648" target="_blank" class="myCredit">website by darkeyes ↗</a>
</div>
END;
?>
