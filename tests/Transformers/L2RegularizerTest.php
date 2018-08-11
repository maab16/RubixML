<?php

namespace Rubix\Tests\Transformers;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\Transformer;
use Rubix\ML\Transformers\L2Regularizer;
use PHPUnit\Framework\TestCase;

class L2RegularizerTest extends TestCase
{
    protected $transformer;

    protected $dataset;

    public function setUp()
    {
        $this->dataset = new Unlabeled([
            [1, 2, 3, 4],
            [40, 20, 30, 10],
            [100, 300, 200, 400],
        ]);

        $this->transformer = new L2Regularizer();
    }

    public function test_build_transformer()
    {
        $this->assertInstanceOf(L2Regularizer::class, $this->transformer);
        $this->assertInstanceOf(Transformer::class, $this->transformer);
    }

    public function test_transform_fitted()
    {
        $this->transformer->fit($this->dataset);

        $this->dataset->apply($this->transformer);

        $this->assertEquals([
            [0.18257418583505536, 0.3651483716701107, 0.5477225575051661, 0.7302967433402214],
            [0.7302967433402214, 0.3651483716701107, 0.5477225575051661, 0.18257418583505536],
            [0.18257418583505536, 0.5477225575051661, 0.3651483716701107, 0.7302967433402214],
        ], $this->dataset->samples());
    }
}
