<?php

namespace Rubix\ML\Tests;

use Rubix\ML\Learner;
use Rubix\ML\DataType;
use Rubix\ML\Estimator;
use Rubix\ML\Persistable;
use Rubix\ML\Backends\Serial;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\BootstrapAggregator;
use Rubix\ML\Regressors\RegressionTree;
use Rubix\ML\Datasets\Generators\SwissRoll;
use Rubix\ML\CrossValidation\Metrics\RSquared;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @group MetaEstimators
 * @covers \Rubix\ML\BootstrapAggregator
 */
class BootstrapAggregatorTest extends TestCase
{
    protected const TRAIN_SIZE = 400;
    protected const TEST_SIZE = 10;
    protected const MIN_SCORE = 0.9;

    protected const RANDOM_SEED = 0;

    /**
     * @var \Rubix\ML\Datasets\Generators\SwissRoll
     */
    protected $generator;

    /**
     * @var \Rubix\ML\BootstrapAggregator
     */
    protected $estimator;

    /**
     * @var \Rubix\ML\CrossValidation\Metrics\RSquared
     */
    protected $metric;

    /**
     * @before
     */
    protected function setUp() : void
    {
        $this->generator = new SwissRoll(4., -7., 0., 1., 0.3);

        $this->estimator = new BootstrapAggregator(new RegressionTree(10), 30, 0.5);

        $this->estimator->setBackend(new Serial());

        $this->metric = new RSquared();

        srand(self::RANDOM_SEED);
    }

    protected function assertPreConditions() : void
    {
        $this->assertFalse($this->estimator->trained());
    }
    
    /**
     * @test
     */
    public function build() : void
    {
        $this->assertInstanceOf(BootstrapAggregator::class, $this->estimator);
        $this->assertInstanceOf(Learner::class, $this->estimator);
        $this->assertInstanceOf(Persistable::class, $this->estimator);
        $this->assertInstanceOf(Estimator::class, $this->estimator);
    }

    /**
     * @test
     */
    public function type() : void
    {
        $this->assertSame(Estimator::REGRESSOR, $this->estimator->type());
    }

    /**
     * @test
     */
    public function compatibility() : void
    {
        $expected = [
            DataType::CATEGORICAL,
            DataType::CONTINUOUS,
        ];

        $this->assertEquals($expected, $this->estimator->compatibility());
    }
    
    /**
     * @test
     */
    public function trainPredict() : void
    {
        $training = $this->generator->generate(self::TRAIN_SIZE);

        $testing = $this->generator->generate(self::TEST_SIZE);

        $this->estimator->train($training);

        $this->assertTrue($this->estimator->trained());

        $predictions = $this->estimator->predict($testing);

        $score = $this->metric->score($predictions, $testing->labels());

        $this->assertGreaterThanOrEqual(self::MIN_SCORE, $score);
    }
    
    /**
     * @test
     */
    public function predictUntrained() : void
    {
        $this->expectException(RuntimeException::class);

        $this->estimator->predict(Unlabeled::quick());
    }
}
