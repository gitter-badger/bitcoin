<?php

namespace Bitcoin\Crypto\DRBG;

use Bitcoin\Crypto\Hash;
use Bitcoin\Util\Buffer;
use Mdanter\Ecc\GeneratorPoint;

/**
 * Class HMACDRBG
 * @package Bitcoin\Crypto\DRBG
 * @author Thomas Kerin
 */
class HMACDRBG implements DRBGInterface
{
    /**
     * @var string
     */
    private $algorithm;

    /**
     * @var string
     */
    private $K;

    /**
     * @var string
     */
    private $V;

    /**
     * @var int
     */
    private $reseedCounter;

    /**
     * Construct a HMAC deterministic byte generator.
     *
     * @param $algo
     * @param Buffer $entropy
     * @param Buffer $personalString
     * @param GeneratorPoint $generator
     */
    public function __construct($algo, Buffer $entropy, Buffer $personalString = null, GeneratorPoint $generator = null)
    {
        if (!in_array($algo, hash_algos())) {
            throw new \RuntimeException('HMACDRGB: Hashing algorithm not found');
        }

        $this->algorithm = $algo;
        $this->initialize($entropy, $personalString);
        return $this;
    }

    /**
     * Initialize the DRBG with the given $entropy and $personalString
     * @param Buffer $entropy
     * @param Buffer $personalString
     * @return $this
     */
    public function initialize(Buffer $entropy, Buffer $personalString = null)
    {
        $hlen       = strlen(hash($this->getHashAlgorithm(), 1, true));
        $vlen       = 8 * ceil($hlen / 8);

        $this->V    = str_pad('', $vlen, chr(0x01), STR_PAD_LEFT);
        $this->K    = str_pad('', $vlen, chr(0x00), STR_PAD_LEFT);
        $seed       = $entropy->serialize() . $personalString ?: '';

        $this->update($seed);
        return $this;
    }

    /**
     * Return the hash of the given binary $data
     * @param $data
     * @return string
     */
    public function hash($data)
    {
        $hash = Hash::hmac($this->algorithm, $data, $this->K, true);
        return $hash;
    }

    /**
     * Update the K and V values.
     *
     * @param null $data
     * @return $this
     */
    public function update($data = null)
    {
        $this->K = $this->hash(sprintf(
            "%s%s%s",
            $this->V,
            chr(0x00),
            $data ?: ''
        ));

        $this->V = $this->hash($this->V);

        if ($data) {
            $this->K = $this->hash(sprintf(
                "%s%s%s",
                $this->V,
                chr(0x01),
                $data
            ));

            $this->V = $this->hash($this->V);
        }

        return $this;
    }

    /**
     * Reseed the DRBG with new entropy, and reset the counter.
     *
     * @param Buffer $entropy
     */
    public function reseed(Buffer $entropy)
    {
        $this->update($entropy);
        $this->reseedCounter = 1;
    }

    /**
     * Return the hashing algorithm used by this generator
     *
     * @return string
     */
    public function getHashAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * Load $numBytes bytes from the DRBG
     *
     * @param int $numNumBytes
     * @return Buffer
     */
    public function bytes($numNumBytes)
    {
        $temp = "";

        // Build a string of $numBytes bytes from hashing the seeded DRBG
        while (strlen($temp) < $numNumBytes) {
            $this->V = $this->hash($this->V);
            $temp   .= $this->V;
        }

        $this->update(null);
        $this->reseedCounter++;

        return new Buffer(substr($temp, 0, $numNumBytes));

    }
}
