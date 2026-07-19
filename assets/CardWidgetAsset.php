<?php

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

class CardWidgetAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $js = [
        'js/page-builder/icon-registry.js',
        'js/page-builder/card-widget.js',
    ];

    public $jsOptions = [
        'position' => View::POS_HEAD,
    ];

    public $css = [
        'css/card-widget.css',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
    ];
}
