<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\AccountWidget;

class AdminAccountWidget extends AccountWidget
{
    protected int | string | array $columnSpan = 'full';
}

