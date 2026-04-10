<?php

// Alternative database config using SQLite (for testing without MySQL)
// Use this if you don't have MySQL installed yet

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'sqlite:@app/runtime/app.db',
    'charset' => 'utf8',
];
