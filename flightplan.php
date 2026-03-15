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
</head>
<body>

<div class="ldgDIV" style="display: flex; flex-direction: column;">
    <a href="/"><img src="images/flightSmartTitleOG.png" width="200" alt="flightSmart"></a>
    <a class="ldgBtton" href="aircraftCalculator.html" title="Aircraft Calculator">Aircraft Calculator (using Community Data!)</a>
    <a class="ldgBttonSelected" href="flightplan.php" title="Flightplan Generator">Flightplan Generator (via Simbrief)</a>
    <a class="ldgBtton" href="kmlToIF.html" title="KML to IF coordinates">KML -> IF Coordinates</a> 
    <a class="ldgBtton" href="descent.html" title="TOD Calculator">Descent Calculator</a>

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
    <br><br>
</div>
                    
                    <button type="button" onclick="simbriefsubmit('flightplan.php');" class="mainButton">--Generate--</button>
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

    // --- SID & STAR ---
    $sid_ident = $simbrief->ofp_array['general']['sid_ident'];
    $sid = (is_string($sid_ident) && trim($sid_ident) !== '') ? $sid_ident : 'N/A';

    $star_ident = $simbrief->ofp_array['general']['star_ident'];
    $star = (is_string($star_ident) && trim($star_ident) !== '') ? $star_ident : 'N/A';
    
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
                <span class="dataHeader">Route</span><br>
                <span class="data">$route</span><br><br>

                <span class="dataHeader">Departure:</span><br>
                <span class="data" style="color: rgb(224,225,226);">$originName ($originICAO)</span><br>
                Runway: <span class="data">$originRunway</span><br>
                SID: <span class="data">$sid</span><br><br>
                
                <span class="dataHeader">Arrival: </span><br>
                <span class="data" style="color: rgb(224,225,226);">$destinationName ($destinationICAO)</span><br>
                Runway: <span class="data">$destinationRunway</span><br>
                STAR: <span class="data">$star</span><br><br>
                
                <span class="dataHeader">Flight Map: </span><br>
                <canvas id="mapCanvas" style="width: 100%;"></canvas>
                <img id="sourceMap" src="$mapSrc" crossorigin="anonymous" style="display:none;" onload="recolorMap()" />
            </div>
END;
}

// --- CLOSE THE TABLE AND ADD JAVASCRIPT ---
print <<<END
        </td>
    </tr>
</table>

<script>
        
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

// --- RAW DATA OUTPUT ---
if (isset($simbrief->ofp_array)) {
    // Encode the array into formatted JSON
    $rawJson = json_encode($simbrief->ofp_array, JSON_PRETTY_PRINT);
    
    // Output inside a styled container
    print <<<END
    <div style="margin: 30px auto; width: 80%; background-color: #1e1e1e; border: 1px solid #444; border-radius: 8px; padding: 15px; font-family: 'B612 Mono', monospace; color: #a9dc76; overflow-x: auto;">
        <h3 style="color: #fff; margin-top: 0;">Raw SimBrief Data</h3>
        <pre style="font-size: 12px;">$rawJson</pre>
    </div>
END;
}

print "</body>\n</html>";
?>
?>
