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
     * Отправка одиночного email сообщения
     * https://notisend.ru/dev/email/api/#email
     *
     * @param $message
     *
     * @return stdClass
     */
    public function sendMessage($message)
    {
        if (empty($message)) {
            return $this->handleError('Empty message');
        }

        $requestResult = $this->sendRequest('email/messages', 'POST', $message);

        return $this->handleResult($requestResult);
    }

    /**
     * Получение информации о сообщении
     * https://notisend.ru/dev/email/api/#TOC_218a2d49ce4e61a5b3e99b45737314a9
     *
     * @param $ID	
     *
     * @return stdClass
     */
    public function getMessageInfo($ID)
    {
        if (empty($ID)) {
            return $this->handleError('Empty id');
        }
	
        $requestResult = $this->sendRequest('email/messages/' . $ID);

        return $this->handleResult($requestResult);
    }
    
    /**
     * Отправка одиночного сообщения по шаблону
     * https://notisend.ru/dev/email/api/#TOC_d7a6319e563f08691be55897faac38c2
     *
     * @param $listID	
     * @param $message
     *
     * @return stdClass
     */
    public function sendMessageWithTemplate($listID, $message )
    {
        if (empty($listID) || empty($message)) {
            return $this->handleError('Empty list id or message');
        }

        $requestResult = $this->sendRequest('email/templates/' . $listID . '/messages', 'POST', $message);

        return $this->handleResult($requestResult);
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
    public function getListInfo($id)
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
        if (empty($listID)) {
            return $this->handleError('Empty list id');
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
     * Список организаций
     * https://notisend.ru/dev/email/api/#TOC_4de9d7a19d96935172bfc8e32b0020c4
     *
     * @param $listID	
     *
     * @return stdClass
     */
    public function listOrganization($listID)
    {
        if (empty($listID)) {
            return $this->handleError('Empty list id');
        }
	
        $requestResult = $this->sendRequest('email/organizations');

        return $this->handleResult($requestResult);
    }
    
    /**
     * Информация об организации
     * https://notisend.ru/dev/email/api/#TOC_c068da17178fafa4fbab1f7caf912cf1
     *
     * @param $ID	
     *
     * @return stdClass
     */
    public function getOrganizationInfo($ID)
    {
        if (empty($ID)) {
            return $this->handleError('Empty id');
        }
	
        $requestResult = $this->sendRequest('email/organizations/' . $ID);

        return $this->handleResult($requestResult);
    }
    
    /**
     * Организация по умолчанию
     * https://notisend.ru/dev/email/api/#TOC_59e753782824cdb97755130bd7f953a5
     *
     * @return stdClass
     */
    public function getDefaultOrganization()
    {
        $requestResult = $this->sendRequest('email/organizations/current');

        return $this->handleResult($requestResult);
    }
    
    /**
     * Задать организацию по умолчанию
     * https://notisend.ru/dev/email/api/#TOC_d6f7d80b166393914a40c7425b45c57c
     *
     * @param $ID	
     *
     * @return stdClass
     */
    public function setDefaultOrganization($ID)
    {
        if (empty($ID)) {
            return $this->handleError('Empty id');
        }
	
        $requestResult = $this->sendRequest('email/organizations/' . $ID . '/current');

        return $this->handleResult($requestResult);
    }
    
    /**
     * Создание рассылки
     * https://notisend.ru/dev/email/api/#TOC_e8f0bf758b04ddeec70cfa1833db9cda
     *
     * @param $campaign
     *
     * @return stdClass
     */
    public function createCampaign($campaign)
    {
        if (empty($campaign)) {
            return $this->handleError('Empty campaign');
        }

        $requestResult = $this->sendRequest('email/campaigns', 'POST', $campaign);

        return $this->handleResult($requestResult);
    }
    
    /**
     * Отправка рассылки
     * https://notisend.ru/dev/email/api/#TOC_34c6772bd3e3a39bd2fdd5032397503f
     *
     * @param $campaign
     *
     * @return stdClass
     */
    public function startCampaign($ID)
    {
        if (empty($ID)) {
            return $this->handleError('Empty id');
        }
	
        $requestResult = $this->sendRequest('email/campaigns/' . $ID . '/deliver');

        return $this->handleResult($requestResult);
    }
    
    /**
     * Информация о рассылке
     * https://notisend.ru/dev/email/api/#TOC_ec268b1d0451f763e02695bdf3b88676
     *
     * @param $ID	
     *
     * @return stdClass
     */
    public function getCampaignInfo($ID)
    {
        if (empty($ID)) {
            return $this->handleError('Empty id');
        }
	
        $requestResult = $this->sendRequest('email/campaigns/' . $ID);

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