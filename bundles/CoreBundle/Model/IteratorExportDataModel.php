<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\DataExporterHelper;

/**
 * Class IteratorExportDataModel.
 */
class IteratorExportDataModel implements \Iterator
{

    private $position;
    private $model;
    private $args;
    private $callback;
    private $total;
    private $data;
    private $page;
    private $totalResult;

    /**
     * IteratorExportDataModel constructor.
     *
     */
    public function __construct(AbstractCommonModel $model, $args, callable $callback)
    {
        $this->model = $model;
        $this->args = $args;
        $this->callback = $callback;
        $this->position = 0;
        $this->total = 0;
        $this->page = 1;
        $this->totalResult = 0;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        if($this->position === $this->totalResult) {
            $data = new DataExporterHelper();
            $this->data = $data->getDataForExport($this->total, $this->model, $this->args, $this->callback);
            $this->total = $this->total + count($this->data);
            $this->totalResult = count($this->data);
            $this->position = 0;
            ++$this->page;
        }
        return $this->data[$this->position];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->position <= $this->totalResult;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->position = 0;
    }
}