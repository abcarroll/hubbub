<?php

namespace Hubbub;

/**
 * DelimitedDataBuffer will buffer a possibly fragmented stream of delimited data and return a list of completed, un-fragmented messages.
 *
 * This is best used on unpredictable network streams. Specify your protocol's delimiter (eg. \n) and fragmented messages may be passed to receive().  Once a
 * fully delimited message is received, it will be ready for consumption via getNextComplete() or getAllComplete().  Since we have defined a queue, you can
 * optionally use a FILO queue by passing DelimitedBuffer::QUEUE_FILO as the second parameter in the constructor for a First-In-Last-Out queue.  It defaults to
 * a First-In-First-Out (FIFO) queue.
 *
 * @package Hubbub
 */
class DelimitedDataBuffer implements \Iterator {
    /**
     * The delimiter to use for item separation on the data stream
     * @var string
     */
    protected $delimiter;

    /**
     * strlen() of $delimiter member
     * @var int
     */
    protected $delimiterLen;

    /**
     * The queue type, either QUEUE_FIFO or QUEUE_FILO
     * @var int
     */
    protected $queueType;

    /**
     * The iterator's position
     * @var int
     */
    protected $i = 0;

    /**
     * Stores incomplete (un-delimited) item fragments until they are completed.
     * @var string
     */
    protected $fragmentBuffer = '';

    /**
     * Stores completely delimited items ready for consumption.
     * @var array
     */
    protected $completedQueue = [];

    const QUEUE_FIFO = 1;
    const QUEUE_FILO = 2;

    /**
     * DelimitedBufferQueue constructor.
     *
     * @param string $delimiter The delimiter to use in the data stream.
     * @param int    $queueType The completed item queue type.
     */
    public function __construct($delimiter = "\n", $queueType = self::QUEUE_FIFO) {
        $this->setDelimiter($delimiter);
        $this->queueType = $queueType;
    }

    /**
     * Receive data from the data stream, and parse it using the currently set delimiter.
     *
     * Receive() is meant to receive and parse data streams that are delimited by a specific delimiter marker such as \n.  You may pass any number of complete,
     * incomplete, or mixed strings of data to the receive() function.  Complete items, i.e items which have an ending delimiter/marker are placed on to the
     * completed items queue, ready for consumption.  Incomplete items will be stored in an internal fragment buffer and await completion by subsequent
     * receive() calls.
     *
     * You may consume (return and remove) data using consumeNext() and consumeAll() methods, or instead peek at the data using the \Iterator interface.
     *
     * @param string $data Arbitrary data of any length, with or without a full delimited item.
     */
    public function receive($data) {
        $this->fragmentBuffer .= $data;
        $pos = strrpos($this->fragmentBuffer, $this->delimiter);
        // If the recvBuffer contains no fragmented messages
        if($pos !== false) {
            if(substr($this->fragmentBuffer, ($this->delimiterLen * -1)) == $this->delimiter) {
                $completedItemsStr = substr($this->fragmentBuffer, 0, ($this->delimiterLen * -1));
                $this->fragmentBuffer = '';
            } else {
                // else, there is a partially received message at the end.  so pull out the complete line(s) and tack the end fragment onto the buffer
                $completedItemsStr = substr($this->fragmentBuffer, 0, $pos);
                $this->fragmentBuffer = substr($this->fragmentBuffer, $pos + $this->delimiterLen);
            }

            $completedItems = explode($this->delimiter, $completedItemsStr);

            if($this->queueType == self::QUEUE_FIFO) {
                array_push($this->completedQueue, ... $completedItems);
            } elseif($this->queueType == self::QUEUE_FILO) {
                array_unshift($this->completedQueue, ... $completedItems);
            }
        }
    }

    /**
     * Returns and removes the next item in the queue.
     *
     * @return mixed
     */
    public function consumeNext() {
        return array_shift($this->completedQueue);
    }

    /**
     * Returns and removes all completed items in the queue.
     *
     * @return array
     */
    public function consumeAll() {
        return array_splice($this->completedQueue, 0, count($this->completedQueue), []);
    }

    /**
     * Sets the delimiter to use for the data stream.
     *
     * @param string $delimiter  One or more characters to use as the delimiter.
     *
     * @throws \Exception
     */
    public function setDelimiter($delimiter) {
        $len = strlen($delimiter);
        if($len > 0) {
            $this->delimiter = $delimiter;
            $this->delimiterLen = $len;
        } else {
            throw new \Exception("Delimiter specified must be one ore more characters");
        }
    }

    /**
     * Gets the delimiter being used for the data stream.
     *
     * @return mixed One or more characters being used as the delimiter.
     */
    public function getDelimiter() {
        return $this->delimiter;
    }

    /**
     * Define the queue as either a First-In-First-Out (FIFO) or First-In-Last-Out (FILO) queue.
     *
     * @param int $queueType Either DelimitedBufferQueue::QUEUE_FIFO or DelimitedBufferQueue::QUEUE_FILO
     *
     * @throws \Exception
     */
    public function setQueueType($queueType) {
        $up = new \Exception("Undetermined queue type passed.  Queue type must be of ::QUEUE_FIFO or ::QUEUE_FILO");
        if($queueType != self::QUEUE_FIFO && $queueType != self::QUEUE_FILO) {
            throw $up;
        } else {
            $this->queueType = $queueType;
        }
    }

    /**
     * Returns the current queue type, either as a First-In-First-Out (FIFO) or First-In-Last-Out (FILO) queue..
     *
     * @return int Either DelimitedBufferQueue::QUEUE_FIFO or DelimitedBufferQueue::QUEUE_FILO
     */
    public function getQueueType() {
        return $this->queueType;
    }

    /**
     * Returns the current item in the iterator pointer.  For the \Iterator interface.
     *
     * @return string|false
     */
    public function current() {
        return $this->completedQueue[$this->i];
    }

    /**
     * Advances the iterator pointer by one.  For the \Iterator interface.
     */
    public function next() {
        $this->completedQueue++;
    }

    /**
     * Returns the current iterator pointer's key.  For the \Iterator interface.
     *
     * @return int
     */
    public function key() {
        return $this->i;
    }

    /**
     * Rewinds the iterator pointer back to the beginning.  For the \Iterator interface.
     */
    public function rewind() {
        $this->i = 0;
    }

    /**
     * Returns the validity of the current iterator pointer.  For the \Iterator interface.
     *
     * @return bool
     */
    public function valid() {
        return isset($this->completedQueue[$this->i]);
    }

    /**
     * Clears all completed items while leaving the fragment buffer alone.
     */
    public function clearComplete() {
        $this->i = 0;
        $this->completedQueue = [];
    }

    /**
     * Clears all completed items, as well as the fragment buffer.
     */
    public function clearAll() {
        $this->fragmentBuffer = '';
        $this->clearComplete();
    }

    /**
     * Returns a count of completed items waiting on the queue.
     *
     * @return int
     */
    public function count() {
        return count($this->completedQueue);
    }

}