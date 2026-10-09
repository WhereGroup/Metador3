<?php

namespace App\Event;


use App\Service\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class SourceEvent
 * @package App\Event
 */
class SourceEvent extends Event
{
    public const CONFIRM = 'source.confirm';
    public const POST_DELETE = 'source.post_delete';

    /** @var  UserInterface $user */
    private $messages;
    private $slug;

    /**
     * TaskManagerEvent constructor.
     * @param $slug
     */
    public function __construct($slug)
    {
        $this->messages = [];
        $this->setSlug($slug);
    }

    /**
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $slug
     * @return SourceEvent
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @param $message
     * @param $parameter
     * @return SourceEvent
     */
    public function addMessage($message, $parameter = [])
    {
        $this->messages[]  = [
            'message'   => $message,
            'parameter' => $parameter
        ];
        return $this;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
