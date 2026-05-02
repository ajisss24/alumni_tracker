<?php
$h = fopen('alumni.csv', 'r');
fgetcsv($h);
$names = [];
for ($i = 0; $i < 100; $i++) {
    $r = fgetcsv($h);
    if ($r) {
        echo $r[0] . " | " . $r[1] . " | " . $r[4] . " | " . $r[5] . "\n";
    }
}
fclose($h);
