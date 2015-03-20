<?php
/* Class srfax - Copyright (C) 2015 KISS IT Consulting, LLC.  All rights reserved.
 * 
 * Simple class that provides an interface to the SRFax web API:
 * https://www.srfax.com/online-fax-features/internet-fax-api/.
 * Usage requires a valid account.
 */

class srfax
{
	private $api_user;
	private $api_pass;
	private $api_url;
    private $sender_fax;
    private $sender_email;
	
    /*
	 * Class constructor
     * $api_user: SRFax API user (customer number).  Required.
     * $api_user: SRFax API password.  Required.
     * $options: Array of optional parameters possible for the given call
     * return: Array to be used as the base parameters to the call() method
     */
	function __construct($api_user, $api_pass, $api_url = "", $sender_fax = null, $sender_email = null){
		$this->api_user = $api_user;
		$this->api_pass = $api_pass;
        if(!empty($api_url)) {
		    $this->api_url = $api_url;
        } else {
            $this->api_url = 'https://www.srfax.com/SRF_SecWebSvc.php';
        }
        $this->sender_fax = $sender_fax;
        $this->sender_email = $sender_email;
	}

    /*
	 * Method to set the options array where applicable
     * $function: Name of the SRFax API function to be called.  Required.
     * $options: Array of optional parameters possible for the given call
     * return: Array to be used as the base parameters to the call() method
     */
    private function _set_options($function, $options = array()) {
        $params = array();
        if(is_array($options) && !empty($options)) {
            $params = $options;
        }
        $params['action'] = $function;
        return $params;
    }

    /*
	 * Method to make an API call to SRFax using cURL.  
     * $params: array of parameters, including the 'action' which is the SRFax API function to call
     * $return_response: True to get the raw response object, false to just get the success response or exception on failure
     * return: JSON decoded object on success or throws an exception on cURL error.
     */
	private function _call($params, $return_response = false) {
		$return = null;
		if(is_array($params) && !empty($params)) {
            // Setup our payload, note we force JSON regardless of what you may pass.
            $params['access_id'] = $this->api_user;
			$params['access_pwd'] = $this->api_pass;
            $params['sResponseFormat'] = 'JSON';
            $payload = json_encode($params);

            // Setup our cURL call and run it
			$options = array (
				CURLOPT_POST => 1,
				CURLOPT_HEADER => 0,
				CURLOPT_URL => $this->api_url,
				CURLOPT_FRESH_CONNECT => 1,
				CURLOPT_RETURNTRANSFER => 1,
                // Not the greatest choice but it gets the job done.
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_FORBID_REUSE => 1,
				CURLOPT_TIMEOUT => 60, 
				CURLOPT_POSTFIELDS => $payload);

			$curl = curl_init();
			curl_setopt_array($curl, $options);
			$response = curl_exec($curl);

            // setup our return based on our status and close the handle
			if (curl_errno($curl) > 0) {
				throw new Exception ('cURL error: '. curl_error($curl));
			} else {
                if(!empty($response)) {
                    $response = json_decode($response);
                    if($response !== null) {
                        if($return_response) {
                            // Return our response as is
                            $return = $response;
                        } else {
                            // Check our response
                            if($response->Status == 'Success') {
                                $return = $response->Result;
                            } else {
                                throw new Exception("API Call returned error: " . $response->Result);
                            }
                        }
                    } else {
                        throw new Exception("Failed to parse response as JSON.");
                    }
                } else {
                    throw new Exception("Empty response returned from SRFax API.");
                }
			}
			curl_close($curl);
		}
		return $return;
	}

