<?php

namespace Bitcoin\Script;

use Bitcoin\Exceptions\ScriptStackException;

/**
 * Class ScriptStack
 * @package Bitcoin
 */
class ScriptStack
{
    /**
     * @var array
     */
    protected $stack = array();

    /**
     * @returns self
     */
    public function __construct()
    {
        return $this;
    }

    /**
     * Pop a value from the stack
     *
     * @return mixed
     * @throws ScriptStackException
     */
    public function pop()
    {
        if (count($this->stack) < 1) {
            throw new ScriptStackException('Attempted to pop from stack when empty');
        }

        return array_pop($this->stack);
    }

    /**
     * Push a value onto the stack
     *
     * @param $value
     * @return $this
     */
    public function push($value)
    {
        array_push($this->stack, $value);
        return $this;
    }

    /**
     * Get index of $pos relative to the top of the stack
     *
     * @param $pos
     * @return int
     */
    private function getIndexFor($pos)
    {
        $index = (count($this->stack) + $pos);
        return $index;
    }

    /**
     * Erase the item at $pos (relative to the top of the stack)
     *
     * @param $pos
     * @return $this
     * @throws ScriptStackException
     */
    public function erase($pos)
    {
        $index = $this->getIndexFor($pos);
        if (!isset($this->stack[$index])) {
            throw new ScriptStackException('No value in this location');
        }

        unset($this->stack[$index]);
        return $this;
    }

    /**
     * Set $value to the $pos position in the stack (Relative to the top)
     *
     * @param $pos
     * @param $value
     * @return $this
     */
    public function set($pos, $value)
    {
        $index = $this->getIndexFor($pos);
        $this->stack[$index] = $value;
        return $this;
    }

    /**
     * Get the $pos value from the stack
     *
     * @param $pos
     * @return mixed
     */
    public function top($pos)
    {
        $index = $this->getIndexFor($pos);
        return $this->stack[$index];
    }

    /**
     * Dump the current stack
     *
     * @return array
     */
    public function dump()
    {
        return $this->stack;
    }
}
