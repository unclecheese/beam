<?php

namespace Heyday\Component\Beam\Helper;

use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentProgressHelper
 * @package Heyday\Component\Beam\Helper
 */
class ContentProgressHelper extends ProgressHelper
{
    /**
     * @var
     */
    protected $content;
    /**
     * @var array
     */
    protected $methods = array();
    /**
     * @var array
     */
    protected $properties = array();
    /**
     * @var
     */
    protected $cols;
    /**
     * @var
     */
    protected $first;
    /**
     * @var string
     */
    protected $prefix = '';
    /**
     * @var bool
     */
    protected $isActive = false;
    /**
     *
     */
    public function __construct()
    {
        $this->cols = exec('tput cols');
        $this->setFormat("\033[34m[%bar%]\033[0m %current%/%max% files");
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function getReflectionMethod($name)
    {
        if (!isset($this->methods[$name])) {
            $this->methods[$name] = new \ReflectionMethod('Symfony\Component\Console\Helper\ProgressHelper', $name);
            $this->methods[$name]->setAccessible(true);
        }

        return $this->methods[$name];
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function getReflectionProperty($name)
    {
        if (!isset($this->properties[$name])) {
            $this->properties[$name] = new \ReflectionProperty('Symfony\Component\Console\Helper\ProgressHelper', $name);
            $this->properties[$name]->setAccessible(true);
        }

        return $this->properties[$name];
    }

    /**
     * @param OutputInterface $output
     * @param null            $max
     * @param string          $prefix
     */
    public function start(OutputInterface $output, $max = null, $prefix = '')
    {
        $this->prefix = $prefix;
        $output->write(str_repeat("\x20", $this->cols * 2)); //next line and end line

        if (!$this->isActive) {
            parent::start($output, $max);
            $this->isActive = true;
        }
    }

    public function finish()
    {
        if ($this->isActive) {
            $this->isActive = false;
            parent::finish();
        }
    }

    /**
     * @param int    $step
     * @param bool   $redraw
     * @param string $content
     */
    public function advance($step = 1, $redraw = false, $content = '')
    {
        $this->setContent($content);
        parent::advance($step, $redraw);
    }

    /**
     * @param string $content
     */
    public function setContent($content = '')
    {
        $space = $this->cols - strlen($this->prefix);
        $contentlen = strlen($content);
        if ($contentlen > $space) {
            $this->content = $this->prefix . '...' . substr($content, -1 * ($space - 3));
        } else {
            $this->content = $this->prefix . str_pad($content, $space, ' ', STR_PAD_LEFT);
        }
    }

    /**
     * @param  bool            $finish
     * @throws \LogicException
     */
    public function display($finish = false)
    {
        if (null === $this->getReflectionProperty('startTime')->getValue($this)) {
            throw new \LogicException('You must start the progress bar before calling display().');
        }

        $message = $this->getReflectionProperty('format')->getValue($this);
        foreach ($this->getReflectionMethod('generate')->invoke($this, $finish) as $name => $value) {
            $message = str_replace("%{$name}%", $value, $message);
        }
        $this->overwrite($this->getReflectionProperty('output')->getValue($this), $message);
    }

    /**
     * {@inheritDoc}
     */
    private function overwrite(OutputInterface $output, $messages)
    {
        $output->write(
            "\033[?25l\033[A\x0D" .
            $this->content .
            "\n\033[K" .
            $messages .
            "\033[?12l\033[?25h"
        );
    }

    /**
     * @param $max
     */
    public function setAutoWidth($max)
    {
        $this->setBarWidth($this->cols - (strlen($max) * 2 + 18));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'contentprogress';
    }
}
