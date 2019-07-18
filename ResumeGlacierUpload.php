<?php 
require 'vendor/autoload.php';
date_default_timezone_set('UTC');
ini_set('display_errors', 1);
use Aws\Glacier\GlacierClient;
use Aws\Glacier\MultipartUploader;
use Aws\Exception\MultipartUploadException;

$p = fopen("Params.txt", "r") or die("Unable to open Params.txt file!");
$AccessKey = fgets($p);
$SecretAccessKey = fgets($p);
$Region = fgets($p);
$VaultName = fgets($p);
$FilePath = fgets($p);
$Concurrency = fgets($p);
fclose($p);

$client = new GlacierClient(array(
  'version' => 'latest',
  'region'  => $Region, 
  'credentials' => array(
    'key' => $AccessKey,
    'secret'  => $SecretAccessKey,
	)
));

$g = file_get_contents('State.txt') or die("Unable to open State.txt file!");
$oldstate = unserialize($g); 

$uploader = new MultipartUploader($client, $FilePath, [
    'vault_name' => $VaultName,
    'concurrency' => $Concurrency,
    'state' => $oldstate,
]);

try {
    $result = $uploader->upload();
    $archiveId = $result->get('archiveId');
    echo "Upload complete.\n";
    echo "Archive ID: $archiveId\n";
} catch (MultipartUploadException $e) {

	// If the upload fails, get the state of the upload
	$state = $e->getState();
	$p = serialize($state);
	file_put_contents('State.txt', $p);
	
	// Save input parameters to a file
	$p = fopen("Params.txt", "w") or die("Unable to open Params.txt file!");
	$txt = $AccessKey."\n";
	fwrite($p, $txt);
	$txt = $SecretAccessKey."\n";
	fwrite($p, $txt);
	$txt = $Region."\n";
	fwrite($p, $txt);
	$txt = $VaultName."\n";
	fwrite($p, $txt);
	$txt = $FilePath."\n";
	fwrite($p, $txt);
	$txt = $Concurrency."\n";
	fwrite($p, $txt);
	fclose($p);
	
	// Options on how you want to resume upload
	echo "Upload failed. Click ";
	echo '<a href="ResumeGlacierUpload.php">here</a>';
	echo " to resume now or run ResumeGlacierUpload.php to resume later (must be within 24 hours).\n";

}

?>
