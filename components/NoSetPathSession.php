<?php

namespace app\components;

use yii\web\Session as YiiSession;

class NoSetPathSession extends YiiSession
{
    public function setSavePath($value)
    {
        // DO NOTHING - session sudah aktif!
        // Biarkan saja apa adanya
    }
}