<?php
/**
 * turbosms.ua HTTP API implementation.
 *
 * @author AlxJzx100 <alxjzx100@gmail.com>
 * @version 1.0.1
 */

namespace Alxjzx100\Turbosmsua;

use DateTime;
use Exception;
use LengthException;
use OutOfRangeException;

class httpApi
{
    protected $apiKey;
    protected static $apiUrl = 'https://api.turbosms.ua';
    protected $connectionType = 'curl';
    protected static $mods = ['sms', 'viber', 'hybrid'];
    protected $currentMode = 'sms';
    protected $start_time;
    protected $is_flash;
    protected $ttl;
    protected $image_url;
    protected $caption;
    protected $action;
    protected $file_id;
    protected $count_clicks;
    protected $is_transactional;

    /**
     * @throws Exception
     */
    public function __construct(string $apiKey)
    {
        if (!empty($apiKey)) {
            return $this->setApiKey($apiKey);
        } else {
            throw new Exception('Api key is empty!');
        }

    }

    /**
     * @throws Exception
     */
    public function setMode(string $mode): self
    {
        if (!in_array($mode, self::$mods)) throw new Exception('Unknown send mode');

        $this->currentMode = $mode;
        return $this;
    }

    private function setApiKey(string $key): self
    {
        $this->apiKey = $key;
        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setStartTime(DateTime $start_time): self
    {
        $current_date = new DateTime();
        if ($current_date > $start_time) throw new OutOfRangeException('Start date is in the past!');

        if ($current_date->diff($start_time)->days > 14) throw new OutOfRangeException('Maximum scheduled date is no more 14 days from current date!');

        $this->start_time = $start_time->format('Y-m-d H:i:s');
        return $this;
    }

    /**
     * Use for SMS message
     * @param int $is_flash
     * @return $this
     */
    public function setIsFlash(int $is_flash = 1): self
    {
        $this->is_flash = $is_flash;
        return $this;
    }

    /**
     * Use for Viber message. Default value 3600 sec
     * MIN - 60 MAX - 86400
     * @param int $ttl
     * @return $this
     */
    public function setTTL(int $ttl): self
    {
        if ($ttl < 60 || $ttl > 86400) throw new OutOfRangeException('TTL is out of range. Min = 60, Max = 86400');

        $this->ttl = $ttl;
        return $this;
    }

    /**
     * Use for Viber message
     * @param string $image_url
     * @return $this
     */
    public function setImage(string $image_url): self
    {
        $this->image_url = $image_url;
        return $this;
    }

    /**
     * Use for Viber message
     * @param string $caption
     * @return $this
     */
    public function setCaption(string $caption): self
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * Use for Viber message
     * @param string $action
     * @return $this
     */
    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Use for Viber message
     * @param int $file_id
     * @return $this
     */
    public function setFileId(int $file_id): self
    {
        $this->file_id = $file_id;
        return $this;
    }

    /**
     * Use for Viber message
     * @param int $count
     * @return $this
     */
    public function countClicks(int $count = 1): self
    {
        $this->count_clicks = $count;
        return $this;
    }

    /**
     * Use for Viber message
     * @param int $trans
     * @return $this
     */
    public function isTransactional(int $trans = 1): self
    {
        $this->is_transactional = $trans;
        return $this;
    }

    public function setConnectionType(string $connectionType): self
    {
        $this->connectionType = $connectionType;
        return $this;
    }

    public function getConnectionType(): string
    {
        return $this->connectionType;
    }

    /**
     * @throws Exception
     */
    public function send($num, string $text, string $sender = 'MAGAZIN', string $senderViber = '')
    {
        $method = '/message/send.json';
        $data = [];

        if (empty($num)) throw new LengthException('Number must be a string or array of strings');

        if (empty($text)) throw new LengthException('Text is empty');

        if (empty($sender)) throw new LengthException('Sender name is empty');

        if (is_string($num)) {
            $data['recipients'][] = $this->phoneFormat($num);
        } elseif (is_array($num)) {
            $data['recipients'] = $this->phoneFormat($num);
        }
        if ($this->start_time) {
            $data['start_time'] = $this->start_time;
        }

        if ($this->currentMode == 'sms' || $this->currentMode == 'hybrid') {
            $data['sms'] = [
                'sender' => $sender,
                'text' => $text,
                'is_flash' => $this->is_flash ?? ''
            ];
        }
        if ($this->currentMode == 'viber' || $this->currentMode == 'hybrid') {
            $data['viber'] = [
                'sender' => empty($senderViber) ? $sender : $senderViber,
                'text' => $text
            ];
            if ($this->ttl) $data['viber']['ttl'] = $this->ttl;
            if ($this->image_url) $data['viber']['image_url'] = $this->image_url;
            if ($this->caption) $data['viber']['caption'] = $this->caption;
            if ($this->action) $data['viber']['action'] = $this->action;
            if ($this->file_id) $data['viber']['file_id'] = $this->file_id;
            if ($this->count_clicks) $data['viber']['count_clicks'] = $this->count_clicks;
            if ($this->is_transactional) $data['viber']['is_transactional'] = $this->is_transactional;
        }

        $result = $this->request($method, $data);
        if ($result->response_code == 0) {
            return $result->response_result;
        } else throw new Exception($result->response_status);
    }

    /**
     * @throws Exception
     */
    public function getBalance()
    {
        $method = '/user/balance.json';
        $result = $this->request($method);
        if ($result->response_code == 0) {
            return $result->response_result;
        } else throw new Exception($result->response_status);
    }

    /**
     * @param int $file_id id from uploadFile method
     * @return mixed
     * @throws Exception
     */
    public function getFileDetails(int $file_id)
    {
        $method = '/file/details.json';
        $data = ['id' => $file_id];
        $result = $this->request($method, $data);
        if ($result->response_code == 0) {
            return $result->response_result;
        } else throw new Exception($result->response_status);
    }

    /**
     *
     * @param string $file
     * @return mixed
     * @throws Exception
     */
    public function uploadFile(string $file)
    {
        $method = '/file/add.json';
        if (base64_encode(base64_decode($file, true)) === $file) {
            $data = ['data' => $file];
        } else {
            $data = ['url' => $file];
        }

        $result = $this->request($method, $data);
        if ($result->response_code == 0) {
            return $result->response_result;
        } else throw new Exception($result->response_status);

    }

    private function request(string $method, $params = null)
    {
        $post = $params
            ? json_encode($params)
            : '';

        $header = [
            'Authorization: Basic ' . $this->getApiKey(),
            'Content-Type: application/json',
        ];
        $url = self::$apiUrl . $method;

        if ('curl' == $this->getConnectionType()) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $context = [
                'http' => [
                    'method' => count($params) ? "POST" : "GET",
                    'header' => implode("\r\n", $header),
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                    'content' => $post ?? '',
                ],
            ];

            $result = file_get_contents($url, false, stream_context_create($context));
        }
        return json_decode($result);
    }

    private function phoneFormat($phone, $mask = '#', $codeSplitter = '0')
    {
        $format = array(
            '12' => '############', // for +38 0XX XX XXX XX or 38 0XX XX XXX XX
            '10' => '38##########' // for 0XX XX XXX XX
        );
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = substr($phone, strpos($phone, $codeSplitter));

        if (array_key_exists(strlen($phone), $format)) {
            $format = $format[strlen($phone)];
        } else {
            return $phone;
        }

        $pattern = '/' . str_repeat('([0-9])?', substr_count($format, $mask)) . '(.*)/';

        $format = preg_replace_callback(
            str_replace('#', $mask, '/([#])/'),
            function () use (&$counter) {
                return '${' . (++$counter) . '}';
            },
            $format
        );

        return ($phone) ? trim(preg_replace($pattern, $format, $phone, 1)) : false;
    }

}

