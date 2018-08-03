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

	//var_dump(json_encode($data));
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
	
	// для отладки
	echo "curl -X ".$method." ".$url." -H 'Content-Type: application/json' -H 'Authorization: Bearer ".$this->token."' -d '".json_encode($data)."'<br>";

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
     * https://notisend.ru/dev/email/api/#TOC_d7a6319e563f08691be55897faac38c2
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
     * https://notisend.ru/dev/email/api/#TOC_e64525558830f2712ecde8e19f7d16d8
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
     * Создание параметра
     * https://notisend.ru/dev/email/api/#TOC_3b06e18961c188597644d60c2343ce48
     *
     * @param $id
     * @param $title
     * @param $kind	Возможные значения: string, numeric, date, boolean, geo
     *
     * @return stdClass
     */
    public function createParameters($id, $title, $kind = 'string')
    {
        if (empty($id) || empty($title)) {
            return $this->handleError('Empty Id or title');
        }
	
	if (!in_array($kind, array('string', 'numeric', 'date', 'boolean', 'geo'))) {
            return $this->handleError('Wrong kind');
        }

        $data = array(
	    'title' => $title,
	    'kind'  => $kind
	);
        $requestResult = $this->sendRequest('email/lists/'.$id.'/parameters', 'POST', $data);

        return $this->handleResult($requestResult);
    }
    
    /**
     * Список параметров
     * https://notisend.ru/dev/email/api/#TOC_bc0ae9e81eb195127212c4538ee073dd
     *
     * @param $id
     *
     * @return stdClass
     */
    public function listParameters($id)
    {
        if (empty($id)) {
            return $this->handleError('Empty Id');
        }

        $requestResult = $this->sendRequest('email/lists/'.$id.'/parameters');

        return $this->handleResult($requestResult);
    }
    
    /**
     * Создание получателя
     * https://notisend.ru/dev/email/api/#TOC_104ddb5cb56a2c6fdda92686e182c9a5
     *
     * @param $listID	
     * @param $emails
     *
     * @return stdClass
     */
    public function addEmail($listID, $email)
    {
        if (empty($listID) || empty($email)) {
            return $this->handleError('Empty list id or emails');
        }

        $requestResult = $this->sendRequest('email/lists/' . $listID . '/recipients', 'POST', $email);

        return $this->handleResult($requestResult);
    }
    
    /**
     * Обновление получателя
     * https://notisend.ru/dev/email/api/#TOC_10fe8b80807429140de5cdedbbca66fa
     *
     * @param $listID	
     * @param $email
     *
     * @return stdClass
     */
    public function updateEmail($listID, $email)
    {
        if (empty($listID) || empty($title)) {
            return $this->handleError('Empty list id or emails');
        }

        $requestResult = $this->sendRequest('email/lists/' . $listID . '/recipients', 'POST', $email);

        return $this->handleResult($requestResult);
    }
    
    /**
     * Список получателей
     * https://notisend.ru/dev/email/api/#TOC_10fe8b80807429140de5cdedbbca66fa
     *
     * @param $listID	
     *
     * @return stdClass
     */
    public function listEmail($listID)
    {
        if (empty($listID) || empty($title)) {
            return $this->handleError('Empty list id or emails');
        }
	
        $requestResult = $this->sendRequest('email/lists/' . $listID . '/recipients');

        return $this->handleResult($requestResult);
    }
    
    /**
     * Импорт большого количества получателей
     * https://notisend.ru/dev/email/api/#TOC_6f234453ae84e3562439ebd55c5c9fb2
     *
     * @param $listID	
     * @param $emails
     *
     * @return stdClass
     */
    public function importEmails($listID, $emails)
    {
        if (empty($listID) || empty($title)) {
            return $this->handleError('Empty list id or emails');
        }

        $requestResult = $this->sendRequest('email/lists/' . $listID . '/recipients/imports', 'POST', $emails);

        return $this->handleResult($requestResult);
    }
    
    /**
     * Создание организации
     * https://notisend.ru/dev/email/api/#TOC_d7c6cfb4c802aab25fc23a2fe24fc665
     *
     * @param $organization
     *
     * @return stdClass
     */
    public function createOrganization($organization)
    {
        if (empty($organization)) {
            return $this->handleError('Empty organization');
        }

        $requestResult = $this->sendRequest('email/organizations', 'POST', $organization);

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