<?php

namespace Scripts;

interface IParse
{
    public function __construct();

    public function parse($url);
}
