<?php


namespace Firesphere\CSPHeaders\Interfaces;

use SilverStripe\ORM\ValidationException;

interface BuilderInterface
{
    /**
     * @param string $file
     * @param array $attributes
     * @param string $requirements
     * @param string $path
     * @return string
     * @throws ValidationException
     */
    public function buildTags($file, $attributes, string $requirements, string $path): string;

    /**
     * @param $requirements
     * @return string
     */
    public function getCustomTags(string $requirements): string;

    /**
     * @param string $requirements
     * @return string
     */
    public function getHeadTags(string $requirements): string;
}
