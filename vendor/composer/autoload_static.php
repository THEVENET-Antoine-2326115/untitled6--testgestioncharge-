<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd37ce8b2e983212410bbd0ee072bea41
{
    public static $prefixLengthsPsr4 = array (
        'm' => 
        array (
            'modules\\blog\\' => 13,
        ),
        '_' => 
        array (
            '_assets\\' => 8,
        ),
        'F' => 
        array (
            'Framework\\Autoload\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'modules\\blog\\' => 
        array (
            0 => __DIR__ . '/../..' . '/modules/blog',
        ),
        '_assets\\' => 
        array (
            0 => __DIR__ . '/../..' . '/_assets',
        ),
        'Framework\\Autoload\\' => 
        array (
            0 => __DIR__ . '/..' . '/aplus/autoload/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd37ce8b2e983212410bbd0ee072bea41::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd37ce8b2e983212410bbd0ee072bea41::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd37ce8b2e983212410bbd0ee072bea41::$classMap;

        }, null, ClassLoader::class);
    }
}
