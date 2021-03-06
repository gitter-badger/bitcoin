<?php

namespace Bitcoin\Block;

use Bitcoin\Bitcoin;
use Bitcoin\Network;
use Bitcoin\Util\Parser;
use Bitcoin\Util\Buffer;
use Pleo\Merkle\FixedSizeTree;
use Bitcoin\Exceptions\MerkleTreeEmpty;

/**
 * Class MerkleRoot
 * @package Bitcoin\Block
 * @author Thomas Kerin
 */
class MerkleRoot
{
    /**
     * @var BlockInterface
     */
    protected $block;

    /**
     * @var callable
     */
    protected $hashFxn;

    /**
     * @var string
     */
    protected $lastHash;

    /**
     * Instantiate the class when given a block
     *
     * @param BlockInterface $block
     */
    public function __construct(BlockInterface $block)
    {
        $this->block   = $block;
        return $this;
    }

    /**
     * @return string
     */
    private function getLastHash()
    {
        $hash = $this->lastHash;
        return $hash;
    }

    /**
     * Set the last hash. Should only be set by calculateHash()
     *
     * @param string $lastHash
     */
    private function setLastHash($lastHash)
    {
        $this->lastHash = $lastHash;
    }

    /**
     * Calculate the hash from the transactions in this block.
     *
     * @return string
     * @throws \Exception
     */
    public function calculateHash()
    {
        $transactions = $this->block->getTransactions();
        $txCount      = count($transactions);
        $hashFxn      = Network::getHashFunction(true);

        if ($txCount == 0) {
            // TODO: Probably necessary. Should always have a coinbase at least.
            throw new MerkleTreeEmpty('Cannot compute Merkle root of an empty tree');
        }

        if ($txCount == 1) {
            $hash = $hashFxn($transactions[0]->serialize());
            $buffer = new Buffer($hash);

        } else {
            // Create a fixed size Merkle Tree
            $tree = new FixedSizeTree($txCount + ($txCount % 2), $hashFxn);

            // Compute hash of each transaction
            $last = '';
            foreach ($transactions as $i => $transaction) {
                $last = $transaction->serialize();
                $tree->set($i, $transaction->serialize());
            }

            // Check if we need to repeat the last hash (odd number of transactions)
            if (!Bitcoin::getMath()->isEven($txCount)) {
                $tree->set($txCount, $last);
            }

            $buffer = new Buffer($tree->hash());
        }

        $hash = new Parser();
        $hash = $hash
            ->writeBytes(32, $buffer, true)
            ->getBuffer()
            ->serialize('hex');

        $this->setLastHash($hash);
        return $this->getLastHash();
    }
}
