<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite946754690a32f5d6b1bdd558c79c58f
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
        'P' => 
        array (
            'PHPSocketIO\\' => 12,
        ),
        'C' => 
        array (
            'Channel\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/workerman',
        ),
        'PHPSocketIO\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/phpsocket.io/src',
        ),
        'Channel\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/channel/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite946754690a32f5d6b1bdd558c79c58f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite946754690a32f5d6b1bdd558c79c58f::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
