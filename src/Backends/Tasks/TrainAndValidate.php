<?php

namespace Rubix\ML\Backends\Tasks;

use Rubix\ML\Learner;
use Rubix\ML\Datasets\Dataset;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\CrossValidation\Metrics\Metric;

class TrainAndValidate extends Task
{
    /**
     * Train the learner and then return its validation score.
     *
     * @param \Rubix\ML\Learner $estimator
     * @param \Rubix\ML\Datasets\Dataset $training
     * @param \Rubix\ML\Datasets\Labeled $testing
     * @param \Rubix\ML\CrossValidation\Metrics\Metric $metric
     * @return float
     */
    public static function score(
        Learner $estimator,
        Dataset $training,
        Labeled $testing,
        Metric $metric
    ) : float {
        $estimator->train($training);

        $predictions = $estimator->predict($testing);

        return $metric->score($predictions, $testing->labels());
    }

    /**
     * @param \Rubix\ML\Learner $estimator
     * @param \Rubix\ML\Datasets\Dataset $training
     * @param \Rubix\ML\Datasets\Labeled $testing
     * @param \Rubix\ML\CrossValidation\Metrics\Metric $metric
     */
    public function __construct(
        Learner $estimator,
        Dataset $training,
        Labeled $testing,
        Metric $metric
    ) {
        parent::__construct([self::class, 'score'], [
            $estimator, $training, $testing, $metric,
        ]);
    }
}
