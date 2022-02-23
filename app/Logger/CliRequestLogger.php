<?php

namespace App\Logger;

use Illuminate\Support\Collection;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
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
        $this->table->setStyle($this->getTableStyle());
        $this->table->setHeaders(['Method', 'URI', 'Response', 'Time', 'Duration']);

        $this->requests = new Collection();
    }

    /**
     * @return ConsoleOutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    protected function getTableStyle()
    {
        return (new TableStyle())
            ->setHorizontalBorderChars('─')
            ->setVerticalBorderChars('│')
            ->setCrossingChars('┼', '┌', '┬', '┐', '┤', '┘', '┴', '└', '├')
        ;
    }

    protected function getRequestColor(?LoggedRequest $request)
    {
        $statusCode = optional($request->getResponse())->getStatusCode();
        $color = 'white';

        if ($statusCode >= 200 && $statusCode < 300) {
            $color = 'green';
        } elseif ($statusCode >= 300 && $statusCode < 400) {
            $color = 'blue';
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            $color = 'yellow';
        } elseif ($statusCode >= 500) {
            $color = 'red';
        }

        return $color;
    }

    public function logRequest(LoggedRequest $loggedRequest)
    {
        $dashboardUrl = 'http://127.0.0.1:'.config('expose.dashboard_port');

        if ($this->requests->has($loggedRequest->id())) {
            $this->requests[$loggedRequest->id()] = $loggedRequest;
        } else {
            $this->requests->prepend($loggedRequest, $loggedRequest->id());
        }
        $this->requests = $this->requests->slice(0, config('expose.max_logged_requests', 10));

        $this->section->clear();

        $this->table->setRows($this->requests->map(function (LoggedRequest $loggedRequest) use ($dashboardUrl) {
            return [
                $loggedRequest->getRequest()->getMethod(),
                $loggedRequest->getRequest()->getUri(),
                '<href='.$dashboardUrl.'/#'.$loggedRequest->id().';fg='.$this->getRequestColor($loggedRequest).';options=bold>'.
                optional($loggedRequest->getResponse())->getStatusCode().' '.optional($loggedRequest->getResponse())->getReasonPhrase()
                .'</>'
                ,
                $loggedRequest->getStartTime()->isToday() ? $loggedRequest->getStartTime()->toTimeString() : $loggedRequest->getStartTime()->toDateTimeString(),
                $loggedRequest->getDuration().'ms',
            ];
        })->toArray());

        $this->table->render();
    }
}
