<?php 
$dirBase = dirname(dirname(__DIR__));
$dirs = array(
        'assets/js/plugins');
$strings = array();
foreach ($dirs as $dir) {
    $d = $dirBase . '/' . $dir;
    if ($dirHandle = opendir($d)) {
        while (false !== ($entry = readdir($dirHandle))) {
            $entryInfo = pathinfo($entry);
            if (isset($entryInfo['extension']) && $entryInfo['extension'] == 'js') {
                // Hay que mejorar la expresión pero ya.
                preg_match_all('/\$\.translate\([\"|\'](.*)",/i', file_get_contents($d . '/' . $entry), $result);
                $strings = array_merge($strings, $result[1]);
            }
        }
        closedir($dirHandle);
    }
}

$strings = array_unique($strings);
   
$translationFilePath = implode(
        DIRECTORY_SEPARATOR,
        array(
                $dirBase,
                'languages',
                'js-translations.php'
        )
);

$fileContents = "<?php\n\n";
$fileContents .= "return " . var_export($strings, true) . ";\n";
file_put_contents($translationFilePath, $fileContents);
echo count($strings) . " strings found.\n";
echo $translationFilePath . " ... Saved!\n";
exit;
?>