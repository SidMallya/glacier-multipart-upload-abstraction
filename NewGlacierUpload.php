<?php 
require 'vendor/autoload.php';
date_default_timezone_set('UTC');
ini_set('display_errors', 1);
use Aws\Glacier\GlacierClient;
use Aws\Glacier\MultipartUploader;
use Aws\Exception\MultipartUploadException;

$AccessKey = $_POST["AccessKey"];
$SecretAccessKey = $_POST["SecretAccessKey"];
$Region = $_POST["Region"];
$VaultName = $_POST["VaultName"];
$FilePath = $_POST["FilePath"];
$ArchiveDescription  = $_POST["ArchiveDescription"];
$PartSize  = $_POST["PartSize"] * 1024 * 1024;
$Concurrency = $_POST["Concurrency"];

$client = new GlacierClient(array(
  'version' => 'latest',
  'region'  => $Region, 
  'credentials' => array(
    'key'     => $AccessKey,
    'secret'  => $SecretAccessKey,
	)
));

$uploader = new MultipartUploader($client, $FilePath, [
    'vault_name' => $VaultName,
    'archive_description' => $ArchiveDescription,
    'part_size' => $PartSize,
    'concurrency' => $Concurrency,
]);

// Check if the retry should happen automatically 
if (isset($_POST["ResumeOption"]) && $_POST["ResumeOption"] == "AutoResume") {
	// Auto resume functionality
	do {
		try {
			$result = $uploader->upload();
			$archiveId = $result->get('archiveId');
			echo "Upload complete.\n";
			echo "Archive ID: $archiveId\n";		
		} catch (MultipartUploadException $e) {
			$uploader = new MultipartUploader($client, $FilePath, [
				'state' => $e->getState(),
			]);
			echo "Upload failed on ".date('j F Y H:i:s')." UTC. Resuming Upload...\n";
		}
	} while (!isset($result));
} else {
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
}
?>
