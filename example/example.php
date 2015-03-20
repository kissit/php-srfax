<?php
/* START: Example configuration */

// Set these options for the SRFax API
$API_URL = 'https://www.srfax.com/SRF_SecWebSvc.php';
$API_USER = getenv('API_USER');
$API_PASS = getenv('API_PASS');

// Sender settings
$SENDER_FAX = getenv('SENDER_FAX');
$SENDER_EMAIL = getenv('SENDER_EMAIL');

// Recipient Settings
$TEST_TO = "18553301239"; // http://faxtoy.net

/* END: Example configuration */

require_once "../srfax.php";

$short_options = 'h';
$long_options = array(  'help' => 'Show this help screen',
                        'func:' => 'Name of func to run (required)',
						'id::' => 'FaxDetailsID to use for func (if applicable)',
                        'file::' => 'File to use for input to send a fax (if applicable)',
                        'viewed::' => 'Viewed state to set for fax - Y or N (if applicable)',
                        'dir::' => 'Direction to use for func - IN or OUT (if applicable)');
						
// Get our command line options & validate required items
$options = getopt($short_options, array_keys($long_options));
if(isset($options['h']) || isset($options['help']) || !isset($options['func'])) {
	echo("\n");
	echo("This tool is used to run examples of the SRFax API lib.  Options are as follows\n ");
	foreach($long_options as $name => $message) {
		$name = str_replace(':', '', $name);
		echo( "\t--$name=$message\n" );
	}
	echo("\n");
	exit();
}
$func = $options['func'];
$id = isset($options['id']) ? $options['id'] : null;
$file = isset($options['file']) ? $options['file'] : null;
$viewed = isset($options['viewed']) ? $options['viewed'] : null;
$dir = isset($options['dir']) ? $options['dir'] : null;
$out = '';

// Initialize srfax
$srfax = new srfax($API_USER, $API_PASS, $API_URL, $SENDER_FAX, $SENDER_EMAIL);

// Queue a test fax
if($func == 'queue' && file_exists($file)) {
    try {
        $filedata = base64_encode(file_get_contents($file));
        $options = array('sCPSubject' => 'Test Fax', 'sFileName_1' => $file, 'sFileContent_1' => $filedata);
        $test = $srfax->Queue_Fax($TEST_TO, $options);
        $out = "Successfully queued fax id: $test";
    } catch (Exception $e) {
        $out = "Error: " . $e->getMessage();
    }
}

// Retrieve an incoming fax
if($func == 'retrieve' && !empty($id) && !empty($dir)) {
    try {
        $test = $srfax->Retrieve_Fax($dir, $id);
        if(!empty($test)) {
            file_put_contents("$id.pdf", $test);
            $out = "Successfully retrieved file as $id.pdf";
        }
    } catch (Exception $e) {
        $out = "Error: " . $e->getMessage();
    }
}

// Attempt to stop a queued fax
if($func == 'stop' && !empty($id)) {
    try {
        $test = $srfax->Stop_Fax($id);
        $out = "Stop request response: \n" . print_r($test, true);
    } catch (Exception $e) {
        $out = "Error: " . $e->getMessage();
    }
}

// Get the status of a fax details id
if($func == 'status' && !empty($id)) {
    try {
        $test = $srfax->Get_FaxStatus($id);
        $out = "Fax properties: \n" . print_r($test, true);
    } catch (Exception $e) {
        $out = "Error: " . $e->getMessage();
    }
}

// Delete a fax details id
if($func == 'delete' && !empty($id) && !empty($dir)) {
    try {
        $test = $srfax->Delete_Fax($dir, $id);
        $out = "Fax deleted\n";
    } catch (Exception $e) {
        $out = "Error: " . $e->getMessage();
    }
}

// Set the viewed status of a fax details id
if($func == 'viewed' && !empty($id) && !empty($dir) && !empty($viewed)) {
    try {
        $test = $srfax->Update_Viewed_Status($dir, $viewed, $id);
        $out = "Fax set to viewed: $viewed\n";
    } catch (Exception $e) {
        $out = "Error: " . $e->getMessage();
    }
}

// Get the fax usage
if($func == 'usage') {
    try {
        $test = $srfax->Get_Fax_Usage();
        $out = "Usage: \n" . print_r($test, true);
    } catch (Exception $e) {
        $out = "Error: " . $e->getMessage();
    }
}

// Get the fax inbox
if($func == 'inbox') {
    try {
        $test = $srfax->Get_Fax_Inbox();
        $out = "Inbox: \n" . print_r($test, true);
    } catch (Exception $e) {
        $out = "Error: " . $e->getMessage();
    }
}

// Get the fax inbox
if($func == 'outbox') {
    try {
        $test = $srfax->Get_Fax_Outbox();
        $out = "Outbox: \n" . print_r($test, true);
    } catch (Exception $e) {
        $out = "Error: " . $e->getMessage();
    }
}

echo "$out\n";
exit(0);

?>
