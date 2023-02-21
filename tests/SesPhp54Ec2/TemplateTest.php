<?php

namespace tests\SesPhp54Ec2;

use SesPhp54Ec2\Template;
use Mockery;

class TemplateTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * メールテンプレートが作成できること
     *
     * @test
     * @covers SesPhp54Ec2\Template::createTemplate
     */
    public function testCreateTemplate()
    {
        $region = 'ap-northeast-1';

        // モックを生成
        $mockResponse = Mockery::mock('response');
        $mockResponse
            ->shouldReceive('getStatusCode')
            ->andReturn(200);

        $mockRequest = Mockery::mock('request');
        $mockRequest
            ->shouldReceive('send')
            ->andReturn($mockResponse);

        $mockSesClient = Mockery::mock('sesClient');
        $mockSesClient
            ->shouldReceive('post')
            ->with('https://email.' . $region . '.amazonaws.com/v2/email/templates')
            ->andReturn($mockRequest);
        $mockFactory = Mockery::mock('alias:\Aws\Ses\SesClient');
        $mockFactory
            ->shouldReceive('factory')
            ->with(['region' => $region])
            ->andReturn($mockSesClient);

        $sesTemplate = new Template($region);

        // リクエストボディの確認
        $expectedTemplateName = $sesTemplate->getTemplateName();
        $subject = 'テストメールです。';
        $body = <<<'PHP_EOL'
テストメールです。
--------------------
山田太郎"
PHP_EOL;
        $expectedBodyArray = [
            "TemplateContent" => [
                "Subject" => $subject,
                "Text" => $body
            ],
            "TemplateName" => $expectedTemplateName
        ];
        $mockRequest
            ->shouldReceive('setBody')
            ->with(json_encode($expectedBodyArray));

        $templateName = $sesTemplate->createTemplate($subject, $body);

        // 処理完了の確認
        $this->assertEquals($expectedTemplateName, $templateName);
    }

    /**
     * メールテンプレートが削除できること
     *
     * @test
     * @covers SesPhp54Ec2\Template::deleteTemplatee
     */
    public function testDeleteTemplate()
    {
        $region = 'ap-northeast-1';

        // モックを生成
        $mockResponse = Mockery::mock('response');
        $mockResponse
            ->shouldReceive('getStatusCode')
            ->andReturn(200);

        $mockRequest = Mockery::mock('request');
        $mockRequest
            ->shouldReceive('send')
            ->andReturn($mockResponse);

        $mockSesClient = Mockery::mock('sesClient');

        $mockFactory = Mockery::mock('alias:\Aws\Ses\SesClient');
        $mockFactory
            ->shouldReceive('factory')
            ->with(['region' => $region])
            ->andReturn($mockSesClient);

        $sesTemplate = new Template($region);

        $templateName = $sesTemplate->getTemplateName();
        $mockSesClient
            ->shouldReceive('delete')
            ->with('https://email.' . $region . '.amazonaws.com/v2/email/templates/' . $templateName)
            ->andReturn($mockRequest);

        $result = $sesTemplate->deleteTemplate();

        // 処理完了の確認
        $this->assertEquals($result, true);
    }
}
