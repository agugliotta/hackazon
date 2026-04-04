<?php

namespace App;

interface IPixifiable
{
    public function getPixie();
    public function setPixie(?Pixie $pixie = null);
}
