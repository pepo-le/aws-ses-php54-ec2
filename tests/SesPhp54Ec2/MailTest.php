<?php

namespace tests\SesPhp54Ec2;

use SesPhp54Ec2\Mail;
use Mockery;

class MailTest extends \PHPUnit_Framework_TestCase
{
    private $accountId;
    private $region;
    private $templateName;
    private $mockPostRequest;

    public function setUp()
    {
        $this->accountId = '111122223333';
        $this->region = 'ap-northeast-1';
        $this->templateName = 'template-12345';
    }

    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * メールが1件送信できること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::sendBulkMail
     */
    function testSendEmail()
    {
        mb_internal_encoding("utf-8");

        $to = 'recipient@example.com';
        $from = '山田太郎 <sender@example.com>';
        $expectedFrom = mb_encode_mimeheader('山田太郎 ') . ' <sender@example.com>';
        $reply = 'sender@example.com';
        $templateData = [];
        $defaultTemplateData = [];

        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 5;
        $mail = new Mail($this->region, $quotaLimit);

        // 宛先の配列
        $mail->addDestination($to, $templateData);
        $expectedDestinationsArray = [];
        $expectedDestinationsArray[] = $this->createExpectedDestinationsArray($to, $templateData);

        // リクエストボディの配列
        $expectedRequestBodyArray =
            $this->createExpectedRequestBodyArray($expectedDestinationsArray, $defaultTemplateData, $expectedFrom, $reply);

        // リクエストボディの確認
        $this->mockPostRequest
            ->shouldReceive('setBody')
            ->with(json_encode($expectedRequestBodyArray));

        $result = $mail->sendBulkMail($from, $reply, $this->templateName, $defaultTemplateData);

        // 処理完了の確認
        $this->assertEquals(true, $result);
    }

    /**
     * パーソナライズされたメールが送信できること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::sendBulkMail
     */
    function testSendPersonalizedEmail()
    {
        mb_internal_encoding("utf-8");

        $to = 'recipient@example.com';
        $from = '山田太郎 <sender@example.com>';
        $expectedFrom = mb_encode_mimeheader('山田太郎 ') . ' <sender@example.com>';
        $reply = 'sender@example.com';
        $defaultTemplateData = ['name' => 'Nanashi'];
        $templateData = ['name' => 'Taro', 'rank' => 'Gold'];

        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 5;
        $mail = new Mail($this->region, $quotaLimit);

        // 宛先の配列
        $mail->addDestination($to, $templateData);
        $expectedDestinationsArray = [];
        $expectedDestinationsArray[] = $this->createExpectedDestinationsArray($to, $templateData);

        // リクエストボディの配列
        $expectedRequestBodyArray =
            $this->createExpectedRequestBodyArray($expectedDestinationsArray, $defaultTemplateData, $expectedFrom, $reply);

        // リクエストボディの確認
        $this->mockPostRequest
            ->shouldReceive('setBody')
            ->with(json_encode($expectedRequestBodyArray));

        $result = $mail->sendBulkMail($from, $reply, $this->templateName, $defaultTemplateData);

        // 処理完了の確認
        $this->assertEquals(true, $result);
    }

    /**
     * メールが50件送信できること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::sendBulkMail
     */
    function testSendEmailWithLimit()
    {
        mb_internal_encoding("utf-8");

        $from = '山田太郎 <sender@example.com>';
        $expectedFrom = mb_encode_mimeheader('山田太郎 ') . ' <sender@example.com>';
        $reply = 'sender@example.com';
        $defaultTemplateData = [];

        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 5;
        $mail = new Mail($this->region, $quotaLimit);

        // 宛先の配列
        $expectedDestinationsArray = [];
        for ($i = 0; $i < 50; $i++) {
            $to = 'recipient' . $i . '@example.com';
            $templateData = ['name' => 'name' . $i];
            // 宛先の追加
            $mail->addDestination($to, $templateData);

            $expectedDestinationsArray[] = $this->createExpectedDestinationsArray($to, $templateData);
        }

        // リクエストボディの配列
        $expectedRequestBodyArray =
            $this->createExpectedRequestBodyArray($expectedDestinationsArray, $defaultTemplateData, $expectedFrom, $reply);

        // リクエストボディの確認
        $this->mockPostRequest
            ->shouldReceive('setBody')
            ->with(json_encode($expectedRequestBodyArray));

        $result = $mail->sendBulkMail($from, $reply, $this->templateName, $defaultTemplateData);

        // 処理完了の確認
        $this->assertEquals(true, $result);
    }

