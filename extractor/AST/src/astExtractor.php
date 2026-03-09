<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/astBuilder.php';

$inputFile = __DIR__ . '/../../../processed_dataset.csv';
$outputFile = __DIR__ . '/../../../extraction-result/php_ast_features.csv';

$total = 0;
$success = 0;
$decodeFail = 0;
$parseFail = 0;

$builder = new astBuilder();

$inputHandle = fopen($inputFile, 'r');
$outputHandle = fopen($outputFile, 'w');

if ($inputHandle === false) {
    die("Error opening input file");
}

$header = fgetcsv($inputHandle);
$headerMap = array_flip($header);

$featureHeaderWritten = false;

while (($row = fgetcsv($inputHandle)) !== false){
    $filepath = $row[$headerMap['filepath']];
    $label = $row[$headerMap['label']];
    $codeb64 = $row[$headerMap['processed_code_b64']];

    $total++;

    $code = base64_decode($codeb64);
    
    if($code === false){
        $decodeFail++;
        continue;
    }

    $code = trim($code);
    
    if ($code === '') {
        $parseFail++;
        continue;
    }


    $result = $builder->builder($code);

    if ($result['ast_parse_error'] == 1) {
        $parseFail++;
    }

    $success++;

    $result['filepath'] = $filepath;
    $result['label'] = $label;

    if(!$featureHeaderWritten){
        fputcsv($outputHandle, array_keys($result));
        $featureHeaderWritten = true;
    }

    fputcsv($outputHandle, array_values($result));
}

fclose($inputHandle);
fclose($outputHandle);

echo "AST extraction completed\n";
echo "Total: $total\n";
echo "Success: $success\n";
echo "Decode Fail: $decodeFail\n";
echo "Parse Fail: $parseFail\n";

?>