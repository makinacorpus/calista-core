<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests\Mock;

/**
 * Defined so that we may use the default page template for testing
 */
class IntItem
{
    public function __construct($value)
    {
        $this->id = $this->title = $this->name = $value;
        $this->created = $this->changed = (new \DateTime())->format('Y-m-d H:i:s');
        $this->thousands = [$this->id - 1000, $this->id + 1000];
        $this->isPublished = (bool)($value % 3);
    }

    public $type = "Integer";
    public $neverSet = null;
    public $id;
    public $thousands = [];
    public $title;
    public $isPublished = true;
    public $name;
    public $changed;
    public $created;
}