    /**
     * replyが未指定の場合はfromのメールアドレスが返信先に設定されること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::sendBulkMail
     */
    function testSendEmailWithoutReplyTo()
    {
        mb_internal_encoding("utf-8");

        $to = 'recipient@example.com';
        $from = '山田太郎 <sender@example.com>';
        $expectedFrom = mb_encode_mimeheader('山田太郎 ') . ' <sender@example.com>';
        $reply = '';
        $expectedReply = 'sender@example.com';
        $templateData = [];
        $defaultTemplateData = [];

        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 5;
        $mail = new Mail($this->region, $quotaLimit);

        // 宛先の配列
        $mail->addDestination($to, $templateData);
        $expectedDestinationsArray = [];
        $expectedDestinationsArray[] = $this->createExpectedDestinationsArray($to, $templateData);

        // リクエストボディの配列
        $expectedRequestBodyArray =
            $this->createExpectedRequestBodyArray($expectedDestinationsArray, $defaultTemplateData, $expectedFrom, $expectedReply);

        // リクエストボディの確認
        $this->mockPostRequest
            ->shouldReceive('setBody')
            ->with(json_encode($expectedRequestBodyArray));

        $result = $mail->sendBulkMail($from, $reply, $this->templateName, $defaultTemplateData);

        // 処理完了の確認
        $this->assertEquals(true, $result);
    }

    /**
     * fromがメールアドレスのみの場合でも送信できること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::sendBulkMail
     */
    function testSendEmailAddressOnly()
    {
        mb_internal_encoding("utf-8");

        $to = 'recipient@example.com';
        $from = 'sender@example.com';
        $reply = 'sender@example.com';
        $templateData = [];
        $defaultTemplateData = [];

        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 5;
        $mail = new Mail($this->region, $quotaLimit);

        // 宛先の配列
        $mail->addDestination($to, $templateData);
        $expectedDestinationsArray = [];
        $expectedDestinationsArray[] = $this->createExpectedDestinationsArray($to, $templateData);

        // リクエストボディの配列
        $expectedRequestBodyArray =
            $this->createExpectedRequestBodyArray($expectedDestinationsArray, $defaultTemplateData, $from, $reply);

        // リクエストボディの確認
        $this->mockPostRequest
            ->shouldReceive('setBody')
            ->with(json_encode($expectedRequestBodyArray));

        $result = $mail->sendBulkMail($from, $reply, $this->templateName, $defaultTemplateData);

        // 処理完了の確認
        $this->assertEquals(true, $result);
    }

    /**
     * 設定セットが指定できること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::sendBulkMail
     */
    function testSendEmailWithConfigSet()
    {
        mb_internal_encoding("utf-8");

        $to = 'recipient@example.com';
        $from = 'sender@example.com';
        $reply = 'sender@example.com';
        $templateData = [];
        $defaultTemplateData = [];
        $configurationsSetName = 'config-name';

        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 5;
        $mail = new Mail($this->region, $quotaLimit);

        // 宛先の配列
        $mail->addDestination($to, $templateData);
        $expectedDestinationsArray = [];
        $expectedDestinationsArray[] = $this->createExpectedDestinationsArray($to, $templateData);

        // リクエストボディの配列
        $expectedRequestBodyArray =
            $this->createExpectedRequestBodyArray($expectedDestinationsArray, $defaultTemplateData, $from, $reply, $configurationsSetName);

        // リクエストボディの確認
        $this->mockPostRequest
            ->shouldReceive('setBody')
            ->with(json_encode($expectedRequestBodyArray));

        $result = $mail->sendBulkMail($from, $reply, $this->templateName, $defaultTemplateData, $configurationsSetName);

        // 処理完了の確認
        $this->assertEquals(true, $result);
    }

    /**
     * 宛先が0件の時は送信できないこと
     *
     * @test
     * @covers SesPhp54Ec2\Mail::sendBulkMail
     */
    function testSendEmailWithNoRecipient()
    {
        mb_internal_encoding("utf-8");

        $from = 'sender@example.com';
        $reply = 'sender@example.com';
        $defaultTemplateData = [];

        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 5;
        $mail = new Mail($this->region, $quotaLimit);

        $result = $mail->sendBulkMail($from, $reply, $this->templateName, $defaultTemplateData);

        // 処理完了の確認
        $this->assertEquals(false, $result);
    }

