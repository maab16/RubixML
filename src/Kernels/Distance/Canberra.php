<?php

namespace Rubix\ML\Kernels\Distance;

use Rubix\ML\DataType;

use const Rubix\ML\EPSILON;

/**
 * Canberra
 *
 * A weighted version of the Manhattan distance, Canberra examines the sum of
 * a series of fractional differences between two samples. Canberra can be
 * very sensitive when both coordinates are near zero.
 *
 * References:
 * [1] G. N. Lance et al. (1967). Mixed-data classificatory programs I.
 * Agglomerative Systems.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class Canberra implements Distance
{
    /**
     * Return the data types that this kernel is compatible with.
     *
     * @return int[]
     */
    public function compatibility() : array
    {
        return [
            DataType::CONTINUOUS,
        ];
    }

    /**
     * Compute the distance between two vectors.
     *
     * @param (int|float)[] $a
     * @param (int|float)[] $b
     * @return float
     */
    public function compute(array $a, array $b) : float
    {
        $distance = 0.;

        foreach ($a as $i => $valueA) {
            $valueB = $b[$i];

            $distance += abs($valueA - $valueB)
                / ((abs($valueA) + abs($valueB)) ?: EPSILON);
        }

        return $distance;
    }
}
