<?php

namespace SesPhp54Ec2;

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

/**
 * AWS-SESでメールを一括送信する
 *
 * @access public
 * @package SesPhp54Ec2
 */
class Mail
{
    /** @var int 一括送信の宛先上限数 */
    const BULK_LIMIT = 50;
    /** @var SesClient */
    private $client;
    /** @var string AWSアカウントID */
    private $accountId;
    /** @var string AWSリージョン */
    private $region;
    /** @var string APIエンドポイント */
    private $baseUrl;
    /** @var array 宛先情報配列 */
    private $destinations;
    /** @var int 1秒あたりの送信数上限 */
    private $quotaLimit;

    /**
     * コンストラクタ
     *
     * @param string $region AWSリージョン
     * @param int $quotaLimit 1秒間のメール送信数上限
     */
    public function __construct($region, $quotaLimit)
    {
        try {
            $this->client = SesClient::factory([
                'region' => $region
            ]);
        } catch (SesException $e) {
            throw $e;
        }

        // AWSアカウント情報を取得
        $metadataUrl = 'http://169.254.169.254/latest/dynamic/instance-identity/document';
        $request = $this->client->get($metadataUrl);
        $response = $request->send();
        $metadata = json_decode($response->getBody(), true);
        $this->accountId = $metadata['accountId'];

        $this->region = $region;
        $this->baseUrl = 'https://email.' . $region . '.amazonaws.com';

        $this->quotaLimit = $quotaLimit;
    }

    /**
     * Destination（宛先データ）の元になる配列を追加
     *
     * @param string $to 宛先メールアドレス
     * @param array $templateDate パーソナライズ用データ配列
     */
    public function addDestination($to, $templateData)
    {
        if (count($this->destinations) >= 50) {
            throw new \Exception('Adding more than 50 destinations is not allowed.');
        }

        $this->destinations[] = [
            'Destination' => [
                'ToAddresses' => [$to]
            ],
            'ReplacementEmailContent' => [
                'ReplacementTemplate' => [
                    'ReplacementTemplateData' => $templateData ? json_encode($templateData) : '{}'
                ]
            ]
        ];
    }

    /**
     * Destination（宛先データ）の元になる配列を空にする
     */
    public function clearDestinations()
    {
        $this->destinations = [];
    }

    /**
     * メールを一括送信
     *
     * @param string $from 送信元
     * @param string $reply 返信先メールアドレス
     * @param string $templateName メールテンプレート名
     * @param array $templateData テンプレートデフォルトデータ配列
     * @return boolean
     */
    public function sendBulkMail($from, $reply, $templateName, $defaultTemplateData)
    {
        if (count($this->destinations) == 0) {
            return false;
        }

        mb_internal_encoding('UTF-8');

        // 送信元をMIMEヘッダエンコーディング
        $matches = [];
        if (preg_match('/^(.*)<(.*)>/', $from, $matches)) {
            $from = mb_encode_mimeheader($matches[1]) . ' <' . $matches[2] . '>';
        }

        // replyが空の場合はFromのメールアドレスを返信先にする
        if (empty($reply)) {
            if ($matches) {
                $reply = $matches[2];
            } else {
                $reply = $from;
            }
        }

        $request = $this->client->post($this->baseUrl . '/v2/email/outbound-bulk-emails');

        $requestBodyArray = [
            'BulkEmailEntries' => $this->destinations,
            'DefaultContent' => [
                'Template' => [
                    'TemplateArn' => 'arn:aws:ses:' . $this->region . ':' . $this->accountId . ':template/' . $templateName,
                    'TemplateData' => $defaultTemplateData ? json_encode($defaultTemplateData) : '{}',
                    'TemplateName' => $templateName
                ]
            ],
            'FromEmailAddress' => $from,
            'ReplyToAddresses' => [$reply]
        ];
        $request->setBody(json_encode($requestBodyArray));
        $response = $request->send();

        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * sendBulkMailの送信上限（50件）または1秒当たりの送信上限に達しているかを判定
     *
     * @return boolean
     */
    public function isLimit()
    {
        return count($this->destinations) >= self::BULK_LIMIT
            || count($this->destinations) >= $this->quotaLimit;
    }

    /**
     * 1秒当たりの送信上限に達しているかを判定
     *
     * @return boolean
     */
    public function isQuotaLimit()
    {
        return count($this->destinations) >= $this->quotaLimit;
    }
}
