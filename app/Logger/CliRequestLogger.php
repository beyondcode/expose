<?php

namespace App\Logger;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class CliRequestLogger extends Logger
{
    /** @var Table */
    protected $table;

    /** @var Collection */
    protected $requests;

    /** @var \Symfony\Component\Console\Output\ConsoleSectionOutput */
    protected $section;

    public function __construct(ConsoleOutputInterface $consoleOutput)
    {
        parent::__construct($consoleOutput);

        $this->section = $this->output->section();

        $this->table = new Table($this->section);
        $this->table->setHeaders(['Method', 'URI', 'Response', 'Duration']);

        $this->requests = new Collection();
    }

    /**
     * @return ConsoleOutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    public function logRequest(LoggedRequest $loggedRequest)
    {
        if ($this->requests->has($loggedRequest->id())) {
            $this->requests[$loggedRequest->id()] = $loggedRequest;
        } else {
            $this->requests->prepend($loggedRequest, $loggedRequest->id());
        }
        $this->requests = $this->requests->slice(0, config('expose.max_logged_requests', 10));

        $this->section->clear();

        $this->table->setRows($this->requests->map(function (LoggedRequest $loggedRequest) {
            return [
                $loggedRequest->getRequest()->getMethod(),
                $loggedRequest->getRequest()->getUri(),
                optional($loggedRequest->getResponse())->getStatusCode().' '.optional($loggedRequest->getResponse())->getReasonPhrase(),
                $loggedRequest->getDuration().'ms',
            ];
        })->toArray());

        $this->table->render();
    }
}