    /*
     * Method to queue a fax via the SRFax API function Queue_Fax
     * $to: The recipient fax number.  11 digit number or up to 50 x 11 digit numbers pipe separated.  Required.
     * $options: Array of optional parameters possible for the SRFax API function Queue_Fax.  Note the file name(s)/data to send must
     *      be passed in here as per the API and that the file content must be base64_encoded on the calling side.
     * $sender_fax: Sender fax number, 10 digits.  Optional if set via constructor.
     * $sender_email: Sender email address.  Optional if set via constructor.
     * $fax_type: Fax type either 'SINGLE' or 'BROADCAST.  Optional, defaults to 'SINGLE'
     * Returns queued fax ID (FaxDetailsID) on success, throws an exception on failure.
     */
    public function Queue_Fax($to, $options = array(), $sender_fax = null, $sender_email = null, $fax_type = 'SINGLE') {
        // Get our base params
        $params = $this->_set_options('Queue_Fax', $options);

        // Validate the to #
        if(empty($to) || strlen($to) < 11) {
            // Try to check for a valid number that is missing a 1 at the beginning and beat it with a hammer
            if(strlen($to) == 10) {
                $to = "1{$to}";
            } else {
                throw new Exception("Invalid recipient fax number.");
            }
        }
        $params['sToFaxNumber'] = $to;

        // Validate sender fax #
        if(empty($sender_fax) || !is_numeric($sender_fax) || strlen($sender_fax) != 10) {
            if(empty($this->sender_fax) || !is_numeric($this->sender_fax) || strlen($this->sender_fax) != 10) {
                throw new Exception("Invalid sender fax number.  Must be 10 digits");
            }
            $sender_fax = $this->sender_fax;
        }
        $params['sCallerID'] = $sender_fax;

        // Validate sender email
        if(empty($sender_email)) {
            if(empty($this->sender_email)) {
                throw new Exception("Invalid sender email address.");
            }
            $sender_email = $this->sender_email;
        }
        $params['sSenderEmail'] = $sender_email;

        // Validate fax type
        $fax_type = strtoupper($fax_type);
        if($fax_type != 'SINGLE' && $fax_type != 'BROADCAST') {
            throw new Exception("Invalid fax type.  Must be 'SINGLE' or 'BROADCAST'.");
        }
        $params['sFaxType'] = $fax_type;
        
        // Make our API Call
        return $this->_call($params);
    }

    /*
     * Method to check the status of a previously queue'd fax(es)
     * $fax_details_id: The FaxDetailsID to check the status of.  
     *      Can also be a pipe separated list of IDs that will in turn be used to call Get_MultiFaxStatus Required.
     * Returns an array of one or more fax properties objects on success, throws an exception on failure.
     */
    public function Get_FaxStatus($fax_details_id) {
        // Figure out which API call we need to use
        if(strpos($fax_details_id, '|') === false) {
            $method = 'Get_FaxStatus';
        } else {
            $method = 'Get_MultiFaxStatus';
        }

        // Get our base params
        $params = $this->_set_options($method);
        $params['sFaxDetailsID'] = $fax_details_id;

        // Make our API Call
        return $this->_call($params);
    }
    
    /*
     * Method to get the fax usage of the account.
     * $options: Array of optional parameters possible for the SRFax API function Get_Fax_Usage
     * Returns array of fax usage properties on success, throws an exception on failure.
     */
	public function Get_Fax_Usage($options = array()) {
        // Get our base params
        $params = $this->_set_options('Get_Fax_Usage', $options);
        
        // Make our API Call
        return $this->_call($params);
	}
    
    /*
     * Method to get the fax inbox.
     * $options: Array of optional parameters possible for the SRFax API function Get_Fax_Inbox
     * Returns array of faxes on success, throws an exception on failure.
     */
	public function Get_Fax_Inbox($options = array()) {
        // Get our base params
        $params = $this->_set_options('Get_Fax_Inbox', $options);
        
        // Make our API Call
        return $this->_call($params);
	}

    /*
     * Method to get the fax outbox.
     * $options: Array of optional parameters possible for the SRFax API function Get_Fax_Outbox
     * Returns array of faxes on success, throws an exception on failure.
     */
	public function Get_Fax_Outbox($options = array()) {
        // Get our base params
        $params = $this->_set_options('Get_Fax_Outbox', $options);
        
        // Make our API Call
        return $this->_call($params);
	}

    /*
     * Method to retrieve a fax, either incoming or outgoing based on parameters
     * $direction: Type of fax, "IN" for inbound, "OUT" for outbound.  Required.
     * $fax_details_id: The fax details id (required if not passing $fax_filename)
     * $fax_filename: The fax file name (required if not passing $fax_details_id)
     * $options: Array of optional parameters possible for the SRFax API function Retrieve_Fax
     * Returns file data from the retrieved fax on success, throws an exception on failure.
     */
	public function Retrieve_Fax($direction, $fax_details_id = null, $fax_filename = null, $options = array()) {
        // Get our base params
        $params = $this->_set_options('Retrieve_Fax', $options);

        // Validate the direction
        $direction = strtoupper($direction);
        if($direction != 'IN' && $direction != 'OUT') {
            throw new Exception("Invalid direction, must be IN or OUT.");
        }
        $params['sDirection'] = $direction;

        // Validate that we have fax information to be retrieved
		if(!empty($fax_details_id)) {
            $params['sFaxDetailsID'] = $fax_details_id;
        } elseif(!empty($fax_filename)) {
            $params['sFaxFileName'] = $fax_filename;
        } else {
            throw new Exception("You must pass either fax_details_id or fax_filename.");
        }

        // Make our API Call and return the results decoded if valid
        return base64_decode($this->_call($params));
    }

