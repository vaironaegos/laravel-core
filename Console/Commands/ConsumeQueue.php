<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Console\Commands;

use Astrotech\Core\Base\Adapter\Contracts\LogSystem;
use Astrotech\Core\Base\Adapter\Contracts\QueueSystem;
use Astrotech\Core\Base\Infra\ConsumerBase;
use LogicException;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;

final class ConsumeQueue extends Command
{
    protected $signature = 'rabbitmq:consume {--queue=}';
    protected $description = 'Connect in queue for consuming messages';
    private string $exchange;
    private string $queue;
    private array $routingKeys;
    private array $actionsMapper = [];

    public function __construct(
        private QueueSystem $queueSystem,
        private LogSystem $logSystem
    ) {
        $this->actionsMapper = require base_path('config/handlersMapper.php');

        $this->exchange = env('APP_NAME');
        parent::__construct();
    }

    public function handle(): void
    {
        $this->queue = $this->option('queue');

        foreach ($this->actionsMapper as $queue => $routingKeys) {
            if ($queue !== $this->queue) {
                continue;
            }

            foreach ($routingKeys as $routingKey => $handlers) {
                $this->queueSystem->prepareChannel($this->queue, $this->exchange, $routingKey);
            }
        }

        $this->queueSystem->consume($this->queue, fn(AMQPMessage $message) => $this->processMessage($message));

        $channel = $this->queueSystem->getChannel();
        while ($channel->is_consuming()) {
            $this->warn("[" . date('Y-m-d H:i:s') . "] Starting app consumer in queue '{$this->queue}'");
            $channel->wait();
        }
    }

    private function processMessage(AMQPMessage $message): void
    {
        $this->info("Message Received: {$message->getBody()} | Routing Key {$message->getRoutingKey()}");

        $messageBody = json_decode($message->getBody(), true);
        $handlers = [];

        foreach ($this->actionsMapper as $queue => $routingKeys) {
            if ($queue !== $this->queue) {
                continue;
            }

            foreach ($routingKeys as $routingKey => $handlersMapped) {
                if ($routingKey !== $messageBody['name']) {
                    continue;
                }

                $handlers = $handlersMapped;
                break;
            }
        }

        if (!empty($handlers)) {
            $this->info("Handlers Found for '" . trim($messageBody['action']) . "' action! Executing...");

            foreach ($handlers as $handlerClassName) {
                $this->info("Executing {$handlerClassName}...");

                /** @var ConsumerBase $handler */
                $handler = new $handlerClassName($message, $this->logSystem);
                $handler->execute();
            }

            $this->info("All handlers executed!");

            try {
                if ($handler->isHasError()) {
                    return;
                }

                $message->ack();
            } catch (LogicException $e) {
                $this->warn("Error: {$e->getMessage()} | {$e->getFile()}:{$e->getLine()}");
            }

            return;
        }

        $this->info("No handlers for Execute!");
        $message->nack(true);
    }
}