    /**
     * 宛先が50件未満のときはfalse、50件ではtrueが返ること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::isLimit
     */
    function testIsLimit()
    {
        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 100;
        $mail = new Mail($this->region, $quotaLimit);

        $this->assertEquals(false, $mail->isLimit());
        for ($i = 1; $i < 50; $i++) {
            $to = 'recipient' . $i . '@example.com';
            // 宛先の追加
            $mail->addDestination($to, []);
        }
        $this->assertEquals(false, $mail->isLimit());

        $mail->addDestination('recipient50@example.com', []);
        $this->assertEquals(true, $mail->isLimit());
    }

    /**
     * 宛先が1秒あたりの送信上限未満のときはfalse、上限値の場合はtrueが返ること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::isQuotaLimit
     */
    function testIsQuotaLimit()
    {
        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 20;
        $mail = new Mail($this->region, $quotaLimit);

        $this->assertEquals(false, $mail->isLimit());
        for ($i = 1; $i < 20; $i++) {
            $to = 'recipient' . $i . '@example.com';
            // 宛先の追加
            $mail->addDestination($to, []);
        }
        $this->assertEquals(false, $mail->isQuotaLimit());

        $mail->addDestination('recipient20@example.com', []);
        $this->assertEquals(true, $mail->isQuotaLimit());
    }

    /**
     * 51件目の宛先を追加しようとしたときは例外が投げられること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::addDestination
     */
    function testLimitException()
    {
        $this->setExpectedException('\Exception');

        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 100;
        $mail = new Mail($this->region, $quotaLimit);

        for ($i = 1; $i < 51; $i++) {
            $to = 'recipient' . $i . '@example.com';
            // 宛先の追加
            $mail->addDestination($to, []);
        }

        $mail->addDestination('recipient51@example.com', []);
    }

    /**
     * 宛先配列が初期化できること
     *
     * @test
     * @covers SesPhp54Ec2\Mail::clearDestinations
     */
    function testClearDestinations()
    {
        // モックを生成
        $this->createCunstructorMocks();

        $quotaLimit = 100;
        $mail = new Mail($this->region, $quotaLimit);

        for ($i = 0; $i < 50; $i++) {
            $to = 'recipient' . $i . '@example.com';
            // 宛先の追加
            $mail->addDestination($to, []);
        }
        $mail->clearDestinations();
        $this->assertEquals(false, $mail->isLimit());

        for ($i = 0; $i < 50; $i++) {
            $to = 'recipient' . $i . '@example.com';
            // 宛先の追加
            $mail->addDestination($to, []);
        }

        $this->assertEquals(true, $mail->isLimit());
    }

    private function createExpectedDestinationsArray($to, $templateData)
    {
        return [
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

    private function createExpectedRequestBodyArray($destinationsArray, $defaultTemplateData, $from, $reply, $configurationsSetName = '')
    {
        return [
            'BulkEmailEntries' => $destinationsArray,
            'DefaultContent' => [
                'Template' => [
                    'TemplateArn' => 'arn:aws:ses:' . $this->region . ':' . $this->accountId . ':template/' . $this->templateName,
                    'TemplateData' => $defaultTemplateData ? json_encode($defaultTemplateData) : '{}',
                    'TemplateName' => $this->templateName
                ]
            ],
            'FromEmailAddress' => $from,
            'ReplyToAddresses' => [$reply],
            'ConfigurationsSetName' => $configurationsSetName
        ];
    }

    private function createCunstructorMocks()
    {
        $mockGetResponse = Mockery::mock('getResponse');
        $mockGetResponse
            ->shouldReceive('getBody')
            ->andReturn('{"accountId": "111122223333"}');

        $mockPostResponse = Mockery::mock('postResponse');
        $mockPostResponse
            ->shouldReceive('getStatusCode')
            ->andReturn(200);

        $mockGetRequest = Mockery::mock('getRequest');
        $mockGetRequest
            ->shouldReceive('send')
            ->andReturn($mockGetResponse);

        $mockPostRequest = Mockery::mock('postRequest');
        $mockPostRequest
            ->shouldReceive('send')
            ->andReturn($mockPostResponse);
        $this->mockPostRequest = $mockPostRequest;

        $mockSesClient = Mockery::mock('sesClient');
        $mockSesClient
            ->shouldReceive('get')
            ->with('http://169.254.169.254/latest/dynamic/instance-identity/document')
            ->andReturn($mockGetRequest);
        $mockSesClient
            ->shouldReceive('post')
            ->with('https://email.' . $this->region . '.amazonaws.com/v2/email/outbound-bulk-emails')
            ->andReturn($mockPostRequest);
        $mockFactory = Mockery::mock('alias:\Aws\Ses\SesClient');
        $mockFactory
            ->shouldReceive('factory')
            ->with(['region' => $this->region])
            ->andReturn($mockSesClient);
    }
}
