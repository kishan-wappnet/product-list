<?php

$header = ['brand_name' => 'make','model_name' => 'model','colour_name' => 'colour',
'gb_spec_name' => 'capacity','network_name' => 'network','grade_name' => 'grade','condition_name' => 'condition'];

$requiredHeader = ['make' => 'brand_name','model' => 'model_name'];

$inputFileName = $outputFileName = '';
foreach($argv as $argvKey => $argument){
    if($argument === '--file'){
        $inputFileName = $argv[$argvKey+1];
    }
    else if (explode('=',$argument)[0] === '--unique-combinations'){
        $outputFileName = explode('=',$argument)[1];
    }
}

$combinationGroupCount = getCombineGroupCount($inputFileName, $requiredHeader,$header);

function convertTSVData($data){
    $delimiter = "\t";
    $csvHeader = explode($delimiter, $data);
    return array_map(function($headers){
        return trim(str_replace('"','',$headers));
    },$csvHeader);
}

function getCombineGroupCount($inputFileName, $requiredHeader,$header){
    $row = 1;
    $newGroupArr = [];
    $csvHeader = [];
    $maxAllowedFileRow = 10000;
    $filePath = dirname(__FILE__).'/examples/'.$inputFileName;
    if(!is_file($filePath)){
        die('This file is not found.');
    }
    
    if (($handle = fopen(dirname(__FILE__).'/examples/'.$inputFileName, "r")) !== FALSE) {
        $totalRows = 0;
        while($content = fgetcsv($handle)){
            $totalRows++;
        }

        if($totalRows > $maxAllowedFileRow){
            die('This file is very large in number of record.');
        }
        fseek($handle, 0);
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if($row === 1){
                $csvHeader = $data;
                if (str_contains($inputFileName, '.tsv')) {
                    $csvHeader = convertTSVData($data[0]);
                }
                foreach($requiredHeader as $valueHeader){
                    if(!in_array($valueHeader,$csvHeader)){
                        echo "$valueHeader header is not available in file.";
                        die;
                        return false;
                    }                    
                }
            }else{
                $productArr = [];
                $existRow = null;
                foreach($csvHeader as $dataKey => $value){
                    $convertedData = (str_contains($inputFileName, '.tsv')) ? convertTSVData($data[0]) : $data;
                    if($convertedData[$dataKey] === "" && in_array($value,$requiredHeader)){
                        echo "$header[$value] header value is empty at row no: $row";
                        die;
                    }
                    $productArr[$header[$value]] = $convertedData[$dataKey]; 
                }
                
                foreach ($newGroupArr as $newGroupKey => $newGroupValue) {
                    if(empty(array_diff_assoc($productArr,$newGroupValue))){
                        $existRow = $newGroupKey;
                    }
                }
                if($existRow){                    
                    $newGroupArr[$existRow]['count'] += 1;
                }else{
                    $newGroupArr[$row] = $productArr;
                    $newGroupArr[$row]['count'] = 1;
                }
            }
            $row++;
        }
        fclose($handle);
        return $newGroupArr;
    }
}

$file = fopen("output/$outputFileName","w");

$combinationGroupCount = array_values($combinationGroupCount);
fputcsv($file, array_keys($combinationGroupCount[0]));


foreach ($combinationGroupCount as $line) {
    fputcsv($file, $line);
} 

fclose($file);
?>