<?php

/*
 * NotiSend REST API Client
 *
 * Documentation
 * https://notisend.ru/dev/email/api/
 *
 * 2018 (c) Zakharov Andrew <https://github.com/ZakharovAndrew>
 */


class ApiClient {

    private $apiUrl = 'https://api.notisend.ru/v1';
    
    private $token;
   

    /**
     * NotiSend API constructor
     *
     * @throws Exception
     */
    public function __construct($token) 
    {
	if (empty($token)) {
	    throw new Exception('Token is empty');
        }
	
	$this->token = $token;
    }
    
    /**
     * Form and send request to API service
     *
     * @param        $path
     * @param string $method
     * @param array $data
     *
     * @return stdClass
     */
    public function sendRequest($path = '', $method = 'GET',  $data = array()) 
    {    
	
	$url = $this->apiUrl . '/' . $path;
        $method = strtoupper($method);
        $curl = curl_init();
        
        $headers = array('Authorization: Bearer ' . $this->token , 'Content-Type: application/json');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        switch ($method) {
            case 'POST':
		curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
            default:
                if (!empty($data)) {
                    $url .= '?' . http_build_query($data);
                }
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($curl);
	$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headerCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $responseBody = substr($response, $header_size);
	
	curl_close($curl);
	
	$retval = new stdClass();
        $retval->data = json_decode($responseBody);
        $retval->http_code = $headerCode;

        return $retval;
    }

    /**
     * Создание группы
     *
     * @param $title
     *
     * @return stdClass
     */
    public function createGroup($title)
    {
        if (empty($title)) {
            return $this->handleError('Empty title');
        }

        $data = array('title' => $title);
        $requestResult = $this->sendRequest('email/lists', 'POST', $data);

        return $this->handleResult($requestResult);
    }
    
    /**
     * Получение списка групп
     *
     * @return stdClass
     */
    public function listGroup()
    {
        $requestResult = $this->sendRequest('email/lists');

        return $this->handleResult($requestResult);
    }
    
    /**
     * Получение информации о группе
     *
     * @param $id
     *
     * @return stdClass
     */
    public function listInfo($id)
    {
        $requestResult = $this->sendRequest('email/lists/'.$id);

        return $this->handleResult($requestResult);
    }
    
    /**
     * Process results
     *
     * @param $data
     *
     * @return stdClass
     */
    private function handleResult($data)
    {
        if (empty($data->data)) {
            $data->data = new stdClass();
        }
        if ($data->http_code !== 200) {
            $data->data->is_error = true;
            $data->data->http_code = $data->http_code;
        }

        return $data->data;
    }
    
    /**
     * Process errors
     *
     * @param null $customMessage
     *
     * @return stdClass
     */
    private function handleError($customMessage = null)
    {
        $message = new stdClass();
        $message->is_error = true;
        if (null !== $customMessage) {
            $message->message = $customMessage;
        }

        return $message;
    }
        
}