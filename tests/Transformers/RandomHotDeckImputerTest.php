<?php

namespace Rubix\ML\Tests\Transformers;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\Elastic;
use Rubix\ML\Transformers\Stateful;
use Rubix\ML\Transformers\Transformer;
use Rubix\ML\Datasets\Generators\Blob;
use Rubix\ML\Kernels\Distance\SafeEuclidean;
use Rubix\ML\Transformers\RandomHotDeckImputer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @group Transformers
 * @covers \Rubix\ML\Transformers\RandomHotDeckImputer
 */
class RandomHotDeckImputerTest extends TestCase
{
    protected const RANDOM_SEED = 0;

    /**
     * @var \Rubix\ML\Datasets\Unlabeled
     */
    protected $dataset;

    /**
     * @var \Rubix\ML\Datasets\Generators\Generator
     */
    protected $generator;
    
    /**
     * @var \Rubix\ML\Transformers\RandomHotDeckImputer
     */
    protected $transformer;

    /**
     * @before
     */
    protected function setUp() : void
    {
        $this->dataset = new Unlabeled([
            [30, 0.001],
            [NAN, 0.055],
            [50, -2.0],
            [60, NAN],
            [10, 1.0],
            [100, 9.0],
        ]);

        $this->generator = new Blob([30., 0.]);

        $this->transformer = new RandomHotDeckImputer(5, true, new SafeEuclidean(), '?');

        srand(self::RANDOM_SEED);
    }
    
    /**
     * @test
     */
    public function build() : void
    {
        $this->assertInstanceOf(RandomHotDeckImputer::class, $this->transformer);
        $this->assertInstanceOf(Transformer::class, $this->transformer);
        $this->assertInstanceOf(Stateful::class, $this->transformer);
        $this->assertInstanceOf(Elastic::class, $this->transformer);
    }
    
    /**
     * @test
     */
    public function fitUpdateTransform() : void
    {
        $this->transformer->fit($this->dataset);

        $this->assertTrue($this->transformer->fitted());

        $this->transformer->update($this->generator->generate(30));

        $this->dataset->apply($this->transformer);

        $this->assertEquals(29.66289097709727, $this->dataset[1][0]);
        $this->assertEquals(-2.0, $this->dataset[3][1]);
    }
    
    /**
     * @test
     */
    public function transformUnfitted() : void
    {
        $this->expectException(RuntimeException::class);

        $samples = $this->dataset->samples();

        $this->transformer->transform($samples);
    }
}
