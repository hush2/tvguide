<?php

// (C) 2012 hush2 <hushywushy@gmail.com>

$name = 'Destiny Cable';

$provider = array(
    'All'                   => 0,
    'Local'                 => 1,
    'Sky Cable'             => 2,
    'Destiny Cable'         => 8,
    'Dream Satellite TV'    => 65,
);

// Add any available from channel from ClickTheCity (case sensitive).
$networks = array('HBO', 'PBO', 'FOX', 'ETC', '2nd Avenue', 'JackTV', 'ESPN', 'Disney Channel', 'Cartoon Network',
                  'National Geographic', 'Discovery Channel', 'History Channel');

// Send a POST request
function fetch($body)
{
    $ch = curl_init("http://www.clickthecity.com/tv/main.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, count(explode('&', $body)));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $result = curl_exec($ch);
    if (!$result) {
        die(curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

function parse($filename, $shows)
{
    global $networks;

    libxml_use_internal_errors(true);

    $doc = new DOMDocument();
    $doc->loadHTML(file_get_contents($filename));
    $xpath = new DOMXpath($doc);

    foreach ($networks as $network) {
        $items = $xpath->query("//img[@alt='{$network}']/../../../td");
        if ($items->length < 1) {
            break;
        }
        $shows[$network]['#'] = $items->item(1)->textContent;
        $anchors = $items->item(3)->getElementsByTagName('a');
        foreach ($anchors as $a) {
            $show_name = $a->textContent;
            $start_time = $a->getAttribute('href');
            if (preg_match('/starttime=(.*)&/', $start_time, $matches)) {
                $start_time = str_replace(array('%3A', '+'), array(':', ' '), $matches[1]);
            }
            $shows[$network][$start_time] = $show_name;
        }
    }
    return $shows;
}

//
$cableid    = $provider[$name];
$today      = date('Y-m-d');        // YY:MM:DD required for POST
$hour       = (int) date('H');      // current hour
$hour2      = $hour % 2 ? $hour - 1 : $hour; // fetch on even hour

$shows = array();
foreach (range($hour2, 24, 2) as $hour) {
    $hour = sprintf('%02d', $hour);  // 2 digit hour
    $filename = "{$today}_{$hour}.html";
    echo "Downloading $filename" . PHP_EOL;
    if (file_exists($filename)) {
        continue;
    }
    $body = "optCable=$cableid&optDate=$today&optTime=$hour:00:00&btnLoad=Go";
    $result = fetch($body);
    file_put_contents($filename, $result);
}
echo PHP_EOL;

$date = date('F j, Y');

$shows = array();
foreach (range($hour2, 24, 2) as $hour) {
    $hour = sprintf('%02d', $hour);
    $filename = "{$today}_{$hour}.html";
    echo "Parsing $filename" . PHP_EOL;
    if (file_exists($filename)) {
        $shows = parse($filename, $shows);
    }
}

$html = "<html><body><title>tvguide</title><table border='1'><tr><h3>$date schedule for $name<h3></tr>";
foreach ($shows as $show_name => $sched) {
    $chan = array_shift($sched); // Channel number
    $html .= "<tr><th colspan='2'>$show_name ($chan)</th></tr>";
    foreach ($sched as $start_time => $show_name) {
        $html .= "<tr><td " . (strpos($start_time, 'PM') ? "style='background-color: #c0c0c0'>" : '>');
        $html .= "$start_time</td><td>$show_name</td></tr>";
    }
}
$html .= "</table></body></html>";

echo "\nWriting file '$date.html'";
file_put_contents("$date.html", $html);
