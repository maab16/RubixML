<?php

namespace Rubix\ML\Transformers;

use const Rubix\ML\EPSILON;

/**
 * L1 Normalizer
 *
 * Transform each sample vector in the sample matrix such that each feature is divided
 * by the L1 norm (or *magnitude*) of that vector. The resulting sample will have
 * continuous features between 0 and 1.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class L1Normalizer implements Transformer
{
    /**
     * Transform the dataset in place.
     *
     * @param array $samples
     */
    public function transform(array &$samples) : void
    {
        foreach ($samples as &$sample) {
            $norm = array_sum(array_map('abs', $sample)) ?: EPSILON;

            foreach ($sample as &$value) {
                $value /= $norm;
            }
        }
    }
}
