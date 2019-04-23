<?php

namespace Rubix\ML\NeuralNet\Layers;

use Rubix\Tensor\Matrix;
use Rubix\Tensor\ColumnVector;
use Rubix\ML\NeuralNet\Parameter;
use Rubix\ML\Other\Helpers\Stats;
use Rubix\ML\NeuralNet\Optimizers\Optimizer;
use Rubix\ML\NeuralNet\Initializers\Constant;
use Rubix\ML\NeuralNet\Initializers\Initializer;
use InvalidArgumentException;
use RuntimeException;
use Generator;
use Closure;

use const Rubix\ML\EPSILON;

/**
 * Batch Norm
 *
 * Normalize the activations of the previous layer such that the mean activation
 * is close to 0 and the activation standard deviation is close to 1. Batch Norm
 * can be used to reduce the amount of covariate shift within the network
 * making it possible to use higher learning rates and converge faster under
 * some circumstances.
 *
 * References:
 * [1] S. Ioffe et al. (2015). Batch Normalization: Accelerating Deep Network
 * Training by Reducing Internal Covariate Shift.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class BatchNorm implements Hidden, Parametric
{
    /**
     * The decay rate of the previous running averages of the global mean
     * and variance.
     *
     * @var float
     */
    protected $decay;

    /**
     * The initializer for the beta parameter.
     *
     * @var \Rubix\ML\NeuralNet\Initializers\Initializer
     */
    protected $betaInitializer;

    /**
     * The initializer for the gamma parameter.
     *
     * @var \Rubix\ML\NeuralNet\Initializers\Initializer
     */
    protected $gammaInitializer;

    /**
     * The width of the layer. i.e. the number of neurons.
     *
     * @var int|null
     */
    protected $width;

    /**
     * The learnable centering parameter.
     *
     * @var \Rubix\ML\NeuralNet\Parameter|null
     */
    protected $beta;

    /**
     * The learnable scaling parameter.
     *
     * @var \Rubix\ML\NeuralNet\Parameter|null
     */
    protected $gamma;

    /**
     * The running mean of each input dimension.
     *
     * @var \Rubix\Tensor\ColumnVector|null
     */
    protected $mean;

    /**
     * The running variance of each input dimension.
     *
     * @var \Rubix\Tensor\ColumnVector|null
     */
    protected $variance;

    /**
     * A cache of inverse standard deviations calculated during the forward pass.
     *
     * @var \Rubix\Tensor\Matrix|null
     */
    protected $stdInv;

    /**
     * A cache of normalized inputs to the layer.
     *
     * @var \Rubix\Tensor\Matrix|null
     */
    protected $xHat;

    /**
     * @param float $decay
     * @param \Rubix\ML\NeuralNet\Initializers\Initializer|null $betaInitializer
     * @param \Rubix\ML\NeuralNet\Initializers\Initializer|null $gammaInitializer
     * @throws \InvalidArgumentException
     */
    public function __construct(
        float $decay = 0.9,
        ?Initializer $betaInitializer = null,
        ?Initializer $gammaInitializer = null
    ) {
        if ($decay < 0. or $decay > 1.) {
            throw new InvalidArgumentException('Decay must be between'
                . " 0 and 1, $decay given.");
        }

        $this->decay = $decay;
        $this->betaInitializer = $betaInitializer ?? new Constant(0.);
        $this->gammaInitializer = $gammaInitializer ?? new Constant(1.);
    }

    /**
     * Return the width of the layer.
     *
     * @throws \RuntimeException
     * @return int
     */
    public function width() : int
    {
        if (!$this->width) {
            throw new RuntimeException('Layer has not been initialized.');
        }

        return $this->width;
    }

    /**
     * Return the parameters of the layer.
     *
     * @throws \RuntimeException
     * @return \Generator
     */
    public function parameters() : Generator
    {
        if (!$this->beta or !$this->gamma) {
            throw new RuntimeException('Layer has not been initilaized.');
        }

        yield $this->beta;
        yield $this->gamma;
    }

    /**
     * Initialize the layer with the fan in from the previous layer and return
     * the fan out for this layer.
     *
     * @param int $fanIn
     * @return int
     */
    public function initialize(int $fanIn) : int
    {
        $fanOut = $fanIn;

        $beta = $this->betaInitializer->initialize($fanIn, 1);
        $gamma = $this->gammaInitializer->initialize($fanIn, 1);

        $this->beta = new Parameter($beta);
        $this->gamma = new Parameter($gamma);

        $this->width = $fanOut;

        return $fanOut;
    }

    /**
     * Compute a forward pass through the layer.
     *
     * @param \Rubix\Tensor\Matrix $input
     * @throws \RuntimeException
     * @return \Rubix\Tensor\Matrix
     */
    public function forward(Matrix $input) : Matrix
    {
        if (!$this->beta or !$this->gamma) {
            throw new RuntimeException('Layer has not been initialized.');
        }

        $gamma = $this->gamma->w()->rowAsVector(0)->transpose();
        $beta = $this->beta->w()->rowAsVector(0)->transpose();

        $means = $variances = [];

        foreach ($input as $row) {
            [$means[], $variances[]] = Stats::meanVar($row);
        }

        $mean = ColumnVector::quick($means);
        $variance = ColumnVector::quick($variances);

        if (!$this->mean or !$this->variance) {
            $this->mean = $mean;
            $this->variance = $variance;
        }

        $stdDev = $variance->clipLower(EPSILON)->sqrt();

        $stdInv = $stdDev->reciprocal();

        $xHat = $stdInv->multiply($input->subtract($mean));

        $this->mean = $this->mean->multiply($this->decay)
            ->add($mean->multiply(1. - $this->decay));

        $this->variance = $this->variance->multiply($this->decay)
            ->add($variance->multiply(1. - $this->decay));

        $this->stdInv = $stdInv;
        $this->xHat = $xHat;

        return $gamma->multiply($xHat)->add($beta);
    }

    /**
     * Compute an inferential pass through the layer.
     *
     * @param \Rubix\Tensor\Matrix $input
     * @throws \RuntimeException
     * @return \Rubix\Tensor\Matrix
     */
    public function infer(Matrix $input) : Matrix
    {
        if (!$this->mean or !$this->variance or !$this->beta or !$this->gamma) {
            throw new RuntimeException('Layer has not been initilaized.');
        }

        $gamma = $this->gamma->w()->rowAsVector(0)->transpose();
        $beta = $this->beta->w()->rowAsVector(0)->transpose();

        $xHat = $input->subtract($this->mean)
            ->divide($this->variance->clipLower(EPSILON)->sqrt());

        return $gamma->multiply($xHat)->add($beta);
    }

    /**
     * Calculate the errors and gradients of the layer and update the parameters.
     *
     * @param Closure $prevGradient
     * @param \Rubix\ML\NeuralNet\Optimizers\Optimizer $optimizer
     * @throws \RuntimeException
     * @return Closure
     */
    public function back(Closure $prevGradient, Optimizer $optimizer) : Closure
    {
        if (!$this->beta or !$this->gamma) {
            throw new RuntimeException('Layer has not been initilaized.');
        }

        if (!$this->stdInv or !$this->xHat) {
            throw new RuntimeException('Must perform forward pass before'
                . ' backpropagating.');
        }

        $dOut = $prevGradient();

        $dBeta = $dOut->sum()->asRowMatrix();
        $dGamma = $dOut->multiply($this->xHat)->sum()->asRowMatrix();

        $gamma = $this->gamma->w()->rowAsVector(0);

        $optimizer->step($this->beta, $dBeta);
        $optimizer->step($this->gamma, $dGamma);

        $stdInv = $this->stdInv;
        $xHat = $this->xHat;

        unset($this->stdInv, $this->xHat);

        return function () use ($dOut, $gamma, $stdInv, $xHat) {
            $dXHat = $dOut->multiply($gamma->transpose());

            $xHatSigma = $dXHat->multiply($xHat)->sum();

            $dXHatSigma = $dXHat->sum();

            return $dXHat->multiply($dOut->m())
                ->subtract($dXHatSigma)
                ->subtract($xHat->multiply($xHatSigma))
                ->multiply($stdInv->divide($dOut->m()));
        };
    }

    /**
     * Return the parameters of the layer in an associative array.
     *
     * @throws \RuntimeException
     * @return array
     */
    public function read() : array
    {
        if (!$this->beta or !$this->gamma) {
            throw new RuntimeException('Layer has not been initilaized.');
        }

        return [
            'beta' => clone $this->beta,
            'gamma' => clone $this->gamma,
        ];
    }

    /**
     * Restore the parameters in the layer from an associative array.
     *
     * @param array $parameters
     * @throws \RuntimeException
     */
    public function restore(array $parameters) : void
    {
        $this->beta = $parameters['beta'];
        $this->gamma = $parameters['gamma'];
    }
}
