<?php declare(strict_types=1);

namespace Ab\LocoX\Clause\Nonterminal;

use Ab\LocoX\MonoParser;
use Ab\LocoX\Exception\ParseFailureException;

/**
 * Callback accepts a single argument containing all submatches, however many
 */
class BoundedRepeat extends MonoParser
{
    private $lower;
    public $optional;

    public function __construct($internal, ?int $lower, ?int $upper, $callback = null)
    {
        $this->lower = $lower;
        if (null === $upper) {
            $this->optional = null;
        } else {
            if ($upper < $lower) {
                throw new \Ab\LocoX\Exception\GrammarException(
                    "Can't create a " . get_class() . " with lower limit " . var_export($lower, true) .
                    " and upper limit " . var_export($upper, true)
                );
            }
            $this->optional = $upper - $lower;
        }
        $this->string = 'new ' . __CLASS__ . '(' . json_encode($internal) . ', ' . var_export($lower, true) . ', ' . var_export(
            $upper,
            true
        ) . ')';
        parent::__construct([$internal], $callback);
    }

    /**
     * default callback: just return the list
     */
    public function defaultCallback()
    {
        return func_get_args();
    }

    public function getResult($string, $i = 0)
    {
        $result = ['j' => $i, 'args' => []];

        // First do the non-optional segment
        // Any parse failures here are terminal
        for ($k = 0; $k < $this->lower; $k ++) {
            $match = $this->internals[0]->match($string, $result['j']);
            $result['j'] = $match['j'];
            $result['args'][] = $match['value'];
        }

        // next, the optional segment
        // null => no upper limit
        for ($k = 0; null === $this->optional || $k < $this->optional; $k ++) {
            try {
                $match = $this->internals[0]->match($string, $result['j']);
                $result['j'] = $match['j'];
                $result['args'][] = $match['value'];
            } catch (ParseFailureException $e) {
                break;
            }
        }

        return $result;
    }

    /**
     * nullable if lower limit is zero OR internal is nullable.
     */
    public function evaluateNullability(): bool
    {
        return 0 === $this->lower || true === $this->internals[0]->nullable;
    }

    /**
     * This parser contains only one internal
     */
    public function firstSet(): array
    {
        return [$this->internals[0]];
    }
}
