<?php

require_once realpath(dirname(__FILE__)) . '/JsonHelpers.php';
global $b_xxx_do_not_check_ssl;

class CurlHelperException extends Exception
{

    protected $data;

    public function __construct($data, $message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->message = $message;
        $this->data = $data;

        //overwrites message using the older version of message set above
        $this->message = (string)$this;
    }

    /**
     * Returns the error type of the exception that has been thrown.
     */
    public function getData()
    {
        return $this->data;
    }

    public function __toString()
    {
        $data = print_r($this->data,true);
        $code = $this->getCode();
        return "[$code] ".$this->message . "\n$data";
    }


}

/**
 * @author Will Woodlief
 * @license MIT
 * @link https://gist.github.com/willwoodlief/1a008ab369ec48968d41d0cec1b9c4d6
 * General Curl Helper. Its multipurpose. Used it in the transcription project and now improved it
 * @example curl_helper('cnn.com',null,$code)
 *          curl_helper('enri.ch',['var1'=>4],$code)
 * @param $url string the url
 * @param $fields array|object|string|null <p>
 *  the params to pass
 *  May be an array, or object containing properties, or a string, or evaluates for false
 *  </p>
 * @param &$http_code integer , will be set to the integer return code of the server. Its only an output variable
 * @param $b_post boolean , default true . POST is true, GET is false
 * @param  $format string (json|xml|text) default json <p>
 *      Tells how the response is formatted, text means no conversion
 * </p>
 * @param $b_verbose boolean, default false. If set to true will print to screen the connection process
 * @param $b_header_only boolean, default false <p>
 *  if true then no body is downloaded, and the return the headers
 * </>
 * @param $ssl_version boolean , default false <p>
 *   if not false, then set CURLOPT_SSLVERSION to the value
 * </p>
 * @param $headers array , default empty <p>
 *   adds to the headers of the request being sent
 * </p>
 * @param $custom_request false|string, default false <p>
 * when set will set custompost instead of post
 * </p>
 * @return array|string|int|null depends on the format and option
 *
 * @throws CurlHelperException <p>
 *   if curl cannot connect
 *   if site gives response in the 500s (if $b_header_only is false)
 *   if the format is json and the the conversion has errors and response is below 500
 * if the format is xml and the conversion has errors and response is below 500
 * </p>

 */
function curl_helper($url, $fields = null, &$http_code, $b_post=true , $format = 'json',
                     $b_verbose=false, $b_header_only = false,$ssl_version = false,$headers=[],
					$custom_request=false)
{
    if (!isset($url)) {
        throw new CurlHelperException($fields,"URL needs to be set");
    }
    $url = strval($url);

    $ch = curl_init();
    try {
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
        ]);

        //curl will not print verbose info to the html browser screen. So we have to capture it and replay it
        $verbose = null;
        if ($b_verbose) {
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        if ($b_header_only) {
            curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
            curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body

            $out_headers = [];
            // this function is called by curl for each header received
            curl_setopt(/**
             * @param $curl resource
             * @param $header string
             * @return int
             */
                $ch, CURLOPT_HEADERFUNCTION,
                function(/** @noinspection PhpUnusedParameterInspection */
                    $curl, $header) use (&$out_headers)
                {
                    $len = strlen($header);
                    $header = explode(':', $header, 2);
                    if (count($header) < 2) // ignore invalid headers
                        return $len;

                    $name = strtolower(trim($header[0]));
                    if (!array_key_exists($name, $out_headers))
                        $out_headers[$name] = [trim($header[1])];
                    else
                        $out_headers[$name][] = trim($header[1]);

                    return $len;
                }
            );
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        //if testing on localhost and url is https, then this gets around it because some localhost do not have ssl certs

        if (isset($_SERVER['REMOTE_ADDR']) ) {
            if( in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ) ) ) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            }
        }
        //note, take this out later
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if ($b_post) {
            if ($custom_request) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_request);
            } else {
                curl_setopt($ch, CURLOPT_POST, count($fields));
            }

            if ($fields) {
                if (is_object($fields) || is_array($fields)) {
                    $b_do_build = true;
                    if (is_array($fields)) {
                        if (array_key_exists('curl_helper_skip_encoding',$fields)) {
                            if ($fields['curl_helper_skip_encoding']) {
                                $b_do_build = false;
                            }
                        }
                    }
                    if (is_object($fields)) {
                        if (property_exists($fields,'curl_helper_skip_encoding')) {
                            if ($fields->curl_helper_skip_encoding) {
                                $b_do_build = false;
                            }
                        }
                    }
                    if ($b_do_build) {
                        $build = http_build_query($fields);
                    } else {
                        $build = $fields;
                    }


                    curl_setopt($ch, CURLOPT_POSTFIELDS, $build );
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
                }

            }

        } else {
            if ($custom_request) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_request);
            }
            if ($fields) {
                if (is_object($fields) || is_array($fields)) {
                    $query = http_build_query($fields);
                } else {
                    $query = $fields;
                }

                $url = $url . '?' . $query;
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0");
       // curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie-name');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '');

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($ssl_version) {
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        }



        $curl_output = curl_exec($ch);

        if ($b_verbose) {
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";
        }



        $http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        if (curl_errno($ch)) {
            throw new CurlHelperException($fields,"could not open url: $url because of curl error: " , curl_error($ch));
        }

        if ($b_header_only) {

            $out_headers['effective_url']=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

            return $out_headers; //journey ends here with just the headers
        }

        if ($http_code == 0 ) {
            throw new CurlHelperException([],"Could not send data to $url" ,$http_code);
        }

        if (!is_string($curl_output) || !strlen($curl_output)) {
            $curl_output = ''; //no longer throwing exception here as sometimes need return code
        }

        //makes it easy to skip formatting
        if ($format === true || !$format) {
            $format = 'none';
        }
        try {
            switch ($format) {
                case 'json':
                    $data_out = JsonHelpers::fromString($curl_output);
                    break;
                case 'xml':

                    $data_out = json_decode(json_encode((array)simplexml_load_string($curl_output)),1);
                    if ($data_out === null) {
                        throw new Exception("failed to decode as xml: $curl_output");
                    }
                    break;
                default: {
                    $data_out = $curl_output;
                }
            }
        }
        catch (Exception $c) {
            $data_out = $curl_output;
        }


        if ( $http_code >= 500) {
            throw new CurlHelperException($data_out,'Server had error',$http_code);
        }


        return $data_out;
    }
    finally {
        //always close curl, in case we need to do a lot of this. Its a limited resource
        curl_close ($ch);
    }
}