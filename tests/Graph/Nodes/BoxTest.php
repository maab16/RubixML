<?php

namespace Rubix\ML\Tests\Graph\Nodes;

use Rubix\ML\Graph\Nodes\Box;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Graph\Nodes\Node;
use Rubix\ML\Graph\Nodes\Hypercube;
use Rubix\ML\Graph\Nodes\BinaryNode;
use PHPUnit\Framework\TestCase;

class BoxTest extends TestCase
{
    protected const COLUMN = 1;
    protected const VALUE = 3.;

    protected const SAMPLES = [
        [5., 2., -3],
        [6., 4., -5],
    ];

    protected const LABELS = [22, 13];

    protected const MIN = [5., 2., -5];
    protected const MAX = [6., 4., -3];

    protected const BOX = [
        self::MIN, self::MAX,
    ];

    /**
     * @var \Rubix\ML\Graph\Nodes\Box
     */
    protected $node;

    public function setUp() : void
    {
        $groups = [
            Labeled::quick([self::SAMPLES[0]], [self::LABELS[0]]),
            Labeled::quick([self::SAMPLES[1]], [self::LABELS[1]]),
        ];

        $this->node = new Box(self::COLUMN, self::VALUE, $groups, self::MIN, self::MAX);
    }

    public function test_build_node() : void
    {
        $this->assertInstanceOf(Box::class, $this->node);
        $this->assertInstanceOf(Hypercube::class, $this->node);
        $this->assertInstanceOf(BinaryNode::class, $this->node);
        $this->assertInstanceOf(Node::class, $this->node);
    }

    public function test_split() : void
    {
        $node = Box::split(Labeled::quick(self::SAMPLES, self::LABELS));

        $this->assertEquals(self::BOX, iterator_to_array($node->sides()));
    }

    public function test_column() : void
    {
        $this->assertSame(self::COLUMN, $this->node->column());
    }

    public function test_value() : void
    {
        $this->assertSame(self::VALUE, $this->node->value());
    }

    public function test_groups() : void
    {
        $expected = [
            Labeled::quick([self::SAMPLES[0]], [self::LABELS[0]]),
            Labeled::quick([self::SAMPLES[1]], [self::LABELS[1]]),
        ];

        $this->assertEquals($expected, $this->node->groups());
    }

    public function test_sides() : void
    {
        $this->assertEquals(self::BOX, iterator_to_array($this->node->sides()));
    }
}
