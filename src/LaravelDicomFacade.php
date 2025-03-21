<?php

namespace Dionizas\LaravelDicom;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mkinyua53\LaravelDicom\Skeleton\SkeletonClass
 */
class LaravelDicomFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-dicom';
    }
}
