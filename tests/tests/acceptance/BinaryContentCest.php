<?php

use Mcustiel\Phiremock\Client\Phiremock as PhiremockClient;
use Mcustiel\Phiremock\Client\Utils\A;
use Mcustiel\Phiremock\Client\Utils\Is;
use Mcustiel\Phiremock\Client\Utils\Respond;
use Mcustiel\Phiremock\Domain\Condition;
use Mcustiel\Phiremock\Domain\Expectation;
use Mcustiel\Phiremock\Domain\Request;
use Mcustiel\Phiremock\Domain\Response;
use React\Dns\Config\Config;
use Codeception\Configuration;

class BinaryContentCest
{
    /**
     * @var \Mcustiel\Phiremock\Client\Phiremock
     */
    private $phiremock;

    public function _before(AcceptanceTester $I)
    {
        $I->sendDELETE('/__phiremock/expectations');
        $this->phiremock = new PhiremockClient('127.0.0.1', '8086');
    }

    // tests
    public function shouldCreateAnExpectationWithBinaryResponseTest(AcceptanceTester $I)
    {
        $expectation = new Expectation();

        $request = new Request();
        $request->setMethod('get');
        $request->setUrl(new Condition('isEqualTo', '/show-me-the-image'));

        $responseContents = file_get_contents(Configuration::dataDir() . '/fixtures/number-1943293_640.jpg');

        $response = new Response();
        $response->setStatusCode(200);
        $response->setHeaders(['Content-Type' => 'image/jpeg', 'Content-Encoding' => 'base64']);
        $response->setBody(base64_encode($responseContents));

        $expectation->setRequest($request)->setResponse($response);
        $this->phiremock->createExpectation($expectation);

        $I->sendGET('/show-me-the-image');
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'image/jpeg');
        $responseBody = $I->grabResponse();
        $I->assertEquals($responseContents, $responseBody);

    }
}

