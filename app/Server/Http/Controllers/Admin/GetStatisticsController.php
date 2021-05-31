<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Contracts\StatisticsRepository;
use App\Contracts\UserRepository;
use App\Server\Configuration;
use App\Server\Connections\ControlConnection;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetStatisticsController extends AdminController
{
    protected $keepConnectionOpen = true;

    /** @var StatisticsRepository */
    protected $statisticsRepository;

    public function __construct(StatisticsRepository $statisticsRepository)
    {
        $this->statisticsRepository = $statisticsRepository;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $from = today()->subWeek()->toDateString();
        $until = today()->toDateString();

        $this->statisticsRepository->getStatistics($request->get('from', $from), $request->get('until', $until))
            ->then(function ($statistics) use ($httpConnection) {
                $httpConnection->send(
                    respond_json([
                        'statistics' => $statistics,
                    ])
                );

                $httpConnection->close();
            });
    }
}
