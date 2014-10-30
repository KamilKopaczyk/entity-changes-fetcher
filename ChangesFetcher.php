<?php


namespace Daimos\ChangesFetcher;


interface ChangesFetcher
{
    public function getChanges($entity);
} 