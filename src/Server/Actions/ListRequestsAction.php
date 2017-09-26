<?php

namespace Mcustiel\Phiremock\Server\Actions;

use Mcustiel\Phiremock\Common\StringStream;
use Mcustiel\Phiremock\Domain\Expectation;
use Mcustiel\Phiremock\Server\Actions\Base\AbstractRequestAction;
use Mcustiel\Phiremock\Server\Model\RequestStorage;
use Mcustiel\Phiremock\Server\Utils\RequestExpectationComparator;
use Mcustiel\PowerRoute\Actions\ActionInterface;
use Mcustiel\PowerRoute\Common\TransactionData;
use Mcustiel\SimpleRequest\RequestBuilder;
use Psr\Log\LoggerInterface;

class ListRequestsAction extends AbstractRequestAction implements ActionInterface
{
    /**
     * @var \Mcustiel\Phiremock\Server\Model\RequestStorage
     */
    private $requestsStorage;
    /**
     * @var \Mcustiel\Phiremock\Server\Utils\RequestExpectationComparator
     */
    private $comparator;

    public function __construct(
        RequestBuilder $requestBuilder,
        RequestStorage $storage,
        RequestExpectationComparator $comparator,
        LoggerInterface $logger
    ) {
        parent::__construct($requestBuilder, $logger);
        $this->requestsStorage = $storage;
        $this->comparator = $comparator;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Mcustiel\PowerRoute\Actions\ActionInterface::execute()
     */
    public function execute(TransactionData $transactionData, $argument = null)
    {
        $transactionData->setResponse(
            $this->processAndGetResponse(
                $transactionData,
                function (TransactionData $transaction, Expectation $expectation) {
                    $this->validateRequestOrThrowException($expectation, $this->logger);
                    $executions = $this->searchForExecutionsCount($expectation);
                    $this->logger->debug('Listed ' . count($executions) . ' request matching the expectation');

                    return $transaction->getResponse()
                        ->withStatus(200)
                        ->withHeader('Content-Type', 'application/json')
                        ->withBody(new StringStream(json_encode($executions)));
                }
            )
        );
    }

    private function searchForExecutionsCount(Expectation $expectation)
    {
        $executions = [];
        foreach ($this->requestsStorage->listRequests() as $request) {
            if ($this->comparator->equals($request, $expectation)) {
                $executions[] = [
                    'method' => $request->getMethod(),
                    'url' => (string) $request->getUri(),
                    'headers' => $request->getHeaders(),
                    'cookies' => $request->getCookieParams(),
                    'body' => (string) $request->getBody(),
                ];
            }
        }

        return $executions;
    }
}
