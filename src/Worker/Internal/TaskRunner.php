<?php
namespace Icicle\Concurrent\Worker\Internal;

use Icicle\Concurrent\ChannelInterface;
use Icicle\Concurrent\Worker\TaskInterface;

class TaskRunner
{
    /**
     * @var bool
     */
    private $idle = true;

    /**
     * @var \Icicle\Concurrent\ChannelInterface
     */
    private $channel;

    public function __construct(ChannelInterface $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @coroutine
     *
     * @return \Generator
     */
    public function run()
    {
        $task = (yield $this->channel->receive());

        while ($task instanceof TaskInterface) {
            $this->idle = false;

            try {
                $result = (yield $task->run());
            } catch (\Exception $exception) {
                $result = new TaskFailure($exception);
            }

            yield $this->channel->send($result);

            $this->idle = true;

            $task = (yield $this->channel->receive());
        }

        yield $task;
    }

    /**
     * @return bool
     */
    public function isIdle()
    {
        return $this->idle;
    }
}