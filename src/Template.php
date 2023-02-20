<?php

namespace SesPhp54Ec2;

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

/**
 * AWS-SESのメールテンプレートを作成・削除するクラス
 *
 * @access public
 * @package SesPhp54Ec2
 */
class Template
{
    /** @var SesClient */
    private $client;
    /** @var string APIエンドポイント */
    private $baseUrl;
    /** @var string メールテンプレート名 */
    private $templateName;

    /**
     * constructor
     *
     * @param string $region AWSリージョン
     */
    public function __construct($region)
    {
        try {
            $this->client = SesClient::factory([
                'region' => $region
            ]);
        } catch (SesException $e) {
            throw $e;
        }

        $this->baseUrl = 'https://email.' . $region . '.amazonaws.com';
        $this->templateName = uniqid('template-');
    }

    /**
     * AWS-SESのメールテンプレートを作成
     *
     * @param string $subject 件名
     * @param string $body 本文
     * @return string|false メールテンプレート名
     */
    public function createTemplate($subject, $body)
    {
        $requestBodyArray = [
            'TemplateContent' => [
                'Subject' => $subject,
                'Text' => $body
            ],
            'TemplateName' => $this->templateName
        ];

        $request = $this->client->post($this->baseUrl . '/v2/email/templates');
        $request->setBody(json_encode($requestBodyArray));
        $response = $request->send();

        if ($response->getStatusCode() == 200) {
            return $this->templateName;
        } else {
            return false;
        }
    }

    /**
     * AWS-SESのメールテンプレートを削除
     * 
     * @return boolean
     */
    public function deleteTemplate()
    {
        $request = $this->client->delete($this->baseUrl . '/v2/email/templates/' . $this->templateName);
        $response = $request->send();

        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * AWS-SESのメールテンプレート名を取得
     *
     * @return string メールテンプレート名
     */
    public function getTemplateName()
    {
        return $this->templateName;
    }
}
