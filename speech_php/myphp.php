<?php

//for speech to text
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;

//for natural language api
use Google\Cloud\Language\V1\Document;
use Google\Cloud\Language\V1\Document\Type;
use Google\Cloud\Language\V1\LanguageServiceClient;

//upload audio
$target_dir="uploads/";
$target_file= $target_dir.basename($_FILES["my_audio"]["name"]);
$upload_ok = 1;
//file size limit
if($_FILES["my_audio"["size"]>500000000){
    echo "Please choose a file less than 500mbs of size";
    $upload_ok= 0;
}
if ($upload_ok == 0){
    echo "Sorry, no file was uploaded"
}
else{(move_uploaded_file($_FILES["my_audio"]["tmp_name"],$target_file))

}

/** Uncomment and populate these variables in your code */
$audioFile = $_FILES["my_audio"]; //path from file picker
$transcript=''; //initiate the global variable
// change these variables if necessary
$encoding = AudioEncoding::LINEAR16;
$sampleRateHertz = 32000;
$languageCode = 'en-US';

// get contents of a file into a string
$content = file_get_contents($audioFile);

// set string as audio content
$audio = (new RecognitionAudio())
    ->setContent($content);

// set config
$config = (new RecognitionConfig())
    ->setEncoding($encoding)
    ->setSampleRateHertz($sampleRateHertz)
    ->setLanguageCode($languageCode);

// create the speech client
$client = new SpeechClient();

// create the asyncronous recognize operation
$operation = $client->longRunningRecognize($config, $audio);
$operation->pollUntilComplete();

if ($operation->operationSucceeded()) {
    $response = $operation->getResult();

    // each result is for a consecutive portion of the audio. iterate
    // through them to get the transcripts for the entire audio file.
    foreach ($response->getResults() as $result) {
        $alternatives = $result->getAlternatives();
        $mostLikely = $alternatives[0];
        $transcript = $mostLikely->getTranscript();
        $confidence = $mostLikely->getConfidence();
        //printf('Transcript: %s' . PHP_EOL, $transcript);
        //printf('Confidence: %s' . PHP_EOL, $confidence);
    }
} else {
    print_r($operation->getError());
}

//$client->close();

/** Uncomment and populate these variables in your code */
$text = $transcript;

// Make sure we have enough words (20+) to call classifyText
if (str_word_count($text) < 20) {
    printf('20+ words are required to classify text.' . PHP_EOL);
    return;
}
$languageServiceClient = new LanguageServiceClient();
try {
    // Create a new Document, add text as content and set type to PLAIN_TEXT
    $document = (new Document())
        ->setContent($text)
        ->setType(Type::PLAIN_TEXT);

    // Call the analyzeSentiment function
    $response = $languageServiceClient->classifyText($document);
    $categories = $response->getCategories();
    // Print document information
    foreach ($categories as $category) {
        printf('Category Name: %s' . PHP_EOL, $category->getName());
        printf('Confidence: %s' . PHP_EOL, $category->getConfidence());
        print(PHP_EOL);
    }
} finally {
    $languageServiceClient->close();
}
$client->close();
?>