    /*
     * Method to update the viewed status of a fax, either incoming or outgoing based on parameters
     * $direction: Type of fax, "IN" for inbound, "OUT" for outbound.  Required.
     * $viewed: Mark the fax as read ('Y') or unread ('N').  Required.
     * $fax_details_id: The fax details id (required if not passing $fax_filename)
     * $fax_filename: The fax file name (required if not passing $fax_details_id)
     * $options: Array of optional parameters possible for the SRFax API function Retrieve_Fax
     * Returns empty string on success, throws an exception on failure.
     */
	public function Update_Viewed_Status($direction, $viewed, $fax_details_id = null, $fax_filename = null, $options = array()) {
        // Get our base params
        $params = $this->_set_options('Update_Viewed_Status', $options);

        // Validate the direction
        $direction = strtoupper($direction);
        if($direction != 'IN' && $direction != 'OUT') {
            throw new Exception("Invalid direction, must be IN or OUT.");
        }
        $params['sDirection'] = $direction;

        // Validate the viewed state
        $viewed = strtoupper($viewed);
        if($viewed != 'Y' && $viewed != 'N') {
            throw new Exception("Invalid viewed option, must be Y or N.");
        }
        $params['sMarkasViewed'] = $viewed;

        // Validate that we have fax information to be retrieved
		if(!empty($fax_details_id)) {
            $params['sFaxDetailsID'] = $fax_details_id;
        } elseif(!empty($fax_filename)) {
            $params['sFaxFileName'] = $fax_filename;
        } else {
            throw new Exception("You must pass either fax_details_id or fax_filename.");
        }

        // Make our API Call and return the results decoded if valid
        return $this->_call($params);
    }

    /*
     * Method to delete a fax, either incoming or outgoing based on parameters
     * $direction: Type of fax, "IN" for inbound, "OUT" for outbound.  Required.
     * $fax_details_id: The fax details id (required if not passing $fax_filename)
     * $fax_filename: The fax file name (required if not passing $fax_details_id)
     * $options: Array of optional parameters possible for the SRFax API function Retrieve_Fax
     * Returns an empty string on success, throws an exception on failure.
     */
	public function Delete_Fax($direction, $fax_details_id = null, $fax_filename = null, $options = array()) {
        // Get our base params
        $params = $this->_set_options('Delete_Fax', $options);

        // Validate the direction
        $direction = strtoupper($direction);
        if($direction != 'IN' && $direction != 'OUT') {
            throw new Exception("Invalid direction, must be IN or OUT.");
        }
        $params['sDirection'] = $direction;

        // Validate that we have fax information to be retrieved
		if(!empty($fax_details_id)) {
            $params['sFaxDetailsID'] = $fax_details_id;
        } elseif(!empty($fax_filename)) {
            $params['sFaxFileName'] = $fax_filename;
        } else {
            throw new Exception("You must pass either fax_details_id or fax_filename.");
        }

        // Make our API Call and return the results decoded if valid
        return $this->_call($params);
    }

    /*
     * Method to delete a fax from the queue.  Note that depending on where it is in processing this may or may not work fully or at all
     * $fax_details_id: The FaxDetailsID to stop 
     * Returns the full json response as per the documentation on success to provide meaningful status messages, throws an exception on failure.
         Status = Success:
            Result:
            • "Fax cancelled but partially sent" – fax was successfully cancelled but
            whatever was in the fax buffer will have been sent.
            • "Fax Cancelled" – the fax was successfully cancelled without any pages being
            sent.
         Status = Failed:
            Result:
            • "Fax transmission completed" – the fax has been sent and the transaction is
            complete
            • "Unable to Cancel Fax" – Fax in the process of conversion and cannot be
            cancelled at this time – you can try again in 10 seconds.
     */
    public function Stop_Fax($fax_details_id) {
        // Get our base params
        $params = $this->_set_options('Stop_Fax');
        $params['sFaxDetailsID'] = $fax_details_id;

        // Make our API Call and get the full response back
        return $this->_call($params, true);
    }
}
